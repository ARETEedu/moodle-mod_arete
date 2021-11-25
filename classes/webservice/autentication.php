<?php
// This file is part of the Augmented Reality Experience plugin (mod_arete) for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

class Autentication
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
