<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/lib/filelib.php');

$domainname = 'http://localhost/moodle';

$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$function = filter_input(INPUT_POST, 'function' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$parameters = filter_input(INPUT_POST, 'parameters' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


//split the unity parameters for all user parameter

//multiply parameter
if( strpos($parameters , '&') !== false){
    $params = explode('&' , $parameters );
    foreach ($params as $param) {
        if(strpos($param , '=') !== false){
            $keyValues = list($key, $value) = explode( '=' , $param);
            $parametersArray[$key] = $value ;
        }
    }
}else //single parameter
{
    if(strpos($parameters , '=') !== false){
            $keyValues = list($key, $value) = explode( '=' , $parameters);
            $parametersArray[$key] = $value ;
    }
}


/// REST CALL
$serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token .  '&moodlewsrestformat=json' .  '&wsfunction='. $function;

$curl = new curl;


$response = $curl->post($serverurl , $parametersArray);

$jsonResult = json_decode($response, true);


print_r($jsonResult);