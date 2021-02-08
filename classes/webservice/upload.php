<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');

$domainname = 'http://localhost/moodle';

$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$filename = filter_input(INPUT_POST, 'filename' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$base64file = filter_input(INPUT_POST, 'base64' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$userid = filter_input(INPUT_POST, 'userid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


//$token = '6c5e1a9a5827138c2eb6070fe996f880';

$function = "core_files_upload";
$filepath = '/';

$context = context_user::instance($userid);
$contextid = $context->id;

    
if(isset($base64file))
{ 
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => $function,
        'contextid' => $contextid,
        'component' => 'user', // usually = table name
        'filearea' => 'draft', // usually = table name
        'itemid' => random_int(100000000, 999999999), // usually = ID of row in table
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename ,
        'filecontent' => $base64file, // any filename
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );


    $serverurl = $domainname . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );

    if($response == true){
        move_file_from_draft_area_to_arete($filename, $userid, $parameters['itemid'], 1, 'arete', 'arlems', $parameters['itemid']);
        echo $filename. ' Saved.';
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
        


    
    
    
