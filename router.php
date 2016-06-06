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
 * An apache rewrite handler for all incoming urls which doesn't match an existing file or directory
 *
 * <Directory /var/www/example.com>
 *   RewriteEngine on
 *   RewriteBase /
 *   RewriteCond %{REQUEST_FILENAME} !-f
 *   RewriteCond %{REQUEST_FILENAME} !-d
 *   RewriteRule ^(.*)$ local/cleanurls/router.php?q=$1 [L,QSA]
 * </Directory>
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

global $CFG;

$path = required_param('q', PARAM_PATH); // TODO should use PARAM_SAFEPATH instead?
local_cleanurls\clean_moodle_url::log("Router: \$_GET: ".$path);
$url = local_cleanurls\clean_moodle_url::unclean($CFG->wwwroot . '/' . $path);

foreach ($url->params() as $k => $v) {
    $_GET[$k] = $v;
}

$file = $CFG->dirroot . $url->get_path();

local_cleanurls\clean_moodle_url::log("Router: including file: ".$file);
if (!is_file($file)) {

    \local_cleanurls\clean_moodle_url::log("Router: Not found, showing 404 instead: ".$file);
    header("HTTP/1.0 404 Not Found");
    $PAGE->set_url($url->get_path());
    $PAGE->set_context(context_course::instance(SITEID));
    $notfound = get_string('filenotfound', 'error');
    $PAGE->set_title($notfound);
    $PAGE->set_heading($notfound);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    die;
    // TODO change core /error/index.php so we can forward to it.
}

chdir(dirname($file));
$CFG->uncleanedurl = $url->raw_out(false);

// If we require any file which defines NO_MOODLE_COOKIES then it will complain
// so perhaps safer to black list these. May be similar issue with other defines.
require($file);

