<?php

class MoodleWebService
{
    var $token;
    var $domain = 'http://localhost/';
    var $service = 'arlem';
    
    
    //send a post request
    function httpPost($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    
    //request token for the user and return token if is availble
    function requestToken($username, $password)
    {
        $response = $this->httpPost($this->domain . 'moodle/login/token.php' , array('username' => $username, 'password'=> $password ,'service' => $this->service) );
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
