<?php

    require_once(dirname(__FILE__). '/../../../../config.php');
    
    $request = filter_input(INPUT_POST, 'request' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $fileid = filter_input(INPUT_POST, 'fileid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $itemid = filter_input(INPUT_POST, 'itemid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    

    //what we need to send back to Unity
    switch ($request){
        case "arlemlist":
            get_arlem_list();
            break;
        default:
            print_r('Error: request is NULL');
            break;
    }
    
    
    
    function get_arlem_list(){
        global $DB;
        $arlems = $DB->get_records('arete_allarlems', null , 'timecreated DESC');
        print_r(json_encode($arlems));   
    }
