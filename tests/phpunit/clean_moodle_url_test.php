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
 * Testcase for Clean URLs.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\clean_moodle_url;
use local_cleanurls\local\cleaner\courseformat_cleaner_interface;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleanurls_testcase.php');

/**
 * Testcase for clean_moodle_url class.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_clean_moodle_url_test extends local_cleanurls_testcase implements courseformat_cleaner_interface {
    public function test_it_cannot_detect_format_uncleaner_if_no_implementation_found() {
        $support = clean_moodle_url::get_format_support('an-invalid-format');
        self::assertNull($support);
    }

    public function test_it_can_detect_an_externally_coded_uncleaner() {
        $support = clean_moodle_url::get_format_support('cleanurlsfakeformat');
        self::assertSame('\format_cleanurlsfakeformat\cleanurls_support', $support);
    }

    public function test_it_can_detect_an_internally_coded_uncleaner() {
        $support = clean_moodle_url::get_format_support('fakeformat');
        self::assertSame(
            '\local_cleanurls\local\courseformat\fakeformat',
            $support);
    }

    public function test_it_rejects_format_support_if_not_uncleaner() {
        $support = clean_moodle_url::get_format_support('notuncleanerfakeformat');
        self::assertDebuggingCalled("Class '" .
                                    '\local_cleanurls\local\courseformat\notuncleanerfakeformat' .
                                    "' must inherit uncleaner.");
        self::assertNull($support);
    }
    public function test_it_rejects_format_support_if_not_cleaner() {
        $support = clean_moodle_url::get_format_support('notcleanerfakeformat');
        self::assertDebuggingCalled("Class '" .
                                    '\local_cleanurls\local\courseformat\notcleanerfakeformat' .
                                    "' must implement courseformat_cleaner_interface.");
        self::assertNull($support);
    }

    public static function get_courseformat_clean_subpath(stdClass $course, cm_info $cm) {
        return null;
    }
}
