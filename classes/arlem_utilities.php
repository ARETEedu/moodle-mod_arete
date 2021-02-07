<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

    require_once(dirname(__FILE__). '/../../../config.php');
    
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
