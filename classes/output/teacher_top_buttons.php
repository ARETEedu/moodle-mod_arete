<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

class teacher_top_buttons extends moodleform{
    
     public function definition() {
        global $CFG;
       
        $mform = $this->_form; // Don't forget the underscore! 

        $splitet_list = $this->_customdata['splitet_list'];
        $page_number = $this->_customdata['page_number'];
        $moduleid = $this->_customdata['moduleid'];

        $buttonarray=array();
        
        //get the get queries from the URL
        $queries = get_queries(true);
        
        if($queries['editing'] === 'on'){
            //Show the save button only in edit mode
            $buttonarray[] = $mform->createElement('submit', 'submitbutton',  get_string('savebutton', 'arete'),  array ('id' => 'saveButton'));
            
            $editButtonValue =  get_string('editmodeenabledbutton', 'arete');
        }else{
            $editButtonValue = get_string('editmodedisabledbutton', 'arete');
        }

        $buttonarray[] = $mform->createElement('button', 'editModeButton', $editButtonValue,  array ('id' => 'editModeButton'));
        $mform->addGroup($buttonarray, 'buttonar', '', '', false);
        
        
        //confirm delete
        if($queries['editing'] === 'on'){
            $mform->addElement('checkbox', 'deleteConfirm', get_string('confirmchanges', 'arete'));
        }
                
        //table
        $table = html_writer::table(draw_table($splitet_list[$page_number-1],'arlemTable',  true, $moduleid)); //arlems table
        $mform->addElement('html', $table); 
        
        $mform->disabledIf('submitbutton', 'deleteConfirm', 'notchecked');
                            
        //hiddens
        $id = $this->_customdata['course_module_id'];
        $searchquery = $this->_customdata['searchquery'];
        $return_url = $CFG->wwwroot .'/mod/arete/view.php?id='. $id . $searchquery . '&pnum=' . $page_number . '&editing=on';
        $mform->addElement('hidden', 'returnurl', $return_url);
        $mform->setType('returnurl', PARAM_URL);
        $mform->addElement('hidden',  'moduleid', $moduleid);
        $mform->setType('moduleid', PARAM_INT);

    }
    
}
