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

namespace local_cleanurls\test\webserver;

defined('MOODLE_INTERNAL') || die();

/**
 * Webtest.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webtest_configphp extends webtest {
    /**
     * @return string
     */
    public function get_name() {
        return 'Test the config.php is properly set';
    }

    /**
     * @return string
     */
    public function get_description() {
        return "Moodle's config.php requires a few changes as described in our README.md file.";
    }

    /**
     * @return string[]
     */
    public function get_troubleshooting() {
        return [
            'Ensure config.php does not override the existing $CFG at the top of the file.',
            'Ensure config.php defines the \'$CFG->urlrewriteclass\' as recommended.',
        ];
    }

    /**
     * @return void
     */
    public function run() {
        $this->errors = [];

        $data = $this->fetch('local/cleanurls/tests/webserver/configphp.php');

        $this->assert_same(200, $data->code, 'HTTP Status');
        $this->assert_contains('$CFG OK', $data->body, 'Object $CFG lost its previous values.');
        $this->assert_contains('$CFG->urlrewriteclass OK', $data->body, 'Configuration $CFG->urlrewriteclass not defined.');
    }
}
