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

use local_cleanurls\local\uncleaner\courseformat\fakeformat_uncleaner;
use local_cleanurls\local\uncleaner\courseformat_uncleaner;
use local_cleanurls\local\uncleaner\root_uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests for course format paths.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseformat_uncleaner_test extends local_cleanurls_testcase {
    /**
     * @expectedException \moodle_exception
     */
    public function test_it_cannot_know_its_possible_children() {
        courseformat_uncleaner::list_child_options();
    }

    public function test_it_cannot_detect_format_uncleaner_if_no_implementation_found() {
        $uncleaner = courseformat_uncleaner::get_format_uncleaner('an-invalid-format');
        self::assertNull($uncleaner);
    }

    public function test_it_can_detect_an_externally_coded_uncleaner() {
        $uncleaner = courseformat_uncleaner::get_format_uncleaner('cleanurlsfakeformat');
        self::assertSame('\format_cleanurlsfakeformat\cleanurls_uncleaner', $uncleaner);
    }

    public function test_it_can_detect_an_internally_coded_uncleaner() {
        $uncleaner = courseformat_uncleaner::get_format_uncleaner('fakeformat');
        self::assertSame(
            '\local_cleanurls\local\uncleaner\courseformat\fakeformat_uncleaner',
            $uncleaner);
    }

    public function test_it_cannot_create_if_parent_has_no_course() {
        self::assertFalse(courseformat_uncleaner::can_create(new root_uncleaner('/')));
    }

    public function test_it_forwards_the_can_create_to_the_format_uncleaner() {
        $this->getDataGenerator()->create_course(
            [
                'shortname' => 'shortname',
                'visible'   => 1,
                'format'    => 'fakeformat',
            ]
        );

        $root = new root_uncleaner('/course/shortname');
        $course = $root->get_child();

        local_cleanurls_unittest_uncleaner::$cancreate = function() {
            return true;
        };
        self::assertTrue(courseformat_uncleaner::can_create($course));

        local_cleanurls_unittest_uncleaner::$cancreate = function() {
            return false;
        };
        self::assertFalse(courseformat_uncleaner::can_create($course));

        $this->resetDebugging(); // Format not found warning.
    }

    public function test_it_cannot_create_if_format_is_invalid() {
        $this->getDataGenerator()->create_course(
            [
                'shortname' => 'shortname',
                'visible'   => 1,
                'format'    => 'invalidformat',
            ]
        );

        $root = new root_uncleaner('/course/shortname');
        $course = $root->get_child();

        self::assertFalse(courseformat_uncleaner::can_create($course));

        $this->resetDebugging(); // Format not found warning.
    }

    public function test_it_has_the_same_subpath_as_parent() {
        $this->getDataGenerator()->create_course(
            [
                'shortname' => 'shortname',
                'visible'   => 1,
                'format'    => 'fakeformat',
            ]
        );

        $root = new root_uncleaner('/course/shortname/some/crazy/path');
        $course = $root->get_child();
        $format = $course->get_child();

        self::assertSame($course->get_subpath(), $format->get_subpath());

        $this->resetDebugging(); // Format not found warning.
    }

    public function test_it_does_not_have_mypath() {
        $this->getDataGenerator()->create_course(
            [
                'shortname' => 'shortname',
                'visible'   => 1,
                'format'    => 'fakeformat',
            ]
        );

        $root = new root_uncleaner('/course/shortname/some/crazy/path');
        $course = $root->get_child();
        $format = $course->get_child();

        self::assertNull($format->get_mypath());

        $this->resetDebugging(); // Format not found warning.
    }

    public function test_it_creates_the_child_for_the_correct_format() {
        $this->getDataGenerator()->create_course(
            [
                'shortname' => 'shortname',
                'visible'   => 1,
                'format'    => 'fakeformat',
            ]
        );

        $root = new root_uncleaner('/course/shortname/some/crazy/path');
        $course = $root->get_child();
        $format = $course->get_child();
        $fakeformat = $format->get_child();

        self::assertInstanceOf(fakeformat_uncleaner::class, $fakeformat);
    }
}
