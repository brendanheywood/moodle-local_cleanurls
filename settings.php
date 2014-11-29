<?php
/* This file is part of Moodle - http://moodle.org/
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
 * @subpackage clean_urls
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_clean_urls', get_string('pluginname', 'local_clean_urls'));

    $ADMIN->add('localplugins', $settings);
    if (!during_initial_install()) {
        $settings->add(new admin_setting_configcheckbox('local_clean_urls/cleaningon',  new lang_string('cleaningon', 'local_clean_urls'),
                                                                       new lang_string('cleaningonhelp', 'local_clean_urls'), 0));
    }
}

