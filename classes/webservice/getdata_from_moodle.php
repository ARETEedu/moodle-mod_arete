<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/lib/filelib.php');


//the variables which  are passed from Unity application
$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$function = filter_input(INPUT_POST, 'function' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$parameters = filter_input(INPUT_POST, 'parameters' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$requestedInfo = filter_input(INPUT_POST, 'request' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


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
$serverurl = $CFG->wwwroot . '/webservice/rest/server.php'. '?wstoken=' . $token .  '&moodlewsrestformat=json' .  '&wsfunction='. $function;

$curl = new curl;
$response = $curl->post($serverurl , $parametersArray);
$jsonResult = json_decode($response, true);


//what we need to send back to Unity
switch ($requestedInfo){
    case "userid":
        print_r(current($jsonResult)[0]['id']);
        break;
    case "mail":
        print_r(current($jsonResult)[0]['email']);
        break;
    default:
        print_r($jsonResult);
        break;
}


