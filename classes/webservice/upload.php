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
 * Adding the file to the Moodle file system and arete plugin filearea
 * and create all needed records on the database
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once("{$CFG->dirroot}/mod/arete/classes/move_arlem_from_draft.php");
require_once("{$CFG->dirroot}/mod/arete/classes/filemanager.php");
require_once("{$CFG->dirroot}/mod/arete/classes/utilities.php");

defined('MOODLE_INTERNAL') || die;

//The variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token');
$title = filter_input(INPUT_POST, 'title');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$base64file = filter_input(INPUT_POST, 'base64');
$userid = filter_input(INPUT_POST, 'userid');
$thumbnail = filter_input(INPUT_POST, 'thumbnail');
$public = filter_input(INPUT_POST, 'public');
$updatefile = filter_input(INPUT_POST, 'updatefile');
$activityjson = filter_input(INPUT_POST, 'activity');
$workplacejson = filter_input(INPUT_POST, 'workplace');

$context = context_user::instance($userid);
$contextid = $context->id;

global $DB;

//If base64 file is exists
/**
 * @param $url
 * @return string
 */
function format_thumbnail_url_for_table($url): string
{
    $partial_url_array = explode('/', $url->__toString());
    $partial_url_array[sizeof($partial_url_array) - 1] = 'thumbnail.jpg';
    $lower_limit = sizeof($partial_url_array) - 5;
    $higher_limit = sizeof($partial_url_array);
    $partial_url = implode('/',
        array_slice($partial_url_array, $lower_limit, $higher_limit));
    return $partial_url;
}

if (isset($base64file)) {
    $itemid = random_int(100000000, 999999999);
    $timemodifeid = 0;
    $timecreated = time();
    $filename = $sessionid . '.zip';

    //Store info of the old file and delete it
    if ($updatefile == '1') {

        $arlem = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid));
        $itemid = $arlem->itemid;
        $fileid = $arlem->fileid;
        $filename = "{$arlem->sessionid}.zip";
        $oldfiledelete = mod_arete_delete_arlem_by_sessionid($sessionid);
        $timemodifeid = time();
        $timecreated = $arlem->timecreated;

        //If unable to delete the old file
        if ($oldfiledelete != true) {
            //Will be checked on the app,therefore needs to be hardcoded
            echo 'Cannot delete old file';
            die;
        }
    }

    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_upload',
        'contextid' => $contextid,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $itemid,
        'filepath' => '/', //Should start with / and end with /
        'filename' => $filename,
        'filecontent' => $base64file,
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    //Upload the file to user draft
    $serverurl = "{$CFG->wwwroot}/webservice/rest/server.php";
    $response = mod_arete_httpPost($serverurl, $parameters);

    //If the file is created in user draft filearea, move it to the plugin filearea and delete it from user draft
    if ($response == true) {

        //Move it to the plugin filearea
        move_file_from_draft_area_to_arete($userid, $parameters['itemid'], context_system::instance()->id,
                get_string('component', 'arete'), get_string('filearea', 'arete'), $parameters['itemid']);

        //If the file is created in plugin filearea
        if (mod_arete_get_arlem_by_name($filename, $parameters['itemid']) !== null) {

            //Delete file and the empty folder from user file area
            mod_arete_delete_user_arlem($filename, $parameters['itemid'], true, $userid);
            mod_arete_delete_user_arlem('.', $parameters['itemid'], true, $userid);
            echo "{$filename} Saved.";

            $url = '';
            //Add the thumbnail to the DB
            if (isset($thumbnail) && $thumbnail != '') {
                $url = mod_arete_upload_thumbnail($contextid, $parameters['itemid']);
            }

            //Insert data to arete_allarlems table
            $arlemdata = new stdClass();
            $arlemdata->fileid = isset($fileid) ? $fileid : mod_arete_get_arlem_by_name($filename, $parameters['itemid'])->get_id();
            $arlemdata->contextid = context_system::instance()->id;
            $arlemdata->userid = $userid;
            $arlemdata->itemid = $parameters['itemid'];
            $arlemdata->sessionid = $sessionid;
            $arlemdata->filename = $filename;
            $arlemdata->title = $title;
            $arlemdata->filesize = (int) (strlen(rtrim($base64file, '=')) * 3 / 4);
            $arlemdata->upublic = (int) $public;
            $arlemdata->activity_json = $activityjson;
            $arlemdata->workplace_json = $workplacejson;
            $arlemdata->timecreated = $timecreated;
            $arlemdata->timemodified = $timemodifeid;
            if (isset($thumbnail) && $thumbnail != '') {
                $partial_url = format_thumbnail_url_for_table($url);
                $arlemdata->thumbnail = $partial_url;
            }
            $DB->insert_record('arete_allarlems', $arlemdata);
        }
    }
}

/**
 * Add thumbnail to the thumbnail filearea
 * @global string $token The user token
 * @global object $CFG The Moodle config object
 * @global string $thumbnail The base64 string
 * @global int $userid  The user id
 * @param int $contextid The context id
 * @param int $itemid The item id of the file in arete_allarlems table
 */
function mod_arete_upload_thumbnail($contextid, $itemid) {

    global $token, $CFG, $thumbnail, $userid;

    $result = mod_arete_upload_thumbnail_all_parameters($token, $contextid, $itemid, $thumbnail, $userid, $CFG);

    return $result['url'];
}

/**
 * Helper method that uploads thumbnail
 *
 * @param $token The token of the user whose id is passed
 * @param int $contextid The context id of the arlem
 * @param int $itemid the itemid of the arlem
 * @param $thumbnail the thumbnail daata
 * @param $userid The id of the user
 * @param $CFG the CFG
 * @return result array
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_arete_upload_thumbnail_all_parameters($token, int $contextid, int $itemid, $thumbnail, $userid, $CFG)
{
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_upload',
        'contextid' => $contextid,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $itemid,
        'filepath' => '/', //Should start with / and end with /
        'filename' => 'thumbnail.jpg',
        'filecontent' => $thumbnail,
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    $serverurl = "{$CFG->wwwroot}/webservice/rest/server.php";
    $response = mod_arete_httpPost($serverurl, $parameters);

    if ($response == true) {
        //Move it to the plugin filearea
        $result = move_file_from_draft_area_to_arete($userid, $parameters['itemid'], context_system::instance()->id,
            get_string('component', 'arete'), 'thumbnail', $parameters['itemid']);

        //Delete file and the empty folder from user file area
        mod_arete_delete_user_arlem('thumbnail.jpg', $parameters['itemid'], true, $userid);
        mod_arete_delete_user_arlem('.', $parameters['itemid'], true, $userid);
    }
    return $result;
}
