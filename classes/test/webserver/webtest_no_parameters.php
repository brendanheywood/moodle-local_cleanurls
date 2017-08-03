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
class webtest_no_parameters extends webtest {
    /**
     * @return string
     */
    public function get_name() {
        return 'Test rewrite without parameters';
    }

    /**
     * @return string
     */
    public function get_description() {
        return 'Clean URLs should work for any URL when no parameters in the query string are supplied.';
    }

    /**
     * @return string[]
     */
    public function get_troubleshooting() {
        return [
            'Check your webserver configuration.',
        ];
    }

    /**
     * @return void
     */
    public function run() {
        $this->errors = [];

        $data = $this->fetch('local/cleanurls/tests/webcheck');
        $this->assert_same(200, $data->code, 'HTTP Status');
        $this->assert_same('[]', $data->body, 'HTTP Body');
    }
}
