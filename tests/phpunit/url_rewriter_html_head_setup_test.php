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

use local_cleanurls\local\cleaner\cleaner;
use local_cleanurls\local\uncleaner\uncleaner;
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
     * @param $mock
     */
    protected function mock_page($mock) {
        global $CFG, $ME, $ORIGINALME, $PAGE;

        $PAGE = new stdClass();

        if ($mock->routed) {
            $ME = '/local/cleanurls/tests/foo.php';
            $ORIGINALME = $mock->cleanable ? '/local/cleanurls/tests/oldbar' : '/local/cleanurls/tests/bar';
            $PAGE->url = new moodle_url($ORIGINALME);
            $CFG->uncleanedurl = (new moodle_url($ME))->raw_out();
        } else {
            unset($CFG->uncleanedurl);
            unset($ORIGINALME);
            $ME = $mock->cleanable ? '/local/cleanurls/tests/foo.php' : '/local/cleanurls/tests/legacy.php';
            $PAGE->url = new moodle_url($ME);
        }
    }

    /**
     * This only ensures we have the basic test URLs used in other tests.
     *
     * @dataProvider provider_with_page_mocks
     */
    public function test_it_requires_some_test_urls_for_testing($mock) {
        $unclean = null;

        $expectedclean = 'http://www.example.com/moodle/local/cleanurls/tests/bar';

        if (!$mock->routed && !$mock->cleanable) {
            $unclean = new moodle_url('/local/cleanurls/tests/legacy.php');
            $expectedclean = 'http://www.example.com/moodle/local/cleanurls/tests/legacy.php';
        }

        if (!$mock->routed && $mock->cleanable) {
            $unclean = new moodle_url('/local/cleanurls/tests/foo.php');
        }

        if ($mock->routed && !$mock->cleanable) {
            $unclean = uncleaner::unclean(new moodle_url('/local/cleanurls/tests/bar'));
        }

        if ($mock->routed && $mock->cleanable) {
            $unclean = uncleaner::unclean(new moodle_url('/local/cleanurls/tests/oldbar'));
        }

        $clean = cleaner::clean($unclean)->raw_out();
        self::assertSame($expectedclean, $clean);
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
            self::assertNotContains("document.addEventListener('click',", $output);
        } else {
            self::assertContains('<base href="http://www.example.com/moodle/local/cleanurls/tests/foo.php">', $output);
            // Ensure correct 'js fix' is available.
            self::assertContains("document.addEventListener('click',", $output);
            $href = "'http://www.example.com/moodle/local/cleanurls/tests/bar' + element.getAttribute('href')";
            self::assertContains("element.href = {$href};", $output);
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
            self::assertNotContains('<base href', $output);
            self::assertNotContains("document.addEventListener('click',", $output);
        } else {
            self::assertContains('<base href="http://www.example.com/moodle/local/cleanurls/tests/foo.php">', $output);
            // Ensure correct 'js fix' is available.
            self::assertContains("document.addEventListener('click',", $output);
            $href = "'http://www.example.com/moodle/local/cleanurls/tests/bar' + element.getAttribute('href')";
            self::assertContains("element.href = {$href};", $output);
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
