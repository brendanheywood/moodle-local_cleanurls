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

use local_cleanurls\cache\cleanurls_cache;
use local_cleanurls\form\analyser_form;
use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\url_history;

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
    echo '<pre>';

    $notfound = get_string("analyser_notfound", 'local_cleanurls');

    $cached = cleanurls_cache::get_unclean_from_clean($url);
    if (is_null($cached)) {
        if (cleanurls_cache::is_disabled_at_config()) {
            $cached = get_string('analyser_cachedisabled', 'local_cleanurls');
        } else {
            $cached = $notfound;
        }
        $cached = "<i>{$cached}</i>";
    } else {
        $cached = $cached->raw_out(true);
    }
    echo "<b> Cached:</b> {$cached}\n";

    $history = url_history::get($url);
    $history = is_null($history) ? "<i>{$notfound}</i>" : htmlentities($history);
    echo "<b>History:</b> {$history}\n";

    echo "\n";
    $root = new root_uncleaner($url);
    $debug = $root->debug_path();
    foreach ($debug as $part) {
        $class = new ReflectionClass($part['class']);
        printf("%25s: %-25s &rarr; %s\n",
               $class->getShortName(),
               htmlentities($part['mypath']),
               $part['uncleaner']->get_unclean_url()->raw_out(true)
        );
    }
    $last = $root;
    while (!is_null($last->get_child())) {
        $last = $last->get_child();
    }
    if (!empty($last->get_subpath())) {
        printf("%25s: %-25s\n",
               '[uncleaned]',
               htmlentities(implode('/', $last->get_subpath()))
        );
    }

    echo '</pre>';
}

echo $OUTPUT->footer();
