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
use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

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
        $this->getDataGenerator()->create_course(['shortname' => 'shortname']);
        $root = new root_uncleaner('/course/shortname');
        self::assertTrue(course_uncleaner::can_create($root));
    }

    public function test_it_cannot_have_unexpected_parent() {
        $parent = new local_cleanurls_unittest_uncleaner();
        self::assertFalse(course_uncleaner::can_create($parent));
    }

    public function test_it_comes_from_course_subpath_in_root() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname']);
        $root = new root_uncleaner('/course/shortname');
        $course = $root->get_child();
        self::assertInstanceOf(course_uncleaner::class, $course);
    }

    public function test_it_cannot_create_if_course_not_expected() {
        $root = new root_uncleaner('/notacourse');
        self::assertFalse(course_uncleaner::can_create($root));
    }

    public function test_it_has_the_shortname() {
        $this->getDataGenerator()->create_course(['shortname' => 'someshortname']);
        $root = new root_uncleaner('/course/someshortname');
        $course = $root->get_child();

        $shortname = $course->get_course_shortname();
        self::assertSame('someshortname', $shortname);
    }

    public function test_it_cleans_course_urls_by_id() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
                                                               'fullname'  => 'full name of the course',
                                                               'shortname' => 'shortname',
                                                               'visible'   => 1,
                                                               'category'  => $category->id,
                                                           ]);

        static::assert_clean_unclean('http://www.example.com/moodle/course/view.php?id=' . $course->id,
                                     'http://www.example.com/moodle/course/shortname',
                                     'http://www.example.com/moodle/course/view.php?name=shortname');
    }

    public function test_it_uncleans_a_course_even_with_a_slash_suffix() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $this->getDataGenerator()->create_course([
                                                     'fullname'  => 'full name of the course',
                                                     'shortname' => 'shortname',
                                                     'visible'   => 1,
                                                     'category'  => $category->id,
                                                 ]);

        $url = 'http://www.example.com/moodle/course/shortname/';
        $expected = 'http://www.example.com/moodle/course/view.php?name=shortname';
        $uncleaned = uncleaner::unclean($url)->raw_out();
        self::assertSame($expected, $uncleaned);
    }

    public function test_it_cleans_course_with_hash_in_shortname() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
                                                               'fullname'  => 'full name of the course #3',
                                                               'shortname' => 'short#name',
                                                               'visible'   => 1,
                                                               'category'  => $category->id,
                                                           ]);

        static::assert_clean_unclean('http://www.example.com/moodle/course/view.php?id=' . $course->id,
                                     'http://www.example.com/moodle/course/short%23name',
                                     'http://www.example.com/moodle/course/view.php?name=short%23name');
    }

    public function test_it_cleans_course_urls_by_name() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $this->getDataGenerator()->create_course([
                                                     'fullname'  => 'full name',
                                                     'shortname' => 'theshortname',
                                                     'visible'   => 1,
                                                     'category'  => $category->id,
                                                 ]);

        static::assert_clean_unclean('http://www.example.com/moodle/course/view.php?name=theshortname',
                                     'http://www.example.com/moodle/course/theshortname');
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
            'http://www.example.com/moodle/course/view.php?edit=1&id=' . $course->id,
            'http://www.example.com/moodle/course/theshortname?edit=1',
            'http://www.example.com/moodle/course/view.php?edit=1&name=theshortname'
        );
    }
}
