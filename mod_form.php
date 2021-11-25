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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

$arlemsList = array();

class mod_arete_mod_form extends moodleform_mod {
    
    public function definition() {
        global $CFG ;

        
        $mform = $this->_form;
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('arname', 'arete'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $this->standard_intro_elements(get_string('description', 'arete'));
        

//-------------------------------------------------------------------------------
        

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function definition_after_data() 
    {    
       global $arlemsList;
        
       $mform = $this->_form;
        
       parent::definition_after_data(); 
//       
       
       foreach ($arlemsList as $arlem) 
        {
            if(is_arlem_assigned($this->_instance, $arlem->get_id()))
            {
                $mform->setDefault('arlemid', $arlem->get_id() );
            }
        }
    }
    
}