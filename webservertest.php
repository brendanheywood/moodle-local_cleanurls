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
 * @var $CFG      stdClass
 * @var $PAGE     moodle_page
 */

use local_cleanurls\test\webserver\webtest;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_cleanurls_webservertest');

$details = optional_param('details', '', PARAM_SAFEDIR);
if (!empty($details)) {
    $details = "\\local_cleanurls\\test\\webserver\\{$details}";
    if (!class_exists($details)) {
        $details = '';
    }
}

if (empty($details)) {
    $renderer = $PAGE->get_renderer('local_cleanurls', 'webserver_summary');
    $tests = webtest::run_available_tests();
    $renderer->set_results($tests);
    echo $renderer->render_page();
} else {
    $test = new $details();
    $renderer = $PAGE->get_renderer('local_cleanurls', 'webserver_details');
    $test->run();
    $renderer->set_result($test);
    echo $renderer->render_page();
}

