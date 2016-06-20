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

// put this file under /var/www/html 
$userName = $_REQUEST['userName'];
$padCode = $_REQUEST['padCode'];
$sessionID = $_REQUEST['session'];
$url = $_REQUEST['url'];
$chat = $_REQUEST['showChat'];

if (!$chat){$chat = true;};

$validUntil = mktime(0, 0, 0, date("m"), date("d") + 1, date("y")); 

$cookie = setcookie('sessionID', $sessionID, $validUntil, '/');

if ($cookie){
	header( "Location: ".$url."/p/".$padCode."?showControls=true&showChat=".$chat."&showLineNumbers=false&useMonospaceFont=false&userName=". $userName) ;
}
else {
	echo "Could not authenticate with cookies. Please enable cookies";
}
?>

