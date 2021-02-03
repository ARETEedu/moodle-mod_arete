<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__). '/../../../config.php');

function createArlem($filename, $filetext, $context){

    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'contextid' => $context->id, // ID of context
        'component' => 'mod_arete',     // usually = table name
        'filearea' => 'arete_arlems',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'filepath' => '/arete/',           // any path beginning and ending in /
        'filename' => $filename  ); // any filename

    // Create file containing text 'hello world'
    $fs->create_file_from_string($fileinfo, $filetext);
}


//delete a file
function deleteArlem($filename, $context)
{
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => 'mod_arete',
        'filearea' => 'arete_arlems',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'contextid' => $context->id, // ID of context
        'filepath' => '/arete/',           // any path beginning and ending in /
        'filename' => $filename); // any filename

    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

    // Delete it if it exists
    if ($file) {
        $file->delete();
    }
}



//return a single file
function getArlem($filename, $context)
{
    
    $fs = get_file_storage();
 
    // Prepare file record object
    $fileinfo = array(
        'component' => 'mod_arete',     // usually = table name
        'filearea' => 'arete_arlems',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'contextid' => $context->id, // ID of context
        'filepath' => '/arete/',           // any path beginning and ending in /
        'filename' => $filename); // any filename

    // Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                          $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

    // Read contents
    if ($file) {
        return $file;
    } else {
        // file doesn't exist - do something
    }
}


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



function isArlemExist($filename, $context )
{

    if(getArlem($filename, $context)){
        return true;
    }else{
        return false;
    }

}


//get an array of all files in this filearea
function getAllArlems($context)
{
    $fs = get_file_storage();
    
    $files = $fs->get_area_files($context->id, 'mod_arete', 'arete_arlems', false, 'sortorder', false);
    
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