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

namespace mod_arete\webservices;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');

$request = filter_input(INPUT_POST, 'request');
$itemid = filter_input(INPUT_POST, 'itemid');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$userid = filter_input(INPUT_POST, 'userid');
$token = filter_input(INPUT_POST, 'token');

//what we need to send back to Unity
switch ($request) {
    case "arlemlist":
        get_arlem_list();
        break;
    case "deleteArlem":
        delete_arlem();
        break;
    case "updateViews":
        update_views();
        break;
    default:
        print_r('Error: request is NULL');
        break;
}

/**
 * Get  ARLEMs from all_arlems table
 *
 */
function get_arlem_list() {

    global $DB, $userid, $token;


    if (isset($userid) && isset($token)) {

        $params = [1, $userid];
        //All pulblic and user's ARLEMs
        $unsortedarlems = $DB->get_records_select('arete_allarlems', ' upublic = ? OR userid = ? ', $params, 'timecreated DESC');

        //the moudules that the user enrolled to their activitie
        $usermoduleids = get_user_arete_modules_ids();

        //if the user is enrolled atleast to one activity which contains arete module
        if (!empty($usermoduleids)) {
            //Sort the list by assigned courses
            $arlems = sorted_arlemList_by_user_assigned($unsortedarlems, $usermoduleids);
        } else {
            $arlems = $unsortedarlems;
        }

        //add author name to ARLEM file
        foreach ($arlems as $arlem) {
            $arlem->author = find_author($arlem);
        }

        print_r(json_encode($arlems));
        return;
    }

    //only public
    $arlems = $DB->get_records('arete_allarlems', array('upublic' => 1), 'timecreated DESC');
    //
    //add author name to ARLEM file
    foreach ($arlems as $arlem) {
        $arlem->author = find_author($arlem);
    }

    print_r(json_encode($arlems));
}

/**
 * parse arete modules of a single course
 *
 * @param course id
 *
 * @return An array with module instance id of the course
 */
function user_courses_contains_arete($courseID) {

    global $CFG, $token;

    $params = array(
        'wstoken' => $token,
        'moodlewsrestformat' => 'json',
        'wsfunction' => 'core_course_get_contents',
        'courseid' => $courseID
    );
    $response = mod_arete_httpPost($CFG->wwwroot . '/webservice/rest/server.php', $params);

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
    $response = mod_arete_httpPost($CFG->wwwroot . '/webservice/rest/server.php', $params);

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
 * @param $arlemList an unsorted ARLEM list
 * @param $user_arete_list list of arete ids which are assigned to the courses which user is enrolled to
 * @return Sorted ARLEM list with ARLEMS of user enrolled courses are on top
 */
function sorted_arlemList_by_user_assigned($arlemList, $user_arete_list) {
    global $DB;

    //get arete_arlems which user is enrolled to its areteid
    $aretearlemlist = $DB->get_records_select('arete_arlem', 'areteid IN ( ? )', array(implode(',', $user_arete_list)));

    $temparlemList = $arlemList;

    if (!empty($arlemList) && !empty($user_arete_list)) {
        $newarray = array();

        foreach ($arlemList as $arlem) {

            foreach ($aretearlemlist as $aretearlem) {

                if ($arlem->fileid == $aretearlem->arlemid) {

                    //find and add the deadline
                    $areteid_of_this_arlem = $aretearlem->areteid;
                    $arlem->deadline = get_course_deadline_by_arete_id($areteid_of_this_arlem);

                    //add the user enrolled arete at the begging of the list
                    $newarray[] = $arlem;

                    if (in_array($arlem, $temparlemList)) {

                        $index = array_search($arlem, $temparlemList);
                        unset($temparlemList[$index]); //remove this item from arlem list
                    }
                }
            }
        }

        $finallist = array();
        $mergedlist = array_merge($newarray, $temparlemList);


        foreach ($mergedlist as $arlem) {
            //add author name to ARLEM file
            $arlem->author = find_author($arlem);

            $finallist[$arlem->id] = $arlem;
        }
        return $finallist;
    }
}

/**
 * Get the deadline of a course which this module is a part of it
 *
 * @global $areteid the arete module instance id

 * @return the deadline date in a specific format
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
    $response = mod_arete_httpPost($CFG->wwwroot . '/webservice/rest/server.php', $params);

    $info = json_decode($response);

    $deadline = $DB->get_field('course', 'enddate', array('id' => $info->cm->course));

    return date('d.m.Y H:i ', $deadline);
}

/**
 * Get the info of thee ARLEms author
 *
 * @global $arlem Arlem from allarlem table
 *
 * @return First name and last name of the author
 */
function find_author($arlem) {

    global $DB;
    //add author name to ARLEM file
    $authoruser = $DB->get_record('user', array('id' => $arlem->userid));
    return $authoruser->firstname . ' ' . $authoruser->lastname;
}

/**
 *
 * Delete an arlem file
 * @global type $DB
 * @global string $itemid
 * @global string $sessionid
 */
function delete_arlem() {

    global $DB, $itemid, $sessionid;

    if (!isset($sessionid)) {
        $sessionid = '';
    }

    if (!isset($itemid)) {
        $itemid = '';
    }

    $filename = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $itemid, 'sessionid' => $sessionid));

    if (isset($itemid) && $filename !== null) {
        delete_arlem_from_plugin($filename, $itemid);
    } else {
        //The text will be used on the webservice app, therefore it is hardcoded
        echo 'Error: Check if itemid is not empty. Or maybe the file you are trying to delete is not exist!';
    }
}

/**
 * update views of the arlem every time the activity opens on MirageXR
 */
function update_views() {
    global $DB, $itemid;
    $currentviews = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

    if ($currentviews !== null) {
        $currentviews->views += 1;
        $DB->update_record('arete_allarlems', $currentviews);
    }
}
