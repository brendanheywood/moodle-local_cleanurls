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

use local_cleanurls\local\uncleaner\course_uncleaner;
use local_cleanurls\local\uncleaner\root_uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../cleanurls_testcase.php');
require_once(__DIR__ . '/unittest_uncleaner.php');

/**
 * Tests for category paths.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_can_be_in_root() {
        $root = new root_uncleaner('/course/shortname');
        self::assertTrue(course_uncleaner::can_create($root));
    }

    public function test_it_cannot_have_unexpected_parent() {
        $parent = new local_cleanurls_unittest_uncleaner();
        self::assertFalse(course_uncleaner::can_create($parent));
    }

    public function test_it_comes_from_course_subpath_in_root() {
        $root = new root_uncleaner('/course');
        $course = $root->get_child();
        self::assertInstanceOf(course_uncleaner::class, $course);
    }

    public function test_it_cannot_be_uncleaned_by_itself() {
        $root = new root_uncleaner('/course');
        $course = $root->get_child();
        self::assertNull($course->get_unclean_url());
    }
}
