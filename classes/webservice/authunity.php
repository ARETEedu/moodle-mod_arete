<?php

require_once 'MoodleWebService.php';

$username = filter_input(INPUT_POST, 'username' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$password = filter_input(INPUT_POST, 'password');
//$username = required_param('username', PARAM_USERNAME);
//$password = required_param('password', PARAM_RAW);

$MoodleWebService = new MoodleWebService();
$token = $MoodleWebService->requestToken($username, $password);        

if(isset($token) && $token != '')
{
   echo "succeed" . ',' . $token; 
}
else
{
    echo ("User login faild");
}

exit();



