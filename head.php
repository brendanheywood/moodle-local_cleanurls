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
 * @subpackage cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($CFG->uncleanedurl)) {

    /**
     * One issue is that when rewriting urls we change their nesting and depth
     * which means legacy urls in the codebase which do NOT use moodle_url and
     * which are also relative links can be broken. To fix this we set the
     * base href to the original uncleaned url.
     */

    $output .= "<base href='$CFG->uncleanedurl'>\n";

} else {

    global $PAGE;
    $clean = $PAGE->url->out();
    $orig = $PAGE->url->orig_out();
    if ($orig != $clean) {

        /**
         * If we have just loaded a legacy url AND we can clean it, instead of
         * cleaning the url, caching it, and waiting for the user or someone
         * else to come back again to see the good url, we can use html5
         * replaceState to fix it imeditately without a page reload.
         *
         * Importantly this needs to happen before any JS on the page uses it,
         * such as any analytics tracking.
         */

        $output .= "<script>history.replaceState && history.replaceState({}, '', '$clean');</script>\n";

        /**
         * Now that each page has two valid urls, we need to tell robots like
         * GoogleBot that they are the same, otherwise Google may think they
         * are low quality duplicates and possibly split pagerank between them.
         *
         * We specify that the clean one is the 'canonical' url so this is what
         * will be shown in google search results pages.
         */

        $output .= "<link rel='canonical' href='$clean' />\n";

        apache_note('CLEANURL', $clean);

    }

}
