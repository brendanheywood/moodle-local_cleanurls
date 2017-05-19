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

namespace local_cleanurls;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class
 *
 * @package     local_cleanurls
 * @subpackage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webserver_tester {
    /** @var bool */
    private $isverbose = false;

    /** @var bool */
    private $passed = true;

    /** @var bool */
    private $dumpcontentenabled = false;

    public function set_verbose($yn) {
        $this->isverbose = $yn;
    }

    public function enable_dump_content($yn) {
        $this->dumpcontentenabled = $yn;
    }

    private function verbose($message) {
        if ($this->isverbose) {
            printf("%s\n", $message);
        }
    }

    private function curl($url) {
        $data = new stdClass();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($curl);
        $data->code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $data->header = trim(substr($response, 0, $header_size));
        $data->body = trim(substr($response, $header_size));

        return $data;
    }

    private function show_result($name, $result) {
        printf("%-60s: %s\n", $name, $result ? 'PASSED' : 'FAILED');
    }

    private function fetch($url) {
        global $CFG;

        $url = $CFG->wwwroot . '/' . $url;

        $this->verbose('GET: ' . $url);
        $data = $this->curl($url);

        if ($this->dumpcontentenabled) {
            if ($data->code == 0) {
                printf("*** DATA DUMP: Error fetching URL!\n");
            } else {
                printf("*** DATA DUMP: Header ***\n");
                echo $data->header . "\n";
                printf("*** DATA DUMP: Body ***\n");
                echo $data->body . "\n";
                printf("*** DATA DUMP: End ***\n");
            }
        }

        return $data;
    }

    public function test() {
        $this->verbose('Verbose Mode!');
        $this->test_existing_file();
        $this->test_existing_directory_without_slash();
        $this->test_existing_directory_with_slash();
        $this->test_invalid_path_not_found();
        $this->test_cleanurls_selftest();
        $this->test_cleanurls_rewrite_no_parameters();
        $this->test_cleanurls_rewrite_simple_parameters();
        $this->test_cleanurls_rewrite_encoded_parameters();
        return $this->passed;
    }

    private function test_existing_file() {
        $data = $this->fetch('local/cleanurls/tests/webserver/index.php');
        $result = ($data->code == 200) && ($data->body == '[]');
        $this->show_result('Fetch an existing file', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_existing_directory_without_slash() {
        $url = 'local/cleanurls/tests/webserver';
        $data = $this->fetch($url);

        $gotcontents = ($data->code == 200) && ($data->body == '[]');
        $redirected = ($data->code == 301)
                      && (strpos($data->header, "\nLocation:") !== false)
                      && (strpos($data->header, $url . '/') !== false);
        $result = $gotcontents || $redirected;

        $this->show_result('Fetch an existing directory without slash', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_existing_directory_with_slash() {
        $data = $this->fetch('local/cleanurls/tests/webserver/');
        $result = ($data->code == 200) && ($data->body == '[]');
        $this->show_result('Fetch an existing directory with slash', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_invalid_path_not_found() {
        $data = $this->fetch('local/cleanurls/givemea404error');
        $result = ($data->code == 404);
        $this->show_result('Fetch an invalid path', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_cleanurls_selftest() {
        $data = $this->fetch('local/cleanurls/tests/bar');
        //$data = $this->fetch('local/cleanurls/tests/webcheck');
        $result = ($data->code == 200) && ($data->body == 'OK');
        $this->show_result('Fetch Clean URLs self test', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_cleanurls_rewrite_no_parameters() {
        $data = $this->fetch('local/cleanurls/tests/webcheck');
        $expected = '{"q":"\/local\/cleanurls\/tests\/webcheck"}';
        $result = ($data->code == 200) && ($data->body == $expected);
        $this->show_result('Test rewrite without parameters', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_cleanurls_rewrite_simple_parameters() {
        $data = $this->fetch('local/cleanurls/tests/webcheck?param=simple');
        $expected = '{"q":"\/local\/cleanurls\/tests\/webcheck","param":"simple"}';
        $result = ($data->code == 200) && ($data->body == $expected);
        $this->show_result('Test rewrite without a simple parameter', $result);
        $this->passed = $this->passed && $result;
    }

    private function test_cleanurls_rewrite_encoded_parameters() {
        $data = $this->fetch('local/cleanurls/tests/webcheck?xyz=x%20%26%20y%2Fz');
        $expected = '{"q":"\/local\/cleanurls\/tests\/webcheck","xyz":"x & y\/z"}';
        $result = ($data->code == 200) && ($data->body == $expected);
        $this->show_result('Test rewrite with \'x & y/z\' as a parameter value', $result);
        $this->passed = $this->passed && $result;
    }
}
