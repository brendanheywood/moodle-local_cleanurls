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
 * local_cleanurls plugin settings
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_cleanurls', get_string('pluginname', 'local_cleanurls'));
    $webservertesturl = new moodle_url('/local/cleanurls/webservertest.php');

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_cleanurls_webservertest',
        new lang_string('webservertest', 'local_cleanurls'),
        $webservertesturl
    ));

    $ADMIN->add('localplugins', $settings);
    if (!during_initial_install()) {

        $section = optional_param('section', '', PARAM_RAW);

        $test = '';

        // If we are on the settings page then also run a router test.
        if ($section == 'local_cleanurls') {
            $tester = new \local_cleanurls\webserver_tester();
            if ($tester->test()) {
                $test .= $OUTPUT->notification(get_string('routerok', 'local_cleanurls'), 'notifysuccess');
            } else {
                $test .= $OUTPUT->notification(get_string('routerbroken', 'local_cleanurls'), 'notifyerror');
            }
        }

        $rewritetest = false;
        $result = isset($CFG->urlrewriteclass)  && $CFG->urlrewriteclass == '\local_cleanurls\url_rewriter';

        // Now test that not only is the router configured but it is cleaning urls correctly. Note
        // that this particular test url is cleaned even if cleaning is off.
        if ($result) {
            $result = substr((new moodle_url('/local/cleanurls/tests/foo.php'))->out(false), -4) == '/bar';
            if ($result) {
                $test .= $OUTPUT->notification(get_string('rewriteok', 'local_cleanurls'), 'notifysuccess');
            } else {
                $test .= $OUTPUT->notification(get_string('rewritebroken', 'local_cleanurls'), 'notifyerror');
            }
        } else {
            $test .= $OUTPUT->notification(get_string('rewritenoconfig', 'local_cleanurls'), 'notifyerror');
        }

        $cleaningonhelp = new lang_string('cleaningonhelp', 'local_cleanurls') .
                          ' <a href="' . $webservertesturl . '">' .
                          new lang_string('cleaningonhelpdebug', 'local_cleanurls') .
                          '</a><br />' . $test;
        $settings->add(new admin_setting_configcheckbox('local_cleanurls/cleaningon',
                        new lang_string('cleaningon',         'local_cleanurls'),
                        $cleaningonhelp, 0));

        $settings->add(new admin_setting_configcheckbox('local_cleanurls/cleanusernames',
                        new lang_string('cleanusernames',     'local_cleanurls'),
                        new lang_string('cleanusernameshelp', 'local_cleanurls'), 0));

        $settings->add( new admin_setting_configcheckbox('local_cleanurls/debugging',
                        new lang_string('debugging',          'local_cleanurls'),
                        new lang_string('debugginghelp',      'local_cleanurls'), 0));
    }
}

