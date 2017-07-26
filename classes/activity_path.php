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
 * @package     local/cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

use moodleform_mod;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local/cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_path {
    const ACTIVITY_PATH_FIELD = 'cleanurls_path';
    const PATHS_TABLE = 'local_cleanurls_cmpaths';

    public static function coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form) {
        $form->addElement('header',
                          'localcleanurls',
                          get_string('path_header', 'local_cleanurls'));

        $form->addElement('text',
                          self::ACTIVITY_PATH_FIELD,
                          get_string('path_setting', 'local_cleanurls'));

        $form->setType(self::ACTIVITY_PATH_FIELD, PARAM_PATH);

        $form->addHelpButton(self::ACTIVITY_PATH_FIELD,
                             'path_setting',
                             'local_cleanurls');

        $default = '';
        if ($modform->get_coursemodule()) {
            $default = self::get_path_for_cmid($modform->get_coursemodule()->id);
        }

        $form->setDefault(self::ACTIVITY_PATH_FIELD, $default);
    }

    public static function coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course) {
        $field = self::ACTIVITY_PATH_FIELD;

        $path = isset($moduleinfo->$field) ? $moduleinfo->$field : '';
        self::save_path_for_cmid($moduleinfo->coursemodule, $path);

        return $moduleinfo;
    }

    public static function pre_course_module_delete($cmid) {
        self::save_path_for_cmid($cmid, '');
    }

    public static function get_path_for_cmid($cmid) {
        global $DB;

        $path = $DB->get_field(self::PATHS_TABLE, 'path', ['cmid' => $cmid]);

        return $path ?: '';
    }

    public static function save_path_for_cmid($cmid, $path) {
        global $DB;

        $path = trim($path, '/');

        $DB->delete_records(self::PATHS_TABLE, ['cmid' => $cmid]);

        if (!empty($path)) {
            $DB->insert_record(self::PATHS_TABLE, (object)['cmid' => $cmid, 'path' => $path]);
        }
    }

    private function __construct() {
    }
}
