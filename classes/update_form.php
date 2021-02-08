<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlem_utilities.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

$arlemsList = array();

class update_form extends moodleform{

    function definition() {
        global $DB,$arlemsList,$moduleid;

       $id = required_param('id', PARAM_INT); 
       list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');
       $moduleid = $cm->instance;
       
        $mform = $this->_form;
        
        //get the list of arlem files from 
        $context = context_course::instance($course->id);
        $arlemsList = getAllArlems();
        
        $mform->addElement('header', 'title', get_string('arlemsectiontitle', 'arete'));
        
        $mform->addElement('static', 'arlemlisttitle', get_string('arlemradiobuttonlabel', 'arete'));
        
        $arlemsGroup = array();
        foreach($arlemsList as $arlem){
             $arlemsGroup[] = $mform->createElement('radio', 'arlem' , '', $arlem->get_filename(), $arlem->get_filename());
        }
        $mform->addGroup($arlemsGroup, 'arlemsButtons', '', array(' <br> '), false);
        
        if(isset($arlemsList[1])){
                    $mform->setDefault('arlem', $arlemsList[1]->get_filename()); //set the first element as default
        }
        
        $mform->addElement('hidden', 'id', $moduleid);
        $mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    
    
    public function definition_after_data() {
       global $arlemsList,$moduleid;
       
       parent::definition_after_data(); 
       
       $mform = $this->_form;   
//       
       
       foreach ($arlemsList as $arlem) 
        {
            if(is_arlem_assigned($moduleid, $arlem->get_id()))
            {
                $mform->setDefault('arlem', $arlem->get_filename() );
            }
        }
    }
    

}
