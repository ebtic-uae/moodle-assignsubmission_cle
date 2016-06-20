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

require_once('../../../../config.php');
require_once('etherpad-lite-client.php');

$cmid = required_param('id', PARAM_INT);
$pad = optional_param('pad', '', PARAM_TEXT);
$groupID = optional_param('groupID', 0, PARAM_INT);
$course = $DB->get_record('course', array('id' => $cmid), '*', MUST_EXIST);
$url = get_config('assignsubmission_cle', 'etherpad_url');
$api = get_config('assignsubmission_cle', 'etherpad_api');

$etherpad = new EtherpadLiteClient($api, $url . '/api');

$groupname = groups_get_group_name($groupID);
global $PAGE, $OUTPUT,$COURSE,$USER;
require_login($course, true);
// check if user is allowed to view pad
 
$context = context_course::instance($course->id);
require_capability('mod/assign:grade', $context);

$tmp = explode("$", $pad);
$ether_group = $tmp[0];
try {
    $ether_author = $etherpad->createAuthorIfNotExistsFor($USER->id, $USER->firstname);
} catch (Exception $e) {
    echo"exception: " . $e;
}
$validUntil = mktime(0, 0, 0, date("m"), date("d") + 1, date("y")); // One day in the future
// create current session and set corresponding cookie
$ether_session = $etherpad->createSession($ether_group, $ether_author->authorID, $validUntil);
$cookie = setcookie('sessionID', $ether_session, $validUntil);



$PAGE->set_url('/mod/assign/submission/cle/viewpad.php', array('id' => $cmid, 'pad' => $pad));
$PAGE->set_title('Collaborative Learning Environment');
$PAGE->set_heading('Group Pad');
$PAGE->set_pagelayout('popup');
$PAGE->set_cacheable(false);


//output page
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading("Pad for Group $groupname");
$group_members = groups_get_members($groupID, "u.firstname,u.lastname");
echo $OUTPUT->heading("Group Members:", 3);
foreach ($group_members as $name => $stuff) {
            echo  $stuff->firstname . " " . $stuff->lastname . "\n<br>";
        }
echo $OUTPUT->box_end();
echo $OUTPUT->box_start();

$host = get_config('assignsubmission_cle', 'etherpad_server');

$etherpad_url_port = get_config('assignsubmission_cle', 'etherpad_url');

$srcStr=$etherpad_url_port.'/auth_session?padName='.$pad.'&sessionID='.$ether_session->sessionID.'&userName='.htmlentities($USER->firstname).'&showChat=false&av=NO';

echo '<iframe src="'.$srcStr.'" width="100%" height="400">Your browser does not diplay iFrames</iframe>';
  
echo $OUTPUT->box_end();
echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();
