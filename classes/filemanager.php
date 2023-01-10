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
 * The methods for communication with the database 
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_arete\webservices\arlem_deletion;
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/webservice/arlem_deletion.php');

//Column order by ASC or DESC
$order = filter_input(INPUT_GET, 'order');
if (!isset($order)) {
    $order = "DESC";
}

$systemcontext = context_system::instance()->id;

/**
 * Delete a file from user draft
 *
 * @param string $filename The Name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 * @param bool $withusercontext If pass true the user id whome is already logged into Moodle will be used
 * @param int $userid Get the contextid of this user
 *
 */
function mod_arete_delete_user_arlem($filename, $itemid = null, $withusercontext = false, $userid = null) {
    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => 'user',
        'filearea' => 'draft',
        'contextid' => mod_arete_get_user_contextid($withusercontext, $userid),
        'filepath' => '/', // Any path beginning and ending in /
        'filename' => $filename);

    //Use itemid too if it is provided
    if (isset($itemid)) {
        $fileitemid = $itemid;
    } else {
        $fileitemid = mod_arete_get_itemid($fileinfo);
    }

    // Get thr file from the file system
    $file = $filestorage->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileitemid, $fileinfo['filepath'], $fileinfo['filename']);

    // Delete it if it exists
    if ($file) {
        $file->delete();
    }
}

/**
 * Get a single file from plugin filearea by passing filename and item id
 * @param string $filename The name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 * @global int $systemcontext The context id
 * @return object The file from file system
 */
function mod_arete_get_arlem_by_name($filename, $itemid) {

    global $systemcontext;

    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => get_string('component', 'arete'),
        'filearea' => get_string('filearea', 'arete'),
        'contextid' => $systemcontext,
        'filepath' => '/',
    );

    // Get file
    $file = $filestorage->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $itemid, $fileinfo['filepath'], $filename);


    // Read contents
    if ($file) {
        return $file;
    } else {
        return null;
    }
}


/**
 * Getting the first itemid of the items with this name that become found in user draft
 * @param array $fileinfo An array of the available info of the ARLEM file in file system  (like itemid, filename, ect)
 * @global object $DB The Moodle database object
 * @return array An array with all info of the ARLEM file in file system
 */
function mod_arete_get_itemid($fileinfo) {
    global $DB;

    $row = $DB->get_records('files', $fileinfo);
    if (!empty($row)) {
        $firstrowfound = current($row)->itemid;
        return $firstrowfound;
    }

    return null;
}

/**
 *
 * return current user contextid
 *
 * @param bool $withusercontext If pass true the user id whome is already logged into Moodle will be used
 * @param int $userid Get the contextid of this user
 * @global object $USER The user object
 * @return int Contextid of the user
 */
function mod_arete_get_user_contextid($withusercontext = false, $userid = null) {
    global $USER;

    if (!isset($userid) && $withusercontext == false) {
        $context = context_user::instance($USER->id);
    } else {
        $context = context_user::instance($userid);
    }

    $contextid = $context->id;

    return $contextid;
}

/**
 * Get the ARLEM from draft filearea of the current user
 * @param string $filename The name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 * @return object The file from user draft area by API if it is exists
 */
function mod_arete_get_user_arlem($filename, $itemid = null) {

    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => 'user', // usually = table name
        'filearea' => 'draft', // usually = table name
        'contextid' => mod_arete_get_user_contextid(), // ID of context
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename); // any filename
    //Use itemid too if it is provided
    if (isset($itemid)) {
        $fileitemid = $itemid;
    } else {
        $fileitemid = getItemID($fileinfo);
    }

    // Get file
    $file = $filestorage->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileitemid, $fileinfo['filepath'], $fileinfo['filename']);

    // Read contents
    if ($file) {
        return $file;
    } else {
        return null;
    }
}

/**
 * Copy ARLEM zip file from file system to temp folder
 * @global object $USER The user object
 * @param string $filename The name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 */
function mod_arete_copy_arlem_to_temp($filename, $itemid) {

    global $USER;
    // Get file
    $file = mod_arete_get_arlem_by_name($filename, $itemid);

    // Read contents
    if ($file) {
        $file->copy_content_to("temp/{$USER->id}/{$file->get_filename()}");
    } else {
        // The file doesn't exist - do something
    }
}

/**
 * Get an array of all files in allarlems table
 * @global object $DB The Moodle database object
 * @global object $USER The user object
 * @global object $COURSE The course object
 * @global string $order The order of the array
 * @param string $sorting Which column should the array be sorted by
 * @return array An array with all ARLEMs for manager, and public and user own files for other users
 */
function mod_arete_get_allarlems($sorting) {
    global $DB, $USER, $COURSE, $order;

    $sortingmode = mod_arete_validate_sorting($sorting);

    //Course context
    $context = context_course::instance($COURSE->id);

    //Only for the managers
    if (has_capability('mod/arete:manageall', $context)) {

        switch ($sortingmode) {
            //Author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
            case "author":
                $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                        . 'JOIN {user} AS u '
                        . 'ON a.userid = u.id '
                        . 'ORDER BY u.username ' . $order;
                $files = $DB->get_records_sql($sql);
                break;
            
            default:
                //Get All ARLEMs
                $files = $DB->get_records('arete_allarlems', null, "$sortingmode $order");
                break;
        }
    } else { //For any other users
        $params = [1, $USER->id];
        switch ($sortingmode) {
            //Author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
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
                $files = $DB->get_records_select('arete_allarlems', $sql, $params, "$sortingmode $order");
                break;
        }
    }

    return $files;
}

/**
 * Get an array of only the user files in allarlems table
 * @global object $DB The Moodle database object
 * @global object $USER The user object
 * @global string $order The order of the array
 * @param string $sorting Which column should the array be sorted by
 * @return array An array with user's files(ARLEMs)
 */
function mod_arete_get_user_arlems($sorting) {
    global $DB, $USER, $order;

    $sortingmode = mod_arete_validate_sorting($sorting);

    switch ($sortingmode) {
        //Author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
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
            $files = $DB->get_records('arete_allarlems', array('userid' => $USER->id), "$sortingmode $order");
            break;
    }

    return $files;
}

/**
 * Search the activity and workplace JSONs and activity name for a word
 * @global object $DB The Moodle database object
 * @global object $USER The user object
 * @global string $order The order of the array
 * @global object $COURSE The course object
 * @param string $searchword The word user searched for
 * @param bool $userSearch Status of the user is searched
 * @param string $sorting Which column should the array be sorted by
 * @return array A list of ARLEMs files in allalrem table
 */
function mod_arete_search_arlems($searchword, $userSearch, $sorting) {
    global $DB, $USER, $order, $COURSE;

    $sortingmode = mod_arete_validate_sorting($sorting);

    //If it is student activityies table seach only between his/her files
    $useridexist = $userSearch ? $USER->id : '';

    $searchquerty = '%' . $searchword . '%';

    //Course context
    $context = context_course::instance($COURSE->id);

    //All result for the managers
    if (has_capability('mod/arete:manageall', $context)) {
        $sql = '(filename LIKE ? OR activity_json LIKE ? OR workplace_json LIKE ?)';
        $params = [$searchquerty, $searchquerty, $searchquerty];
        $results = $DB->get_records_select('arete_allarlems', $sql, $params, "$sortingmode $order");
    } else {
        $params = [1, $useridexist, $searchquerty, $searchquerty, $searchquerty];
        switch ($sortingmode) {
            //Author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
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
                $results = $DB->get_records_select('arete_allarlems', $sql, $params, "$sortingmode $order");
                break;
        }
    }

    return $results;
}

/**
 * Get the ARLEM URL or play link for oopening in WEKIT protocol
 * @param string $filename The name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 * @param bool $downloadmode If true the link with HTTP protocol will be return for direct download
 * @return string The URL of the ARLEM file
 */
function mod_arete_get_arlem_url($filename, $itemid, $downloadmode = null) {
    global $DB;
    $file = mod_arete_get_arlem_by_name($filename, $itemid);

    $url = '#';
    if ($file) {
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                        $file->get_filearea(), $file->get_itemid(),
                        $file->get_filepath(), $file->get_filename(), false);
    }

    $allarlemfiles = $DB->get_record('arete_allarlems', array('filename' => $filename, 'itemid' => $itemid));

    $path = explode("/", parse_url($url, PHP_URL_PATH));

    if ($downloadmode != null) {
        return $url;
    } else {
        $urlparamsstring = implode("/", array_slice($path, 2));
        return "wekit://load?download={$urlparamsstring}&id={$allarlemfiles->sessionid}";
    }
}

/**
 * Get the URL of any file in mod_arete file system
 * @param object $file The file from file API system
 * @return string The URL of the ARLEM
 */
function mod_arete_get_url($file) {
    $url = '#';
    if ($file) {
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                        $file->get_filearea(), $file->get_itemid(),
                        $file->get_filepath(), $file->get_filename(), false);
    }

    return $url;
}


/**
 * Delete a file from file system
 * @global object $DB The Moodle database object
 * @global int $systemcontext The context id
 * @param string $filename The name of ARLEM in filearea
 * @param int $itemid The itemid of ARLEM in filearea
 */
function mod_arete_delete_arlem_from_plugin($filename, $itemid = null) {
    global $DB, $systemcontext;

    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => get_string('component', 'arete'),
        'filearea' => get_string('filearea', 'arete'),
        'contextid' => $systemcontext,
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename);


    //Use itemid too if it is provided
    if (isset($itemid)) {
        $fileitemid = $itemid;
    } else {
        $fileitemid = mod_arete_get_itemid($fileinfo);
    }

    // Getting the file
    $file = $filestorage->get_file($systemcontext, $fileinfo['component'], $fileinfo['filearea'],
            $fileitemid, $fileinfo['filepath'], $fileinfo['filename']);




    // Delete it if it exists
    if ($file) {

        //Delete the zip file
        $file->delete();
    }
}

/**
 *
 * Update arete_allarlems table
 * @global object $DB The Moodle database object
 * @param string $filename The filename of the ARLEM
 * @param int $itemid The itemid of the ARLEM
 * @param array $params An array with the key,value of the columns need to be updated
 */
function mod_arete_update_arlem_object($filename, $itemid, $params) {

    global $DB;
    $alrem = $DB->get_record('arete_allarlems', array('itemid' => $itemid, 'filename' => $filename));


    foreach ($params as $key => $value) {
        $alrem->$key = $value;
    }

    $DB->update_record('arete_allarlems', $alrem);
}


/**
 *
 * Get the thumbnail of Arlemfile by itemid
 * @global object $CFG The Moodle config object
 * @param int $itemid The itemid from allarlem table
 * @return array An array which contains the thumbnail URL and 
 * the CSS class of the thumbnail img tag
 */
function mod_arete_get_thumbnail($itemid) {

    global $CFG;

    $filestorage = get_file_storage();
    $thumbnail = $filestorage->get_file(context_system::instance()->id, get_string('component', 'arete'),
            'thumbnail', $itemid, '/', 'thumbnail.jpg');

    //If the thumbnail file exists
    if ($thumbnail) {
        $thumb_url = moodle_url::make_pluginfile_url($thumbnail->get_contextid(), $thumbnail->get_component(),
                        $thumbnail->get_filearea(), $thumbnail->get_itemid(),
                        $thumbnail->get_filepath(), $thumbnail->get_filename(), false);
        $css = 'ImgThumbnail';
    } else {
        $thumb_url = "{$CFG->wwwroot}/mod/arete/pix/no-thumbnail.jpg";
        $css = 'no-thumbnail';
    }
    
    return array($thumb_url, $css);
}


/**
 * Get the owner of this Arlem
 * @global object $DB The Moodle database object
 * @param object $arlem The arlem record from allarlem table
 * @param object @PAGE The page you are going to use this user info on
 * @return array An array which contains the user object and his/her profile photo
 */
function mod_arete_get_arlem_owner($arlem, $PAGE) {

    global $DB;

    if (isset($arlem->userid)) {
        $authoruser = $DB->get_record('user', array('id' => $arlem->userid));
    }
    $userpicture = new user_picture($authoruser);
    $src = $userpicture->get_url($PAGE);

    return array($authoruser, $src);
}

/**
 * Return who assigned this arlem to this course module
 * @global object $DB The Moodle database object
 * @param object $arlem The arlem record from allarlem table
 * @param int $moduleid The course module id
 * @return string The first and last name of the teacher/manager who assigned this ARLEM to this course module
 */
function mod_arete_get_who_assigned_arlem($arlem, $moduleid) {

    global $DB;
    $teacherid = $DB->get_record('arete_arlem', array('areteid' => $moduleid, 'arlemid' => $arlem->fileid));
    $assignedbyuser = $DB->get_record('user', array('id' => $teacherid->teacherid));



    if (!empty($assignedbyuser)) {
        $assignedby = "{$assignedbyuser->firstname} {$assignedbyuser->lastname}";
    } else {
        $assignedby = get_string('notsetyet', 'arete');
    }

    return $assignedby;
}


/**
 * Upload a custom file to the mod_arete filearea
 * @param string $filepath The local path of the file
 * @param string $filename The name of the new file
 * @param int $itemid The item id in allarlems table
 * @param object $date If you wand update the file you can use the original timecreated
 * @return object The file object which is uploaded to the system
 */
function mod_arete_upload_custom_file($filepath, $filename, $itemid = null, $date = null) {

    $filestorage = get_file_storage();

    $context = context_system::instance();

    //create a new itemid if it is not proveded
    if ($itemid == null) {
        $itemid = random_int(100000000, 999999999);
    }

    if ($date == null) {
        $date = time();
    }

    $fileinfo = array(
        'contextid' => $context->id,
        'component' => get_string('component', 'arete'),
        'filearea' => get_string('filearea', 'arete'),
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $filename,
        'timecreated' => $date
    );

    $newfile = $filestorage->create_file_from_pathname($fileinfo, $filepath);

    return $newfile;
}

/**
 * Delete an ARLEM from file system using session id

 * @param string $sessionid The activity id (sessionid in allalrems table)
 * @return bool The status of the file deletion
 * @throws dml_exception if something goes wrong in the queries
 * @global object $DB The Moodle database object
 */
function mod_arete_delete_arlem_by_sessionid($sessionid) {

    global $DB;

    $file = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid));
    if (!empty($file)) {
        mod_arete_delete_arlem_from_plugin($file->filename, $file->itemid);
        $deletion = new arlem_deletion();
        $deletion->mod_arete_delete_arlem_from_other_tables($DB,
            $file->sessionid, $file->itemid, $file->fileid);
        return true;
    }

    return false;
}

/**
 * Get the number of views on the app
 * @global object $DB The Moodle database object
 * @param int $itemid The item id
 * @return int The number of views of this file/ARLEM
 */
function mod_arete_get_views($itemid) {
    global $DB;

    $views = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

    if (!empty($views)) {
        return $views->views;
    } else {
        return 0;
    }
}

/**
 * Validate sorting query
 * @param string  $sortingmode
 * @return string The sorting mode after checking for validation. 
 * timecreated will be return if the sorting mode was not valid
 */
function mod_arete_validate_sorting($sortingmode) {

    switch ($sortingmode) {
        case "filename":
        case "views":
        case "filesize":
        case "timecreated":
        case "timemodified":
        case "rate":
        case "author":
            return $sortingmode;
        default :
            return "timecreated";
    }
}
