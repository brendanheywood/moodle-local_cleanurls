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

function xmldb_local_cleanurls_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2017072500) {
        // Define table local_cleanurls_cmpaths to be created.
        $table = new xmldb_table('local_cleanurls_cmpaths');

        // Adding fields to table local_cleanurls_cmpaths.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('path', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_cleanurls_cmpaths.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_cleanurls_cmpaths.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Cleanurls savepoint reached.
        upgrade_plugin_savepoint(true, 2017072500, 'local', 'cleanurls');
    }

    if ($oldversion < 2017072501) {

        // Define field cmid to be added to local_cleanurls_cmpaths.
        $table = new xmldb_table('local_cleanurls_cmpaths');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key locclecmp_cmid_uk (unique) to be added to local_cleanurls_cmpaths.
        $table = new xmldb_table('local_cleanurls_cmpaths');
        $key = new xmldb_key('locclecmp_cmid_uk', XMLDB_KEY_UNIQUE, array('cmid'));

        // Launch add key locclecmp_cmid_uk.
        $dbman->add_key($table, $key);

        // Cleanurls savepoint reached.
        upgrade_plugin_savepoint(true, 2017072501, 'local', 'cleanurls');
    }

    if ($oldversion < 2017073100) {

        // Define table local_cleanurls_history to be created.
        $table = new xmldb_table('local_cleanurls_history');

        // Adding fields to table local_cleanurls_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clean', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('unclean', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_cleanurls_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('loccleurlhis_cle_uk', XMLDB_KEY_UNIQUE, ['clean']);

        // Adding indexes to table local_cleanurls_history.
        $table->add_index('loccleurlurlhis_timemodified_ix', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);

        // Conditionally launch create table for local_cleanurls_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Cleanurls savepoint reached.
        upgrade_plugin_savepoint(true, 2017073100, 'local', 'cleanurls');
    }

    return true;
}
