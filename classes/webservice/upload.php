<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

//the variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token');
$filename = filter_input(INPUT_POST, 'filename' ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sessionid = filter_input(INPUT_POST, 'sessionid',FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$base64file = filter_input(INPUT_POST, 'base64');
$userid = filter_input(INPUT_POST, 'userid');
$thumbnail = filter_input(INPUT_POST, 'thumbnail');
$public = filter_input(INPUT_POST, 'public');

$context = context_user::instance($userid);
$contextid = $context->id;

global $DB;


//if base64 file is exists
if(isset($base64file))
{ 
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_upload',
        'contextid' => $contextid,
        'component' => 'user', 
        'filearea' => 'draft', 
        'itemid' => random_int(100000000, 999999999), 
        'filepath' => '/', //should start with / and end with /
        'filename' => $filename ,
        'filecontent' => $base64file, 
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    $serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );

    //if file is created in user draft filearea, move it to the plugin filearea and delete it from user draft
    if($response == true){
        
        //move it to the plugin filearea
        move_file_from_draft_area_to_arete( $userid, $parameters['itemid'], context_system::instance()->id , get_string('component', 'arete'), get_string('filearea', 'arete'), $parameters['itemid']);

        //if file is created in plugin filearea
        if(getArlemByName($filename, $parameters['itemid']) !== null)
        {
            
            //delete file and the empty folder from user file area
            deleteUserArlem($filename, $parameters['itemid'], true, $userid);
            deleteUserArlem('.', $parameters['itemid'], true, $userid);
            echo $filename. ' Saved.';
            
            //add thumbnail to DB
            upload_thumbnail($contextid,$parameters['itemid'] );
            
            ///insert data to arete_allarlems table
            $arlemdata = new stdClass();
            $arlemdata->fileid = getArlemByName($filename, $parameters['itemid'])->get_id();
            $arlemdata->contextid =  context_system::instance()->id;
            $arlemdata->userid =  $userid;
            $arlemdata->itemid =  $parameters['itemid'];
            $arlemdata->sessionid = $sessionid;
            $arlemdata->filename = $filename;
            $arlemdata->filesize = (int) (strlen(rtrim($base64file, '=')) * 3 / 4);
            $arlemdata->upublic =  (int) $public;
            $arlemdata->timecreated = time();
            $arlemdata->timemodified = 0;
            $DB->insert_record('arete_allarlems', $arlemdata);

        }
        
    }

}


/*
 * 
 * Add thumbnail to the thumbnail filearea
 */
function upload_thumbnail($contextid,$itemid){
    
    global $token ,$CFG,$thumbnail, $userid;

    $parameters = array(
    'wstoken' => $token,
    'wsfunction' => 'core_files_upload',
    'contextid' => $contextid,
    'component' => 'user', 
    'filearea' => 'draft', 
    'itemid' => $itemid, 
    'filepath' => '/', //should start with / and end with /
    'filename' => 'thumbnail.jpg' ,
    'filecontent' => $thumbnail, 
    'contextlevel' => 'user',
    'instanceid' => $userid,
    );
    
    $serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );
    
    if($response == true){
        //move it to the plugin filearea
        move_file_from_draft_area_to_arete( $userid, $parameters['itemid'], context_system::instance()->id , get_string('component', 'arete'), 'thumbnail', $parameters['itemid']);
        
        //delete file and the empty folder from user file area
        deleteUserArlem('thumbnail.jpg', $parameters['itemid'], true, $userid);
        deleteUserArlem('.', $parameters['itemid'], true, $userid);
        
    }
    
}
    