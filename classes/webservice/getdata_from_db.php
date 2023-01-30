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
 * Get the data needed by MirageXR from database
 * and send it to the app. This file can be
 * used to to delete or update an ARLEM file too
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\webservices;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/webservice/arlem_deletion.php');


$request = filter_input(INPUT_POST, 'request');
$itemid = filter_input(INPUT_POST, 'itemid');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$userid = filter_input(INPUT_POST, 'userid');
$token = filter_input(INPUT_POST, 'token');

//Check the request and do what needs be done
switch ($request) {
    case 'arlemlist':
        get_arlem_list();
        break;
    
    case 'deleteArlem':
        delete_arlem();
        break;
    
    case 'updateViews':
        update_views();
        break;
    
    default:
        //Will be check on the app, therefore needs to be hardcoded
        print_r('Error: request is NULL');
        break;
}

/**
 * Get the ARLEMs list from all_arlems table
 * @global object $DB Moodle database object
 * @global int $userid The user id
 * @global string $token The user token
 * @return array A JSON array
 */
function get_arlem_list() {

    global $DB, $userid, $token;


    if (isset($userid) && isset($token)) {

        $params = [1, $userid];
        //All pulblic and user's ARLEMs
        $sql = ' upublic = ? OR userid = ? ';
        $unsortedarlems = $DB->get_records_select('arete_allarlems', $sql, $params, 'timecreated DESC');

        //The moudules that the user enrolled to their activitie
        $usermoduleids = get_user_arete_modules_ids();

        //If the user is enrolled atleast to one activity which contains arete module
        if (!empty($usermoduleids)) {
            //Sort the list by assigned courses
            $arlems = sorted_arlemList_by_user_assigned($unsortedarlems, $usermoduleids);
        } else {
            $arlems = $unsortedarlems;
        }

        //Add author name to ARLEM file
        foreach ($arlems as $arlem) {
            $arlem->author = find_author($arlem);
        }

        print_r(json_encode($arlems));
        return;
    }

    //Get only the public ARLEMs
    $arlems = $DB->get_records('arete_allarlems', array('upublic' => 1), 'timecreated DESC');
    //
    //Adding author name to the ARLEM object
    foreach ($arlems as $arlem) {
        $arlem->author = find_author($arlem);
    }

    print_r(json_encode($arlems));
}

/**
 * Parse arete modules of a single course
 * @global object $CFG The Moodle config object
 * @global string $token The user token
 * @param int $courseID The course id
 * @return array An array with module instance id of the course
 */
function user_courses_contains_arete($courseID) {

    global $CFG, $token;

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_course_get_contents',
        'courseid' => $courseID
    );
    $response = mod_arete_httpPost("{$CFG->wwwroot}/webservice/rest/server.php", $params);

    $aretemodules = array();
    foreach (json_decode($response) as $items) {

        $modules = $items->modules;

        foreach ($modules as $mod) {
            if (strcmp($mod->modname, get_string('modname', 'arete')) === 0) {
                $aretemodules[] = $mod;
            }
        }
    }

    return $aretemodules;
}

/**
 * Get the courses which the user is enrolled to
 * 
 * @global object $CFG The Moodle config object
 * @global string $token The user token
 * @global int $userid The user id
 * @return An array with the arete modules ids of courses which the user is enrolled to
 */
function get_user_arete_modules_ids() {

    global $CFG, $token, $userid;

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_enrol_get_users_courses',
        'userid' => $userid
    );
    $response = mod_arete_httpPost("{$CFG->wwwroot}/webservice/rest/server.php" , $params);

    $usermoduleids = array();

    foreach (json_decode($response) as $course) {
        $aretemodules = user_courses_contains_arete($course->id);
        foreach ($aretemodules as $arete) {
            $usermoduleids[] = $arete->instance;
        }
    }

    return $usermoduleids;
}

/**
 * Bring the ARLEMs which are assigned to logged in user on top of the list
 * 
 * @global object $DB Moodle database object
 * @param array $arlemList an unsorted ARLEM list
 * @param array $user_arete_list list of arete ids which are assigned to the courses which user is enrolled to
 * @return array Sorted ARLEM list with ARLEMS of user enrolled courses are on top
 */
function sorted_arlemList_by_user_assigned($arlemList, $user_arete_list) {
    global $DB;

    //Get arete_arlems which user is enrolled to its areteid
    $array_par = join(',',array_fill(0,count($user_arete_list),'?'));
    $sql = 'areteid IN ( '.$array_par.' )';
    $aretearlemlist = $DB->get_records_select('arete_arlem', $sql, $user_arete_list);

    $temparlemList = $arlemList;

    if (!empty($arlemList) && !empty($user_arete_list)) {
        $newarray = array();

        foreach ($arlemList as $arlem) {

            foreach ($aretearlemlist as $aretearlem) {

                if ($arlem->fileid == $aretearlem->arlemid) {

                    //Find and add the deadline
                    $areteid_of_this_arlem = $aretearlem->areteid;
                    $arlem->deadline = get_course_deadline_by_arete_id($areteid_of_this_arlem);

                    //Add the user enrolled arete at the start of the list
                    $newarray[] = $arlem;

                    if (in_array($arlem, $temparlemList)) {

                        $index = array_search($arlem, $temparlemList);
                        unset($temparlemList[$index]); //Remove this item from arlem list
                    }
                }
            }
        }

        $finallist = array();
        $mergedlist = array_merge($newarray, $temparlemList);


        foreach ($mergedlist as $arlem) {
            //Add author name to ARLEM file
            $arlem->author = find_author($arlem);

            $finallist[$arlem->id] = $arlem;
        }
        return $finallist;
    }
}

/**
 * Get the deadline of a course which this module is a part of it
 * @global object $CFG The Moodle config object
 * @global string $token The user token
 * @global object $DB The Moodle database object
 * @param int $areteid the arete module instance id
 * @return string the deadline date in a specific format
 */
function get_course_deadline_by_arete_id($areteid) {

    global $CFG, $token, $DB;

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_course_get_course_module_by_instance',
        'module' => 'arete',
        'instance' => $areteid
    );
    $response = mod_arete_httpPost("{$CFG->wwwroot}/webservice/rest/server.php" , $params);

    $info = json_decode($response);

    // 2 is END_Of_COURSE, 28 is ARETE module
    $module = $DB->get_field('modules', 'id', array('name' => 'arete'));
    $deadline = $DB->get_field('course_modules', 'completionexpected', array('course' => $info->cm->course, 'module' => $module));

    return date('d.m.Y H:i ', $deadline);
}

/**
 * Get the info of the author of the ARLEM file
 * @global object $DB The Moodle database object
 * @param object $arlem An ARLEM object
 * @return string A string which contains the first name and last name of the author
 */
function find_author($arlem) {

    global $DB;
    //Add author name to ARLEM object
    $authoruser = $DB->get_record('user', array('id' => $arlem->userid));
    return "{$authoruser->firstname} {$authoruser->lastname}";
}

/**
 * Delete an arlem file
 * @global object $DB The Moodle database object
 * @global int $itemid The id of the ARLEM in arete_allalrems table
 * @global string $sessionid The activity id
 */
function delete_arlem() {

    global $DB, $itemid, $sessionid;

    if (!isset($sessionid)) {
        $sessionid = '';
    }

    if (!isset($itemid)) {
        $itemid = '';
    }

    $fileReference = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $itemid, 'sessionid' => $sessionid));
    $fileid = $DB->get_field('arete_allarlems', 'fileid', array('itemid' => $itemid, 'sessionid' => $sessionid));

    if (isset($itemid) && $fileReference !== null && $fileid !== null) {
        $result = delete_arlem_from_plugin($fileReference, $itemid, $sessionid, $fileid);
        print_r(json_encode($result));
    } else {
        //The text will be used on the webservice app, therefore it is hardcoded
        echo 'Error: Check if itemid is not empty. Or maybe the file you are trying to delete is not exist!';
        print_r(json_encode(false));
    }
}

/**
 * Delete an arlem file
 *
 * @throws \dml_exception if something goes wrong in the queries
 * @global object $DB The Moodle database object
 * int $itemid The id of the ARLEM in arete_allalrems table
 * Object $fileReference the file reference of the file we have to delete
 * Object $fileid the fileId of the arlem
 **/
function delete_arlem_from_plugin($fileReference, $itemid, $sessionid, $fileid){
    global $DB;

    if (!empty($fileReference)) {
        mod_arete_delete_arlem_from_plugin($fileReference, $itemid);
        $deletion = new arlem_deletion();
        $deletion->mod_arete_delete_arlem_from_other_tables($DB, $sessionid, $itemid, $fileid);
        return true;
    }
    return false;

}


/**
 * Update views of the ARLEM every time the activity opens on MirageXR
 * @global object $DB The Moodle database object
 * @global int $itemid The id of the ARLEM in arete_allalrems table 
 */
function update_views() {
    global $DB, $itemid;
    $currentviews = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

    if ($currentviews !== null) {
        $currentviews->views += 1;
        $DB->update_record('arete_allarlems', $currentviews);
    }
}
