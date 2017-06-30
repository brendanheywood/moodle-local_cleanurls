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
 * Testcase for Clean URLs.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\test\webserver\webtest;
use local_cleanurls\test\webserver\webtest_fake;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../mocks/webtest_fake.php');

/**
 * Testcase for clean_moodle_url class.
 *
 * @package    local_cleanurls
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_webtest_test extends advanced_testcase {
    public function provider_for_test_it_makes_short_strings() {

        return [
            ['', "''"],
            ['abc', "'abc'"],
            [
                'This is a very long string that is on the limit of characters expected, it will not be cropped ok?',
                "'This is a very long string that is on the limit of characters expected, it will not be cropped ok?'",
            ],
            [
                'This is a very long string that is on the limit of characters expected, so it will be cropped okay?',
                "'This is a very long string that is on the limit of characters expected, so it will be cropped ok...",
            ],
            ["All\nblank\tcharacters\rshould become blanks\n\n!", "'All blank characters should become blanks  !'"],
            [
                'Anything 😰non 😕 basic 👍 🚩 ASCII 😃 should become❤question ? marks',
                "'Anything ????non ???? basic ???? ???? ASCII ???? should become???question ? marks'",
            ],
            [123, '123'],
            [['a' => 'b'], "array (   'a' => 'b', )"],
        ];
    }

    /** @dataProvider provider_for_test_it_makes_short_strings */
    public function test_it_makes_short_strings($input, $expected) {
        $actual = webtest::make_short_string($input);
        self::assertSame($expected, $actual);
    }

    public function test_it_has_all_tests() {
        $expected = [];
        $dir = opendir(__DIR__ . '/../../../classes/test/webserver');
        while (false !== ($file = readdir($dir))) {
            if ('webtest_' == substr($file, 0, 8)) {
                $expected[] = 'local_cleanurls\\test\\webserver\\' . substr($file, 0, -4);
            }
        }
        closedir($dir);
        sort($expected);

        $actual = webtest::get_available_tests();
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test_it_fails_if_not_executed() {
        $webtest = new webtest_fake();
        self::assertFalse($webtest->has_passed());
    }

    public function test_it_fails_if_has_errors() {
        $webtest = new webtest_fake();
        $webtest->fakeerrors = ['error'];
        $webtest->run();
        self::assertFalse($webtest->has_passed());
    }

    public function test_it_passes_if_no_errors() {
        $webtest = new webtest_fake();
        $webtest->run();
        self::assertTrue($webtest->has_passed());
    }

    public function test_it_provides_a_name() {
        $webtest = new webtest_fake();
        $webtest->fakename = 'My Name';
        self::assertSame('My Name', $webtest->get_name());
    }

    public function test_it_provides_a_description() {
        $webtest = new webtest_fake();
        $webtest->fakedescription = 'My Description';
        self::assertSame('My Description', $webtest->get_description());
    }

    public function test_it_provides_a_troubleshooting() {
        $webtest = new webtest_fake();
        $webtest->faketroubleshooting = ['My Solutions'];
        self::assertSame(['My Solutions'], $webtest->get_troubleshooting());
    }

    public function test_it_gets_the_errors() {
        $webtest = new webtest_fake();
        $webtest->fakeerrors = ['Abc'];
        $webtest->run();
        self::assertSame(['Abc'], $webtest->get_errors());
    }

    public function test_it_asserts_same_generates_no_errors() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same('A', 'A', 'The Same');
        self::assertSame([], $webtest->get_errors());
    }

    public function test_it_asserts_same_generates_errors() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same('A', 'B', 'Not the Same');
        $expected = "Failed: Not the Same\nExpected: 'A'\nFound: 'B'";
        self::assertSame([$expected], $webtest->get_errors());
    }

    public function test_it_asserts_same_generates_errors_with_short_string() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same(['A'], ['B'], 'Not the Same');
        $expected = "Failed: Not the Same\nExpected: array (   0 => 'A', )\nFound: array (   0 => 'B', )";
        self::assertSame([$expected], $webtest->get_errors());
    }

    public function test_it_assert_contains_tests_found_in_string() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('world', 'Hello world!', 'Contains');
        self::assertSame([], $webtest->get_errors());
    }

    public function test_it_assert_contains_tests_not_found_in_string() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('universe', 'Hello world!', 'Not contains');
        $expected = "Failed: Not contains\nNeedle: 'universe'\nHaystack: 'Hello world!'";
        self::assertSame([$expected], $webtest->get_errors());
    }

    public function test_it_assert_contains_tests_found_in_array() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('world', ['hello', 'world'], 'Contains');
        self::assertSame([], $webtest->get_errors());
    }

    public function test_it_assert_contains_tests_not_found_in_array() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('universe', ['hello', 'world'], 'Not contains');
        $expected = "Failed: Not contains\nNeedle: 'universe'\nHaystack: array (   0 => 'hello',   1 => 'world', )";
        self::assertSame([$expected], $webtest->get_errors());
    }

    public function test_it_assert_contains_rejects_unknown_types() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('a class', new stdClass(), 'Invalid');
        $expected = "*** Not implemented assert_contains for this data type.";
        self::assertSame([$expected], $webtest->get_errors());
    }

    public function test_it_fetches_a_url() {
        $webtest = new webtest_fake();
        $data = $webtest->fetch('200:HEADER:BODY');
        self::assertSame(['code' => 200, 'header' => 'HEADER', 'body' => 'BODY'], (array)$data);
    }

    public function test_it_adds_debug_code_when_fetching_url() {
        $webtest = new webtest_fake();
        $webtest->fetch('500:HEADER:BODY');
        $actual = $webtest->get_debug();
        $expected = "Fetching: https://www.example.com/moodle/500:HEADER:BODY\n" .
                    "*** DATA DUMP: Header ***\n" .
                    "HEADER\n" .
                    "*** DATA DUMP: Body ***\n" .
                    "BODY\n" .
                    "*** DATA DUMP: End ***\n";
        self::assertSame($expected, $actual);
    }
}
