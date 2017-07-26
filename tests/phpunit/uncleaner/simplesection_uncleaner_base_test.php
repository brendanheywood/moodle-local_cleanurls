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

use local_cleanurls\local\courseformat\fakesimplesection;
use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;

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
class local_cleanurls_simplesection_uncleaner_base_test extends local_cleanurls_testcase {
    public function test_it_can_be_created_inside_courseformat_uncleaner() {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['shortname' => 'simplecourse', 'format' => 'fakesimplesection']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => "Forum First Section"]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'Custom Section']);

        $root = new root_uncleaner('/course/simplecourse/' .
                                   "custom-section/{$forum->cmid}-forum-first-section");

        $format = $root->get_child()->get_child();
        self::assertTrue(fakesimplesection::can_create($format));
        self::assertInstanceOf(fakesimplesection::class, $format->get_child());

        $this->resetDebugging(); // Invalid format message.
    }

    public function test_it_creates_path_subpath_and_section() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['shortname' => 'simplecourse', 'format' => 'fakesimplesection']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Forum First Section', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'Custom Section']);

        $root = new root_uncleaner('/course/simplecourse/' .
                                   "custom-section/{$forum->cmid}-forum-first-section/sub/path");
        $format = $root->get_child()->get_child()->get_child();
        self::assertInstanceOf(fakesimplesection::class, $format);

        self::assertSame("custom-section", $format->get_mypath(), 'Invalid mypath.');

        $expected = ["{$forum->cmid}-forum-first-section", 'sub', 'path'];
        self::assertSame($expected, $format->get_subpath(), 'Invalid subpath.');

        self::assertSame('Custom Section', $format->get_section()->name, 'Invalid section.');

        $this->resetDebugging(); // Invalid format message.
    }

    public function test_it_cannot_be_created_if_parent_has_no_course() {
        $root = new root_uncleaner('/course');
        self::assertFalse(fakesimplesection::can_create($root));
    }

    public function test_it_cannot_be_created_if_course_has_the_wrong_format() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'somethingelse']);
        $root = new root_uncleaner('/course/shortname/forum/123-idme');
        $format = $root->get_child()->get_child();
        self::assertFalse(fakesimplesection::can_create($format));
        $this->resetDebugging(); // Invalid format message.
    }

    public function test_it_requires_a_section() {
        $this->getDataGenerator()->create_course(['shortname' => 'shortname', 'format' => 'fakesimplesection']);
        $root = new root_uncleaner('/course/shortname');
        $format = $root->get_child()->get_child();
        self::assertFalse(fakesimplesection::can_create($format));
    }

    public function test_it_does_not_unclean_if_section_not_found() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['shortname' => 'Weekly', 'format' => 'fakesimplesection']);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'name' => 'Forum First Week', 'section' => 1]
        );
        list(, $cm) = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        // Give a name to the section.
        $DB->update_record('course_sections', (object)['id' => $cm->section, 'name' => 'Custom Section']);

        // When a URL cannot uncleaned, it will warn (debug) and use the course as fallback.
        $url = 'http://www.example.com/moodle/course/Weekly/' .
               "this-section-does-not-exists/{$forum->cmid}-forum-first-week";
        $expected = 'http://www.example.com/moodle/course/view.php?name=Weekly';

        $unclean = uncleaner::unclean($url);
        self::assertSame($expected, $unclean->raw_out());

        $this->resetDebugging(); // Could not unclean warning + fake format.
    }
}
