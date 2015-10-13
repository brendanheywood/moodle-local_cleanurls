<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local
 * @subpackage cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Convert moodle_urls into clean_moodle_urls if possible
 *
 * @param $url moodle_url a url to potentially rewrite
 * @return moodle_url
 */
function local_cleanurls_url_rewrite($url) {

    if (get_config('local_cleanurls', 'cleaningon')) {
        return clean_moodle_url::clean($url);
    }
    return $url;

}

function local_cleanurls_pre_head_content() {

    global $CFG, $PAGE;

    $output = '';

    if (isset($CFG->uncleanedurl)) {

        // One issue is that when rewriting urls we change their nesting and depth
        // which means legacy urls in the codebase which do NOT use moodle_url and
        // which are also relative links can be broken. To fix this we set the
        // base href to the original uncleaned url.
        $output .= "<base href='$CFG->uncleanedurl'>\n";

    } else {

        $clean = $PAGE->url->out();
        $orig = $PAGE->url->raw_out(false);
        if ($orig != $clean) {

            // If we have just loaded a legacy url AND we can clean it, instead of
            // cleaning the url, caching it, and waiting for the user or someone
            // else to come back again to see the good url, we can use html5
            // replaceState to fix it imeditately without a page reload.
            //
            // Importantly this needs to happen before any JS on the page uses it,
            // such as any analytics tracking.
            $output .= "<script>history.replaceState && history.replaceState({}, '', '$clean');</script>\n";

            // Now that each page has two valid urls, we need to tell robots like
            // GoogleBot that they are the same, otherwise Google may think they
            // are low quality duplicates and possibly split pagerank between them.
            //
            // We specify that the clean one is the 'canonical' url so this is what
            // will be shown in google search results pages.
            $output .= "<link rel='canonical' href='$clean' />\n";

            apache_note('CLEANURL', $clean);

        }

    }
    return $output;
}

class clean_moodle_url extends moodle_url {

    /*
     *
     */
    public static function log($msg) {

        $debug = get_config('local_cleanurls', 'debugging');
        $debug && error_log($msg);

    }

    public static function sluggify($string, $dash) {

        $string = strtolower($string);
        $string = str_replace(' ', '-', $string);

        if ($dash) {
            return '-' . $string;
        }
        return $string;

    }

    /*
     * Takes a moodle_url and either returns a clean_moodle_url object with
     * clean cloned properties or if nothing is done the original object.
     *
     * @param $orig moodle_url
     * @return moodle_url
     */
    public static function clean(moodle_url $orig) {

        global $DB, $CFG;

        $path   = $orig->path;
        $params = $orig->params();

        $config = get_config('local_cleanurls');

        $origurl = $orig->raw_out(false);

        $cache = cache::make('local_cleanurls', 'outgoing');
        $cached = $cache->get($origurl);
        if ($cached) {
            $clean = new clean_moodle_url($cached);
            self::log("Found cached:" . $origurl . " => " . $cached);
            return $clean;
        }

        self::log("Cleaning: " . $origurl . " Path is: $path");

        // If moodle is installed inside a dir like example.com/somepath/moodle/index.php
        // then remove the 'somepath/moodle' part and store for later.
        $slashstart = strlen(parse_url($CFG->wwwroot, PHP_URL_SCHEME)) + 3;
        $slashpos = strpos($CFG->wwwroot, '/', $slashstart);
        $moodle = '';
        if ($slashpos) {
            $moodle = substr($CFG->wwwroot, $slashpos);
            $path = substr($path, strlen($moodle));
            self::log("Removed wwwroot from path: $path");
        }

        // Ignore any admin urls for safety.
        if (substr($path, 0, 6) == '/admin') {
            self::log("Ignoring admin url");
            return $orig;
        }

        // Ignore any theme files.
        if (substr($path, 0, 6) == '/theme') {
            self::log("Ignoring theme file");
            return $orig;
        }

        // Ignore any lib files.
        if (substr($path, 0, 4) == '/lib') {
            self::log("Ignoring lib file");
            return $orig;
        }

        // Ignore non .php files.
        if (substr($path, -4) !== ".php") {
            self::log("Ignoring non .php file");
            return $orig;
        }

        // Remove the php extension.
        $path = substr($path, 0, -4);

        // Remove /index from end.
        if (substr($path, -6) == '/index') {
            self::log("Removing /index");
            $path = substr($path, 0, -5);
        }

        if ($path == "/course/view" && $params['id'] ) {
            // Clean up course urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));

            $newpath = "/course/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);
                self::log("Rewrite course");
            }

        } else if ($path == "/user/" && $params['id'] ) {
            // Clean up user course list urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));

            $newpath = "/course/$slug/user";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                self::log("Rewrite user profile");
            }

        } else if (preg_match("/^\/mod\/(\w+)\/$/", $path, $matches) && $params['id'] ) {
            // Clean up mod view pages /index has already been removed earlier.

            $mod = $matches[1];

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));

            $newpath = "/course/$slug/$mod";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                self::log("Rewrite mod view: $path");
            }

        } else if (preg_match("/^\/mod\/(\w+)\/view$/", $path, $matches) && isset($params['id']) ) {
            // Clean up mod view pages.

            $id = $params['id'];
            $mod = $matches[1];
            list ($course, $cm) = get_course_and_cm_from_cmid($id, $mod);

            $slug = self::sluggify($cm->name, true);
            $shortcode = $course->shortname;

            $newpath = "/course/$shortcode/$mod/$id$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                self::log("Rewrite mod view: $path");
            }
        }

        // Clean up user id's into usernmes?
        if ($config->cleanusernames) {

            // In profile urls.
            if ($path == "/user/profile" && $params['id'] ) {
                $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));
                $newpath = "/user/$slug";

                if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                    $path = $newpath;
                    unset ($params['id']);
                    self::log("Rewrite user profile");
                }
            }

            // Clean up user profile urls inside course.
            if ($path == "/user/view" && $params['id'] && $params['course']) {
                $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));
                $newpath = "/user/$slug";

                if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                    $path = $newpath;

                    if ($params['course'] != 1) {
                        $slug = $DB->get_field('course', 'shortname', array('id' => $params['course'] ));
                        $path = "/course/$slug$path";
                        unset ($params['course']);
                    }
                    unset ($params['id']);
                    self::log("Rewrite user profile");
                }
            }
        }

        // Ignore if clashes with a directory.
        if (is_dir($CFG->dirroot . $path ) && substr($path, -1) != '/') {
            self::log("Ignoring dir clash");
            return $orig;
        }

        $clean = new clean_moodle_url($orig);
        $clean->path = $moodle . $path;
        $clean->remove_all_params();
        $clean->params($params);

        $cleaned = $clean->raw_out(false);
        $cache->set($origurl, $cleaned);

        self::log("Clean:".$cleaned);
        return $clean;

    }

    /*
     * Takes a string and converts it into an unclean moodle_url object
     */
    public static function unclean($clean) {

        global $CFG, $DB;

        self::log("Incoming url: $clean");

        $url = new moodle_url($clean);
        $path = $url->path;
        $params = $url->params();

        self::log("Incoming path: $path");

        // If moodle is installed inside a dir like example.com/somepath/moodle/index.php
        // then remove the 'somepath/moodle' part and store for later.
        $slashstart = strlen(parse_url($CFG->wwwroot, PHP_URL_SCHEME)) + 3;
        $slashpos = strpos($CFG->wwwroot, '/', $slashstart);
        $moodle = '';
        if ($slashpos) {
            $moodle = substr($CFG->wwwroot, $slashpos);
            $path = substr($path, strlen($moodle));
            self::log("Removed wwwroot from path: $path");
        }

        // These regex's must be in order of higher specificity to lowest.

        if (preg_match("/^\/course\/(.+)\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[2]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[2] . ".php")) {
                $path = "/user/view.php";
                $params['id']     = $DB->get_field('user',   'id', array('username'  => $matches[2] ));
                $params['course'] = $DB->get_field('course', 'id', array('shortname' => $matches[1] ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/user\/?$/", $path, $matches)) {
            // Clean up course user list urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => $matches[1] ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/profile.php";
                if (isset($params['course'])) {
                    $path = "/user/view.php";
                }
                $params['id'] = $DB->get_field('user', 'id', array('username' => $matches[1] ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/(\w+)\/(\d+)(-.*)?$/", $path, $matches)) {
            // Clean up course mod view.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/mod/$matches[2]/view.php";
                $params['id'] = $matches[3];
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/(\w+)\/?$/", $path, $matches)) {

            // Clean up course mod index.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/mod/$matches[2]/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => $matches[1] ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)$/", $path, $matches)) {
            // Clean up course urls.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/course/view.php";
                $params['name'] = $matches[1];
                self::log("Rewritten to: $path");
            }
        }

        // Put back .php extension if doesn't end in .php or slash.
        if (substr($path, -1, 1) !== '/' && substr($path, -4) !== '.php') {
            $path .= '.php';
        }

        self::log("Rewritten to: $path");

        $url->path = $moodle . $path;
        $url->remove_all_params();
        $url->params($params);
        return $url;

    }

}
