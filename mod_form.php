<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_update_arlems_list.php');

$allarlemitems =array();
$selectedfiles = array();
    
class mod_arete_mod_form extends moodleform_mod {
    
    public function definition() {
        global $CFG, $DB, $selectedfiles, $allarlemitems;
        //check for new arlems files
        $arlem_update_manager = new mod_arete_update_arlems_list();
        $arlem_update_manager->arete_insert_new_arlems();
        $arlem_update_manager->arete_update_arlems();
        
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
        
        
        $mform->addElement('static', 'arlemlisttitle', get_string('arlemlisttitle', 'arete'));
        //get the list of arlem files from 
        $arlems = $DB->get_records('arete_allarlems');
        
        foreach($arlems as $key){
             $mform->addElement('advcheckbox', $key->name , $key->name, null, array('group' => 1));
             array_push($allarlemitems, $key->name);
        }
        
        $this->add_checkbox_controller(1); //create a check/uncheck for all checkboxes

//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    
     public function validation($data, $files) {
        global  $allarlemitems,$selectedfiles;
         
        $errors = parent::validation($data, $files);
        
        //check which files are selected and add them to a new array
        foreach ($allarlemitems as $item) {

            if($data[$item] == true) {
                array_push($selectedfiles, $item);
            }
        }

        return $errors;
    }

}