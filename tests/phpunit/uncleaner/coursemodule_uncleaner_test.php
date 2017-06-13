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

use local_cleanurls\local\uncleaner\coursemodule_uncleaner;
use local_cleanurls\local\uncleaner\root_uncleaner;

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
class coursemodule_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_can_be_created() {
        $root = new root_uncleaner('/forum/123-idme');
        self::assertTrue(coursemodule_uncleaner::can_create($root));
    }

    public function test_it_requires_a_modname() {
        $root = new root_uncleaner('/');
        self::assertFalse(coursemodule_uncleaner::can_create($root));
    }

    public function test_it_requires_a_valid_modname() {
        $root = new root_uncleaner('/invalidmodname');
        self::assertFalse(coursemodule_uncleaner::can_create($root));
    }

    public function test_it_does_not_require_an_cmid_slug() {
        $root = new root_uncleaner('/forum');
        self::assertTrue(coursemodule_uncleaner::can_create($root));
    }

    public function test_it_creates_from_a_valid_url() {
        $root = new root_uncleaner('/course/shortname/forum/123-myforum');
        $course = $root->get_child();
        $module = $course->get_child();
        self::assertInstanceOf(coursemodule_uncleaner::class, $module);
    }

    public function test_it_has_a_mypath_with_modname_and_id() {
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame('forum/123-idme', $module->get_mypath());
    }

    public function test_it_provides_the_module_name() {
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame('forum', $module->get_modname());
    }

    public function test_it_provides_the_course_module_id() {
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame(123, $module->get_cmid());
    }

    public function test_it_provides_null_for_an_invalid_course_module_id() {
        $root = new root_uncleaner('/course/shortname/forum/idme');
        $module = $root->get_child()->get_child();
        self::assertNull($module->get_cmid());
    }

    public function test_it_provides_null_if_no_course_module_id() {
        $root = new root_uncleaner('/course/shortname/forum');
        $module = $root->get_child()->get_child();
        self::assertNull($module->get_cmid());
    }

    public function test_it_cannot_be_root() {
        self::assertFalse(coursemodule_uncleaner::can_create(null));
    }

    public function test_it_requires_a_valid_parent() {
        self::assertFalse(coursemodule_uncleaner::can_create(new stdClass()));
    }

    public function test_it_cleans_course_module_view_urls() {
        global $DB;

        $category = $this->getDataGenerator()->create_category(['name' => 'category']);

        $course = $this->getDataGenerator()->create_course(
            [
                'fullname'  => 'course long name',
                'shortname' => 'shortname',
                'visible'   => 1,
                'category'  => $category->id,
            ]
        );
        // We are enforcing 'customformat' to not trigger format-specific cleaning/uncleaning.
        $course->format = 'invalidformat';
        $DB->update_record('course', $course);

        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'name'   => 'A Test Forum',
        ]);

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $forum->cmid;
        $expected = 'http://www.example.com/moodle/course/shortname/forum/' . $forum->cmid . '-a-test-forum';
        static::assert_clean_unclean($url, $expected);
        $this->resetDebugging(); // There will be a debugging regarding the invalid 'customformat'.
    }

    public function test_it_cleans_course_modules_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course(['fullname'  => 'course long name',
                                                            'shortname' => 'shortname',
                                                            'visible'   => 1,
                                                            'category'  => $category->id]);

        static::assert_clean_unclean('http://www.example.com/moodle/mod/forum/index.php?id='.$course->id,
                                     'http://www.example.com/moodle/course/shortname/forum');
    }
}
