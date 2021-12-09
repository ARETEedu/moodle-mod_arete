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
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');

//the variables which  are passed from Unity application
$token = filter_input(INPUT_POST, 'token');
$userid = filter_input(INPUT_POST, 'userid');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$title = filter_input(INPUT_POST, 'title');
$public = filter_input(INPUT_POST, 'public');
$updatefile = filter_input(INPUT_POST, 'updatefile');
$activityjson = filter_input(INPUT_POST, 'activity');
$workplacejson = filter_input(INPUT_POST, 'workplace');


//if file exist and still user not confirmed updating of the file
if (mod_arete_is_sessionid_exist($sessionid)) {
    if ($updatefile == '0') {

        //if the user is owner of the file update otherwise clone
        if (mod_arete_is_user_owner_of_file($userid, $sessionid)) {
            echo 'Error: File exist, update';
        } else {
            echo 'Error: File exist, clone';
        }
    } else { //update or clone
        mod_arete_process();
    }
} else { // file not exsit at all
    mod_arete_process();
}

/**
 * After checking the file existancy and ownership do the uploading
 * @global type $CFG
 * @global type $token
 * @global type $userid
 * @global type $sessionid
 * @global type $public
 * @global type $updatefile
 * @global type $activityjson
 * @global type $workplacejson
 * @global type $title
 */
function mod_arete_process() {

    global $CFG, $token, $userid, $sessionid, $public, $updatefile, $activityjson, $workplacejson, $title;

    //if the file is received from Unity application
    if (isset($_FILES['myfile'])) {

        $file = $_FILES['myfile']['tmp_name'];

        //convert the file to base64 string
        $filebase64 = base64_encode(file_get_contents($file));

        //To get file extension
        //$fileExt = pathinfo($img, PATHINFO_EXTENSION) ;
        //Get the thumbnail
        $thumbbase64 = '';
        if (isset($_FILES['thumbnail'])) {
            $thumbnail = $_FILES['thumbnail']['tmp_name'];
            //convert the thumbnail  to base64 string
            $thumbbase64 = base64_encode(file_get_contents($thumbnail));
        }

        //check public key if exist and is true
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

        $curlhandle = curl_init($CFG->wwwroot . '/mod/arete/classes/webservice/upload.php');
        curl_setopt($curlhandle, CURLOPT_POST, true);
        curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, true);


        $response = curl_exec($curlhandle);

        if ($response == true) {
            echo $response;

            //OR move the actual file to the destination
            //    move_uploaded_file($tmpimg, $destination . $img );
        } else {
            echo 'Error: ' . curl_error($curlhandle);
        }

        curl_close($curlhandle);
    } else {
        //The text will be used on the webservice app, therefore it is hardcoded
        echo "[error] there is no data with name [myfile]";
        exit();
    }
}
