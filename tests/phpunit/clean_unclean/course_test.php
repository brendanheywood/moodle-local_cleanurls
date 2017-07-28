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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_course_cleanunclean_test extends local_cleanurls_testcase {
    public function test_it_cleans_course_urls_by_id() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
                                                               'fullname'  => 'full name of the course',
                                                               'shortname' => 'shortname',
                                                               'visible'   => 1,
                                                               'category'  => $category->id,
                                                           ]);

        static::assert_clean_unclean('http://www.example.com/moodle/course/view.php?id=' . $course->id,
                                     'http://www.example.com/moodle/course/shortname');
    }

    public function test_it_cleans_course_urls_by_name() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
                                                     'fullname'  => 'full name',
                                                     'shortname' => 'theshortname',
                                                     'visible'   => 1,
                                                     'category'  => $category->id,
                                                 ]);

        static::assert_clean_unclean('http://www.example.com/moodle/course/view.php?name=theshortname',
                                     'http://www.example.com/moodle/course/theshortname',
                                     "http://www.example.com/moodle/course/view.php?id={$course->id}");
    }

    public function test_it_cleans_in_edit_mode() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
                                                               'fullname'  => 'full name',
                                                               'shortname' => 'theshortname',
                                                               'visible'   => 1,
                                                               'category'  => $category->id,
                                                           ]);

        static::assert_clean_unclean(
            "http://www.example.com/moodle/course/view.php?edit=1&id={$course->id}",
            'http://www.example.com/moodle/course/theshortname?edit=1',
            "http://www.example.com/moodle/course/view.php?edit=1&id={$course->id}"
        );
    }

    public function test_it_does_not_clean_section_number_if_format_not_supported() {
        $course = $this->getDataGenerator()->create_course([
                                                     'shortname' => 'name',
                                                     'format'    => 'unknownformat',
                                                 ]);

        static::assert_clean_unclean(
            "http://www.example.com/moodle/course/view.php?id={$course->id}&section=1",
            'http://www.example.com/moodle/course/name?section=1'
        );

        $this->assertDebuggingCalled();
    }
}
