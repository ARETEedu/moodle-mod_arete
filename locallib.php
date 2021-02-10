<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../config.php');


function arete_delete_activity($id){
    
    
    global $DB;
    if ($DB->get_record('arete', array('id' => $id)) !== null)
    {
        $DB->delete_records('arete', array('id' => $id));
        
        if ($DB->get_record('arete_arlem', array('areteid' => $id))!== null)
        {
            $DB->delete_records('arete_arlem', array('areteid'=> $id));
        }
        
    }

}