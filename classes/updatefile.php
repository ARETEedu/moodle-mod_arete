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
 * Update the file with the changes which applied to the file on the edit page
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

use context_system,
    ZipArchive,
    RecursiveIteratorIterator,
    RecursiveDirectoryIterator;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/utilities.php");

defined('MOODLE_INTERNAL') || die;

$itemid = required_param('itemid', PARAM_INT);
$sessionid =  required_param('sessionID', PARAM_TEXT);
$pageid = required_param('id', PARAM_INT);
$pnum = required_param('pnum', PARAM_INT);
$sorting = optional_param('sort', null, PARAM_TEXT);
$order = optional_param('order', null, PARAM_TEXT);
$searchquery = optional_param('qword', null, PARAM_TEXT);
$userdirpath = required_param('userDirPath', PARAM_TEXT);

global $USER;

$activityjson = '';
$workplacejson = '';
$numberofupdated = 0;

$qword = isset($searchquery) && $searchquery != '' ? "&qword=$searchquery" : '';
$sortingmode = isset($sorting) && $sorting != '' ? "&sort=$sorting" : '';
$ordermode = isset($order) && $order != '' ? "&order=$order" : '';

//If cancel button is pressed
$cancelBtn = optional_param('cancelBtn', null, PARAM_TEXT);
if ($cancelBtn !== null) {

    //Remove temp dir which is used on editing
    $tempdir = "$userdirpath/";
    if (is_dir($tempdir)) {
        mod_arete_deleteDir($tempdir);
    }

    //Return to the first page
    redirect("$CFG->wwwroot/mod/arete/view.php?id={$pageid}&pnum={$pnum}&editing=on{$sortingmode}{$ordermode}{$qword}");

    return;
}

$uploadedfile = $_FILES['files']['tmp_name'];
$lastfile = end($uploadedfile);

//Replace user selected files
if (!empty(array_filter($_FILES['files']['name']))) {

    $sesskey = optional_param('sesskey', null, PARAM_TEXT);
    if (!isset($sesskey) || $sesskey !== sesskey()) {
        echo get_string('accessdenied', 'arete');
        die;
    }

    // Loop through each file in files[] array
    foreach ($uploadedfile as $key => $value) {

        $filetempname = $_FILES['files']['tmp_name'][$key];
        $filename = $_FILES['files']['name'][$key];
        $fileextention = pathinfo($filename, PATHINFO_EXTENSION);

        $result = replace_file($userdirpath, $filename, $fileextention, $filetempname, $lastfile == $value);
    }
}


//Replace the new json file after editing in json validator
$userupdatesactivity = in_array($sessionid . '-activity.json', array_filter($_FILES['files']['name']));
$userupdatesworkplace = in_array($sessionid . '-workplace.json', array_filter($_FILES['files']['name']));

//Replcace activity json if user does not select it manually to update
if (!$userupdatesactivity) {
    replace_file($userdirpath, "$sessionid-activity", 'json', "$sessionid-activity.json", $userupdatesworkplace ? false : true);
}
//Replcace workplace json if user does not select it manually to update
if (!$userupdatesworkplace) {
    replace_file($userdirpath, "$sessionid-workplace", 'json', "$sessionid-workplace.json", true);
}

/**
 * Replace old files with new files in temp folder before zipping them a
 * @global object $DB The Moodle database object
 * @global int $itemid The item id
 * @global string $activityjson The activity JSON string
 * @global string $workplacejson The workplace JSON string
 * @global int $numberofupdated How many files are modified
 * @param string $dir The directory path which is created in temp folder
 * @param string $filename The file name
 * @param string $fileextention The file extension
 * @param string $filetempname The file temp name
 * @param string $mainDir The root folder path
 */
function replace_file($dir, $filename, $fileextention, $filetempname, $is_lastFile = false) {

    global $DB, $itemid, $numberofupdated, $userdirpath;

    $ffs = scandir($dir);

    unset($ffs[array_search('.', $ffs, true)]);
    unset($ffs[array_search('..', $ffs, true)]);

    // Prevent empty ordered elements
    if (count($ffs) < 1) {
        return;
    }

    //Replace files with same name and extension
    foreach ($ffs as $ff) {

        //Thumbnail file can be uploaded even if it not exist already
        if (!in_array('thumbnail.jpg', $ffs) && $filename == "thumbnail.jpg") {
            move_uploaded_file($filetempname, "$userdirpath/thumbnail.jpg");
            $numberofupdated++;

            //Other selected file need to have a similar file in the zip file to be replaced
        } else if ($filename == $ff && pathinfo($ff, PATHINFO_EXTENSION) == $fileextention) {
            move_uploaded_file($filetempname, "$dir/$ff");

            $numberofupdated++;
        }

        //Include all files in subfolders
        else if (is_dir("$dir/$ff")) {
            replace_file("$dir/$ff", $filename, $fileextention, $filetempname);
        }
    }

    //Only once at the end. create zip file after all file are replaced
    if ($is_lastFile == true) {
        $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
        zipFiles($file);
    }
}

/**
 * Create the zip file for this ARLEM file and replace it in file system
 * @global string $userdirpath The path of the user directory which is created in temp folder
 * @global string $sessionid The activity id (sessionid in allalrems table)
 * @global string $activityjson The activity JSON string
 * @global string $workplacejson The workplace JSON string
 * @param object $arlem The ARLEM object
 */
function zipFiles($arlem) {
    global $userdirpath, $sessionid, $activityjson, $workplacejson;
    // Get real path for our folder
    $rootpath = $userdirpath;

    //Get JSON data from files
    $activityjson = file_get_contents("$userdirpath/$sessionid-activity.json", FILE_USE_INCLUDE_PATH);
    $workplacejson = file_get_contents("$userdirpath/$sessionid-workplace.json", FILE_USE_INCLUDE_PATH);

    $newtitle = json_decode($activityjson)->name;

    //Edit $sessionid if filename needs to be changed
    $newfilename = "$sessionid.zip";

    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open("$rootpath/$newfilename", ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootpath),
            RecursiveIteratorIterator::LEAVES_ONLY
    );


    foreach ($files as $name => $file) {
        $pathparts = pathinfo($name);
        $filename = $pathparts['basename'];

        // Skip directories (they would be added automatically) and the zipfile itself
        if (!$file->isDir() && $filename != $newfilename) {
            // Get real and relative path for current file
            $filepath = $file->getRealPath();
            $relativepath = substr($filepath, strlen($rootpath) + 1);

            // Add current file to archive
            $zip->addFile($filepath, $relativepath);

            //update thumbnail
            if ($filename === 'thumbnail.jpg') {
                update_thumbnail($filepath);
            }
        }
    }

    //add JSON files to the new zip file
    $zip->addFile("$userdirpath/$sessionid-activity.json", "$sessionid-activity.json");
    $zip->addFile("$userdirpath/$sessionid-workplace.json", "$sessionid-workplace.json");

    // Zip archive will be created only after closing object
    $zip->close();

    upload_new_zip("$rootpath/$newfilename", $arlem->filename, $newfilename, $newtitle);
}

/**
 * Upload the new zip file into the file system
 * @global int $itemid The itemid
 * @global object $DB The Moodle database object
 * @global int $pageid The page id
 * @global int $pnum The the page id in pagination
 * @global object $CFG The Moodle config file
 * @global string $userdirpath The user directory path in the temp folder
 * @global string $activityjson The activity JSON string
 * @global string $workplacejson The workplace JSON string
 * @global int $numberofupdated The number of files which are modified
 * @global string $sortingmode The sorting mode
 * @global string $ordermode How the sorted column is ordered
 * @global string $qword The word the user has searched for
 * @param string $filepath The path of the file
 * @param string $filename The file name
 */
function upload_new_zip($filepath, $oldFileName, $newfilename, $newtitle) {

    global $itemid, $DB, $pageid, $pnum, $CFG, $userdirpath, $activityjson, $workplacejson, $numberofupdated;

    //Get the file which need to be updated
    $existingarlem = mod_arete_get_arlem_by_name($oldFileName, $itemid);
    $oldfileid = $existingarlem->get_id();

    //Use the same date if file exist
    if (isset($existingarlem)) {

        $date = $existingarlem->get_timecreated();
        $existingarlem->delete(); //Delete the old file
    } else {
        $date = time();
    }

    //Add the updated file to the file system
    $newarlem = mod_arete_upload_custom_file($filepath, $newfilename, $itemid, $date);

    //The new file id
    $newarlemid = $newarlem->get_id();


    //Update the record of the file in allarlems table
    //The common records
    $parameters = array(
        'fileid' => $newarlemid,
        'timecreated' => $date,
        'filesize' => $newarlem->get_filesize(),
        'title' => $newtitle
    );

    //Update activity_json if updated
    if ($activityjson !== '') {
        $parameters += array('activity_json' => $activityjson);
    }

    //Update workplace_json if updated
    if ($workplacejson !== '') {
        $parameters += array('workplace_json' => $workplacejson);
    }

    //Get the
    $arlemdata = $DB->get_records('arete_allarlems', array('itemid' => $itemid));

    //Update timemodified only if at least one file is updated or json files are edited
    if (isset($arlemdata['activity_json']) && isset($arlemdata['workplace_json'])) {
        if ($numberofupdated != 0 || $arlemdata['activity_json'] != $activityjson || $arlemdata['workplace_json'] != $workplacejson) {
            $parameters += array('timemodified' => time());
        }
    }

    //Update the file name
    $parameters += array('filename' => $newfilename);

    //Update the table now
    mod_arete_update_arlem_object($oldFileName, $itemid, $parameters);

    //Update the record of the file in arete_arlem table
    $activitiesusethisarlem = $DB->get_records('arete_arlem', array('arlemid' => $oldfileid));
    foreach ($activitiesusethisarlem as $activity) {
        $activity->arlemid = $newarlemid; //This is the id of the new file
        $activity->timecreated = $date;
        $DB->update_record('arete_arlem', $activity);
    }

    //Remove temp dir which is used on editing
    $tempdir = $userdirpath . '/';
    if (is_dir($tempdir)) {
        mod_arete_deleteDir($tempdir);
    }

    global $sortingmode, $ordermode, $qword;

    //Return to the first page
    redirect("$CFG->wwwroot/mod/arete/view.php?id={$pageid}&pnum={$pnum}&editing=on{$sortingmode}{$ordermode}{$qword}");
}

/*
 * Delete the old thumbnail and create a new one
 * @global int $itemid The item id
 * @param string $filepath The path to the new thumbnail
 */

function update_thumbnail($filepath) {
    global $itemid;

    $context = context_system::instance()->id;
    $fs = get_file_storage();

    $filerecord = array('contextid' => $context, 'component' => get_string('component', 'arete'), 'filearea' => 'thumbnail',
        'itemid' => $itemid, 'filepath' => '/', 'filename' => 'thumbnail.jpg',
        'timecreated' => time(), 'timemodified' => time());

    $oldthumbnail = $fs->get_file($context, $filerecord['component'], 'thumbnail', $itemid, '/', 'thumbnail.jpg');

    if ($oldthumbnail) {
        $oldthumbnail->delete();
    }

    $fs->create_file_from_pathname($filerecord, $filepath);
}
