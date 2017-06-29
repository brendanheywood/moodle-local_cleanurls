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
 * CLI interface for webserver_tester class.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var $CFG    stdClass
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'dump'    => false,
        'help'    => false,
        'verbose' => false,
    ],
    [
        'd' => 'dump',
        'h' => 'help',
        'v' => 'verbose',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo <<<TXT
Tests if your webserver is read to use Clean URLs.

Options:
  -d, --dump     Shows the contents of fetched pages.
  -h, --help     Lists the available options.
  -v, --verbose  Shows debugging information.

TXT;
    return;
}

$tester = new \local_cleanurls\test\webserver\webserver_tester();
$tester->set_verbose($options['verbose']);
$tester->set_dump_contents($options['dump']);

$tester->test();
