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

namespace mod_arete;

use context_system,
    ZipArchive,
    RecursiveIteratorIterator,
    RecursiveDirectoryIterator;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

$itemid = filter_input(INPUT_POST, 'itemid');
$sessionid = filter_input(INPUT_POST, 'sessionID');
$pageid = filter_input(INPUT_POST, 'id');
$pnum = filter_input(INPUT_POST, 'pnum');
$sorting = filter_input(INPUT_POST, 'sort');
$order = filter_input(INPUT_POST, 'order');
$searchquery = filter_input(INPUT_POST, 'qword');
$userdirpath = filter_input(INPUT_POST, 'userDirPath');

global $USER;


$activityjson = '';
$workplacejson = '';
$numberofupdated = 0;

$qword = isset($searchquery) && $searchquery != '' ? '&qword=' . $searchquery : '';
$sortingmode = isset($sorting) && $sorting != '' ? '&sort=' . $sorting : '';
$ordermode = isset($order) && $order != '' ? '&order=' . $order : '';

//if cancel button is pressed
if (filter_input(INPUT_POST, 'cancelBtn') !== null) {

    //remove temp dir which is used on editing
    $tempdir = $userdirpath . '/';
    if (is_dir($tempdir)) {
        mod_arete_deleteDir($tempdir);
    }

    //return to the first page
    redirect($CFG->wwwroot . '/mod/arete/view.php?id=' . $pageid . '&pnum=' . $pnum . '&editing=on' . $sortingmode . $ordermode . $qword);

    return;
}

$uploadedfile = $_FILES['files']['tmp_name'];
$lastfile = end($uploadedfile);

//replace user selected files
if (!empty(array_filter($_FILES['files']['name']))) {

    $sesskey = filter_input(INPUT_POST, 'sesskey');
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


///replace the new json file after editing in json validator
$userupdatesactivity = in_array($sessionid . '-activity.json', array_filter($_FILES['files']['name']));
$userupdatesworkplace = in_array($sessionid . '-workplace.json', array_filter($_FILES['files']['name']));

//replcace activity json if user does not select it manually to update
if (!$userupdatesactivity) {
    replace_file($userdirpath, $sessionid . '-activity', 'json', $sessionid . '-activity.json', $userupdatesworkplace ? false : true);
}
//replcace workplace json if user does not select it manually to update
if (!$userupdatesworkplace) {
    replace_file($userdirpath, $sessionid . '-workplace', 'json', $sessionid . '-workplace.json', true);
}

///

/**
 * replace old files with new files in temp folder before zipping them a
 * @global type $DB
 * @global type $itemid
 * @global type $activityjson
 * @global type $workplacejson
 * @global int $numberofupdated
 * @param type $dir
 * @param type $filename
 * @param type $fileextention
 * @param type $filetempname
 * @param type $mainDir
 * @return type
 */
function replace_file($dir, $filename, $fileextention, $filetempname, $is_lastFile = false) {

    global $DB, $itemid, $numberofupdated, $userdirpath;

    $ffs = scandir($dir);

    unset($ffs[array_search('.', $ffs, true)]);
    unset($ffs[array_search('..', $ffs, true)]);

    // prevent empty ordered elements
    if (count($ffs) < 1) {
        return;
    }

    //replace files with same name and extension
    foreach ($ffs as $ff) {

        //thumbnail file can be uploaded even if it not exist already
        if (!in_array('thumbnail.jpg', $ffs) && $filename == "thumbnail.jpg") {
            move_uploaded_file($filetempname, $userdirpath . '/thumbnail.jpg');
            $numberofupdated++;

            //other selected file need to have a similar file in the zip file to be replaced
        } else if ($filename == $ff && pathinfo($ff, PATHINFO_EXTENSION) == $fileextention) {
            move_uploaded_file($filetempname, $dir . '/' . $ff);

            $numberofupdated++;
        }

        //include all files in subfolders
        else if (is_dir($dir . '/' . $ff)) {
            replace_file($dir . '/' . $ff, $filename, $fileextention, $filetempname);
        }
    }

    //only once at the end. create zip file after all file are replaced
    if ($is_lastFile == true) {
        $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
        zipFiles($file);
    }
}

/**
 * Create the zip file for this ARLEM file and replace it in file system
 * @global type $userdirpath the user folder inside temp folder where all files including the new files are located there
 * @param type $arlem ARLEM object (from all_arlem table)
 */
function zipFiles($arlem) {
    global $userdirpath, $sessionid, $activityjson, $workplacejson;
    // Get real path for our folder
    $rootpath = $userdirpath;


    //get JSON data from files
    $activityjson = file_get_contents($userdirpath . '/' . $sessionid . '-activity.json', FILE_USE_INCLUDE_PATH);
    $workplacejson = file_get_contents($userdirpath . '/' . $sessionid . '-workplace.json', FILE_USE_INCLUDE_PATH);

    $newtitle = json_decode($activityjson)->name;

    //Edit $sessionid if filename needs to be changed
    $newfilename = $sessionid . '.zip';

    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($rootpath . '/' . $newfilename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
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
    $zip->addFile($userdirpath . '/' . $sessionid . '-activity.json', $sessionid . '-activity.json');
    $zip->addFile($userdirpath . '/' . $sessionid . '-workplace.json', $sessionid . '-workplace.json');

    // Zip archive will be created only after closing object
    $zip->close();

    upload_new_zip($rootpath . '/' . $newfilename, $arlem->filename, $newfilename, $newtitle);
}

/**
 * Upload the new zip file into the file system
 * @global type $itemid
 * @global type $DB
 * @global type $pageid
 * @global type $pnum
 * @global type $CFG
 * @global type $userdirpath
 * @global string $activityjson
 * @global string $workplacejson
 * @global int $numberofupdated
 * @global type $sortingmode
 * @global type $ordermode
 * @global type $qword
 * @param type $filepath
 * @param type $filename
 */
function upload_new_zip($filepath, $oldFileName, $newfilename, $newtitle) {

    global $itemid, $DB, $pageid, $pnum, $CFG, $userdirpath, $activityjson, $workplacejson, $numberofupdated;

    //get the file which need to be updated
    $existingarlem = mod_arete_get_arlem_by_name($oldFileName, $itemid);
    $oldfileid = $existingarlem->get_id();

    //use the same date if file exist
    if (isset($existingarlem)) {

        $date = $existingarlem->get_timecreated();
        $existingarlem->delete(); //delete the old file
    } else {
        $date = time();
    }

    //add the updated file to the file system
    $newarlem = mod_arete_upload_custom_file($filepath, $newfilename, $itemid, $date);

    //the new file id
    $newarlemid = $newarlem->get_id();


    ///update the record of the file in allarlems table
    //the common records
    $parameters = array(
        'fileid' => $newarlemid,
        'timecreated' => $date,
        'filesize' => $newarlem->get_filesize(),
        'title' => $newtitle
    );

    //update activity_json if updated
    if ($activityjson !== '') {
        $parameters += array('activity_json' => $activityjson);
    }

    //update workplace_json if updated
    if ($workplacejson !== '') {
        $parameters += array('workplace_json' => $workplacejson);
    }

    //get the
    $arlemdata = $DB->get_records('arete_allarlems', array('itemid' => $itemid));

    //update timemodified only if at least one file is updated or json files are edited
    if (isset($arlemdata['activity_json']) && isset($arlemdata['workplace_json'])) {
        if ($numberofupdated != 0 || $arlemdata['activity_json'] != $activityjson || $arlemdata['workplace_json'] != $workplacejson) {
            $parameters += array('timemodified' => time());
        }
    }

    //update the file name
    $parameters += array('filename' => $newfilename);

    //update the table now
    mod_arete_update_arlem_object($oldFileName, $itemid, $parameters);
    ///
    //update the record of the file in arete_arlem table
    $activitiesusethisarlem = $DB->get_records('arete_arlem', array('arlemid' => $oldfileid));
    foreach ($activitiesusethisarlem as $activity) {
        $activity->arlemid = $newarlemid; //this is the id of the new file
        $activity->timecreated = $date;
        $DB->update_record('arete_arlem', $activity);
    }

    //remove temp dir which is used on editing
    $tempdir = $userdirpath . '/';
    if (is_dir($tempdir)) {
        mod_arete_deleteDir($tempdir);
    }

    global $sortingmode, $ordermode, $qword;
    //return to the first page
    redirect($CFG->wwwroot . '/mod/arete/view.php?id=' . $pageid . '&pnum=' . $pnum . '&editing=on' . $sortingmode . $ordermode . $qword);
}

/*
 * Delete the old thumbnail and create a new one
 *
 * @param $filepath path to the new thumbnail
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
