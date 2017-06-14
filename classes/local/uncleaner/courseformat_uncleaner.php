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

use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class courseformat_uncleaner
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseformat_uncleaner extends uncleaner implements hascourse_uncleaner_interface {
    /**
     * This should never be called for this uncleaner.
     *
     * The method 'prepare_child()' should be properly overriden so it
     * does not rely on knowing the child options.
     *
     * @return \string[]
     * @throws \moodle_exception
     */
    public static function list_child_options() {
        throw new moodle_exception('Cannot determine child options without knowing the course format.');
    }

    protected function prepare_child() {
        $format = self::get_format_uncleaner($this->get_course()->format);
        $this->child = new $format($this);
    }

    protected function prepare_path() {
        $this->mypath = null;
        $this->subpath = $this->parent->subpath;
    }

    /**
     * Quick check if this object should be created for the given parent.
     *
     * @param uncleaner $parent
     * @return bool
     */
    public static function can_create($parent) {
        if (!is_a($parent, hascourse_uncleaner_interface::class)) {
            return false;
        }

        $course = $parent->get_course();
        $cleaner = self::get_format_uncleaner($course->format);
        if (!is_a($cleaner, uncleaner::class, true)) {
            return false;
        }

        // Note we are passing the parent to ask if we can create,
        // but if we can we are going to pass 'this' as parent instead.
        return $cleaner::can_create($parent);
    }

    /**
     * Locates the class that should handle the course format.
     *
     * @param string $format
     * @return string
     */
    public static function get_format_uncleaner($format) {
        $classname = "\\format_{$format}\\cleanurls_uncleaner";
        if (class_exists($classname)) {
            return $classname;
        }

        $classname = "\\local_cleanurls\\local\\uncleaner\\courseformat\\{$format}_uncleaner";
        if (class_exists($classname)) {
            return $classname;
        }

        return null;
    }

    /**
     * @return moodle_url
     */
    public function get_unclean_url() {
        return null;
    }

    /**
     * Finds the course related to this uncleaner, most likely by getting it from its parent.
     *
     * @return stdClass Course data object.
     */
    public function get_course() {
        return $this->parent->get_course();
    }
}
