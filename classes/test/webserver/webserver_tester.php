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

    /** @var webtest[] */
    private $tests;

    public function __construct() {
        $this->tests = [
            new webtest_existing_file(),
            new webtest_directory_without_slash(),
            new webtest_directory_with_slash(),
            new webtest_invalid_path(),
            new webtest_selftest(),
            new webtest_no_parameters(),
            new webtest_simple_parameters(),
            new webtest_encoded_parameters(),
            new webtest_slash_arguments(),
            new webtest_configphp(),
        ];

        foreach ($this->tests as $test) {
            $test->set_tester($this);
        }
    }

    public function set_verbose($yn) {
        $this->isverbose = $yn;
    }

    public function set_dump_contents($yn) {
        $this->dumpcontentenabled = $yn;
    }

    public function verbose($message) {
        if ($this->isverbose) {
            printf("%s\n", $message);
        }
    }

    public function dump_contents($data) {
        if ($this->isverbose) {
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
    }

    public function test() {
        $this->verbose('Verbose Mode!');

        $this->passed = true;
        foreach ($this->tests as $test) {
            $this->verbose("\nRunning: " . get_class($test));
            $test->run();
            $test->print_result();
            $this->passed = $this->passed && $test->has_passed();
        }

        return $this->passed;
    }
}
