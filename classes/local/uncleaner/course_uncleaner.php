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
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_uncleaner extends uncleaner implements hascourse_uncleaner_interface {
    /**
     * @return string[]
     */
    public static function list_child_options() {
        return [
            user_course_uncleaner::class,
            courseformat_uncleaner::class,
            coursemodule_uncleaner::class,
        ];
    }

    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        // It must be child of root.
        if (!is_a($parent, root_uncleaner::class)) {
            return false;
        }

        // Next token must be 'course'.
        if ((count($parent->subpath) < 1) || ($parent->subpath[0] != 'course')) {
            return false;
        }

        return true;
    }

    protected $course = null;

    protected $coursename = null;

    /**
     * It:
     * - Consumes 'course'.
     * - Adds the next path as 'mypath' (course shortname).
     * - Leave the rest as subpaths.
     */
    protected function prepare_path() {
        $this->subpath = $this->parent->subpath;
        $course = array_shift($this->subpath);
        $this->coursename = array_shift($this->subpath);
        $this->mypath = "{$course}/{$this->coursename}";
        $this->coursename = urldecode($this->coursename);
    }

    /**
     * Allow creating child even without subpath (for example singleactivity format).
     */
    protected function prepare_child() {
        $this->child = null;

        $options = static::list_child_options();
        foreach ($options as $option) {
            if ($option::can_create($this)) {
                $this->child = new $option($this);
                return;
            }
        }
    }

    /**
     * If it founda course, return it. Otherwise return parent's.
     *
     * @return moodle_url
     */
    public function get_unclean_url() {
        $id = $this->get_course_id();
        if (is_null($id)) {
            return null;
        }

        return $this->create_unclean_url('/course/view.php', ['id' => $id]);
    }

    public function get_course_shortname() {
        if (empty($this->coursename)) {
            return null;
        }
        return $this->coursename;
    }

    /**
     * @return stdClass
     */
    public function get_course() {
        global $DB;

        $shortname = $this->get_course_shortname();
        if (is_null($shortname)) {
            return null;
        }

        if (is_null($this->course)) {
            $this->course = $DB->get_record('course', ['shortname' => $shortname]);
        }

        return $this->course;
    }

    public function get_course_id() {
        $course = $this->get_course();

        if (!is_object($course)) {
            return null;
        }

        return $this->course->id;
    }

    public function get_course_format() {
        $course = $this->get_course();

        if (!is_object($course)) {
            return null;
        }

        return $this->course->format;
    }
}
