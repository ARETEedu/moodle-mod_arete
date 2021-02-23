<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

class MoodleWebService
{
    var $token;
    var $service = 'aretews';
    var $domain;
    
    function  __construct($domain){
        $this->domain = $domain;
        
    }


    //request token for the user and return token if is availble
    function requestToken($username, $password)
    {

        $response = httpPost($this->domain . '/login/token.php' , array('username' => $username, 'password'=> $password ,'service' => $this->service) );

        $this->token = json_decode($response)->{'token'};
        
        return $this->getToken();
    }
    
    //return the token of the user
    function getToken()
    {
        if(isset($this->token) && $this->token != '')
        {
            return $this->token;
        }else
        {
            return '';
        }
    }
}
