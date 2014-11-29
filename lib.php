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
function local_clear_urls_clean($url){

    global $DB;

    // If not php then ignore it quickly
    if (strrpos($url, ".php") === false ){
        return $url;
    }


    // Remove .php

    // Remove index

    //

    // More aggresive information adding url's

    if (preg_match ("/^(.*)\/course\/view\.php\?id=(\d+)(.*)/", $url, $matches)){
        #    error_log("In  uri: $url");

        $shortname = $DB->get_field('course', 'shortname', array('id' => $matches[2]));

        // $url = $matches[1] . "/course/" . $matches[2];
        $url = $matches[1] . "/course/" . $shortname;
        #e($matches);
        #    error_log("Out course uri: $url");
        # exit;
        return $url;
    }


#    $url .= "&foo=bar";
#    error_log("Out uri: $url");


    return $url;

}

function local_clear_urls_unclean($url){


    if (preg_match("/^course\/(.+)$/", $url, $matches)){
        return "course/view.php?name=".$matches[1];
    }

    error_log("In  uri: $url");
    $url .= "&foo=bar";
    error_log("Out uri: $url");
    return $url;

}


