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

use local_cleanurls\test\webserver\webtest;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Class webserver_details_renderer
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webserver_details_renderer extends plugin_renderer_base {
    /** @var webtest */
    protected $test;

    public function set_result($result) {
        $this->test = $result;
    }

    public function render_page() {
        return $this->header() .
               $this->render_summary() .
               $this->render_description() .
               $this->render_errors() .
               $this->render_troubleshooting() .
               $this->render_debugging() .
               $this->footer();
    }

    private function render_summary() {
        return $this->heading($this->test->get_name()) .
               'Result: ' . webserver_summary_renderer::render_passed_or_fail($this->test) .
               '<br /><br />';
    }

    private function render_description() {
        return $this->heading('Description') .
               htmlentities($this->test->get_description()) .
               '<br /><br />';
    }

    private function render_errors() {
        $html = $this->heading('Problems');

        if ($this->test->has_passed()) {
            $html .= 'No problems found.<br /><br />';
        } else {
            $html .= '<ul>';
            foreach ($this->test->get_errors() as $error) {
                $html .= '<li>' . htmlentities($error) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private function render_troubleshooting() {
        $html = $this->heading('Troubleshooting');

        $html .= '<ul>';
        foreach ($this->test->get_troubleshooting() as $hint) {
            $html .= '<li>' . htmlentities($hint) . '</li>';
        }

        $html .= '<li><a href="https://github.com/brendanheywood/moodle-local_cleanurls/blob/master/README.md" target="_blank">' .
                 'Click here to view instructions on how to configure your webserver.</a></li>' .
                 '</ul>';

        return $html;
    }

    private function render_debugging() {
        $debug = $this->test->get_debug();

        $html = $this->heading('Debugging');
        if (empty($debug)) {
            $html .= 'No debugging information.';
        } else {
            $html .= '<pre>' . htmlentities($debug) . '</pre>';
        }
        $html .= '<br /><br />';

        return $html;
    }
}
