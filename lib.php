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

defined('MOODLE_INTERNAL') || die();

use local_cleanurls\activity_path;

function local_cleanurls_coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form) {
    activity_path::coursemodule_standard_elements($modform, $form);
}

function local_cleanurls_pre_course_module_delete(stdClass $cm) {
    activity_path::pre_course_module_delete($cm->id);
}

function local_cleanurls_coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course) {
    return activity_path::coursemodule_edit_post_actions($moduleinfo, $course);
}
