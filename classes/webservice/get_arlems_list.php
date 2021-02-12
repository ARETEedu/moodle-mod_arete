<?php

    require_once(dirname(__FILE__). '/../../../../config.php');
    
    $userid = filter_input(INPUT_POST, 'userid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $context = CONTEXT_USER::instance($userid);
    
    if(!has_capability('mod/arete:view', $context))
    {
        print_r('You should login first!') ;
        return false;
    }

    global $DB;

    $arlems = $DB->get_records('files', array('contextid' => context_system::instance()->id, 'component' => get_string('component', 'arete'), 'filearea' =>  get_string('filearea', 'arete')));

    print_r(json_encode($arlems));