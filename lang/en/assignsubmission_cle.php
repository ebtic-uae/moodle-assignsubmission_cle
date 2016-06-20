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

$string['allowclesubmissions'] = 'Enabled';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['enabled'] = 'CLE';
$string['enabled_help'] = 'This enables the Collaborative Learning Environment that is currently trialed at Khalifa University. Please do NOT enable unless you are part of the Trial! Questions can be sent to benjamin.hirsch@kustar.ac.ae';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['cle'] = 'CLE';
$string['clefilename'] = 'cle.html';
$string['clesubmission'] = 'Allow CLE submission';
$string['pluginname'] = 'Collaborative Learning Environment';
$string['numwords'] = '({$a} words)';
$string['numwordsforlog'] = 'Submission word count: {$a} words';
$string['url'] = 'Etherpad server location';
$string['url_help'] = 'Enter the location of the server, including http://';
$string['api'] = 'Etherpad API key';
$string['api_help'] = 'The secret key for the Etherpad server';
$string['stats'] = 'Enable etherpad statistics';
$string['stats_help'] = 'If enabled, the etherpad database is mined for usage information. Note that if this is enabled, the etherpad MySQL data MUST be filled in, and the etherpad MYSQL server must allow remote connections if it is running on an external server.';
$string['user'] = 'MySQL user for etherpad (needed for statistics)';
$string['user_help'] = 'MySQL user name that has access to the etherpad database';
$string['server'] = 'Host';
$string['server_help'] = 'MySQL host of the etherpad installation';
$string['password'] = 'Password';
$string['password_help'] = 'MySQL host of the etherpad installation';
$string['db'] = 'Database';
$string['db_help'] = 'MySQL database used by the etherpad installation';
