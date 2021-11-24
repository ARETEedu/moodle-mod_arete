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
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

//the variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token');
$title = filter_input(INPUT_POST, 'title');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$base64file = filter_input(INPUT_POST, 'base64');
$userid = filter_input(INPUT_POST, 'userid');
$thumbnail = filter_input(INPUT_POST, 'thumbnail');
$public = filter_input(INPUT_POST, 'public');
$updatefile = filter_input(INPUT_POST, 'updatefile');
$activityJson = filter_input(INPUT_POST, 'activity');
$workplaceJson = filter_input(INPUT_POST, 'workplace');

$context = context_user::instance($userid);
$contextid = $context->id;

global $DB;

//if base64 file is exists
if(isset($base64file))
{ 
    $itemid = random_int(100000000, 999999999);
    $timemodifeid = 0;
    $timecreated = time();   
    $filename = $sessionid . '.zip';
    
   //store info of the old file and delete it
   if($updatefile == '1') {
       
      $arlem = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid ));
      $itemid = $arlem->itemid;
      $fileid = $arlem->fileid;
      $filename = $arlem->sessionid. '.zip';
      $oldfile_delete = delete_arlem_by_sessionid($sessionid);
      $timemodifeid = time();
      $timecreated = $arlem->timecreated;
      
      //if unable to delete the old file
      if($oldfile_delete != true){
          echo "Cannot delete old file";
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
        'filepath' => '/', //should start with / and end with /
        'filename' => $filename ,
        'filecontent' => $base64file, 
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    //upload file to user draft
    $serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );

    //if file is created in user draft filearea, move it to the plugin filearea and delete it from user draft
    if($response == true){
        
        //move it to the plugin filearea
        move_file_from_draft_area_to_arete( $userid, $parameters['itemid'], context_system::instance()->id , get_string('component', 'arete'), get_string('filearea', 'arete'), $parameters['itemid']);

        //if file is created in plugin filearea
        if(getArlemByName($filename, $parameters['itemid']) !== null)
        {
            
            //delete file and the empty folder from user file area
            deleteUserArlem($filename, $parameters['itemid'], true, $userid);
            deleteUserArlem('.', $parameters['itemid'], true, $userid);
            echo $filename. ' Saved.';
            
            //add thumbnail to DB
            if(isset($thumbnail) && $thumbnail != ''){
                upload_thumbnail($contextid,$parameters['itemid'] );
            }

            
            ///insert data to arete_allarlems table
            $arlemdata = new stdClass();
            $arlemdata->fileid = isset($fileid) ? $fileid : getArlemByName($filename, $parameters['itemid'])->get_id();
            $arlemdata->contextid =  context_system::instance()->id;
            $arlemdata->userid =  $userid;
            $arlemdata->itemid =  $parameters['itemid'];
            $arlemdata->sessionid = $sessionid;
            $arlemdata->filename = $filename;
            $arlemdata->title = $title;
            $arlemdata->filesize = (int) (strlen(rtrim($base64file, '=')) * 3 / 4);
            $arlemdata->upublic =  (int) $public;
            $arlemdata->activity_json = $activityJson;
            $arlemdata->workplace_json = $workplaceJson;
            $arlemdata->timecreated = $timecreated;
            $arlemdata->timemodified = $timemodifeid;
            $DB->insert_record('arete_allarlems', $arlemdata);

        }
        
    }

}


/*
 * 
 * Add thumbnail to the thumbnail filearea
 */
function upload_thumbnail($contextid,$itemid){
    
    global $token ,$CFG,$thumbnail, $userid;

    $parameters = array(
    'wstoken' => $token,
    'wsfunction' => 'core_files_upload',
    'contextid' => $contextid,
    'component' => 'user', 
    'filearea' => 'draft', 
    'itemid' => $itemid, 
    'filepath' => '/', //should start with / and end with /
    'filename' => 'thumbnail.jpg' ,
    'filecontent' => $thumbnail, 
    'contextlevel' => 'user',
    'instanceid' => $userid,
    );
    
    $serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
    $response = httpPost($serverurl , $parameters );
    
    if($response == true){
        //move it to the plugin filearea
        move_file_from_draft_area_to_arete( $userid, $parameters['itemid'], context_system::instance()->id , get_string('component', 'arete'), 'thumbnail', $parameters['itemid']);
        
        //delete file and the empty folder from user file area
        deleteUserArlem('thumbnail.jpg', $parameters['itemid'], true, $userid);
        deleteUserArlem('.', $parameters['itemid'], true, $userid);
        
    }
    
}
    

