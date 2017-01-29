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
 * Class uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

defined('MOODLE_INTERNAL') || die();

/**
 * Class uncleaner
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uncleaner {

    /**
     * Takes a string and converts it into an unclean moodle_url object
     *
     * @param string $clean the incoming url
     * @return \moodle_url the original moodle_url
     */
    public static function unclean($clean) {

        global $CFG, $DB;

        clean_moodle_url::log("Incoming url: $clean");

        $url = new clean_moodle_url($clean);
        $path = $url->get_path();
        $params = $url->params();

        clean_moodle_url::log("Incoming path: $path");

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

        // These regex's must be in order of higher specificity to lowest.

        if ($path == '/local/cleanurls/tests/bar') {
            $path = '/local/cleanurls/tests/foo.php';
            clean_moodle_url::log("Rewritten to: $path");
        } else if (preg_match("/^\/course\/(.+)\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[2]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[2] . ".php")) {
                $path = "/user/view.php";
                $params['id']     = $DB->get_field('user',   'id', array('username'  => urldecode($matches[2]) ));
                $params['course'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/user\/?$/", $path, $matches)) {
            // Clean up course user list urls inside course.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/user\/(\w+)\/(discussions)$/", $path, $matches)) {
            // Unclean paths
            // e.g.: http://moodle.com/user/username/discussions
            // into http://moodle.com/mod/forum/user.php?id=123&mode=discussions .
            $path = "/mod/forum/user.php";
            $params['id'] = $DB->get_field('user', 'id', array('username'  => urldecode($matches[1]) ));
            $params['mode'] = $matches[2];
            clean_moodle_url::log("Rewritten to: $path");

        } else if (preg_match("/^\/user\/(.+)$/", $path, $matches)) {
            // Clean up user profile urls.
            if (!is_dir ($CFG->dirroot . '/user/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/user/' . $matches[1] . ".php")) {
                $path = "/user/profile.php";
                if (isset($params['course'])) {
                    $path = "/user/view.php";
                }
                $params['id'] = $DB->get_field('user', 'id', array('username' => urldecode($matches[1]) ));
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/(\w+)\/(\d+)(-.*)?$/", $path, $matches)) {
            // Clean up course mod view.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/mod/$matches[2]/view.php";
                $params['id'] = $matches[3];
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)\/(\w+)\/?$/", $path, $matches)) {

            // Clean up course mod index.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/mod/$matches[2]/index.php";
                $params['id'] = $DB->get_field('course', 'id', array('shortname' => urldecode($matches[1]) ));
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match("/^\/course\/(.+)$/", $path, $matches)) {
            // Clean up course urls.
            if (!is_dir ($CFG->dirroot . '/course/' . $matches[1]) &&
                !is_file($CFG->dirroot . '/course/' . $matches[1] . ".php")) {
                $path = "/course/view.php";
                $params['name'] = $matches[1];
                clean_moodle_url::log("Rewritten to: $path");
            }

        } else if (preg_match('/^\/category(\/.*-(\d+))?$/', $path, $matches)) {
            // Clean up category urls.
            $path = "/course/index.php";

            // We need at least 2 matches to get a valid id. Use the last match (last part of path).
            $params['categoryid'] = (count($matches) <= 1) ? 0 : array_pop($matches);

            clean_moodle_url::log("Rewritten to: $path");
        }

        // Put back .php extension if doesn't end in .php or slash.
        if (substr($path, -1, 1) !== '/' && substr($path, -4) !== '.php') {
            $path .= '.php';
        }

        clean_moodle_url::log("Rewritten to: $path");

        $url->set_path($moodle . $path);
        $url->remove_all_params();
        $url->params($params);
        return $url;

    }
}
