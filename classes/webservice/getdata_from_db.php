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


$request = required_param('request', PARAM_RAW);
$itemid = optional_param('itemid', null, PARAM_INT);
$sessionid = optional_param('sessionid', null, PARAM_RAW);
$userid = optional_param('userid', null, PARAM_INT);
$token = optional_param('token', null, PARAM_RAW);

global $DB, $CFG;

//Check the request and do what needs be done
switch ($request) {
    case 'arlemlist':
        get_arlem_list($CFG, $DB, $userid, $token);
        break;
    
    case 'deleteArlem':
        delete_arlem($DB, $itemid, $sessionid, $token);
        break;
    
    case 'updateViews':
        update_views($DB, $itemid, $token);
        break;
    
    default:
        //Will be check on the app, therefore needs to be hardcoded
        print_r('Error: request is NULL');
        break;
}

/**
 * Get the ARLEMs list from all_arlems table
 *
 * @param object $CFG  the CFG object
 * @param object $DB Moodle database object
 * @param int $userid The user id
 * @param string $token The user token
 * @return array A JSON array
 */
function get_arlem_list($CFG, $DB, $userid, $token) {

    $fields = "id , contextid , fileid , userid , itemid , sessionid , filename , title , views , filesize , upublic , rate , timecreated , timemodified";

    if (isset($userid) && isset($token)) {

        $service_record = $DB->get_record('external_services', ['component' => 'mod_arete']);
        $token_record = $DB->get_record('external_tokens',
            ['externalserviceid' => $service_record->id,
            'token' => $token]);
        $user_contextid = $token_record->userid;

        if (is_siteadmin($user_contextid) || $userid==$user_contextid) {

            $params = [1, $userid];
            //All public and user's ARLEMs
            $sql = ' upublic = ? OR userid = ? ';
            $unsortedarlems = $DB->get_records_select('arete_allarlems', $sql, $params, 'timecreated DESC', $fields);

            //The moudules that the user enrolled to their activitie
            $usermoduleids = get_user_arete_modules_ids($CFG, $token, $userid);

            //If the user is enrolled atleast to one activity which contains arete module
            if (!empty($usermoduleids)) {
                //Sort the list by assigned courses
                $arlems = sorted_arlemList_by_user_assigned($CFG, $DB, $token, $unsortedarlems, $usermoduleids);
            } else {
                $arlems = $unsortedarlems;
            }

            //Add author name to ARLEM file
            foreach ($arlems as $arlem) {
                $arlem->author = find_author($DB, $arlem);
            }

            print_r(json_encode($arlems));
            return;
        }


    }

    //Get only the public ARLEMs
    $arlems = $DB->get_records('arete_allarlems', array('upublic' => 1), 'timecreated DESC', $fields);
    //
    //Adding author name to the ARLEM object
    foreach ($arlems as $arlem) {
        $arlem->author = find_author($DB, $arlem);
    }

    print_r(json_encode($arlems));
}

/**
 * Parse arete modules of a single course
 * @param object $CFG The Moodle config object
 * @param string $token The user token
 * @param int $courseID The course id
 * @return array An array with module instance id of the course
 */
function user_courses_contains_arete($CFG, $token,$courseID) {

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
 * @param object $CFG The Moodle config object
 * @param string $token The user token
 * @param int $userid The user id
 * @return An array with the arete modules ids of courses which the user is enrolled to
 */
function get_user_arete_modules_ids($CFG, $token, $userid) {

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_enrol_get_users_courses',
        'userid' => $userid
    );
    $response = mod_arete_httpPost("{$CFG->wwwroot}/webservice/rest/server.php" , $params);

    $usermoduleids = array();

    foreach (json_decode($response) as $course) {
        $aretemodules = user_courses_contains_arete($CFG, $token, $course->id);
        foreach ($aretemodules as $arete) {
            $usermoduleids[] = $arete->instance;
        }
    }

    return $usermoduleids;
}

/**
 * Bring the ARLEMs which are assigned to logged in user on top of the list
 *
 * @param object $CFG The CFG object
 * @param object $DB Moodle database object
 * @param string $token The token
 * @param array $arlemList an unsorted ARLEM list
 * @param array $user_arete_list list of arete ids which are assigned to the courses which user is enrolled to
 * @return array Sorted ARLEM list with ARLEMS of user enrolled courses are on top
 */
function sorted_arlemList_by_user_assigned($CFG, $DB, $token, $arlemList, $user_arete_list) {

    $finallist = array();

    //Get arete_arlems which user is enrolled to its areteid
    $sql = 'areteid IN ( ? )';
    $aretearlemlist = $DB->get_records_select('arete_arlem', $sql, array(implode(',', $user_arete_list)));

    $temparlemList = $arlemList;

    if (!empty($arlemList) && !empty($user_arete_list)) {
        $newarray = array();

        foreach ($arlemList as $arlem) {

            foreach ($aretearlemlist as $aretearlem) {

                if ($arlem->fileid == $aretearlem->arlemid) {

                    //Find and add the deadline
                    $areteid_of_this_arlem = $aretearlem->areteid;
                    $arlem->deadline = get_course_deadline_by_arete_id($CFG,  $DB, $token, $areteid_of_this_arlem);

                    //Add the user enrolled arete at the begging of the list
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
            $arlem->author = find_author($DB, $arlem);

            $finallist[$arlem->id] = $arlem;
        }
        return $finallist;
    }
    return $finallist;
}

/**
 * Get the deadline of a course which this module is a part of it
 * @param object $CFG The Moodle config object
 * @param object $DB The Moodle database object
 * @param string $token The user token
 * @param int $areteid the arete module instance id
 * @return string the deadline date in a specific format
 */
function get_course_deadline_by_arete_id($CFG,  $DB, $token, $areteid) {

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_course_get_course_module_by_instance',
        'module' => 'arete',
        'instance' => $areteid
    );
    $response = mod_arete_httpPost("{$CFG->wwwroot}/webservice/rest/server.php" , $params);

    $info = json_decode($response);

    $deadline = $DB->get_field('course', 'enddate', array('id' => $info->cm->course));

    return date('d.m.Y H:i ', $deadline);
}

/**
 * Get the info of the author of the ARLEM file
 * @param object $DB The Moodle database object
 * @param object $arlem An ARLEM object
 * @return string A string which contains the first name and last name of the author
 */
function find_author($DB, $arlem) {


    //Add author name to ARLEM object
    $authoruser = $DB->get_record('user', array('id' => $arlem->userid));
    return "{$authoruser->firstname} {$authoruser->lastname}";
}

/**
 * Delete an arlem file
 * @param object $DB The Moodle database object
 * @param int $itemid The id of the ARLEM in arete_allalrems table
 * @param string $sessionid The activity id
 * @param string $token The token
 */
function delete_arlem($DB, $itemid, $sessionid, $token) {

    if (!isset($token) || empty($token)){
        echo 'Only authenticated user can delete arlems';
        print_r(json_encode(false));
    }

    if (!isset($sessionid)) {
        $sessionid = '';
    }

    if (!isset($itemid)) {
        $itemid = '';
    }

    $fileReference = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $itemid, 'sessionid' => $sessionid));
    $fileid = $DB->get_field('arete_allarlems', 'fileid', array('itemid' => $itemid, 'sessionid' => $sessionid));
    $arlem_owner_user_id = $DB->get_field('arete_allarlems', 'userid', array('itemid' => $itemid, 'sessionid' => $sessionid));
    $is_private = $DB->get_field('arete_allarlems', 'upublic', array('itemid' => $itemid, 'sessionid' => $sessionid)) == 0;
    $service_record = $DB->get_record('external_services', ['component' => 'mod_arete']);
    $token_record = $DB->get_record('external_tokens',
        ['externalserviceid' => $service_record->id,
            'token' => $token]);
    $user_contextid = $token_record->userid;



    if ((isset($itemid) && $fileReference !== null && $fileid !== null) &&
        (!$is_private || ($is_private &&
                ($user_contextid==$arlem_owner_user_id || is_siteadmin($user_contextid) )))) {
        $result = delete_arlem_from_plugin($DB, $fileReference, $itemid, $sessionid, $fileid);
        print_r(json_encode($result));
    } else {
        //The text will be used on the webservice app, therefore it is hardcoded
        echo 'Error: Check if itemid is not empty. Or maybe the file you are trying to delete does not exist!';
        print_r(json_encode(false));
    }
}

/**
 * Delete an arlem file
 *
 * @throws \dml_exception if something goes wrong in the queries
 * @param object $DB The Moodle database object
 * @param Object $fileReference the file reference of the file we have to delete
 * @param int $itemid The id of the ARLEM in arete_allalrems table
 * @param string $sessionid The activity id
 * @param Object $fileid the fileId of the arlem
 **/
function delete_arlem_from_plugin( $DB, $fileReference, $itemid, $sessionid, $fileid){
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
 * @param object $DB The Moodle database object
 * @param int $itemid The id of the ARLEM in arete_allalrems table
 * @param string $token The token
 */
function update_views($DB, $itemid, $token) {
    $viewContainer = new \stdClass();
    if (isset($itemid) && $itemid != '' && isset($token)){

        $service_record = $DB->get_record('external_services', ['component' => 'mod_arete']);
        $token_record = $DB->get_record('external_tokens',
            ['externalserviceid' => $service_record->id,
                'token' => $token]);
        $user_contextid = $token_record->userid;

        if ($user_contextid != null) {
            $currentviews = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

            if ($currentviews !== null) {
                $viewContainer->views = $currentviews->views + 1;
                $currentviews->views = $viewContainer->views;
                $DB->update_record('arete_allarlems', $currentviews);
            }
        }
    }
    print_r (json_encode($viewContainer));
}
