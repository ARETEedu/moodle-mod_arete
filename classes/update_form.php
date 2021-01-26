<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');

$arlemsList = array();

class update_form extends moodleform{

    function definition() {
        global $DB,$arlemsList,$moduleid;

       $id = required_param('id', PARAM_INT); 
       list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');
       $moduleid = $cm->instance;
       
        $mform = $this->_form;
        
        //get the list of arlem files from 
        $arlemsList = $DB->get_records('arete_allarlems');
        
        $mform->addElement('header', 'title', get_string('arlemsectiontitle', 'arete'));
        
        $mform->addElement('static', 'arlemlisttitle', get_string('arlemradiobuttonlabel', 'arete'));
        
        $arlemsGroup = array();
        foreach($arlemsList as $key){
             $arlemsGroup[] = $mform->createElement('radio', 'arlem' , '', $key->name, $key->name);
        }
        $mform->addGroup($arlemsGroup, 'arlemsButtons', '', array(' <br> '), false);

        $mform->setDefault('arlem', $arlemsList[1]->name); //set the first element as default
        
        $mform->addElement('hidden', 'id', $moduleid);
        $mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    
    
    public function definition_after_data() {
       global $arlemsList,$moduleid;
       
       parent::definition_after_data(); 
       
       $mform = $this->_form;   
       
       $utilities = new mod_arete_arlems_utilities();

       foreach ($arlemsList as $arlem) 
        {
            if($utilities->is_arlem_assigned($moduleid, $arlem->id))
            {
                $mform->setDefault('arlem', $arlem->name);
            }
        }
    }
    

}
