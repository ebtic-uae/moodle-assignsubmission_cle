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
 
require_once('etherpad-lite-client.php');
defined('MOODLE_INTERNAL') || die();
/**
 * File area for online text submission assignment
 */
define('ASSIGNSUBMISSION_CLE_FILEAREA', 'submissions_cle');

class assign_submission_cle extends assign_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('cle', 'assignsubmission_cle');
    }

    /**
     * Get onlinetext submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_cle_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_cle', array('submission' => $submissionid));
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $USER, $OUTPUT, $CFG;
        global $etherpad, $pad_code, $url, $ether_session, $ether_author,$ether_group;
        $edit = optional_param('editsubmission', 0, PARAM_BOOL);
        $cle_session = optional_param('cle_session', 0, PARAM_BOOL);
        $submitted = optional_param('submitted', 0, PARAM_BOOL);
        $groupID = optional_param('group', -1, PARAM_INT);
        $context = $this->assignment->get_context();
        $cm = $this->assignment->get_course_module();
        $course = $this->assignment->get_course();
        $url = get_config('assignsubmission_cle', 'etherpad_url');
        $api = get_config('assignsubmission_cle', 'etherpad_api');
        MoodleQuickForm::registerElementType('cleeditor', $CFG->dirroot . '/mod/assign/submission/cle/cleeditor.php', 'moodlequickform_cleeditor');
 
        //get group data
        if ($groupID == -1) {
            $groupID = groups_get_activity_group($cm, true);
        }
        $assigned_group = new StdClass();
        if ($groupID != false) {
            $assigned_group->id = $groupID;
            $assigned_group->name = groups_get_group_name($groupID);
            $tmp2 = groups_get_group($groupID);
            $assigned_group->timecreated = $tmp2->timecreated;
        } else {

            $assigned_group->timecreated = "0";
            $assigned_group->id = 0;
            $assigned_group->name = "no group";
        }
        add_to_log($course->id, "CLE assignment", "view", "view.php?id={$cm->id}", "Assignment: {$this->assignment->get_instance()->id}", $cm->id);

        //initialise etherpad and set the stage for session registration
        $etherpad = new EtherpadLiteClient($api, $url . '/api');
        $curr_pad = substr(hash("sha256", $assigned_group->timecreated . $assigned_group->name . $cm->id), 0, 16);
        $validUntil = mktime(0, 0, 0, date("m"), date("d") + 1, date("y")); // One day in the future  
        if ($curr_pad == null) {
            $curr_pad = 'ebtic';
        };
        try {
            $ether_author = $etherpad->createAuthorIfNotExistsFor($USER->id, $USER->firstname);
        } catch (Exception $e) {
            echo"exception: " . $e;
        }
        try {
            $ether_group = $etherpad->createGroupIfNotExistsFor($assigned_group->id);
        } catch (Exception $e) {
            $ether_group = $etherpad->createGroupIfNotExistsFor("ebtic");
        }
        $pad_code = $ether_group->groupID . "\$" . $curr_pad;
       
        if (!$edit) {
            global $ether_session;
            unset($session_list);
            try {
                $session_list = $etherpad->listSessionsOfAuthor($ether_author->authorID);
            } catch (Exception $e) {
                echo "session list error: " . $e->getMessage();
            }
            foreach ($session_list as $s => $tmp) {
                try {
                    $etherpad->deleteSession($s);
                    setCookie('sessionID', $s, time() - 36000);
                } catch (Exception $e) {
                    echo $e;
                }
            }
            unset($session_list);
            // create current session and set corresponding cookie
           $ether_session = $etherpad->createSession($ether_group->groupID, $ether_author->authorID, $validUntil);
            $cookie = setcookie('sessionID', $ether_session->sessionID, $validUntil, '/');
            $edit = true;
        }

        //create pad for student

        if ($edit) {
            try {
                $etherpad->createGroupPad($ether_group->groupID, $curr_pad, "");
            } catch (Exception $e) {
                // this generally catches as the pad only needs to be
                // created once - but it is convenient to just catch the
                // exception instead of checking for existence.
            }
            //register CLE editor as QuickHTML thingy
            $editoroptions = $this->get_edit_options($pad_code, $USER->firstname);
            
            $mform->addElement('cleeditor', 'cleeditor', '', array('pad_code' => $pad_code, 'firstname' => $USER->firstname, 'url' => $url, 'cm' => $cm->id, 'id' => $this->assignment->get_instance()->id,'session' => $ether_session->sessionID), $editoroptions);
        }

        return true;
    }

    /**
     * Editor format options
     *
     * @return array
     */
    private function get_edit_options() {
        $editoroptions = array(
            'noclean' => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $this->assignment->get_course()->maxbytes,
            'context' => $this->assignment->get_context(),
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );
        return $editoroptions;
    }

    /**
     * Save data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB, $CFG, $USER;
        global $etherpad, $ether_author, $ether_group, $pad_code, $url, $showviewlink;
        
        $editoroptions = $this->get_edit_options();

        //collect group data
        $groupID = groups_get_activity_group($this->assignment->get_course_module(), true);
        $group_members = groups_get_members($groupID, "u.firstname,u.lastname");
        $data->onlinetext = "<h2>" . groups_get_group_name($groupID) . " </h2>\nMembers:<br>";                      
        foreach ($group_members as $name => $stuff) {
            $data->onlinetext .= $stuff->firstname . " " . $stuff->lastname . "\n<br>";            
        }                      

        //copy members only to members field
        $data->members = $data->onlinetext;
        
        $cmid = $this->assignment->get_course()->id;
        // add link to pad (for playback etc) and link to stats page for pad
        $data->onlinetext .= '<p><a target="_blank" href = "' . $CFG->wwwroot . '/mod/assign/submission/cle/viewpad.php?pad=' . $pad_code . '&id='.$cmid.'&groupID='.$groupID.'"> Link to Collaboration Area</a><p>';
        $data->onlinetext .= '<p><a target="_blank" href = "' . $CFG->wwwroot . '/mod/assign/submission/cle/viewstats.php?pad=' . $pad_code . '&id='.$cmid.'&groupID='.$groupID.'"> Link to statistics</a><p>';
        
        $data->write_links .= '<p><a target="_blank" href = "' . $CFG->wwwroot . '/mod/assign/submission/cle/viewpad.php?pad=' . $pad_code . '&id='.$cmid.'&groupID='.$groupID.'"> Link to Collaboration Area</a><p>';
        $data->write_links .= '<p><a target="_blank" href = "' . $CFG->wwwroot . '/mod/assign/submission/cle/viewstats.php?pad=' . $pad_code . '&id='.$cmid.'&groupID='.$groupID.'"> Link to statistics</a><p>';   
        
        // write etherpad data to both onlinetext and etherpad_only
        $temp_data = $etherpad->getHTML($pad_code)->html;
        $data->onlinetext .= $temp_data;
        $data->etherpad_text_only = $temp_data;
        
        $data->onlinetext_editor['format'] = 1;
        $onlinetextsubmission = $this->get_cle_submission($submission->id);

        if ($onlinetextsubmission) {

            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
            
            $onlinetextsubmission->links = $data->write_links;
            $onlinetextsubmission->members = $data->members;
            $onlinetextsubmission->etherpad_text_only = $data->etherpad_text_only;

            return $DB->update_record('assignsubmission_cle', $onlinetextsubmission);
        } else {

            $onlinetextsubmission = new stdClass();
            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
            
            $onlinetextsubmission->submission = $submission->id;
            $onlinetextsubmission->assignment = $this->assignment->get_instance()->id;
            
            $onlinetextsubmission->etherpad_author_id = $ether_author->authorID;
            $onlinetextsubmission->etherpad_group_id = $ether_group->groupID;
            $onlinetextsubmission->etherpad_pad_id = $pad_code;
            
            $onlinetextsubmission->moodle_author_id = $USER->id;
            $onlinetextsubmission->moodle_group_id = $groupID;

            $onlinetextsubmission->links = $data->write_links;
            $onlinetextsubmission->members = $data->members;
            $onlinetextsubmission->etherpad_text_only = $data->etherpad_text_only;
            
            return $DB->insert_record('assignsubmission_cle', $onlinetextsubmission) > 0;
        }
    }

    /**
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'cle') {
            $onlinetextsubmission = $this->get_cle_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlinetext;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'cle') {
            $onlinetextsubmission = $this->get_cle_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlineformat;
            }
        }
        return 0;
    }

    /**
     * Display onlinetext word count in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink - If the summary has been truncated set this to true
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {

        $onlinetextsubmission = $this->get_cle_submission($submission->id);
        // show the view link if you are allowed to grade
        $context = $this->assignment->get_context();
        if (has_capability('mod/assign:grade', $context)) {
            $showviewlink = true;
        } else {
            $showviewlink = false;
        };

        if ($onlinetextsubmission) {
            
            // write members, links and text separately
            $text = $onlinetextsubmission->members;
            if ($showviewlink) {
                $text .= $onlinetextsubmission->links;
            } else {
                // add a line break between the members and data
                $text .= '<br>';
            }                          
            $text .= $onlinetextsubmission->etherpad_text_only;
            $shorttext = shorten_text($text, 140);
            if ($text != $shorttext) {
                return $shorttext . get_string('numwords', 'assignsubmission_cle', count_words($text));
            } else {
                return $shorttext;
            }
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     *
     * @param stdClass $submission - For this is the submission data
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        global $DB;
        $files = array();
        $onlinetextsubmission = $this->get_cle_submission($submission->id);
        if ($onlinetextsubmission) {
            $user = $DB->get_record("user", array("id" => $submission->userid), 'id,username,firstname,lastname', MUST_EXIST);

            $prefix = clean_filename(fullname($user) . "_" . $submission->userid . "_");
            $finaltext = str_replace('@@PLUGINFILE@@/', $prefix, $onlinetextsubmission->onlinetext);
            $submissioncontent = "<html><body>" . format_text($finaltext, $onlinetextsubmission->onlineformat, array('context' => $this->assignment->get_context())) . "</body></html>";      //fetched from database

            $files[get_string('clefilename', 'assignsubmission_cle')] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_cle', ASSIGNSUBMISSION_CLE_FILEAREA, $submission->id, "timemodified", false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $result = '';

        $onlinetextsubmission = $this->get_cle_submission($submission->id);


        if ($onlinetextsubmission) {

            // render for portfolio API
            $result .= $this->assignment->render_editor_content(ASSIGNSUBMISSION_CLE_FILEAREA, $onlinetextsubmission->submission, $this->get_type(), 'cle', 'assignsubmission_cle');
        }

        return $result;
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'cle' && $version >= 2011112900) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;

        $onlinetextsubmission = new stdClass();
        $onlinetextsubmission->onlinetext = $oldsubmission->data1;
        $onlinetextsubmission->onlineformat = $oldsubmission->data2;

        $onlinetextsubmission->submission = $submission->id;
        $onlinetextsubmission->assignment = $this->assignment->get_instance()->id;

        if ($onlinetextsubmission->onlinetext === null) {
            $onlinetextsubmission->onlinetext = '';
        }

        if ($onlinetextsubmission->onlineformat === null) {
            $onlinetextsubmission->onlineformat = editors_get_preferred_format();
        }

        if (!$DB->insert_record('assignsubmission_cle', $onlinetextsubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 'mod_assignment', 'submission', $oldsubmission->id,
                // New file area
                $this->assignment->get_context()->id, 'assignsubmission_cle', ASSIGNSUBMISSION_CLE_FILEAREA, $submission->id);
        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $onlinetextsubmission = $this->get_cle_submission($submission->id);
        $onlinetextloginfo = '';
        $text = format_text($onlinetextsubmission->onlinetext, $onlinetextsubmission->onlineformat, array('context' => $this->assignment->get_context()));
        $onlinetextloginfo .= get_string('numwordsforlog', 'assignsubmission_cle', count_words($text));

        return $onlinetextloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_cle', array('assignment' => $this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_CLE_FILEAREA => $this->get_name());
    }

}

