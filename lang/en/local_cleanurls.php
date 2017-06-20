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
 * Language items for cleanurls
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Clean URLs';
$string['cleaningon'] = 'Cleaning on';
$string['cleaningonhelp'] = 'Off by default until the webserver is properly configured.';
$string['cleaningonhelpdebug'] = '(view tests)';
$string['routerok'] = 'Rewrite router is working (inbound links)';
$string['routerbroken'] = 'Rewrite router is NOT working (inbound links)';
$string['rewriteok'] = 'Rewrite function is configured properly (outbound links)';
$string['rewritebroken'] = 'Rewrite function is NOT working (outbound links), if you are running a moodle before 3.1 ensure the patch has been applied properly.';
$string['rewritenoconfig'] = 'Rewrite function is NOT configured (outbound links), please add this to your config.php:<br>
<pre>
$CFG->urlrewriteclass = \'\\\\local_cleanurls\\\\url_rewriter\';
</pre>';
$string['cleanusernames'] = 'Rewrite userid\'s into usernames?';
$string['cleanusernameshelp'] = '<p>If username\'s change this is not recommended.</p><p>If on this may also be a privacy issue if your usernames expose anything sensitive.</p>';
$string['debugging'] = 'Debugging on';
$string['debugginghelp'] = 'Logs rewrite process to php error log';
$string['cachedef_outgoing'] = 'Cleaned url mapping';
$string['cachedef_uncleaning'] = 'Clean URLs mapping: clean to unclean.';
$string['webservertest'] = 'Clean URLs Webserver Test';
$string['webservertesthelp'] = 'Click here to view instructions on how to configure your webserver.';
$string['webservertestdebug'] = 'Debug Information';

