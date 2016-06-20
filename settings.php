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

$settings->add(new admin_setting_configcheckbox('assignsubmission_cle/default',
                   new lang_string('default', 'assignsubmission_cle'),
                   new lang_string('default_help', 'assignsubmission_cle'), 0));

$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_url',
                    new lang_string('url', 'assignsubmission_cle'),
                    new lang_string('url_help', 'assignsubmission_cle'),
                'http://10.10.2.36:9001'));
				
$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_api',
                    new lang_string('api', 'assignsubmission_cle'),
                    new lang_string('api_help', 'assignsubmission_cle'),
                'secret key'));
$settings->add(new admin_setting_configcheckbox('assignsubmission_cle/etherpad_stats',
                   new lang_string('stats', 'assignsubmission_cle'),
                   new lang_string('stats_help', 'assignsubmission_cle'), 1));
$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_server',
                    new lang_string('server', 'assignsubmission_cle'),
                    new lang_string('server_help', 'assignsubmission_cle'),
                'localhost'));
$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_user',
                    new lang_string('user', 'assignsubmission_cle'),
                    new lang_string('user_help', 'assignsubmission_cle'),
                'etherpad'));
$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_password',
                    new lang_string('password', 'assignsubmission_cle'),
                    new lang_string('password_help', 'assignsubmission_cle'),
                'secret key'));
$settings->add(new admin_setting_configtext('assignsubmission_cle/etherpad_db',
                    new lang_string('db', 'assignsubmission_cle'),
                    new lang_string('db_help', 'assignsubmission_cle'),
                'etherpad'));