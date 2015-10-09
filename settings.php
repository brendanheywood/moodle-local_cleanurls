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
 *  cqu_staff_sync local plugin settings
 *
 * @package    local
 * @subpackage cleanurls
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_cleanurls', get_string('pluginname', 'local_cleanurls'));

    $ADMIN->add('localplugins', $settings);
    if (!during_initial_install()) {

        $section = optional_param('section', '', PARAM_RAW);

        $routertest = '';

        // If we are on the settings page then also run a router test.
        if ($section == 'local_cleanurls') {
            $result = @file_get_contents($CFG->wwwroot . '/local/cleanurls/tests/file');
            if ($result == 'OK') {
                $routertest = $OUTPUT->notification(get_string('routerok', 'local_cleanurls'), 'notifysuccess');
            } else {
                $routertest = $OUTPUT->notification(get_string('routerbroken', 'local_cleanurls'), 'notifyfailure');
            }
        }

        $settings->add(new admin_setting_configcheckbox('local_cleanurls/cleaningon',
                        new lang_string('cleaningon',       'local_cleanurls'),
                        new lang_string('cleaningonhelp',   'local_cleanurls') . $routertest, 0));

        $settings->add( new admin_setting_configcheckbox('local_cleanurls/debugging',
                        new lang_string('debugging',        'local_cleanurls'),
                        new lang_string('debugginghelp',    'local_cleanurls'), 0));
    }
}

