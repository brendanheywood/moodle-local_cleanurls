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
use local_cleanurls\cache\cleanurls_cache;

function local_cleanurls_coursemodule_standard_elements(moodleform_mod $modform, MoodleQuickForm $form) {
    activity_path::coursemodule_standard_elements($modform, $form);
}

function local_cleanurls_coursemodule_validation(moodleform_mod $modform, array $data) {
    return activity_path::coursemodule_validation($modform, $data);
}

function local_cleanurls_pre_course_module_delete(stdClass $cm) {
    activity_path::pre_course_module_delete($cm->id);
}

function local_cleanurls_coursemodule_edit_post_actions(stdClass $moduleinfo, stdClass $course) {
    return activity_path::coursemodule_edit_post_actions($moduleinfo, $course);
}

function local_cleanurls_before_footer() {
    if (!cleanurls_cache::is_debugging()) {
        return;
    }

    $data = cleanurls_cache::get_outgoing_debug();
    $gets = array_sum($data);
    $count = count($data);

    ksort($data);
    $byurl = var_export($data, true);

    arsort($data);
    $bycount = var_export($data, true);

    echo <<<HTML
<!--

***** Clean URLs Debug Information *****

Cache Gets: $gets

URL Count: $count

***** Clean URLs Debug Information - Ordered by URL *****

$byurl

***** Clean URLs Debug Information - Ordered by Count *****

$bycount

***** End of Clean URLs Debug Information *****

-->
HTML;
}
