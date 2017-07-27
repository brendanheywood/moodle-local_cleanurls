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

use course_modinfo;
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

    public static function coursemodule_validation(moodleform_mod $modform, array $data) {
        global $CFG;

        $errors = [];
        $path = $data['cleanurls_path'];

        // Check if path is not a module name.
        if (file_exists($CFG->dirroot . '/mod/' . $path)) {
            return ['cleanurls_path' => get_string('invalid_path_modulename', 'local_cleanurls')];
        }

        $modinfo = get_fast_modinfo($modform->get_course());
        $found = self::coursemodule_validation_existing_subpath($path, $modform->get_coursemodule()->id, $modinfo);
        if (!is_null($found)) {
            $details = "{$found->name} (#{$found->id})";
            $error = get_string('invalid_path_alreadyused', 'local_cleanurls', $details);
            return ['cleanurls_path' => $error];
        }

        return $errors;
    }

    private static function coursemodule_validation_existing_subpath($path, $mycmid, course_modinfo $modinfo) {
        global $DB;

        $cms = $modinfo->cms;
        $mysection = $cms[$mycmid]->sectionnum;
        unset($cms[$mycmid]);

        $cmids = array_keys($cms);
        $found = $DB->get_records_list(self::PATHS_TABLE, 'cmid', $cmids);
        foreach ($found as $foundentry) {
            if ($foundentry->path != $path) {
                continue;
            }

            $foundcm = $cms[$foundentry->cmid];
            if ($mysection == $foundcm->sectionnum) {
                return $foundcm;
            }
        }

        return null;
    }
}
