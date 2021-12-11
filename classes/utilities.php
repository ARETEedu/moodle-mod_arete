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
 * The Helper methods
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once( "$CFG->dirroot/mod/arete/classes/filemanager.php");

defined('MOODLE_INTERNAL') || die;

/**
 * Check if the file assigned to the activity module
 * @global object $DB The Moodle database object
 * @param int $areteid The activity module id
 * @param int $arlemid The id of the ARLEM
 * @return boolean Status of the assignment
 */
function mod_arete_is_arlem_assigned($areteid, $arlemid) {
    global $DB;

    $arlems = $DB->get_records('arete_arlem', array('areteid' => $areteid, 'arlemid' => $arlemid));

    if (empty($arlems)) {
        return false;
    }

    return true;
}

/**
 * Check for ARLEM existancy by using itemid
 * @global object $DB The Moodle database object
 * @param int $itemid The itemid of the ARLEM
 * @return boolean Status of the ARLEM existancy
 */
function mod_arete_is_arlem_exist($itemid) {
    global $DB;

    if (!empty($DB->get_record('arete_allarlems', array('itemid' => $itemid)))) {
        return true;
    }

    return false;
}

/**
 * Check for ARLEM existancy by using sessionid
 * @global object $DB The Moodle database object
 * @param string $sessionid The sessionif of the ARLEM
 * @return boolean Status of the ARLEM existancy
 */
function mod_arete_is_sessionid_exist($sessionid) {
    global $DB;

    if (!empty($DB->get_record('arete_allarlems', array('sessionid' => $sessionid)))) {
        return true;
    }

    return false;
}

/**
 *
 * Check if the user is the owner of the ARLEM
 * @global object $DB The Moodle database object
 * @param string $userid The user id
 * @param string $sessionid The sessionif of the ARLEM
 *
 * @return boolean The status of the the user ownership of this ARLEM
 */
function mod_arete_is_user_owner_of_file($userid, $sessionid) {
    global $DB;

    $file = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid));
    if (!empty($file)) {
        if ($file->userid == $userid) {
            return true;
        }
    }

    return false;
}


/**
 * Delete a directory and all file and subdirectories
 * @param string $dir The path of the directory
 * @return boolean Status of the directory deletion
 */
function mod_arete_deleteDir($dir) {
    //Delete all files from temp filearea
    mod_arete_delete_all_temp_file();

    if (substr($dir, strlen($dir) - 1, 1) != '/') {
        $dir .= '/';
    }

    if ($handle = opendir($dir)) {
        while ($obj = readdir($handle)) {
            if ($obj != '.' && $obj != '..') {
                if (is_dir($dir . $obj)) {
                    if (!mod_arete_deleteDir($dir . $obj)) {
                        return false;
                    }
                } elseif (is_file($dir . $obj)) {
                    if (!unlink($dir . $obj)) {
                        return false;
                    }
                }
            }
        }

        closedir($handle);

        if (!@rmdir($dir)) {
            return false;
        }

        return true;
    }
    return false;
}


/**
 * Creating temp files inside the file system
 * @param string $filepath The file path
 * @param string $filename The file name
 * @return object The temp file in file system
 */
function mod_arete_create_temp_files($filepath, $filename) {

    $context = context_system::instance();
    $fs = get_file_storage();

    $fileinfo = array(
        'contextid' => $context->id,
        'component' => get_string('component', 'arete'),
        'filearea' => 'temp',
        'itemid' => random_int(0, 1000),
        'filepath' => '/',
        'filename' => $filename,
        'timecreated' => time()
    );


    //Add the updated file to the file system
    $tempfile = $fs->create_file_from_pathname($fileinfo, $filepath);

    return $tempfile;
}


/**
 * Getting the file from temp filearea
 * @param string $filename The filename
 * @return object The file from temp filearea
 */
function mod_arete_get_temp_file($filename) {

    $context = context_system::instance();
    $fs = get_file_storage();

    $fileinfo = array(
        'contextid' => $context->id,
        'component' => get_string('component', 'arete'),
        'filearea' => 'temp',
        'filepath' => '/',
        'filename' => $filename,
        'timecreated' => time()
    );


    //add the updated file to the file system
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'],
            $fileinfo['filearea'], mod_arete_get_itemid($fileinfo),
            $fileinfo['filepath'], $filename);

    return $file;
}


/**
 * Deleting all files in temp filearea after editing is done
 * @global object $DB The Moodle database object
 */
function mod_arete_delete_all_temp_file() {

    global $DB;

    $fs = get_file_storage();

    // Get temp files
    $params = array(
        'component' => get_string('component', 'arete'),
        'filearea' => 'temp'
    );
    $temps = $DB->get_records('files', $params);

    foreach ($temps as $temp) {
        $tempfile = $fs->get_file($temp->contextid, get_string('component', 'arete'), 'temp', $temp->itemid, '/', $temp->filename);

        if ($tempfile) {
            $tempfile->delete();
        }
    }
}

/**
 * File size convertor
 * This will convert bytes to KB, MB and GB
 * @param int $size The file size in bytes
 * @return string The size of the files with the suffix
 */
function mod_arete_get_readable_filesize($size) {

    if ($size > 1000000000) {
        $size /= pow(1024, 3);
        $size = round($size, 2);
        $size .= ' GB';
    } else if ($size > 1000000) {
        $size /= pow(1024, 2);
        $size = round($size, 2);
        $size .= ' MB';
    } else if ($size > 1024) {
        $size /= 1024;
        $size = round($size, 2);
        $size .= ' KB';
    } else {
        $size = $size / 1024;
        $size = round($size, 2);
        $size .= ' KB';
    }

    return $size;
}

/**
 * Send a post request
 * @param string $url The URL of the File request will be sent to
 * @param array $data The query parameters
 * @return object The response from the file
 */
function mod_arete_httpPost($url, $data) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

/**
 * Create an object with all existing queries parameters in the URL
 * @param bool $onlyvalue If true only the value of the parameters will be included
 * @return array An array of all query parameters
 */
function mod_arete_get_queries($onlyvalue = false) {


    $id = required_param('id', PARAM_INT);
    $pnum = optional_param('pnum', 1, PARAM_INT);
    $itemid = optional_param('itemid', null, PARAM_INT);
    $arlemuserid = optional_param('user', null, PARAM_INT);
    $searchword = optional_param('qword', null, PARAM_TEXT);
    $editing = optional_param('editing', null, PARAM_TEXT);
    $pagetype = optional_param('mode', null, PARAM_TEXT);
    $sorting = optional_param('sort', null, PARAM_TEXT);
    $order = optional_param('order', null, PARAM_TEXT);

    //
    $idvalue = '';
    if (isset($id) && $id != '') {
        $idvalue = !$onlyvalue ? "&id=$id" : $id;
    }

    //
    $pnumvalue = '';
    if (isset($pnum) && $pnum != '') {
        $pnumvalue = !$onlyvalue ? "&pnum=$pnum" : $pnum;
    }

    //
    $itemidvalue = '';
    if (isset($itemid) && $itemid != '') {
        $itemidvalue = !$onlyvalue ? "&itemid=$itemid" : $itemid;
    }

    //
    $arlemuseridvalue = '';
    if (isset($arlemuserid) && $arlemuserid != '') {
        $arlemuseridvalue = !$onlyvalue ? "&author=$arlemuserid" : $arlemuserid;
    }


    $editingmode = '';
    if (isset($editing) && $editing == 'on') {
        $editingmode = !$onlyvalue ? '&editing=on' : 'on';
    }

    $pagemode = '';
    if (isset($pagetype) && $pagetype != '') {
        $pagemode = !$onlyvalue ? "&mode=$pagetype" : $pagetype;
    }

    $sortingmode = '';
    if (isset($sorting) && $sorting != '') {
        $sortingmode = !$onlyvalue ? "&sort=$sorting" : $sorting;
    }


    //Pass the search word in url if exist
    $searchquery = '';
    if (isset($searchword) && $searchword != '') {
        $searchquery = !$onlyvalue ? "&qword=$searchword" : $searchword;
    }

    //
    $ordermode = '';
    if (isset($order) && $order != '') {
        $ordermode = !$onlyvalue ? "&order=$order" : $order;
    }

    $queries = array(
        'id' => $idvalue,
        'pnum' => $pnumvalue,
        'itemid' => $itemidvalue,
        'author' => $arlemuseridvalue,
        'mode' => $pagemode,
        'editing' => $editingmode,
        'sort' => $sortingmode,
        'qword' => $searchquery,
        'order' => $ordermode);

    return $queries;
}
