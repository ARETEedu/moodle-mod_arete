<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

//the variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$filename = filter_input(INPUT_POST, 'filename' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


global $DB;

//if base64 file is exists
if(isset($base64file))
{ 
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_get_files',
        'contextid' => context_system::instance()->id,
        'component' => get_string('component', 'arete'), 
        'filearea' => get_string('filearea', 'arete'), 
        'itemid' => random_int(100000000, 999999999), 
        'filepath' => '/', //should start with / and end with /
        'filename' => $filename ,
        'modified' => null,
        'contextlevel' => null,
        'instanceid' => null,
    );


    $serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );

    //if file is created in user draft filearea, move it to the plugin filearea and delete it from user draft
    if($response == true){
        
  
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
        


    
    
    
