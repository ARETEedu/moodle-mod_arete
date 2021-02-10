<?php

defined('MOODLE_INTERNAL') || die;

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



//Returns true if arlem exist
function is_arlem_exist($fileid){
    global $DB;
    
    if($DB->get_record('arete_allarlems' , array ('fileid' => $fileid)) !== null)
    {
        return true;
    }
    
    return false;

}


