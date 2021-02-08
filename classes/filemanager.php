<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__). '/../../../config.php');



///create a textfile in arete filearea (used mainly for testing
function createArlem($filename, $filetext, $context){

    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'contextid' => $context->id, // ID of context
        'component' => 'arete',     // usually = table name
        'filearea' => 'arlems',     // usually = table name
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



//return a single file from plugin filearea
function getArlem($filename)
{
    
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => 'arete',     // usually = table name
        'filearea' => 'arlems',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'contextid' => 1, // ID of context
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename); // any filename

    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                          $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

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
function copyArlemToTemp($filename, $context){

    // Get file
    $file = getArlem($filename,$context );

    // Read contents
    if ($file) {
        $file->copy_content_to('temp/' . $file->get_filename());
    } else {
        // file doesn't exist - do something
    }

}


///check if this file is exist
function isArlemExist($filename, $context )
{

    if(getArlem($filename, $context)){
        return true;
    }else{
        return false;
    }

}


//get an array of all files in plug in filearea
function getAllArlems($emptyFiles = false)
{
    $fs = get_file_storage();

    $files = $fs->get_area_files( 1 , 'arete', 'arlems', false, 'sortorder', $emptyFiles);
    
    return $files;  
}


///get the file url
function getArlemURL($filename, $context)
{
    $file = getArlem($filename, $context);
    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);

    return $url;
}



 function urlByHash($contenthash){
    
     $l1 = $contenthash[0].$contenthash[1];
     $l2 = $contenthash[2].$contenthash[3];
     
     print_r("/$l1/$l2");
   
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
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => 'arete',
        'filearea' => 'arlems',    
        'contextid' => 1, 
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $filename); 

    //use itemid too if it is provided
    if(isset($itemid)){
        $fileItemId = $itemid;
    }else{
        $fileItemId = getItemID($fileinfo);
    }
    
    // Get file
    $file = $fs->get_file(1, $fileinfo['component'], $fileinfo['filearea'], 
            $fileItemId, $fileinfo['filepath'], $fileinfo['filename']);

    // Delete it if it exists
    if ($file) {
        $file->delete();
    }
}