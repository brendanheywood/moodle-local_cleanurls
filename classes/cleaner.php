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
 * Class cleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class cleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner {
    /**
     * Takes a moodle_url and either returns a clean_moodle_url object with
     * clean cloned properties or if nothing is done the original object.
     *
     * @param moodle_url $orig a url to clean
     * @return moodle_url
     */
    public static function clean(\moodle_url $orig) {

        global $DB, $CFG;

        $path   = $orig->get_path();

        // This is a special case which will always be cleaned even if the
        // cleaner is off, used for confirming that it all works.
        if (substr($path, -30) == '/local/cleanurls/tests/foo.php') {
            clean_moodle_url::log("Rewrite test url");
            return new clean_moodle_url('/local/cleanurls/tests/bar');
        }

        $config = get_config('local_cleanurls');

        if (empty($config->cleaningon)) {
            clean_moodle_url::log("Cleaning is not on");
            return $orig;
        }

        $params = $orig->params();

        $origurl = $orig->raw_out(false);

        $cache = \cache::make('local_cleanurls', 'outgoing');
        $cached = $cache->get($origurl);
        if ($cached) {
            $clean = new clean_moodle_url($cached);
            clean_moodle_url::log("Found cached:" . $origurl . " => " . $cached);
            return $clean;
        }

        clean_moodle_url::log("Cleaning: " . $origurl . " Path is: $path");

        // If moodle is installed inside a dir like example.com/somepath/moodle/index.php
        // then remove the 'somepath/moodle' part and store for later.
        $slashstart = strlen(parse_url($CFG->wwwroot, PHP_URL_SCHEME)) + 3;
        $slashpos = strpos($CFG->wwwroot, '/', $slashstart);
        $moodle = '';
        if ($slashpos) {
            $moodle = substr($CFG->wwwroot, $slashpos);
            $path = substr($path, strlen($moodle));
            clean_moodle_url::log("Removed wwwroot from path: $path");
        }

        // Remember the original path before rewriting it.
        $originalpath = $path;

        // Remove /index.php from end.
        if (substr($path, -10) == '/index.php') {
            clean_moodle_url::log("Removing /index.php");
            $path = substr($path, 0, -9);
        }

        if ($path == "/course/view.php" && !empty($params['id']) ) {
            // Clean up course urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);
                clean_moodle_url::log("Rewrite course");
            }
        } else if ($path == "/course/view.php" && !empty($params['name']) ) {
            // Clean up course urls.

            $slug = urlencode($params['name']);
            $newpath = "/course/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['name']);
                clean_moodle_url::log("Rewrite course by name.");
            }

        } else if ($path == "/user/" && $params['id'] ) {
            // Clean up user course list urls.

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug/user";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                clean_moodle_url::log("Rewrite user profile");
            }

        } else if ($path == "/course/" && isset($params['categoryid']) ) {

            // Clean up category list urls.
            $catid = $params['categoryid'];
            $path = '';

            // Grab all ancestor slugs.
            while ($catid) {
                $cat = $DB->get_record('course_categories', array('id' => $catid));
                $slug = clean_moodle_url::sluggify($cat->name, false);
                $path = '/' . $slug . '-' . $catid . $path;
                $catid = $cat->parent;
            }
            $path = '/category' .  $path;
            unset ($params['categoryid']);
            clean_moodle_url::log("Rewrite category page");

        } else if (preg_match("/^\/mod\/(\w+)\/$/", $path, $matches) && $params['id'] ) {
            // Clean up mod view pages /index has already been removed earlier.

            $mod = $matches[1];

            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));
            $slug = urlencode($slug);
            $newpath = "/course/$slug/$mod";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                clean_moodle_url::log("Rewrite mod view: $path");
            }

        } else if (preg_match("/^\/mod\/(\w+)\/view.php$/", $path, $matches) && isset($params['id']) ) {
            // Clean up mod view pages.

            $id = $params['id'];
            $mod = $matches[1];
            list ($course, $cm) = get_course_and_cm_from_cmid($id, $mod);

            $slug = clean_moodle_url::sluggify($cm->name, true);
            $shortcode = urlencode($course->shortname);

            $newpath = "/course/$shortcode/$mod/$id$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                clean_moodle_url::log("Rewrite mod view: $path");
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
                    clean_moodle_url::log("Rewrite user profile");
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
                    clean_moodle_url::log("Rewrite user profile");
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
                    clean_moodle_url::log("Rewrite user profile");
                }
            }
        }

        // URL was not rewritten.
        if ($path == $originalpath) {
            return $orig;
        }

        $clean = new clean_moodle_url($orig);
        $clean->set_path($moodle . $path);
        $clean->remove_all_params();
        $clean->params($params);

        $cleaned = $clean->raw_out(false);
        $cache->set($origurl, $cleaned);

        clean_moodle_url::log("Clean:".$cleaned);
        return $clean;

    }
}
