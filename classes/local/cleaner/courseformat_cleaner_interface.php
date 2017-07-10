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

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * courseformat_cleaner_interface
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface courseformat_cleaner_interface {
    /**
     * This method will be called when CleanURLs wants to translate an activity (course module) into a clean URL.
     *
     * @param stdClass $course The Course being cleaned.
     * @param cm_info  $cm     The Course Module being cleaned.
     * @return string          The relative path from the course in which this course module will be accessed.
     */
    public static function get_courseformat_module_clean_subpath(stdClass $course, cm_info $cm);

    /**
     * This method will be called when CleanURLs wants to translate a course section into a clean URL.
     *
     * @param stdClass $course  The Course being cleaned.
     * @param int      $section The section number requested.
     * @return string The relative path from the course in which this section is.
     */
    public static function get_courseformat_section_clean_subpath(stdClass $course, $section);
}
