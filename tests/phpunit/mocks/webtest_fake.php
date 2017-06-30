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
class webtest_fake extends webtest {
    public $fakename = 'This is a fake test name.';

    public $fakedescription = 'This is a fake test description.';

    public $faketroubleshooting = [
        'Ensure fake test works at first.',
        'Ensure fake test works again.',
    ];

    public $fakeerrors = [];

    /**
     * @return string
     */
    public function get_name() {
        return $this->fakename;
    }

    /**
     * @return string
     */
    public function get_description() {
        return $this->fakedescription;
    }

    /**
     * @return string[]
     */
    public function get_troubleshooting() {
        return $this->faketroubleshooting;
    }

    /**
     * @return void
     */
    public function run() {
        $this->errors = $this->fakeerrors;
    }

    protected function curl($url) {
        $url = explode('/', $url);
        $url = array_pop($url);
        list($code, $header, $body) = explode(':', $url);
        return (object)[
            'code'   => (int)$code,
            'header' => $header,
            'body'   => $body,
        ];
    }

    public function fetch($url) {
        // Just expose the protected method.
        return parent::fetch($url);
    }
}
