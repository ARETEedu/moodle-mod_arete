<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__). '/../../../config.php');

$system_context = context_system::instance()->id;

///create a textfile in arete filearea (used mainly for testing
function createArlem($filename, $filetext, $context){

    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'contextid' => $context->id, // ID of context
        'component' => get_string('component', 'arete'),     // usually = table name
        'filearea' => get_string('filearea', 'arete'),     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename  ); // any filename

    // Create file containing text 'hello world'
    $fs->create_file_from_string($fileinfo, $filetext);
}



//delete a file from user draft 
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



//return a single file from plugin filearea by passing filename and item id
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


///get the first itemid of the items with this name that become found in user draft
function getItemID($fileinfo){
    global $DB;
    
    $row = $DB->get_records('files', $fileinfo);
    if(isset($row)){
        $firstRowFound = current($row)->itemid; 
        return $firstRowFound;
    }

    return null;
}


///return current user contextid
//$WITH_USER_CONTEXT pass true the user id whome is already logged into Moodle will be used
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



//get the arlem from draft filearea of the current user
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


//copy file to temp folder
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



//get an array of all files in plug in filearea
function getAllArlems()
{
//    $fs = get_file_storage();
//
//    $files = $fs->get_area_files( 1 , get_string('component', 'arete'), get_string('filearea', 'arete'), false, 'timecreated DESC ', $emptyFiles);
    global $DB,$USER, $COURSE;
    
    //course context
    $context = context_course::instance($COURSE->id);
       
    //manager
    if(has_capability('mod/arete:manageall', $context)){
            $files = $DB->get_records('arete_allarlems' , null, 'timecreated DESC'); //all arlems
    }else //others
    {
           $files = $DB->get_records_select('arete_allarlems', 'upublic = 1 OR userid = ' . $USER->id  , null, 'timecreated DESC');  //only public and for the user
    }

    return $files;  
}


///get the file url
function getArlemURL($filename, $itemid)
{
    $file = getArlemByName($filename, $itemid);

    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
    return $url;
}



///for test remove later
//get an array of all files in plug in filearea
function getAllUserArlems( $WITH_USER_CONTEXT = false, $userid = null , $emptyFiles = false)
{
    $fs = get_file_storage();

    $files = $fs->get_area_files( getUserContextid($WITH_USER_CONTEXT,$userid) , 'user', 'draft', false, 'sortorder', $emptyFiles);
    
    return $files;  
}
///

//delete a file from plugin filearea
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


    // Delete it if it exists
    if ($file) {
        $id = $file->get_id();
        
        $file->delete();
        
        //delete it from arete_allarlems table
        if($DB->get_records('arete_allarlems', array('fileid' => $id)) !== null)
        {
            $DB->delete_records('arete_allarlems', array('fileid' => $file->get_id()));
        }

    }
}

/***
 * 
 * update arete_allarlems table
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


