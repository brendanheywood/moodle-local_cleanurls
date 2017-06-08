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
 * Tests for CleanURLS URL Parser.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/testparser.php');
require_once(__DIR__ . '/../../cleanurls_testcase.php');

/**
 * Tests for urlparser.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_urlparser_test extends local_cleanurls_testcase {
    public function test_it_exists() {
        self::assertTrue(class_exists('\local_cleanurls\local\uncleaner\uncleaner'));
    }

    public function test_it_takes_a_parent_parser() {
        $root = new root_uncleaner('/');
        $parser = new local_cleanurls_testparser($root);
        $parent = $parser->get_parent();
        self::assertSame($root, $parent);
    }

    public function test_it_takes_null_as_parent_parser() {
        $parser = new local_cleanurls_testparser(null);
        $parent = $parser->get_parent();
        self::assertNull($parent);
    }

    public function test_it_may_have_a_child() {
        $parent = new local_cleanurls_testparser(
            null, 'test_it_may_have_a_child', 'parent');

        self::assertSame('http://www.example.com/moodle/test_it_may_have_a_child/child',
                         $parent->get_child()->get_unclean_url()->raw_out());
    }

    public function provider_for_test_it_does_not_take_wrong_types() {
        return [[1], ['string'], [['array']], [(object)['type' => 'objects']]];
    }

    /**
     * @dataProvider provider_for_test_it_does_not_take_wrong_types
     */
    public function test_it_does_not_take_wrong_types($input) {
        $this->expectException(invalid_parameter_exception::class);
        new local_cleanurls_testparser($input);
    }

    public function test_it_consumes_one_subpath() {
        $root = new root_uncleaner('/hello/world');
        $parser = new local_cleanurls_testparser($root);
        self::assertSame(['world'], $parser->get_subpath());
    }

    public function test_it_has_its_path() {
        $root = new root_uncleaner('/hello/world');
        $parser = new local_cleanurls_testparser($root);
        self::assertSame('hello', $parser->get_mypath());
    }

    public function test_it_inherits_parameters() {
        $root = new root_uncleaner('/hello/world?a=b&c=d');
        $parser = new local_cleanurls_testparser($root);
        self::assertSame(['a' => 'b', 'c' => 'd'], $parser->get_parameters());
    }

    public function test_it_lists_child_options() {
        $options = uncleaner::list_child_options();
        self::assertSame([], $options);
    }

    public function test_cannot_create_child_if_option_not_available() {
        local_cleanurls_testparser::$childoptions = [
            root_uncleaner::class,
        ];
        $test = new local_cleanurls_testparser();
        self::assertNull($test->get_child());
    }

    public function test_creates_child_if_option_available() {
        local_cleanurls_testparser::$childoptions = [
            local_cleanurls_testparser::class,
        ];
        $test = new local_cleanurls_testparser();
        self::assertInstanceOf(local_cleanurls_testparser::class, $test->get_child());
    }
}