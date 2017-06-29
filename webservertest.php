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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_cleanurls_webservertest');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('webservertest', 'local_cleanurls'));

echo '<a href="https://github.com/brendanheywood/moodle-local_cleanurls/blob/master/README.md" target="_blank">' .
     get_string('webservertesthelp', 'local_cleanurls') . '</a><br/><br/>';

echo $OUTPUT->heading(get_string('webservertestsummary', 'local_cleanurls'));

$tester = new \local_cleanurls\test\webserver\webserver_tester();
$tester->set_verbose(false);

ob_start();
$tester->test();
$debug = ob_get_contents();
ob_end_clean();

echo '<pre>'.htmlentities($debug).'</pre>';

echo $OUTPUT->heading(get_string('webservertestdebug', 'local_cleanurls'));

$tester = new \local_cleanurls\test\webserver\webserver_tester();
$tester->set_verbose(true);

ob_start();
$tester->test();
$debug = ob_get_contents();
ob_end_clean();

echo '<pre>'.htmlentities($debug).'</pre>';

echo $OUTPUT->footer();

