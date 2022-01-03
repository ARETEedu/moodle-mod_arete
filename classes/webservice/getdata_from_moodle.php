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
 * Sending information from Moodle to Unity
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\webservices;

require_once('../../../../config.php');
require_once("{$CFG->dirroot}/lib/filelib.php");

//the variables which  are passed from Unity application
$token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$function = filter_input(INPUT_POST, 'function', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$parameters = filter_input(INPUT_POST, 'parameters', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$requestedinfo = filter_input(INPUT_POST, 'request', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


//Split the unity parameters for all user parameter
if (strpos($parameters, '&') !== false) { //For multiply parameters
    $params = explode('&', $parameters);
    foreach ($params as $param) {
        if (strpos($param, '=') !== false) {
            $keyValues = list($key, $value) = explode('=', $param);
            $parametersarray[$key] = $value;
        }
    }
} else { //For single parameter
    if (strpos($parameters, '=') !== false) {
        $keyValues = list($key, $value) = explode('=', $parameters);
        $parametersarray[$key] = $value;
    }
}


// REST CALL
$serverurl = "{$CFG->wwwroot}/webservice/rest/server.php?wstoken={$token}&moodlewsrestformat=json&wsfunction={$function}";

$curl = new \curl;
$response = $curl->post($serverurl, $parametersarray);
$jsonresult = json_decode($response, true);


//Check what unity needs and send it back to Unity
switch ($requestedinfo) {
    case 'userid':
        print_r(current($jsonresult)[0]['id']);
        break;
    
    case 'mail':
        print_r(current($jsonresult)[0]['email']);
        break;
    
    default:
        print_r($jsonresult);
        break;
}