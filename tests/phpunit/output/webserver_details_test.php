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
 * Tests.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\output\webserver_summary_renderer;
use local_cleanurls\test\webserver\webtest_fake;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../mocks/webtest_fake.php');

/**
 * Tests.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_output_webserver_details_test extends advanced_testcase {
    /** @var webserver_summary_renderer */
    protected $renderer = null;

    /** @var webtest_fake */
    protected $test = null;

    protected function setUp() {
        global $PAGE;
        parent::setUp();
        $PAGE->set_url('/local/cleanurls/webservertest.php');

        $this->test = new webtest_fake();
        $this->test->run();
        $this->renderer = $PAGE->get_renderer('local_cleanurls', 'webserver_details', RENDERER_TARGET_GENERAL);
        $this->renderer->set_result($this->test);
    }

    public function test_it_outputs() {
        $contains = [
            '<html',
            '<body',
            '</body',
            '</html',
            '<h2>This is a fake test name</h2>',
            '<span class="statusok">Passed</span>',
            '<h2>Description</h2>',
            'This is a fake test description.',
            '<h2>Problems</h2>',
            'No problems found.',
            '<h2>Troubleshooting</h2>',
            '<li>Ensure fake test works at first.</li>',
            '<li>Ensure fake test works again.</li>',
            '<a href="https://github.com/brendanheywood/moodle-local_cleanurls/blob/master/README.md"',
            '<h2>Debugging</h2>',
            'No debugging information.',
        ];

        $output = $this->renderer->render_page();

        foreach ($contains as $contain) {
            self::assertContains($contain, $output);
        }
    }

    public function test_it_outputs_one_error() {
        $contains = [
            '<h2>Problems</h2>',
            '<li>I have an error.</li>',
        ];

        $notcontains = [
            'No problems found.',
        ];

        $this->test->fakeerrors = ['I have an error.'];
        $this->test->run();
        $output = $this->renderer->render_page();

        foreach ($contains as $contain) {
            self::assertContains($contain, $output);
        }

        foreach ($notcontains as $notcontain) {
            self::assertNotContains($notcontain, $output);
        }
    }

    public function test_it_outputs_many_errors() {
        $contains = [
            '<h2>Problems</h2>',
            '<li>I have an error.</li>',
            '<li>I have another error.</li>',
        ];

        $notcontains = [
            'No problems found.',
        ];

        $this->test->fakeerrors = ['I have an error.', 'I have another error.'];
        $this->test->run();
        $output = $this->renderer->render_page();

        foreach ($contains as $contain) {
            self::assertContains($contain, $output);
        }

        foreach ($notcontains as $notcontain) {
            self::assertNotContains($notcontain, $output);
        }
    }

    public function test_it_has_debugging_information() {
        $debugging = "Fetching: https://www.example.com/moodle/200:My Header:My Body
*** DATA DUMP: Header ***
My Header
*** DATA DUMP: Body ***
My Body
*** DATA DUMP: End ***";
        $contains = [
            '<h2>Debugging</h2>',
            $debugging,
        ];

        $notcontains = [
            'No debugging information.',
        ];

        $this->test->fetch('200:My Header:My Body');
        $output = $this->renderer->render_page();

        foreach ($contains as $contain) {
            self::assertContains($contain, $output);
        }

        foreach ($notcontains as $notcontain) {
            self::assertNotContains($notcontain, $output);
        }
    }
}
