<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

$arlemsList = array();

class mod_arete_mod_form extends moodleform_mod {
    
    public function definition() {
        global $CFG, $arlemsList , $COURSE;
        
        $courseid = $COURSE->id;
        $context = context_course::instance($courseid);
        
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
     /*   
        $mform->addElement('header', 'title', get_string('arlemsectiontitle', 'arete'));
        
        $mform->addElement('static', 'arlemlisttitle', get_string('arlemradiobuttonlabel', 'arete'));
         
        //get the list of arlem files from 
        $arlemsList = getAllArlems();
        
        $arlemsGroup = array();
        foreach($arlemsList as $arlem){
             $arlemsGroup[] = $mform->createElement('radio', 'arlemid' , '', $arlem->get_filename(), $arlem->get_id());
        }
        $mform->addGroup($arlemsGroup, 'arlemsButtons', '', array(' <br> '), false);
        $mform->addRule('arlemsButtons', null, 'required', null, 'client');
        
        if(isset($arlemsList[1])){
            $mform->setDefault('arlemid', $arlemsList[1]->get_id()); //set the first element as default
        }
      
      */
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