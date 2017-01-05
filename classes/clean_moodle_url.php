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
 * The main cleaning and uncleaning logic
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

/**
 * The main cleaning and uncleaning logic
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_moodle_url extends \moodle_url {

    /**
     * A log util for debugging
     *
     * @param string $msg an log message
     */
    public static function log($msg) {

        $debug = get_config('local_cleanurls', 'debugging');
        // @codingStandardsIgnoreStart
        $debug && error_log($msg);
        // @codingStandardsIgnoreEnd

    }

    /**
     * A util for crafting human readable url components
     *
     * This is non reversible and only used to augment a url to make it more
     * obvious, but isn't used at all for routing. Usually it is prefixed
     * with and id such as /page/1234-some-nice-name
     *
     * @param string $string a string to url escape and prettify
     * @param boolean $dash if present a dash is prepended
     * @return string
     */
    public static function sluggify($string, $dash) {
        $string = self::sanitize_title_with_dashes($string);

        if ($dash) {
            return '-' . $string;
        }
        return $string;

    }

    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/sanitize_title_with_dashes/
     *
     * @param string $title
     * @return string
     */
    private static function sanitize_title_with_dashes($title) {
        $title = strip_tags($title);
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
        $title = mb_strtolower($title, 'UTF-8');
        $title = self::utf8_uri_encode($title, 200);
        $title = strtolower($title);

        $title = preg_replace('/&.+?;/', '', $title); // Kill entities.
        $title = str_replace('.', '-', $title);
        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');
        return $title;
    }

    /**
     * Borrowed from WordPress
     *
     * @param string $utf8string
     * @return string
     *
     * https://developer.wordpress.org/reference/functions/utf8_uri_encode/
     */
    private static function utf8_uri_encode($utf8string) {
        $unicode = '';
        $values = [];
        $numoctets = 1;
        $unicodelength = 0;
        self::mbstring_binary_safe_encoding();
        $stringlength = strlen($utf8string);
        self::reset_mbstring_encoding();
        for ($i = 0; $i < $stringlength; $i++) {
            $value = ord($utf8string[$i]);
            if ($value < 128) {
                $unicode .= chr($value);
                $unicodelength++;
            } else {
                if (count($values) == 0) {
                    if ($value < 224) {
                        $numoctets = 2;
                    } else if ($value < 240) {
                        $numoctets = 3;
                    } else {
                        $numoctets = 4;
                    }
                }
                $values[] = $value;
                if (count($values) == $numoctets) {
                    for ($j = 0; $j < $numoctets; $j++) {
                        $unicode .= '%'.dechex($values[$j]);
                    }
                    $unicodelength += $numoctets * 3;
                    $values = [];
                    $numoctets = 1;
                }
            }
        }
        return $unicode;
    }

    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/mbstring_binary_safe_encoding/
     *
     * @param bool $reset
     */
    private static function mbstring_binary_safe_encoding($reset = false) {
        static $encodings = [];
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    /**
     * Borrowed from WordPress
     *
     * https://developer.wordpress.org/reference/functions/reset_mbstring_encoding/
     */
    private static function reset_mbstring_encoding() {
        self::mbstring_binary_safe_encoding(true);
    }

    /**
     * Takes a moodle_url and either returns a clean_moodle_url object with
     * clean cloned properties or if nothing is done the original object.
     *
     * @param moodle_url $orig a url to clean
     * @return moodle_url
     */
    public static function clean(\moodle_url $orig) {

        global $DB, $CFG;

        $path   = $orig->path;

        // This is a special case which will always be cleaned even if the
        // cleaner is off, used for confirming that it all works.
        if (substr($path, -30) == '/local/cleanurls/tests/foo.php') {
            self::log("Rewrite test url");
            return new clean_moodle_url('/local/cleanurls/tests/bar');
        }

        $config = get_config('local_cleanurls');

        if (empty($config->cleaningon)) {
            self::log("Cleaning is not on");
            return $orig;
        }

        $params = $orig->params();

        $origurl = $orig->raw_out(false);

        $cache = \cache::make('local_cleanurls', 'outgoing');
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

        // Remember the original path before rewriting it.
        $originalpath = $path;

        // Remove /index.php from end.
        if (substr($path, -10) == '/index.php') {
            self::log("Removing /index.php");
            $path = substr($path, 0, -9);
        }

        if ($path == "/course/view.php" && $params['id'] ) {
            // Clean up course urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);
                self::log("Rewrite course");
            }

        } else if ($path == "/user/" && $params['id'] ) {
            // Clean up user course list urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug/user";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                self::log("Rewrite user profile");
            }

        } else if ($path == "/course/" && isset($params['categoryid']) ) {

            // Clean up category list urls.
            $catid = $params['categoryid'];
            $path = '';

            // Grab all ancestor slugs.
            while ($catid) {
                $cat = $DB->get_record('course_categories', array('id' => $catid));
                $slug = self::sluggify($cat->name, false);
                $path = '/' . $slug . '-' . $catid . $path;
                $catid = $cat->parent;
            }
            $path = '/category' .  $path;
            unset ($params['categoryid']);
            self::log("Rewrite category page");

        } else if (preg_match("/^\/mod\/(\w+)\/$/", $path, $matches) && $params['id'] ) {
            // Clean up mod view pages /index has already been removed earlier.

            $mod = $matches[1];

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug/$mod";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                self::log("Rewrite mod view: $path");
            }

        } else if (preg_match("/^\/mod\/(\w+)\/view.php$/", $path, $matches) && isset($params['id']) ) {
            // Clean up mod view pages.

            $id = $params['id'];
            $mod = $matches[1];
            list ($course, $cm) = get_course_and_cm_from_cmid($id, $mod);

            $slug = self::sluggify($cm->name, true);
            $shortcode = urlencode($course->shortname);

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
            if ($path == "/user/profile.php" && $params['id'] ) {
                $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));
                $slug = urlencode($slug);
                $newpath = "/user/$slug";

                if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                    $path = $newpath;
                    unset ($params['id']);
                    self::log("Rewrite user profile");
                }
            }

            // Clean up user profile urls inside course.
            if ($path == "/user/view.php" && $params['id'] && $params['course']) {
                $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));
                $slug = urlencode($slug);
                $newpath = "/user/$slug";

                if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                    $path = $newpath;

                    if ($params['course'] != 1) {
                        $slug = $DB->get_field('course', 'shortname', array('id' => $params['course'] ));
                        $slug = urlencode($slug);
                        $path = "/course/$slug$path";
                        unset ($params['course']);
                    }
                    unset ($params['id']);
                    self::log("Rewrite user profile");
                }
            }

            // Clean up user profile urls in forum posts
            // ie http://moodle.com/mod/forum/user.php?id=123&mode=discussions
            // should become http://moodle.com/user/username/discussions .
            if ($path == "/mod/forum/user.php" && $params['id'] && (isset($params['mode']) && $params['mode'] == 'discussions')) {
                $slug = $DB->get_field('user', 'username', array('id' => $params['id']));
                $slug = urlencode($slug);
                $newpath = "/user/$slug";
                if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                    $path = $newpath . '/' . $params['mode'];
                    unset ($params['id']);
                    unset ($params['mode']);
                    self::log("Rewrite user profile");
                }
            }
        }

        // URL was not rewritten.
        if ($path == $originalpath) {
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

    /**
     * Takes a string and converts it into an unclean moodle_url object
     *
     * @param string $clean the incoming url
     * @return \moodle_url the original moodle_url
     */
    public static function unclean($clean) {

        global $CFG, $DB;

        self::log("Incoming url: $clean");

        $url = new \moodle_url($clean);
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

        if ($path == '/local/cleanurls/tests/bar') {
            $path = '/local/cleanurls/tests/foo.php';
            self::log("Rewritten to: $path");
        } else if (preg_match("/^\/course\/(.+)\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[2]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[2] . ".php")) {
                $path = "/user/view.php";
                $params['id']     = $DB->get_field('user',   'id', array('username'  => urldecode($matches[2]) ));
                $params['course'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/user\/?$/", $path, $matches)) {
            // Clean up course user list urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
                self::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/user\/(\w+)\/(discussions)$/", $path, $matches)) {
            // Unclean paths
            // e.g.: http://moodle.com/user/username/discussions
            // into http://moodle.com/mod/forum/user.php?id=123&mode=discussions .
            $path = "/mod/forum/user.php";
            $params['id'] = $DB->get_field('user', 'id', array('username'  => urldecode($matches[1]) ));
            $params['mode'] = $matches[2];
            self::log("Rewritten to: $path");

        } else if (preg_match("/^\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/profile.php";
                if (isset($params['course'])) {
                    $path = "/user/view.php";
                }
                $params['id'] = $DB->get_field('user', 'id', array('username' => urldecode($matches[1]) ));
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
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
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

        } else if (preg_match('/^\/category(\/.*-(\d+))?$/', $path, $matches)) {
            // Clean up category urls.
            $path = "/course/index.php";

            // We need at least 2 matches to get a valid id. Use the last match (last part of path).
            $params['categoryid'] = (count($matches) <= 1) ? 0 : array_pop($matches);

            self::log("Rewritten to: $path");
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
