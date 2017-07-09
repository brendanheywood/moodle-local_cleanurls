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
 * This is a mock format for CleanURLs uncleaner tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We are enforcing a namespace outside cleanurls scope to emulate a format plugin.
namespace format_cleanurlsfakeformat;

use cm_info;
use local_cleanurls\local\cleaner\courseformat_cleaner_interface;
use local_cleanurls_unittest_uncleaner;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * This is a mock format for CleanURLs uncleaner tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanurls_support extends local_cleanurls_unittest_uncleaner implements courseformat_cleaner_interface {
    public static function get_courseformat_module_clean_subpath(stdClass $course, cm_info $cm) {
        return null;
    }

    public static function get_courseformat_section_clean_subpath(stdClass $course, $section) {
        return null;
    }
}
