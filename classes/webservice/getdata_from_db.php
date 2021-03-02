<?php

    require_once(dirname(__FILE__). '/../../../../config.php');
    
    $request = filter_input(INPUT_POST, 'request');
    $fileid = filter_input(INPUT_POST, 'fileid');
    $itemid = filter_input(INPUT_POST, 'itemid');
    $userid = filter_input(INPUT_POST, 'userid');

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
        global $DB,$userid;
        
        if(isset($userid)){
            $arlems =  $DB->get_records_select('arete_allarlems', 'upublic = 1 OR userid = ' . $userid  , null, 'timecreated DESC');  //only public and for the user
        }else{
            $arlems =  $DB->get_records('arete_allarlems' , array('upublic' => 1 ), 'timecreated DESC');  //only public and for the user
        }
        
        print_r(json_encode($arlems));   
    }
