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
 * @package    local
 * @subpackage clean_urls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function local_clean_urls_clean($url){

    global $DB, $CFG;

    // only clean urls if internal to this moodle
    $base = $CFG->wwwroot . '/';
    $basel = strlen($base);

    if (strrpos ($url , $base) !== 0){
        return $url;
    }

    // If not php then ignore it quickly
    if (strrpos($url, ".php") === false ){
        return $url;
    }

    // Ignore any theme files
    if ( substr($url,$basel,5) == 'theme'){
        return $url;
    }

    // Remove .php extension
#    $url = preg_replace ("/\.php/", '', $url,1);
    // Note this is fairly dangerous when there a file which has the same name as a directory
    // eg /settings.php and /settings/
    // normal apache settings redirect this, or serve it directly
    // so we check if a dir of the same name exists

    // Remove index

    // TODO convert this to proper url parsing instead of regex
    if (preg_match ("/^(.*)\/course\/view.php\?id=(\d+)(.*)/", $url, $matches)){
        $shortname = $DB->get_field('course', 'shortname', array('id' => $matches[2]));
        return $matches[1] . "/course/" . $shortname;
    }


    return $url;

}

function local_clean_urls_unclean($url, $includebase = 1){

    global $CFG;
    $base = $CFG->wwwroot . '/';
    $basel = strlen($base);

    $debug = get_config('local_clean_urls', 'debugging');
    $debug && error_log("Incoming url: $url");

    if(!$includebase){
        $base = '';
    }

    if (preg_match("/^course\/(.+)$/", $url, $matches)){
        $url = "course/view.php?name=".$matches[1];
        $debug && error_log("Rewritten to: $url");
        return $base . $url;
    }

    $debug && error_log("Rewrtten to: $url");
    return $base . $url;

    // Add back in php extension
    // $qp = strpos ($url, "?");
    // if (!$qp){
    //     $qp = strpos ($url, "#");
    // }
    // if ($qp){
    //     $url = substr($url,0,$qp) . '.php' . substr($url,$qp);
    // } else {
    //     $url = $url . '.php';
    // }

    return $base . $url;

}

