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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__). '/../../../config.php');

//sort the table by ASC or DESC 
$order = filter_input(INPUT_GET, 'order' );
if(!isset($order)){
    $order = "DESC"; 
}

$system_context = context_system::instance()->id;

/**
 * Delete a file from user draft 
 * 
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * @param $WITH_USER_CONTEXT If pass true the user id whome is already logged into Moodle will be used
 * @param $userid Get the contextid of this user
 * 
 */
function deleteUserArlem($filename, $itemid = null , $WITH_USER_CONTEXT = false, $userid = null)
{
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => 'user',
        'filearea' => 'draft',    
        'contextid' => getUserContextid($WITH_USER_CONTEXT, $userid), 
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename); 

    //use itemid too if it is provided
    if(isset($itemid)){
        $fileItemId = $itemid;
    }else{
        $fileItemId = getItemID($fileinfo);
    }
    
    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
            $fileItemId, $fileinfo['filepath'], $fileinfo['filename']);

    // Delete it if it exists
    if ($file) {
        $file->delete();
    }
}



/**
 * Get a single file from plugin filearea by passing filename and item id
 * 
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * 
 * @return The file from file system
 */
function getArlemByName($filename, $itemid)
{

    global $system_context;

    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => get_string('component', 'arete'),  
        'filearea' => get_string('filearea', 'arete'),          
        'contextid' => $system_context, 
        'filepath' => '/',  
        ); 

    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                         $itemid, $fileinfo['filepath'], $filename);

    
    // Read contents
    if ($file) {
        return $file;
    } else {
        return null;
    }
}


/**
 * get the first itemid of the items with this name that become found in user draft
 * @param $fileinfo An array of the available info of the ARLEM file in file system  (like itemid, filename, ect)
 * 
 * @return an array with all info of the ARLEM file in file system
 */
function getItemID($fileinfo){
    global $DB;
    
    $row = $DB->get_records('files', $fileinfo);
    if(!empty($row)){
        $firstRowFound = current($row)->itemid; 
        return $firstRowFound;
    }

    return null;
}


/**
 * 
 * return current user contextid
 * 
 * @param $WITH_USER_CONTEXT If pass true the user id whome is already logged into Moodle will be used
 * @param $userid Get the contextid of this user
 * 
 * @return Contextid of the user
 */
function getUserContextid($WITH_USER_CONTEXT = false, $userid = null){
    global $USER;
    
    if(!isset($userid) && $WITH_USER_CONTEXT == false){  
        $context = context_user::instance($USER->id);
    }else{
        $context = context_user::instance($userid);
    }
    
    $contextid = $context->id;
    
    return $contextid;
}



/**
 * Get the arlem from draft filearea of the current user
 * 
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * 
 * @return The file from user draft area by API if it is exists
 */
function getUserArlem($filename, $itemid = null)
{
    
    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => 'user',     // usually = table name
        'filearea' => 'draft',     // usually = table name
        'contextid' => getUserContextid(), // ID of context
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename); // any filename

    
    //use itemid too if it is provided
    if(isset($itemid)){
        $fileItemId = $itemid;
    }else{
        $fileItemId = getItemID($fileinfo);
    }
    
    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
    $fileItemId , $fileinfo['filepath'], $fileinfo['filename']);

    // Read contents
    if ($file) {
        return $file;
    } else {
        return null;
    }
}


/**
 * Copy ARLEM zip file from file system to temp folder
 * 
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 */
function copyArlemToTemp($filename,  $itemid){

    global $USER;
    // Get file
    $file = getArlemByName($filename, $itemid );

    // Read contents
    if ($file) {
        $file->copy_content_to('temp/'. $USER->id . '/' . $file->get_filename());
    } else {
        // file doesn't exist - do something
    }

}



/**
 * Get an array of all files in allarlems table
 * 
 * @return an array with all ARLEMs for manager, and public and user own files for other users
 */
function getAllArlems($sorting)
{
    global $DB,$USER, $COURSE, $order;
    
    $sortingMode = validate_sorting($sorting);
    
    //course context
    $context = context_course::instance($COURSE->id);
       
    //manager
    if(has_capability('mod/arete:manageall', $context)){

        switch ($sortingMode){
            //author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
            case "author": 
                $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                    . 'JOIN {user} AS u '
                    . 'ON a.userid = u.id '
                    . 'ORDER BY u.username '. $order;
                $files = $DB->get_records_sql($sql); 
                break;
            default:
                //all arlems
                $files = $DB->get_records('arete_allarlems' , null, $sortingMode . ' ' . $order); 
                break;
        }
        
    }else //others
    {
        $params = [1, $USER->id];
        switch ($sortingMode){
            //author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
            case "author":
                $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                    . 'JOIN {user} AS u '
                    . 'ON a.userid = u.id '
                    . 'WHERE a.upublic = ? '
                    . 'OR a.userid = ? '
                    . 'ORDER BY u.username ';
                $files = $DB->get_records_sql($sql . $order, $params); 
                break;
            default:
                $sql = 'upublic = ? OR userid = ? ';
                $files = $DB->get_records_select('arete_allarlems', $sql, $params , $sortingMode . ' ' . $order);
                break;
        }

    }

    return $files;  
}


/**
 * Get an array of all user files in allarlems table
 * 
 * @return an array with all ARLEMs for user
 */
function getAllUserArlems($sorting)
{
    global $DB,$USER,$order;
    
    $sortingMode = validate_sorting($sorting);
    
    switch ($sortingMode){
        //author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
        case "author":
            $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                . 'JOIN {user} AS u '
                . 'ON a.userid = u.id '
                . 'WHERE a.userid = ? '
                . 'ORDER BY u.username ';
            $params = array($USER->id);
            $files = $DB->get_records_sql($sql . $order, $params); 
            break;
        default:
            $files = $DB->get_records('arete_allarlems', array('userid' => $USER->id) , $sortingMode . ' ' . $order);  
            break;
    }

    return $files;  
}



/**
 * 
 * Search the activity and workplace JSONs and activity name for a word
 * @return A list of ArLEm files in allalrem table
 * 
 */
function search_arlems($searchWord, $userSearch, $sorting){
    global $DB,$USER,$order,$COURSE;
    
    $sortingMode = validate_sorting($sorting);
    
    //if it is student activityies table seach only between his/her files
    $userid_if_available = $userSearch ? $USER->id : '';
    
    $searchQuerty = '%'. $searchWord .'%';
    
    //course context
    $context = context_course::instance($COURSE->id);
    
    //All result for the managers
    if(has_capability('mod/arete:manageall', $context)){
        $sql = '(filename LIKE ? OR activity_json LIKE ? OR workplace_json LIKE ?)';
        $params = [$searchQuerty, $searchQuerty, $searchQuerty];
        $results = $DB->get_records_select('arete_allarlems',  $sql, $params, $sortingMode . ' ' . $order);  
    }
    else{
        $params = [1, $userid_if_available, $searchQuerty, $searchQuerty, $searchQuerty];
        switch ($sortingMode){
            //author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
            case "author":
                $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                    . 'JOIN {user} AS u ON a.userid = u.id '
                    . 'WHERE (a.upublic = ? OR a.userid = ?) '
                    . 'AND (filename LIKE ? '
                    . 'OR activity_json LIKE ? '
                    . 'OR workplace_json LIKE ?) '
                    . 'ORDER BY u.username ';
                $results = $DB->get_records_sql($sql . $order, $params); 
                break;
            default:
                $sql = '(upublic = ? OR userid = ?) '
                    . 'AND (filename LIKE ? OR activity_json LIKE ? OR workplace_json LIKE ?)';
                $results = $DB->get_records_select('arete_allarlems', $sql, $params, $sortingMode . ' ' . $order);
                break;
        }  
    }

    
    return $results;
}
    
    
/**
 * Get the ARLEM URL or play link for oopening in WEKIT protocol
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * @param $downloadMode if true the link with http protocol will be return for direct download
 */
function getArlemURL($filename, $itemid, $downloadMode = null)
{
    global $DB;
    $file = getArlemByName($filename, $itemid);
    
    $url = '#';
    if($file){
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(),
                $file->get_filepath(), $file->get_filename(), false);
    }

    $file_in_allarlem = $DB->get_record('arete_allarlems' , array('filename' => $filename, 'itemid' => $itemid));
    
    $path = explode("/" , parse_url($url,PHP_URL_PATH));
    
    if($downloadMode != null){
        return $url;
    }
    else{
        return 'wekit://load?download=' . implode("/" ,array_slice($path, 2)) . '&id=' . $file_in_allarlem->sessionid;
    }

}




/**
 * Get the URL of any file in mod_arete file system
 * @param $file the file from file API system
 */
function GetURL($file){
    $url = '#';
    if($file){
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(),
                $file->get_filepath(), $file->get_filename(), false);
    }

    return $url;
}




/**
 * Delete a file from file system
 * 
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 */
function deletePluginArlem($filename, $itemid = null )
{
    global $DB,$system_context;;
    
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => get_string('component', 'arete'),
        'filearea' => get_string('filearea', 'arete'),    
        'contextid' => $system_context, 
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename); 
    
    
    //use itemid too if it is provided
    if(isset($itemid)){
        $fileItemId = $itemid;
    }else{
        $fileItemId = getItemID($fileinfo);
    }
    
    // Get file
    $file = $fs->get_file($system_context, $fileinfo['component'], $fileinfo['filearea'], 
            $fileItemId, $fileinfo['filepath'], $fileinfo['filename']);

    
    $thumbnail = $fs->get_file($system_context, $fileinfo['component'], 'thumbnail', 
            $fileItemId, $fileinfo['filepath'], 'thumbnail.jpg');
    
    // Delete it if it exists
    if ($file) {

        //delete thumbnail
        if($thumbnail){
            $thumbnail->delete();
        }
        
        //delete it from arete_allarlems table
        if(!empty($DB->get_records('arete_allarlems', array('itemid' => $fileItemId))))
        {
            $DB->delete_records('arete_allarlems', array('itemid' => $fileItemId));
        }
        
        
        //delete rating of this arlem
        if(!empty($DB->get_records('arete_rating', array('itemid' => $fileItemId))))
        {
            $DB->delete_records('arete_rating', array('itemid' => $fileItemId));
        }

        //delete zip file
        $file->delete();
    }
}

/**
 * 
 * Update arete_allarlems table
 * 
 * @param $filename filename of the ARLEM
 * @param $itemid itemid of the ARLEM
 * @param $params an array with the key,value of the columns need to be updated
 */
function updateArlemObject($filename, $itemid, $params){
    
    global $DB;
    $alrem = $DB->get_record('arete_allarlems', array( 'itemid' => $itemid , 'filename' => $filename));


    foreach ($params as $key => $value) {
        $alrem->$key = $value;
    }
    
    $DB->update_record('arete_allarlems' ,$alrem);
}





/**
 * 
 * Get the thumbnail of Arlemfile by itemid
 * 
 * @param $itemid the itemid from allarlem table
 * return thumbnail url and the css class that should use for that thumbnail img
 */
function get_thumbnail($itemid){
    
    global $CFG;
    
        $fs = get_file_storage();
        $thumbnail = $fs->get_file(context_system::instance()->id, get_string('component', 'arete'), 'thumbnail', $itemid, '/', 'thumbnail.jpg');
        //if the thumbnail file exists
       if($thumbnail){
          $thumb_url = moodle_url::make_pluginfile_url($thumbnail->get_contextid(), $thumbnail->get_component(),
                  $thumbnail->get_filearea(), $thumbnail->get_itemid(),
                  $thumbnail->get_filepath(), $thumbnail->get_filename(), false);
          $css = 'ImgThumbnail';
       }else{
           $thumb_url= $CFG->wwwroot.'/mod/arete/pix/no-thumbnail.jpg';
          $css = 'no-thumbnail';
       }

       
       return array($thumb_url, $css);
}


/**
 * Get the owner of this Arlem
 * 
 * @param $arlem the arlem record from allarlem table
 * @param @PAGE the page you are going to use this user info on
 * return the user object and his/her profile photo
 */
function getARLEMOwner($arlem, $PAGE){
    
    global $DB;
    
    if(isset($arlem->userid) ){
       $authoruser = $DB->get_record('user', array('id' => $arlem->userid)); 
    }
    $user_picture=new user_picture($authoruser);
    $src=$user_picture->get_url($PAGE);
    
    return array($authoruser, $src);
    
}


/**
 * Return who assigned this arlem to this course module
 * @param $arlem arlem file from allarlem table
 * @param $moduleid the course module id
 * 
 * return the first and last name of the teacher/manager who assigned this ARLEM to this course module
 */
function get_who_assigned_ARLEM($arlem, $moduleid){
    
    global $DB;
    $teacherId = $DB->get_record('arete_arlem', array('areteid' => $moduleid, 'arlemid' => $arlem->fileid));
    $assignedbyUser = $DB->get_record('user', array('id' => $teacherId->teacherid)); 
    

    
    if(!empty($assignedbyUser)){
        $assignedby = $assignedbyUser->firstname . ' ' . $assignedbyUser->lastname;
    }else{
        $assignedby = get_string('notsetyet', 'arete');
    }

    return $assignedby;
}



/**
 * Upload a custom file to the mod_arete filearea
 * 
 * @param $filepath the local path of the file
 * @param $filename the name of the new file
 * @param $itemid in files table
 * @param $date if you wand update the file you can use the original timecreated
 * 
 */
function upload_custom_file($filepath, $filename, $itemid = null, $date = null){
    
         $fs = get_file_storage();
            
        $context = context_system::instance();
        
        //create a new itemid if it is not proveded
        if($itemid == null){
            $itemid = random_int(100000000, 999999999);
        }
        
        if($date == null){
            $date = time();
        }
        
        $fileinfo = array(
        'contextid'=>$context->id, 
        'component'=> get_string('component', 'arete') ,
        'filearea'=>get_string('filearea', 'arete'),
        'itemid'=> $itemid, 
        'filepath'=>'/',
        'filename'=>$filename,
        'timecreated'=>$date
      );
        
       $newFile = $fs->create_file_from_pathname($fileinfo, $filepath);
       
       return $newFile;
}



/**
 * Delete an ARLEM from file system using session id
 * 
 * @param type $sessionid
 *
 */
function delete_arlem_by_sessionid($sessionid){
    
    global $DB;
          
    $file = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid));
    if(!empty($file)){
        deletePluginArlem($file->filename, $file->itemid);
        $DB->delete_records('arete_allarlems', array('sessionid' => $sessionid));
        return true;
    }
    
    return false;
}




/**
 * Get the number of views on the app
 */
function get_views($itemid){
    global $DB;
    
    $views = $DB->get_record('arete_allarlems', array( 'itemid' => $itemid));
    
    if(!empty($views)){
        return $views->views;
    }
    else{
        return 0;
    }
}



/**
 * validate sorting query
 */
function validate_sorting($sortingMode){
    
    switch ($sortingMode){
        case "filename":
        case "views":
        case "filesize":
        case "timecreated":
        case "timemodified":
        case "rate":
        case "author":
            return $sortingMode;
        default :
            return "timecreated";
    }
}
