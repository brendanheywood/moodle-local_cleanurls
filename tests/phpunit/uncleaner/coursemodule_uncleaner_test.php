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
 * Tests.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_coursemodule_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_can_be_created() {
        local_cleanurls_unittest_uncleaner::$course = (object)['id' => 123];
        $subpath = ['forum', '123-idme'];
        $parent = new local_cleanurls_unittest_uncleaner(null, ['subpath' => $subpath]);
        self::assertTrue(coursemodule_uncleaner::can_create($parent));
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
        local_cleanurls_unittest_uncleaner::$course = (object)['id' => 123];
        $subpath = ['forum'];
        $parent = new local_cleanurls_unittest_uncleaner(null, ['subpath' => $subpath]);
        self::assertTrue(coursemodule_uncleaner::can_create($parent));
    }

    public function test_it_creates_from_a_valid_url() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);

        $root = new root_uncleaner('/course/shortname/forum/123-myforum');
        $course = $root->get_child();
        $module = $course->get_child();
        self::assertInstanceOf(coursemodule_uncleaner::class, $module);
        $this->resetDebugging(); // Invalid format message.
    }

    public function test_it_has_a_mypath_with_modname_and_id() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame('forum/123-idme', $module->get_mypath());
    }

    public function test_it_provides_the_module_name() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame('forum', $module->get_modname());
    }

    public function test_it_provides_the_course_module_id() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $module = $root->get_child()->get_child();
        self::assertSame(123, $module->get_cmid());
    }

    public function test_it_provides_null_for_an_invalid_course_module_id() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);
        $root = new root_uncleaner('/course/shortname/forum/idme');
        $module = $root->get_child()->get_child();
        self::assertNull($module->get_cmid());
    }

    public function test_it_provides_null_if_no_course_module_id() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'unknownformat']);
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
}
