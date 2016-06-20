<?php
 
// The Collaborative Learning Environment (CLE) is an assignment plugin for Moodle that allows groups to work in real-time on assignments and provides insight to teachers into individual student contributions and group collaboration via usage statistics.

// Copyright (C) 2016 EBTIC

//  CLE is free software designed to work with the Moodle Learning Management system: 
//  you can redistribute it and/or modify it under the terms of the GNU General Public 
//  License as published by the Free Software Foundation, either version 3 of the 
//  License, or (at your option) any later version.

//  CLE is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.

//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

/// For more information on Moodle see - http://moodle.org/

/**
 * This file enables the CLE plugin to be used within the Moodle learning management system 
 *
 * @since      Moodle 2.8.1
 * @package    cle
 * @copyright  2016 EBTIC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignsubmission_cle_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;
    if ($oldversion < 2012061718) {

        // Define field etherpad_author_id to be added to assignsubmission_cle
        $table = new xmldb_table('assignsubmission_cle');
        $field = new xmldb_field('etherpad_author_id', XMLDB_TYPE_CHAR, '36', null, null, null, null, 'onlineformat');

        // Conditionally launch add field etherpad_author_id
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('etherpad_group_id', XMLDB_TYPE_CHAR, '36', null, null, null, null, 'etherpad_author_id');

        // Conditionally launch add field etherpad_group_id
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('etherpad_pad_id', XMLDB_TYPE_CHAR, '36', null, null, null, null, 'etherpad_group_id');

        // Conditionally launch add field etherpad_pad_id
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('moodle_group_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'etherpad_pad_id');

        // Conditionally launch add field moodle_group_id
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('moodle_author_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'moodle_group_id');

        // Conditionally launch add field moodle_author_id
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('etherpad_text_only', XMLDB_TYPE_TEXT, null, null, null, null, null, 'etherpad_pad_id');

        // Conditionally launch add field etherpad_text_only
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

       $field = new xmldb_field('links', XMLDB_TYPE_TEXT, null, null, null, null, null, 'etherpad_text_only');

        // Conditionally launch add field display_links
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('members', XMLDB_TYPE_TEXT, null, null, null, null, null, 'links');

        // Conditionally launch add field members
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // cle savepoint reached
        upgrade_plugin_savepoint(true, 2012061718, 'assignsubmission', 'cle');
    }

    return true;
}

