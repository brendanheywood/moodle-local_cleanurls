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

class clean_moodle_url extends moodle_url {

    /*
     * Takes a moodle_url and either returns a cloned object with cleaned properties
     * of if nothing is done the original object
     */
    public static function clean($orig) {

        global $DB, $CFG;
        $debug = get_config('local_cleanurls', 'debugging');

        $path   = $orig->path;
        $params = $orig->params();

        $debug && error_log("Cleaning:".$orig->orig_out());

        // Remove the moodle dir if present.
        $slash = strpos($CFG->wwwroot, '/', 8);
        $moodle = '';
        if ($slash) {
            $moodle = substr($CFG->wwwroot, $slash);
            $path = substr($path, strlen($moodle));
        }

        // Ignore non .php files.
        if (substr($path, -4) !== ".php") {
            $debug && error_log("Ignoring non .php file");
            return $orig;
        }

        // Ignore any theme files.
        if (substr($path, 0, 6) == '/theme') {
            $debug && error_log("Ignoring theme file");
            return $orig;
        }

        // Ignore any lib files.
        if (substr($path, 0, 4) == '/lib') {
            $debug && error_log("Ignoring lib file");
            return $orig;
        }

        // Remove the php extension.
        $path = substr($path, 0, -4);

        // Remove /index from end.
        if (substr($path, -6) == '/index') {
            $path = substr($path, 0, -5);
        }

        // Clean up course urls.
        if ($path == "/course/view" && $params['id'] ) {
            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));

            $newpath = "/course/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);
                $debug && error_log("Rewrite course");
            }
        }

        // Clean up user course list urls.
        if ($path == "/user/" && $params['id'] ) {
            $slug = $DB->get_field('course', 'shortname', array('id' => $params['id'] ));

            $newpath = "/course/$slug/user/";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                $debug && error_log("Rewrite user profile");
            }
        }
        // Clean up user profile urls.
        if ($path == "/user/profile" && $params['id'] ) {
            $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));

            $newpath = "/user/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                $debug && error_log("Rewrite user profile");
            }
        }
        // Clean up user profile urls inside course.
        if ($path == "/user/view" && $params['id'] && $params['course']) {
            $slug = $DB->get_field('user', 'username', array('id' => $params['id'] ));

            $newpath = "/user/$slug";
            if (!is_dir($CFG->dirroot . $newpath) && !is_file($CFG->dirroot . $newpath . ".php")) {
                $path = $newpath;
                unset ($params['id']);

                if ($params['course']) {
                    $slug = $DB->get_field('course', 'shortname', array('id' => $params['course'] ));
                    $path = "/course/$slug$path";
                    unset ($params['course']);
                }
                $debug && error_log("Rewrite user profile");
            }
        }

        // Ignore if clashes with a directory.
        if (is_dir($CFG->dirroot . $path ) && substr($path, -1) != '/') {
            $debug && error_log("Ignoring dir clash");
            return $orig;
        }

        $clone = new moodle_url($orig);
        $clone->path = $moodle . $path;
        $clone->remove_all_params();
        $clone->params($params);
        $debug && error_log("Clean:".$clone->out());
        return $clone;

    }

    /*
     * Takes a string and converts it into an unclean moodle_url object
     */
    public static function unclean($clean) {

        global $CFG, $DB;

        $debug = get_config('local_cleanurls', 'debugging');
        $debug && error_log("Incoming url: $clean");

        $url = new moodle_url($clean);
        $path = $url->path;
        $params = $url->params();

        $debug && error_log("Incoming path: $path");

        // Remove the moodle dir if present.
        $slash = strpos($CFG->wwwroot, '/', 8);
        $moodle = '';
        if ($slash) {
            $moodle = substr($CFG->wwwroot, $slash);
            $path = substr($path, strlen($moodle));
        }

        // These regex's must be in order of higher specificity to lowest.

        // Clean up user profile urls inside course.
        if (preg_match("/^\/course\/(.+)\/user\/(.+)$/", $path, $matches)) {
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[2]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[2] . ".php")) {
                $path = "/user/view.php";
                $params['id']     = $DB->get_field('user',   'id', array('username'  => $matches[2] ));
                $params['course'] = $DB->get_field('course', 'id', array('shortname' => $matches[1] ));
                $debug && error_log("Rewritten to: $path");
            }

            // Clean up user profile urls inside course.
        } else if (preg_match("/^\/course\/(.+)\/user\/$/", $path, $matches)) {
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => $matches[1] ));
                $debug && error_log("Rewritten to: $path");
            }

            // Clean up user profile urls.
        } else if (preg_match("/^\/user\/(.+)$/", $path, $matches)) {
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/profile.php";
                $params['id'] = $DB->get_field('user', 'id', array('username' => $matches[1] ));
                $debug && error_log("Rewritten to: $path");
            }

            // Clean up course urls.
        } else if (preg_match("/^\/course\/(.+)$/", $path, $matches)) {
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/course/view.php";
                $params['name'] = $matches[1];
                $debug && error_log("Rewritten to: $path");
            }
        }

        // Put back .php extension if doesn't end in .php or slash.
        if (substr($path, -1, 1) !== '/' && substr($path, -4) !== '.php') {
            $path .= '.php';
        }

        $debug && error_log("Rewritten to: $path");

        $url->path = $moodle . $path;
        $url->remove_all_params();
        $url->params($params);
        return $url;

    }
}
