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
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls\local\uncleaner;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_course_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_course_uncleaner extends uncleaner {
    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // It must come from course.
        if (!is_a($parent, course_uncleaner::class)) {
            return false;
        }

        // It must have the 'user' subpath.
        if ((count($parent->subpath) < 1) || ($parent->subpath[0] != 'user')) {
            return false;
        }

        return true;
    }

    /**
     * It:
     * - Consumes 'user' subpath.
     * - Adds the next path as 'mypath' (username).
     * - Leave the rest as subpaths.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        array_shift($this->subpath);
        $this->mypath = array_shift($this->subpath);
    }

    protected $userid = null;

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        /** @var course_uncleaner $courseuncleaner */
        $courseuncleaner = $this->parent;

        $courseid = $courseuncleaner->get_course_id();
        if (is_null($courseid)) {
            return null;
        }

        $userid = $this->get_user_id();
        if (is_null($userid)) {
            $path = '/user/index.php';
            $this->parameters['id'] = $courseid;
        } else {
            $path = '/user/view.php';
            $this->parameters['id'] = $userid;
            $this->parameters['course'] = $courseid;
        }

        return new moodle_url($path, $this->parameters);
    }

    public function get_user_id() {
        global $DB;

        $username = $this->get_username();
        if (is_null($username)) {
            return null;
        }

        if (is_null($this->userid)) {
            $this->userid = $DB->get_field('user', 'id', ['username' => $username]);
        }

        return $this->userid;
    }

    public function get_username() {
        if (is_null($this->mypath)) {
            return null;
        }

        return urldecode($this->mypath);
    }
}
