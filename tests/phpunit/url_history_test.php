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
 * Tests
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleanurls_testcase.php');

/**
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_url_history_test extends local_cleanurls_testcase {
    public function test_it_remembers_a_course_url_even_after_it_changes_shortname() {
        global $DB;

        $course = self::getDataGenerator()->create_course(['shortname' => 'oldname']);
        $originalurl = new moodle_url('/course/view.php', ['id' => $course->id]);
        $expected = $originalurl->raw_out();
        $oldcleaned = $originalurl->out(); // This will force 'caching' and 'storing' the old URL.

        $course->shortname = 'newname';
        $DB->update_record('course', $course);
        purge_all_caches();
        $originalurl->out(); // This will force 'caching' and 'storing' the new URL.

        // Regardless of the new URL, the old one should still be accessible without relying on cache.
        $actual = uncleaner::unclean($oldcleaned)->raw_out();
        self::assertSame($expected, $actual);
    }

    public function test_it_overwrites_a_course_url_after_another_course_gets_its_shortname() {
        $this->markTestSkipped('Test/Feature not yet implemented.');
    }
}
