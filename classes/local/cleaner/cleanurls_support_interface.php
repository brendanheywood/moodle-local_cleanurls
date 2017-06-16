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
 * Provides an interface for adding CleanURLs support.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls\local\cleaner;

use cm_info;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * cleanurls_support_interface
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface cleanurls_support_interface {
    /**
     * This method will be called when CleanURLs wants to translate an activity (course module) into an URL.
     *
     * It will result a subpath which will appear like in a URL such as http://moodle/course/mycourse/subpath
     *
     * @param stdClass $course The Course being cleaned.
     * @param cm_info  $cm     The Course Module being cleaned.
     * @return string          The relative path from the course in which this course module will be accessed.
     */
    public static function get_clean_subpath(stdClass $course, cm_info $cm);

    /**
     * This method will be called when CleanURLs needs to find the activity (course module) for a given URL.
     *
     * When requesting a URL like http://moodle/course/mycourse/subpath1/subpath2 then CleanURLs will:
     *      1) Resolve the URL until the course 'mycourse'.
     *      2) Discover the format of 'mycourse' to call this method from.
     *      3) Pass the subpaths as an array, such as ['subpath1', 'subpath2'].
     *
     * It will then expect a course module id (cmid) that was resolved from the path.
     *
     * @param stdClass $course The Course being uncleaned.
     * @param string[] $path   All subpaths in the URL after the course name.
     * @return int|null The course module id (cmid) translated from that path or null if it cannot be translated.
     */
    public static function get_cmid_for_path(stdClass $course, array $path);
}
