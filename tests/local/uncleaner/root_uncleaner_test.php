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
 * CleanURLS URL Parser for: / (root).
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../cleanurls_testcase.php');

/**
 * Tests for flexsections_support.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_urlparser_root_test extends local_cleanurls_testcase {
    public function test_it_exists() {
        self::assertTrue(class_exists('\local_cleanurls\local\uncleaner\root_uncleaner'));
    }

    public function test_it_is_a_parser() {
        $root = new root_uncleaner('/');
        self::assertInstanceOf(uncleaner::class, $root);
    }

    public function test_it_takes_a_url_as_string() {
        $root = new root_uncleaner('/');
        self::assertSame('http://www.example.com/moodle/', $root->get_original_raw_url());
    }

    public function test_it_gives_the_clean_url() {
        $root = new root_uncleaner('/');
        $clean = $root->get_clean_url();
        self::assertSame('http://www.example.com/moodle/', $clean->out());
    }

    public function provider_for_test_it_takes_only_strings() {
        return [
            [1],
            [['array']],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider provider_for_test_it_takes_only_strings
     */
    public function test_it_takes_only_strings_or_moodle_urls($input) {
        $this->expectException(invalid_parameter_exception::class);
        new root_uncleaner($input);
    }

    public function test_it_does_not_have_a_parent() {
        $root = new root_uncleaner('/');
        self::assertNull($root->get_parent());
    }

    public function test_it_extracts_the_moodle_path() {
        $root = new root_uncleaner('/abc/def');
        self::assertSame('/moodle', $root->get_moodle_path());
    }

    public function provider_for_test_it_has_a_subpath() {
        return [
            ['', []],
            ['/', []],
            ['/abc', ['abc']],
            ['/abc/def', ['abc', 'def']],
            ['/abc/def/ghi', ['abc', 'def', 'ghi']],
        ];
    }

    /**
     * @dataProvider provider_for_test_it_has_a_subpath
     */
    public function test_it_has_a_subpath($url, $expected) {
        $root = new root_uncleaner($url);
        self::assertSame($expected, $root->get_subpath(), "URL: {$url}");
    }

    public function provider_for_test_it_has_parameters() {
        return [
            ['?a=b', ['a' => 'b']],
            ['/?c=d', ['c' => 'd']],
            ['/abc?e=f', ['e' => 'f']],
            ['/abc/def?g=h', ['g' => 'h']],
            ['/hello?a=b&c=d', ['a' => 'b', 'c' => 'd']],
            ['/hello?arr=abc%5B%5D', ['arr' => 'abc[]']],
        ];
    }

    /**
     * @dataProvider provider_for_test_it_has_parameters
     */
    public function test_it_has_parameters($url, $expected) {
        $root = new root_uncleaner($url);
        self::assertSame($expected, $root->get_parameters(), "URL: {$url}");
    }

    public function test_it_extracts_the_moodle_path_is_blank_if_root() {
        global $CFG;

        $CFG->wwwroot = 'http://moodle.test';
        $root = new root_uncleaner('/abc/def');
        self::assertSame('', $root->get_moodle_path());
    }

    public function test_it_has_its_path_empty() {
        $root = new root_uncleaner('/hello/world');
        self::assertSame('', $root->get_mypath());
    }
}
