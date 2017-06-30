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

namespace local_cleanurls\output;

use html_table;
use html_writer;
use local_cleanurls\test\webserver\webtest;
use plugin_renderer_base;
use ReflectionClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class webserver_summary_renderer
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webserver_summary_renderer extends plugin_renderer_base {
    /** @var webtest[] */
    protected $tests = [];

    public function set_results($results) {
        $this->tests = $results;
    }

    public function render_page() {
        return $this->header() .
               $this->render_tests() .
               $this->footer();
    }

    public function render_tests() {
        return $this->heading(get_string('webservertest', 'local_cleanurls')) .
               $this->render_tests_table();
    }

    public function render_tests_table() {
        $table = new html_table();

        $table->head = [
            get_string('webservertest_test', 'local_cleanurls'),
            get_string('webservertest_result', 'local_cleanurls'),
            get_string('webservertest_description', 'local_cleanurls'),
        ];
        $table->attributes = ['class' => 'admintable generaltable'];
        $table->id = 'cleanurls_webservertest_table';

        $table->data = [];
        foreach ($this->tests as $webtest) {
            $classname = (new ReflectionClass($webtest))->getShortName();
            $table->data[] = [
                html_writer::link("/local/cleanurls/webservertest.php?details={$classname}", $webtest->get_name()),
                self::render_passed_or_fail($webtest),
                $webtest->get_description(),
            ];
        }

        return html_writer::table($table);
    }

    protected function render_passed_or_fail(webtest $webtest) {
        $message = 'webservertest_' . ($webtest->has_passed() ? 'passed' : 'failed');
        $message = get_string($message, 'local_cleanurls');

        $class = 'status' . ($webtest->has_passed() ? 'ok' : 'critical');
        return '<span class="' . $class . '">' . $message . '</span>';
    }
}
