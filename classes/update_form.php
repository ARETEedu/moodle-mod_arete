<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');

$arlems_full_list =array();
$selectedfiles = array();
$moduleid = new stdClass();
    
class update_form extends moodleform{

    function definition() {
        global $DB,$arlems_full_list,$moduleid;
                
       $id = required_param('id', PARAM_INT); 
       list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');
       $moduleid = $cm->instance;
       


        $mform = $this->_form;

        //get the list of arlem files from 
        $arlems_full_list = $DB->get_records('arete_allarlems');
        
        
        foreach($arlems_full_list as $arlem){
            
            $mform->addElement('checkbox', $arlem->name , $arlem->name);
        }

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid', $moduleid);
        $mform->setType('cmid', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    public function definition_after_data() {
       global $arlems_full_list,$DB,$moduleid;
       
        parent::definition_after_data(); 
       

          
       $mform = $this->_form;   
       
       $arlem_utilities = new mod_arete_arlems_utilities();
       

       foreach ($arlems_full_list as $arlem) 
        {
               if(!empty($arlem_utilities->is_arlem_assigned($moduleid, $arlem->id)))
               {
                   $mform->setDefault($arlem->name, true);
               }
        }
    }
    
     public function validation($data, $files) {
        global  $arlems_full_list,$selectedfiles;
         
        //cleare the list
        $selectedfiles = array();
       
        $errors = parent::validation($data, $files);
        
        //check which files are selected and add them to a new array
        foreach ($arlems_full_list as $item) {

            if($data[$item] == true) {
                array_push($selectedfiles, $item);
            }
        }

        return $errors;
    }

}
