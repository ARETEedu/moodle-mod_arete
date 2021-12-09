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

require_once(dirname(__FILE__) . '/../../../config.php');

//sort the table by ASC or DESC
$order = filter_input(INPUT_GET, 'order');
if (!isset($order)) {
    $order = "DESC";
}

$systemcontext = context_system::instance()->id;

/**
 * Delete a file from user draft
 *
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * @param $withusercontext If pass true the user id whome is already logged into Moodle will be used
 * @param $userid Get the contextid of this user
 *
 */
function mod_arete_delete_user_arlem($filename, $itemid = null, $withusercontext = false, $userid = null) {
    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => 'user',
        'filearea' => 'draft',
        'contextid' => mod_arete_get_user_contextid($withusercontext, $userid),
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename);

    //use itemid too if it is provided
    if (isset($itemid)) {
        $fileitemid = $itemid;
    } else {
        $fileitemid = mod_arete_get_itemid($fileinfo);
    }

    // Get file
    $file = $filestorage->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileitemid, $fileinfo['filepath'], $fileinfo['filename']);

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
 * get the first itemid of the items with this name that become found in user draft
 * @param $fileinfo An array of the available info of the ARLEM file in file system  (like itemid, filename, ect)
 *
 * @return an array with all info of the ARLEM file in file system
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
 * @param $withusercontext If pass true the user id whome is already logged into Moodle will be used
 * @param $userid Get the contextid of this user
 *
 * @return Contextid of the user
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
 * Get the arlem from draft filearea of the current user
 *
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 *
 * @return The file from user draft area by API if it is exists
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
    //use itemid too if it is provided
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
 *
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 */
function mod_arete_copy_arlem_to_temp($filename, $itemid) {

    global $USER;
    // Get file
    $file = mod_arete_get_arlem_by_name($filename, $itemid);

    // Read contents
    if ($file) {
        $file->copy_content_to('temp/' . $USER->id . '/' . $file->get_filename());
    } else {
        // file doesn't exist - do something
    }
}

/**
 * Get an array of all files in allarlems table
 *
 * @return an array with all ARLEMs for manager, and public and user own files for other users
 */
function mod_arete_get_allarlems($sorting) {
    global $DB, $USER, $COURSE, $order;

    $sortingmode = mod_arete_validate_sorting($sorting);

    //course context
    $context = context_course::instance($COURSE->id);

    //manager
    if (has_capability('mod/arete:manageall', $context)) {

        switch ($sortingmode) {
            //author on allalrems is a user id while we need sort the column by username, therefore we join the table with user table
            case "author":
                $sql = 'SELECT a.* FROM {arete_allarlems} AS a '
                        . 'JOIN {user} AS u '
                        . 'ON a.userid = u.id '
                        . 'ORDER BY u.username ' . $order;
                $files = $DB->get_records_sql($sql);
                break;
            default:
                //all arlems
                $files = $DB->get_records('arete_allarlems', null, $sortingmode . ' ' . $order);
                break;
        }
    } else { //others
        $params = [1, $USER->id];
        switch ($sortingmode) {
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
                $files = $DB->get_records_select('arete_allarlems', $sql, $params, $sortingmode . ' ' . $order);
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
function mod_arete_get_user_arlems($sorting) {
    global $DB, $USER, $order;

    $sortingmode = mod_arete_validate_sorting($sorting);

    switch ($sortingmode) {
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
            $files = $DB->get_records('arete_allarlems', array('userid' => $USER->id), $sortingmode . ' ' . $order);
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
function mod_arete_search_arlems($searchword, $userSearch, $sorting) {
    global $DB, $USER, $order, $COURSE;

    $sortingmode = mod_arete_validate_sorting($sorting);

    //if it is student activityies table seach only between his/her files
    $useridexist = $userSearch ? $USER->id : '';

    $searchquerty = '%' . $searchword . '%';

    //course context
    $context = context_course::instance($COURSE->id);

    //All result for the managers
    if (has_capability('mod/arete:manageall', $context)) {
        $sql = '(filename LIKE ? OR activity_json LIKE ? OR workplace_json LIKE ?)';
        $params = [$searchquerty, $searchquerty, $searchquerty];
        $results = $DB->get_records_select('arete_allarlems', $sql, $params, $sortingmode . ' ' . $order);
    } else {
        $params = [1, $useridexist, $searchquerty, $searchquerty, $searchquerty];
        switch ($sortingmode) {
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
                $results = $DB->get_records_select('arete_allarlems', $sql, $params, $sortingmode . ' ' . $order);
                break;
        }
    }


    return $results;
}

/**
 * Get the ARLEM URL or play link for oopening in WEKIT protocol
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 * @param $downloadmode if true the link with http protocol will be return for direct download
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
        return 'wekit://load?download=' . implode("/", array_slice($path, 2)) . '&id=' . $allarlemfiles->sessionid;
    }
}

/**
 * Get the URL of any file in mod_arete file system
 * @param $file the file from file API system
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
 *
 * @param $filename name of ARLEM in filearea
 * @param $itemid the itemid of ARLEM in filearea
 */
function mod_arete_delete_arlem_from_plugin($filename, $itemid = null) {
    global $DB, $systemcontext;
    ;

    $filestorage = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => get_string('component', 'arete'),
        'filearea' => get_string('filearea', 'arete'),
        'contextid' => $systemcontext,
        'filepath' => '/', // any path beginning and ending in /
        'filename' => $filename);


    //use itemid too if it is provided
    if (isset($itemid)) {
        $fileitemid = $itemid;
    } else {
        $fileitemid = mod_arete_get_itemid($fileinfo);
    }

    // Get file
    $file = $filestorage->get_file($systemcontext, $fileinfo['component'], $fileinfo['filearea'],
            $fileitemid, $fileinfo['filepath'], $fileinfo['filename']);


    $thumbnail = $filestorage->get_file($systemcontext, $fileinfo['component'], 'thumbnail',
            $fileitemid, $fileinfo['filepath'], 'thumbnail.jpg');

    // Delete it if it exists
    if ($file) {

        //delete thumbnail
        if ($thumbnail) {
            $thumbnail->delete();
        }

        //delete it from arete_allarlems table
        if (!empty($DB->get_records('arete_allarlems', array('itemid' => $fileitemid)))) {
            $DB->delete_records('arete_allarlems', array('itemid' => $fileitemid));
        }


        //delete rating of this arlem
        if (!empty($DB->get_records('arete_rating', array('itemid' => $fileitemid)))) {
            $DB->delete_records('arete_rating', array('itemid' => $fileitemid));
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
 *
 * @param $itemid the itemid from allarlem table
 * return thumbnail url and the css class that should use for that thumbnail img
 */
function mod_arete_get_thumbnail($itemid) {

    global $CFG;

    $filestorage = get_file_storage();
    $thumbnail = $filestorage->get_file(context_system::instance()->id, get_string('component', 'arete'), 'thumbnail', $itemid, '/', 'thumbnail.jpg');
    //if the thumbnail file exists
    if ($thumbnail) {
        $thumb_url = moodle_url::make_pluginfile_url($thumbnail->get_contextid(), $thumbnail->get_component(),
                        $thumbnail->get_filearea(), $thumbnail->get_itemid(),
                        $thumbnail->get_filepath(), $thumbnail->get_filename(), false);
        $css = 'ImgThumbnail';
    } else {
        $thumb_url = $CFG->wwwroot . '/mod/arete/pix/no-thumbnail.jpg';
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
 * @param $arlem arlem file from allarlem table
 * @param $moduleid the course module id
 *
 * return the first and last name of the teacher/manager who assigned this ARLEM to this course module
 */
function mod_arete_get_who_assigned_arlem($arlem, $moduleid) {

    global $DB;
    $teacherid = $DB->get_record('arete_arlem', array('areteid' => $moduleid, 'arlemid' => $arlem->fileid));
    $assignedbyuser = $DB->get_record('user', array('id' => $teacherid->teacherid));



    if (!empty($assignedbyuser)) {
        $assignedby = $assignedbyuser->firstname . ' ' . $assignedbyuser->lastname;
    } else {
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
 *
 * @param type $sessionid
 *
 */
function mod_arete_delete_arlem_by_sessionid($sessionid) {

    global $DB;

    $file = $DB->get_record('arete_allarlems', array('sessionid' => $sessionid));
    if (!empty($file)) {
        mod_arete_delete_arlem_from_plugin($file->filename, $file->itemid);
        $DB->delete_records('arete_allarlems', array('sessionid' => $sessionid));
        return true;
    }

    return false;
}

/**
 * Get the number of views on the app
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
 * validate sorting query
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
