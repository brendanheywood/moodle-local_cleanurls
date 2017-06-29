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
class webtest_slash_arguments extends webtest {
    /**
     * @return string
     */
    public function get_name() {
        return 'Test slash arguments are not affected';
    }

    /**
     * @return string
     */
    public function get_description() {
        return 'Slash arguments, which look like "file.php/a/b/c" should work properly without going through Clean URLs.';
    }

    /**
     * @return string[]
     */
    public function get_troubleshooting() {
        return [
            'Check your webserver configuration, this requests should be handled by the webserver without using Clean URLs.',
        ];
    }

    /**
     * @return void
     */
    public function run() {
        $this->errors = [];

        $data = $this->fetch('local/cleanurls/tests/webserver/slash_arguments.php/abc/def/xyz');

        $this->assert_same(200, $data->code, 'HTTP Status');
        $this->assert_same('/abc/def/xyz', $data->body, 'HTTP Body');
    }
}
