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
 * @var $CFG      stdClass
 * @var $PAGE     moodle_page
 * @var $OUTPUT
 */

use local_cleanurls\form\analyser_form;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_cleanurls_analyser');

$url = null;

$form = new analyser_form();
if ($form->is_cancelled()) {
    redirect($PAGE->url);
}
$formdata = $form->get_data();

if (!is_null($formdata)) {
    $url = new moodle_url($formdata->url);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('analyser', 'local_cleanurls'), 1);
$form->display();

if (!is_null($url)) {
    echo $OUTPUT->heading(get_string('analyser_results', 'local_cleanurls'), 2);
    echo 'Results for: ' . $url->raw_out(true);
}

echo $OUTPUT->footer();
