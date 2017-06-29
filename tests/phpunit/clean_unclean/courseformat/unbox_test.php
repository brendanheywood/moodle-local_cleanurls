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

use local_cleanurls\local\courseformat\unbox;
use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../cleanurls_testcase.php');

/**
 * Tests for unbox format support.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_unbox_format_test extends local_cleanurls_testcase {
    /** @var stdClass */
    private $course = null;

    /** @var stdClass[] */
    private $forums = [];

    /** @var stdClass[] */
    private $sections = null;

    protected function setUp() {
        global $CFG;
        parent::setUp();

        $plugin = $CFG->dirroot . '/course/format/unbox';
        if (!file_exists($plugin)) {
            $this->markTestSkipped('format_unbox not available.');
            return;
        }

        $this->resetAfterTest(true);
    }

    public function test_its_uncleaner_can_be_created() {
        $this->create_course();
        $root = new root_uncleaner($this->get_example_url());
        $format = $root->get_child()->get_child();
        self::assertTrue(unbox::can_create($format));
        $unbox = $format->get_child();
        self::assertInstanceOf(unbox::class, $unbox);
    }

    public function test_its_uncleaner_requires_a_course() {
        $this->create_course();
        $root = new root_uncleaner('/');
        self::assertFalse(unbox::can_create($root));
    }

    public function test_its_uncleaner_requires_the_correct_format() {
        $this->create_course(['format' => 'topics']);
        $root = new root_uncleaner($this->get_example_url());
        $course = $root->get_child();
        $format = $course->get_child();
        self::assertFalse(unbox::can_create($format));
    }

    public function test_it_cleans_and_uncleans() {
        $this->create_course();

        $url = 'http://www.example.com/moodle/mod/forum/view.php?id=' . $this->forums[6]->cmid;
        $expected = 'http://www.example.com/moodle/course/mycourse/topic-1/topic-2/topic-6/' .
                    "{$this->forums[6]->cmid}-forum-6";
        self::assert_clean_unclean($url, $expected);
    }

    private function create_course($options = []) {
        global $DB;

        $defaults = [
            'shortname'   => 'mycourse',
            'format'      => 'unbox',
            'numsections' => 7,
        ];
        $options = array_merge($defaults, $options);
        $this->course = $this->getDataGenerator()->create_course($options);
        $sectionstmp = $DB->get_records('course_sections', ['course' => $this->course->id], 'section ASC');
        $this->sections = [];
        foreach ($sectionstmp as $section) {
            $this->sections[$section->section] = $section;
        }

        $this->create_course_section_parent(1, null); // No parent.
        $this->create_course_section_parent(2, 1);
        $this->create_course_section_parent(3, 1);
        $this->create_course_section_parent(4, 0); // Another way for no parent (root parent).
        $this->create_course_section_parent(5, 2);
        $this->create_course_section_parent(6, 2);
        $this->create_course_section_parent(7, 3);
    }

    private function create_course_section_parent($idchild, $numparent) {
        global $DB;
        if (!is_null($numparent)) {
            $DB->insert_record('course_format_options', (object)[
                'courseid'  => $this->course->id,
                'format'    => 'unbox',
                'sectionid' => $this->sections[$idchild]->id,
                'name'      => 'parent',
                'value'     => $this->sections[$numparent]->section,
            ]);
        }
        $num = $this->sections[$idchild]->section;
        $this->forums[$num] = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $this->course->id, 'name' => "Forum {$num}", 'section' => $num]
        );
    }

    private function get_example_url() {
        return "/course/mycourse/topic-1/topic-2/topic-6/{$this->forums[6]->cmid}-forum";
    }
}
