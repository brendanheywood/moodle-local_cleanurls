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

use local_cleanurls\url_rewriter;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/cleanurls_testcase.php');

/**
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_url_rewriter_html_head_setup_test extends local_cleanurls_testcase {
    public function tearDown() {
        parent::tearDown();
        url_rewriter::clear_last_apache_note();
    }

    /**
     * Mocks 4 different types of access to this page.
     *
     * Pages with 'nonrouted' are pages that are not coming through a router (using unclean URL).
     * Pages with 'routed' are coming through a router (using clean URL).
     *
     * The 'routed-canonical' is a page with the correct clean url.
     * The 'routed-legacy' is a page with a clean url that works but should be replaced by its canonical version.
     *
     * @return array
     */
    public function provider_with_page_mocks() {
        return [
            'nonrouted-uncleanable' => [(object)['routed' => false, 'cleanable' => false]],
            'nonrouted-cleanable'   => [(object)['routed' => false, 'cleanable' => true]],
            'routed-canonical'      => [(object)['routed' => true, 'cleanable' => false]],
            'routed-legacy'         => [(object)['routed' => true, 'cleanable' => true]],
        ];
    }

    /**
     * Sets up the page using the given data provider information.
     *
     * Use the following links to make sure you are mocking it correctly:
     * - unrouted uncleanable: /local/cleanurls/tests/legacy.php?key=value
     * - unrouted cleanable:   /local/cleanurls/tests/foo.php?key=value
     * - routed   uncleanable: /local/cleanurls/tests/bar?key=value
     * - routed   cleanable:   /local/cleanurls/tests/oldbar?key=value
     *
     * @param $mock
     */
    protected function mock_page($mock) {
        global $CFG, $ME, $PAGE;

        $PAGE = new stdClass();

        if ($mock->routed) {
            $ME = '/local/cleanurls/tests/foo.php?key=value';
            $CFG->cleanurloriginal = $mock->cleanable ? '/local/cleanurls/tests/oldbar' : '/local/cleanurls/tests/bar';
            $CFG->uncleanedurl = (new moodle_url($ME))->raw_out();
        } else {
            unset($CFG->uncleanedurl);
            unset($CFG->cleanurloriginal);
            $ME = $mock->cleanable ? '/local/cleanurls/tests/foo.php' : '/local/cleanurls/tests/legacy.php';
            $ME .= '?key=value';
        }

        $PAGE->url = new moodle_url($ME);
        $PAGE->url->remove_all_params(); // Preten`d the parameter is not an official parameter of that page.
    }

    public function test_it_routes_unclean_to_clean() {
        global $CFG, $ME;

        $wwwroot = new moodle_url($CFG->wwwroot);
        $mdldirpath = $wwwroot->get_path(); // e.g. "/moodle"

        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $course = $this->getDataGenerator()->create_course([
            'fullname'  => 'full name',
            'shortname' => 'theshortname',
            'visible'   => 1,
            'category'  => $category->id,
        ]);

        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name'   => 'A Test Page',
        ]);

        $user = $this->getDataGenerator()->create_user(['email' => 'exampleuser@example.com', 'username' => 'exampleuser']);

        // Course view URL.
        $ME = "/moodle/course/view.php?id={$course->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);

        // Course users URL.
        $ME = "/moodle/user/?id={$course->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);

        // Course users URL w/index.php. Moodle dir shouldn't appear twice.
        $ME = "/moodle/user/index.php?id={$course->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);
        self::assertNotContains($CFG->wwwroot . $mdldirpath, $output);

        // Course index URL.
        $ME = "/moodle/course/?categoryid={$category->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);

        // Course index URL w/index.php. Moodle dir shouldn't appear twice.
        $ME = "/moodle/course/index.php?categoryid={$category->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);
        self::assertNotContains($CFG->wwwroot . $mdldirpath, $output);

        // Module URL
        $ME = "/moodle/mod/page/?id={$course->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);

        // Module URL w/index.php. Moodle dir shouldn't appear twice.
        $ME = "/moodle/mod/page/index.php?id={$course->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);
        self::assertNotContains($CFG->wwwroot . $mdldirpath, $output);

        // Module view URL
        $ME = "/moodle/mod/page/view.php?id={$page->cmid}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);

        // User profile URL
        $ME = "/moodle/user/profile.php?id={$user->id}";
        $output = url_rewriter::html_head_setup();
        self::assertNotEquals('', $output);
    }

    /**
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_generates_base_href($mock) {
        $this->mock_page($mock);
        $output = url_rewriter::html_head_setup();

        // Element 'base href' must be always present, except if an unclean URL that cannot be cleaned.
        if (!$mock->routed && !$mock->cleanable) {
            self::assertNotContains('<base href', $output);
        } else {
            self::assertContains(
                '<base href="http://www.example.com/moodle/local/cleanurls/tests/foo.php?key=value">',
                $output
            );
        }
    }

    /**
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_generates_anchor_fix($mock) {
        $this->mock_page($mock);
        $output = url_rewriter::html_head_setup();

        // In those cases where 'base href' is set, we must also provide the javascript anchor fix.
        if (!$mock->routed && !$mock->cleanable) {
            self::assertNotContains("document.addEventListener('click',", $output);
        } else {
            self::assertContains("document.addEventListener('click',", $output);
        }
    }

    /**
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_generates_history_replacestate($mock) {
        $this->mock_page($mock);
        $output = url_rewriter::html_head_setup();

        // The history.replaceState should happend to:
        // a) Unclean URLs that can be cleaned.
        // b) Clean URLs that are not canonical.
        if ($mock->cleanable) {
            $clean = 'http://www.example.com/moodle/local/cleanurls/tests/bar';
            $script = "<script>history.replaceState && history.replaceState({}, '', '{$clean}');</script>";
            self::assertContains($script, $output);
        } else {
            self::assertNotContains('history.replaceState', $output);
        }
    }

    /**
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_generates_canonical_url($mock) {
        $this->mock_page($mock);
        $output = url_rewriter::html_head_setup();

        // The canonical reference must exist for any page that has a better URL.
        if ($mock->cleanable) {
            $canonical = '<link rel="canonical" href="http://www.example.com/moodle/local/cleanurls/tests/bar" />';
            self::assertContains($canonical, $output);
        } else {
            self::assertNotContains('<link rel="canonical"', $output);
        }
    }

    /**
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_sets_apache_flag($mock) {
        $this->mock_page($mock);
        url_rewriter::html_head_setup();
        $note = url_rewriter::get_last_apache_note();

        // Any URL that can be further cleaned should flag apache what is the cleaned version.
        if ($mock->cleanable) {
            self::assertSame('http://www.example.com/moodle/local/cleanurls/tests/bar', $note);
        } else {
            self::assertNull($note);
        }
    }
}
