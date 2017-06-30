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
            [123, '123'],
            [['a' => 'b'], "array (\n  'a' => 'b',\n)"],
        ];
    }

    /** @dataProvider provider_for_test_it_makes_short_strings */
    public function test_it_makes_short_strings($input, $expected) {
        $actual = webtest::make_debug_string($input);
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
        $expected = 'Not the Same';
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
        $expected = "Not contains";
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
        $expected = "Not contains";
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

    public function test_it_gets_available_tests_mocked() {
        $expected = ['local_cleanurls\test\webserver\webtest_fake'];
        $actual = webtest_fake::get_available_tests();
        self::assertSame($expected, $actual);
    }

    public function test_it_runs_available_tests_mocked() {
        $actual = webtest_fake::run_available_tests();
        self::assertCount(1, $actual);
        self::assertInstanceOf(webtest_fake::class, $actual[0]);
    }

    public function test_it_adds_to_debugging_when_asserting_same_valid() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same('A', 'A', 'The Same');

        $expected = "\n*** PASSED: assert_same ***\n" .
                    "Expected:\n'A'\nActual:\n'A'\n\n";
        self::assertSame($expected, $webtest->get_debug());
    }

    public function test_it_adds_to_debugging_when_asserting_same_invalid() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same('A', 'B', 'Not The Same');

        $expected = "\n*** FAILED: assert_same ***\n" .
                    "Expected:\n'A'\nActual:\n'B'\n\n";
        self::assertSame($expected, $webtest->get_debug());
    }

    public function test_it_adds_to_debugging_when_asserting_contains_valid() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('Hello', 'Hello World!', 'Contains');

        $expected = "\n*** PASSED: assert_contains ***\n" .
                    "Needle:\n'Hello'\nHaystack:\n'Hello World!'\n\n";
        self::assertSame($expected, $webtest->get_debug());
    }

    public function test_it_test_it_adds_to_debugging_when_asserting_contains_invalid() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_contains('Universe', 'Hello World!', 'Contains');

        $expected = "\n*** FAILED: assert_contains ***\n" .
                    "Needle:\n'Universe'\nHaystack:\n'Hello World!'\n\n";
        self::assertSame($expected, $webtest->get_debug());
    }

    public function test_it_asserts_same_generates_errors_with_short_string() {
        $webtest = new webtest_fake();
        $webtest->run();
        $webtest->assert_same(['A'], ['B'], 'Not the Same');
        $expected = "\n*** FAILED: assert_same ***\n" .
                    "Expected:\narray (\n  0 => 'A',\n)\n" .
                    "Actual:\narray (\n  0 => 'B',\n)\n\n";
        self::assertSame($expected, $webtest->get_debug());
    }
}
