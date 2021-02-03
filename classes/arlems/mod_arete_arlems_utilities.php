<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

class mod_arete_arlems_utilities {
    
    //get the arlem id from main arlem list using name
    function get_arlemid_from_db($arlemname){
        
        global $DB;
                
        $arlems_on_db = $DB->get_records('arete_allarlems');
        
        
        foreach($arlems_on_db as $arlem)
        {   

            if($arlem->name == $arlemname)
            {
                return $arlem->id;
            }
        }
    }
    
    //get the arlem url from main arlem list using id
    function get_arlemurl_from_db($arlemid){
        
        global $DB;
                
        $arlems_on_db = $DB->get_records('arete_allarlems');
        
        
        foreach($arlems_on_db as $arlem)
        {   

            if($arlem->id == $arlemid)
            {
                return $arlem->url;
            }
        }
    }
    
    
    
    //get the arlem name from main arlem list using id
    function get_arlemname_from_db($arlemid){

    global $DB;

    $arlems_on_db = $DB->get_records('arete_allarlems');


    foreach($arlems_on_db as $arlem)
    {   

        if($arlem->id == $arlemid)
        {
            return $arlem->name;
        }
    }
    }
    
    
    //get the list of arlem files which is assig
    function is_arlem_assigned($areteid, $arlemid)
    {
        global $DB;

        $arlems = $DB->get_records('arete_arlem', array('areteid' => $areteid, 'arlemid' => $arlemid));
        
        if(empty($arlems)){
            return false;
        }
        
        return true;
    }


}
