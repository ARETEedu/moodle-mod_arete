<?php

require_once('../../../../config.php');
//require_once($CFG->dirroot.'/lib/filelib.php');

$domainname = 'http://localhost/moodle';

$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$base64file = filter_input(INPUT_POST, 'base64' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

global $USER;

//$token = '6c5e1a9a5827138c2eb6070fe996f880';
$filename ='img.png';

$function = "core_files_upload";
$filepath = '/';


if(isset($base64file))
{


    $filename = 'testi.png';
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => $function,
        'component' => 'user', // usually = table name
        'filearea' => 'draft', // usually = table name
        'itemid' => 0, // usually = ID of row in table
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename,
        'filecontent' => $base64file, // any filename
        'contextlevel' => 'user',
        'instanceid' => $USER->id,
    );

    $myfile = fopen("base64.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $parameters['filecontent']);
    fclose($myfile);

//    print_r($parameters)  ;exit();
    $serverurl = $domainname . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );


    print_r($response);
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
        



