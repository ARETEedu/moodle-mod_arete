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
 * Gets the files from Unity to upload to the Moodle and
 * creating database records
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once("{$CFG->dirroot}/mod/arete/classes/utilities.php");

//The variables which  are passed from Unity application
$token = required_param('token', PARAM_RAW);
$userid = required_param('userid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_RAW);
$title = optional_param('title', null, PARAM_RAW);
$public = optional_param('public', 0, PARAM_INT);
$updatefile = required_param('updatefile', PARAM_INT);
$activityjson = required_param('activity', PARAM_RAW);
$workplacejson = required_param( 'workplace', PARAM_RAW);


//If the file exist 
if (mod_arete_is_sessionid_exist($sessionid)) {
    if ($updatefile == '0') { // If the user still user not confirmed updating of the file (On MirageXR)

        //If the user is owner of the file,
        //the user needs to decide that the file should be updated or cloned
        if (mod_arete_is_user_owner_of_file($userid, $sessionid)) {
            echo 'Error: File exist, update'; //Will be checked on the app, therefore needs to be hardcoded
        } else {
            echo 'Error: File exist, clone'; //Will be checked on the app, therefore needs to be hardcoded
        }
    } else { // the user confirmed what should be done on this file
        mod_arete_process(); 
    }
} else { // File not exsit on the server at all, then just save it as a new file
    mod_arete_process();
}

/**
 * After checking the file existancy and ownership do the uploading
 * It will send another request to upload.php
 * @global object $CFG The Moodle config object
 * @global string $token The user token
 * @global int $userid The user id
 * @global string $sessionid The activity id
 * @global int $public The privacy status of the file
 * @global int $updatefile The status of the user decision what should be done on the file
 *          0: not decided yet, 1: update, any other number: clone
 * @global string $activityjson The JSON string of the activity 
 * @global string $workplacejson The JSON string of the workplace 
 * @global string $title The title of the activity
 */
function mod_arete_process() {

    global $CFG, $token, $userid, $sessionid, $public, $updatefile, $activityjson, $workplacejson, $title;

    //If the file is received from Unity application
    if (isset($_FILES['myfile'])) {

        $file = $_FILES['myfile']['tmp_name'];

        //Convert the file to base64 string
        $filebase64 = base64_encode(file_get_contents($file));

        //Get the thumbnail
        $thumbbase64 = '';
        if (isset($_FILES['thumbnail'])) {
            $thumbnail = $_FILES['thumbnail']['tmp_name'];

            $thumbbase64 = base64_encode(file_get_contents($thumbnail)); //Convert the thumbnail  to base64 string
        }

        //Check public key if exist and is true
        if (isset($public) && $public == 1) {
            $publicuploadprivacy = 1;
        } else {
            $publicuploadprivacy = 0;
        }

        $data = array(
            'base64' => $filebase64,
            'token' => $token,
            'title' => $title,
            'userid' => $userid,
            'sessionid' => $sessionid,
            'thumbnail' => $thumbbase64,
            'public' => $publicuploadprivacy,
            'updatefile' => $updatefile,
            'activity' => $activityjson,
            'workplace' => $workplacejson
        );

        $curlhandle = curl_init("{$CFG->wwwroot}/mod/arete/classes/webservice/upload.php");
        curl_setopt($curlhandle, CURLOPT_POST, true);
        curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlhandle);

        if ($response == true) {
            echo $response;

        } else {
            echo 'Error: ' . curl_error($curlhandle);
        }

        curl_close($curlhandle);
    } else {
        //The text will be used on the webservice app, therefore it is hardcoded
        echo '[error] there is no data with name [myfile]';
    }
}
