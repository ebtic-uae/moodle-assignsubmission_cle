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

defined('MOODLE_INTERNAL') || die();

require_once("HTML/QuickForm/input.php");

class MoodleQuickForm_cleeditor extends HTML_QuickForm_input {

    /** help message */
    public $_helpbutton = '';

    /** stores the result of the last validation: null - undefined, false - no errors, string - error(s) text */
    protected $validationerrors = null;

    /** if element has already been validated * */
    protected $wasvalidated = false;

    /** If non-submit (JS) button was pressed: null - unknown, true/false - button was/wasn't pressed */
    protected $nonjsbuttonpressed = false;

    /** Message to display in front of the editor (that there exist grades on this rubric being edited) */
    protected $regradeconfirmation = false;

    function MoodleQuickForm_cleeditor($elementName = null, $elementLabel = null, $attributes = null) {
        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
    }

    /**
     * set html for help button
     *
     * @access   public
     * @param array $help array of arguments to make a help button
     * @param string $function function name to call to get html
     */
    public function setHelpButton($helpbuttonargs, $function = 'helpbutton') {
        debugging('component setHelpButton() is not used any more, please use $mform->setHelpButton() instead');
    }

    /**
     * get html for help button
     *
     * @access   public
     * @return  string html for help button
     */
    public function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * The renderer will take care itself about different display in normal and frozen states
     *
     * @return string
     */
    public function getElementTemplateType() {
        return 'default';
    }

    /**
     * Specifies that confirmation about re-grading needs to be added to this rubric editor.
     * $changelevel is saved in $this->regradeconfirmation and retrieved in toHtml()
     *
     * @see gradingform_rubric_controller::update_or_check_rubric()
     * @param int $changelevel
     */
    public function add_regrade_confirmation($changelevel) {
        $this->regradeconfirmation = $changelevel;
    }

    /**
     * Returns html string to display this element
     *
     * @return string
     */
    public function toHtml() {
		
        global $CFG,$PAGE;
        $pad_code = $this->_attributes['pad_code'];
        $firstname = $this->_attributes['firstname'];
        $id = $this->_attributes['id'];
        $cmid = $this->_attributes['cm'];
        $url = $this->_attributes['url'];
		
	$host = get_config('assignsubmission_cle', 'etherpad_server');
	$etherpad_url_port = get_config('assignsubmission_cle', 'etherpad_url');
		
	$session = $this->_attributes['session'];
	$cm = get_coursemodule_from_id('', $cmid);
	$group_string='';
			
	$redirectStr=$etherpad_url_port.'/auth_session?padName='.$pad_code.'&sessionID='.$session.'&userName='.htmlentities($firstname).'&showControls=true&showChat=true&showLineNumbers=false&useMonospaceFont=false';

	$html = $group_string.'<iframe src="'.$redirectStr.'" width="100%" height="400">Your browser does not display iFrames</iframe>';

	return $html;
    }

    /**
     * Prepares the data passed in $_POST:
     * - processes the pressed buttons 'addlevel', 'addcriterion', 'moveup', 'movedown', 'delete' (when JavaScript is disabled)
     *   sets $this->nonjsbuttonpressed to true/false if such button was pressed
     * - if options not passed (i.e. we create a new rubric) fills the options array with the default values
     * - if options are passed completes the options array with unchecked checkboxes
     * - if $withvalidation is set, adds 'error_xxx' attributes to elements that contain errors and creates an error string
     *   and stores it in $this->validationerrors
     *
     * @param array $value
     * @param boolean $withvalidation whether to enable data validation
     * @return array
     */
    protected function prepare_data($value = null, $withvalidation = false) {
        return null;
    }

    /**
     * Scans array $ids to find the biggest element ! NEWID*, increments it by 1 and returns
     *
     * @param array $ids
     * @return string
     */
    protected function get_next_id($ids) {
        $maxid = 0;
        foreach ($ids as $id) {
            if (preg_match('/^NEWID(\d+)$/', $id, $matches) && ((int) $matches[1]) > $maxid) {
                $maxid = (int) $matches[1];
            }
        }
        return 'NEWID' . ($maxid + 1);
    }

    /**
     * Checks if a submit button was pressed which is supposed to be processed on client side by JS
     * but user seem to have disabled JS in the browser.
     * (buttons 'add criteria', 'add level', 'move up', 'move down', etc.)
     * In this case the form containing this element is prevented from being submitted
     *
     * @param array $value
     * @return boolean true if non-submit button was pressed and not processed by JS
     */
    public function non_js_button_pressed($value) {
        if ($this->nonjsbuttonpressed === null) {
            $this->prepare_data($value);
        }
        return $this->nonjsbuttonpressed;
    }

    /**
     * Validates that rubric has at least one criterion, at least two levels within one criterion,
     * each level has a valid score, all levels have filled definitions and all criteria
     * have filled descriptions
     *
     * @param array $value
     * @return string|false error text or false if no errors found
     */
    public function validate($value) {
        if (!$this->wasvalidated) {
            $this->prepare_data($value, true);
        }
        return $this->validationerrors;
    }

    /**
     * Prepares the data for saving
     * @see prepare_data()
     *
     * @param array $submitValues
     * @param boolean $assoc
     * @return array
     */
    public function exportValue(&$submitValues, $assoc = false) {
        $value = $this->prepare_data($this->_findValue($submitValues));
        return $this->_prepareValue($value, $assoc);
    }
	
	public function str_lreplace($search, $replace, $subject){	
		$pos = strrpos($subject, $search);
		if($pos !== false){
			$subject = substr_replace($subject, $replace, $pos, strlen($search));		
		}
		return $subject;
	}

}
