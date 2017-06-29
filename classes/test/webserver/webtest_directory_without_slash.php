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
class webtest_directory_without_slash extends webtest {
    /**
     * @return string
     */
    public function get_name() {
        return 'Fetch an existing directory without slash';
    }

    /**
     * @return string
     */
    public function get_description() {
        return 'When fetching an directory without the trailing slash either: ' .
               '1) The webserver should redirect the request to have the address with slash; or ' .
               '2) The webserver should serve the request as if the trailing slash was provided.';
    }

    /**
     * @return string[]
     */
    public function get_troubleshooting() {
        return [
            'Ensure your webserver is properly configured.',
        ];
    }

    /**
     * @return void
     */
    public function run() {
        $this->errors = [];

        $url = 'local/cleanurls/tests/webserver';
        $data = $this->fetch($url);

        $this->assert_contains($data->code, [200, 301], 'HTTP Status');

        if ($data->code == 200) {
            $this->assert_same('[]', $data->body, 'HTTP Body');
        }

        if ($data->code == 301) {
            $this->assert_contains("\nLocation:", $data->header, 'HTTP Location Header');
            $this->assert_contains("{$url}/", $data->header, 'Redirected URL');
        }
    }
}
