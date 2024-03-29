<?php

// This file is part of the Augmented Reality Experience plugin (mod_arete) for Moodle - http://moodle.org/
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
 * The menu for the teacher and admins on top of the ARLEMS table
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\output;

use moodleform,
    html_writer;

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once("$CFG->libdir/formslib.php");

/**
 * Includes the button to enable/disable edit mode and allow to
 * save the table changes
 * The main table will be called from this class
 */
class teacher_top_buttons extends moodleform {

    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        $splitetlist = $this->_customdata['splitetlist'];
        $pagenumber = $this->_customdata['pagenumber'];
        $moduleid = $this->_customdata['moduleid'];

        $buttonarray = array();

        //Get the get queries from the URL
        $editmode = optional_param('editing', null, PARAM_TEXT);

        if ($editmode === 'on') {
            //Show the save button only in edit mode
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savebutton', 'arete'), array('id' => 'saveButton'));

            $editButtonValue = get_string('editmodeenabledbutton', 'arete');
        } else {
            $editButtonValue = get_string('editmodedisabledbutton', 'arete');
        }

        $buttonarray[] = $mform->createElement('button', 'editModeButton', $editButtonValue, array('id' => 'editModeButton'));
        $mform->addGroup($buttonarray, 'buttonar', '', '', false);


        //The checkbox for confirming of the deletion of the files
        if ($editmode === 'on') {
            $mform->addElement('checkbox', 'deleteConfirm', get_string('confirmchanges', 'arete'), '', array('id' => 'confirmchk'));
        }

        //Calling the main table
        $table = html_writer::table(draw_table($splitetlist[$pagenumber - 1], 'arlemTable', true, $moduleid)); //arlems table
        $mform->addElement('html', $table);

        //Adding all other elements
        $id = $this->_customdata['coursemoduleid'];
        $searchquery = $this->_customdata['searchquery'];
        $return_url = "{$CFG->wwwroot}/mod/arete/view.php?id={$id}{$searchquery}&pnum={$pagenumber}&editing=on";
        $mform->addElement('hidden', 'returnurl', $return_url);
        $mform->setType('returnurl', PARAM_URL);
        $mform->addElement('hidden', 'moduleid', $moduleid);
        $mform->setType('moduleid', PARAM_INT);
    }

}
