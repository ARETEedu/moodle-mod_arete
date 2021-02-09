<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

//the variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$filename = filter_input(INPUT_POST, 'filename' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$base64file = filter_input(INPUT_POST, 'base64' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$userid = filter_input(INPUT_POST, 'userid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

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

        move_file_from_draft_area_to_arete( $userid, $parameters['itemid'], 1, 'arete', 'arlems', $parameters['itemid']);
        
        //if file is created in plugin filearea
        if(getArlemByName($filename, $parameters['itemid']) !== null)
        {
            ///insert data to arete_allarlems table
            $arlemdata = new stdClass();
            $arlemdata->fileid = getArlemByName($filename, $parameters['itemid'])->get_id();
            $arlemdata->userid =  $userid;
            $arlemdata->itemid =  $parameters['itemid'];
            $arlemdata->timecreated = time();
            $DB->insert_record('arete_allarlems', $arlemdata);
            
            
            //delete file and the rmpty folder from user file area
            deleteUserArlem($filename, $parameters['itemid'], true, $userid);
            deleteUserArlem('.', $parameters['itemid'], true, $userid);
            echo $filename. ' Saved.';
        }
        
    }

}

    /// REST CALL
    //send a post request
    function httpPost($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
        


    
    
    
