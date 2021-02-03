<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

class mod_arete_update_arlems_list {
   
    //Add a new record on db for the new arlems
    function arete_insert_new_arlems ()
    {
        global $DB, $CFG, $fileList;
      
        
        $fileList = glob($CFG->dirroot.'/mod/arete/files'.'/*.zip', GLOB_BRACE);

        foreach($fileList as $file)
        {   
            $filename = pathinfo($file, PATHINFO_FILENAME);

            if(!$DB->record_exists('arete_allarlems', array('name' => $filename)))
            {           
                $arlem  = new stdClass();
                $arlem->name = $filename;
                $arlem->url = $file;
                $arlem->timecreated = time();
                $DB->insert_record('arete_allarlems', $arlem);
            }   
        }
    }
    
    
    //Delete the records which their arlems are not exist 
    function arete_update_arlems()
    {
        global $DB, $fileList;
                
        $arlems_on_db = $DB->get_records('arete_allarlems');
        
        
        foreach($arlems_on_db as $arlem)
        {   
            if(!in_array($arlem->url , $fileList))
            {
                $DB->delete_records('arete_allarlems', array('id' => $arlem->id));
            }
        }
    }

}

