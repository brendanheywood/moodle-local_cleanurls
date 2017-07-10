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

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class testbase
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class webtest {
    /**
     * @param $var mixed
     * @return string
     */
    public static function make_debug_string($var) {
        return var_export($var, true);
    }

    /**
     * @return string[]
     */
    public static function get_available_tests() {
        return [
            webtest_existing_file::class,
            webtest_directory_without_slash::class,
            webtest_directory_with_slash::class,
            webtest_invalid_path::class,
            webtest_selftest::class,
            webtest_no_parameters::class,
            webtest_simple_parameters::class,
            webtest_encoded_parameters::class,
            webtest_slash_arguments::class,
            webtest_configphp::class,
        ];
    }

    /**
     * @return webtest[]
     */
    public static function run_available_tests() {
        $tests = static::get_available_tests();
        $results = [];
        foreach ($tests as $test) {
            $test = new $test();
            $test->run();
            $results[] = $test;
        }
        return $results;
    }

    /**
     * @return bool
     */
    public static function check_available_tests_pass() {
        $tests = static::get_available_tests();

        foreach ($tests as $test) {
            $test = new $test();
            $test->run();
            if (!$test->has_passed()) {
                return false;
            }
        }

        return true;
    }

    /** @var string */
    protected $debug = '';

    public function get_debug() {
        return $this->debug;
    }

    /** @var string[] */
    protected $errors = ['Test has not been executed yet.'];

    public function has_passed() {
        return (count($this->errors) == 0);
    }

    public function get_errors() {
        return $this->errors;
    }

    /**
     * @return string
     */
    public abstract function get_name();

    /**
     * @return string
     */
    public abstract function get_description();

    /**
     * @return string[]
     */
    public abstract function get_troubleshooting();

    /**
     * @return void
     */
    public abstract function run();

    protected function curl($url) {
        $data = new stdClass();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        $response = curl_exec($curl);
        $data->code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headersize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $data->header = trim(substr($response, 0, $headersize));
        $data->body = trim(substr($response, $headersize));

        return $data;
    }

    protected function fetch($url) {
        global $CFG;

        $url = $CFG->wwwroot . '/' . $url;

        $this->debug .= "Fetching: {$url}\n";
        $data = $this->curl($url);

        if ($data->code == 0) {
            $this->debug .= "*** DATA DUMP: Error fetching URL!\n";
        } else {
            $this->debug .= "*** DATA DUMP: Header ***\n{$data->header}\n";
            $this->debug .= "*** DATA DUMP: Body ***\n{$data->body}\n";
            $this->debug .= "*** DATA DUMP: End ***\n";
        }

        return $data;
    }

    /**
     * @param $expected mixed
     * @param $actual   mixed
     * @param $message  string
     * @return void
     */
    public function assert_same($expected, $actual, $message) {
        $passed = ($expected === $actual);

        $this->debug .= "\n*** " . ($passed ? 'PASSED' : 'FAILED') . ": assert_same ***\n" .
                        "Expected:\n" . self::make_debug_string($expected) . "\n" .
                        "Actual:\n" . self::make_debug_string($actual) . "\n\n";

        if (!$passed) {
            $this->errors[] = $message;
        }
    }

    /**
     * @param $needle     mixed
     * @param $haystack   mixed
     * @param $message    string
     * @return void
     */
    public function assert_contains($needle, $haystack, $message) {
        if (!is_array($haystack) && !is_string($haystack)) {
            $this->errors[] = '*** Not implemented assert_contains for this data type.';
            return;
        }

        $found = false
                 || (is_array($haystack) && in_array($needle, $haystack))
                 || (is_string($haystack) && (strpos($haystack, $needle) !== false));

        $this->debug .= "\n*** " . ($found ? 'PASSED' : 'FAILED') . ": assert_contains ***\n" .
                        "Needle:\n" . self::make_debug_string($needle) . "\n" .
                        "Haystack:\n" . self::make_debug_string($haystack) . "\n\n";

        if (!$found) {
            $this->errors[] = $message;
        }
    }
}
