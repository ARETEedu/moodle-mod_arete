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
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

use moodleform, html_writer;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class teacher_top_buttons extends moodleform {

    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        $splitet_list = $this->_customdata['splitet_list'];
        $page_number = $this->_customdata['page_number'];
        $moduleid = $this->_customdata['moduleid'];

        $buttonarray = array();

        //get the get queries from the URL
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


        //confirm delete
        if ($editmode === 'on') {
            $mform->addElement('checkbox', 'deleteConfirm', get_string('confirmchanges', 'arete'), '', array('id' => 'confirmchk'));
        }

        //table
        $table = html_writer::table(draw_table($splitet_list[$page_number - 1], 'arlemTable', true, $moduleid)); //arlems table
        $mform->addElement('html', $table);

        //hiddens
        $id = $this->_customdata['course_module_id'];
        $searchquery = $this->_customdata['searchquery'];
        $return_url = $CFG->wwwroot . '/mod/arete/view.php?id=' . $id . $searchquery . '&pnum=' . $page_number . '&editing=on';
        $mform->addElement('hidden', 'returnurl', $return_url);
        $mform->setType('returnurl', PARAM_URL);
        $mform->addElement('hidden', 'moduleid', $moduleid);
        $mform->setType('moduleid', PARAM_INT);
    }

}