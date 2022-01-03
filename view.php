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
use function \mod_arete\output\init as init;
use function \mod_arete\output\create_student_menu as create_student_menu;
use function \mod_arete\output\draw_table_for_students as draw_table_for_students;
use function \mod_arete\output\searchbox as searchbox;
use function \mod_arete\output\draw_table_for_teachers as draw_table_for_teachers;
use \mod_arete\output\pagination as pagination;
use \mod_arete\output\edit_arlem as edit_arlem;

require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/mod/arete/locallib.php");
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/output/outputs.php");

defined('MOODLE_INTERNAL') || die;

//The current user
global $USER;

//Let javascript knows about the userid
echo "<script>window.userid=$USER->id</script>";

//Get module id, course and moudle infos
// Course Module ID.
$id = required_param('id', PARAM_INT);
$urlparams = array('id' => $id);
$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');


//Need to be login for this course
require_course_login($course, false, $cm);


//Check if we are in edit mode
$pagemode = optional_param('mode', '', PARAM_TEXT);

//Page configuration
$PAGE->set_url($url);
if ($pagemode == 'edit') {
    $PAGE->set_title(get_string('editpagetitle', 'arete'));
} else {
    $PAGE->set_title(get_string('modulename', 'arete'));
}

//Custom css file
$PAGE->requires->css('/mod/arete/css/styles.css');
//rating css file
$PAGE->requires->css('/mod/arete/assets/star-rating/dist/star-rating.css');

//For rating stars
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/mod/arete/assets/star-rating/dist/star-rating.js"));
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/mod/arete/js/table.js"));


//Print Moodle header
echo $OUTPUT->header();

//ID of this activity
$moduleid = $cm->instance;

//Course context
$context = context_course::instance($course->id);

//The view of any user
if (has_capability('mod/arete:view', $context)) {

    //If it is the edit mode
    if ($pagemode == "edit") {
        $editpageTitle = html_writer::start_tag('span', ['class' => 'titles']);
        $editpageTitle .= get_string('editpagetitle', 'arete');
        $editpageTitle .= html_writer::end_tag('span');
        $editpageTitle .= html_writer::empty_tag('br');
        $editpageTitle .= html_writer::empty_tag('br');
        echo $editpageTitle;
    } else {
        //Print the description
        $descriptionLabel = html_writer::start_tag('span', ['class' => 'titles']);
        $descriptionLabel .= get_string('description', 'arete');
        $descriptionLabel .= html_writer::end_tag('span');
        echo $descriptionLabel;
        $description = $DB->get_field('arete', 'intro', array('id' => $moduleid));
        echo "<h5>$description</h5>";
        echo html_writer::empty_tag('br');
    }
}

//Students view and teacher view
if (has_capability('mod/arete:assignedarlemfile', $context) || has_capability('mod/arete:arlemfulllist', $context)) {
    //Initiated ata for using in javascript
    init(0);

    //Create the top menu(not on edit/structure page)
    if ($pagemode != 'edit') {
        echo create_student_menu();
    }

    //Do not show on edit mode and on the user page
    if ($pagemode != 'edit' && $pagemode != 'user') {
        //Add the role to the top of the advtivity
        $roles = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            $rolestr[] = role_get_name($role, $context);
        }
        $rolestr = implode(', ', $rolestr);

        //Print the role of the user
        if (isset($rolestr)) {
            $role = html_writer::start_tag('div', ['class' => 'right']);
            $role .= get_string('rolelabel', 'arete');
            $role .= html_writer::start_tag('span', ['id' => 'role']);
            $role .= $rolestr;
            $role .= html_writer::end_tag('span');
            $role .= html_writer::end_tag('div');
            echo $role;
        } else {
            //Undefined
            $role = html_writer::start_tag('div', ['class' => 'right']);
            $role .= get_string('roleundefined', 'arete');
            $role .= html_writer::end_tag('div');
            echo $role;
        }

        //The label of assinged ARLEM
        $assignedlabel = html_writer::start_tag('span', ['class' => 'titles']);
        $assignedlabel .= get_string('assignedarlem', 'arete');
        $assignedlabel .= html_writer::end_tag('span');
        echo $assignedlabel;

        //print assigned table
        $result = draw_table_for_students($moduleid);

        //show notification if no arlem is assigned
        if ($result == false) {
            echo $OUTPUT->notification(get_string('notassignedyer', 'arete'));
        }
    }
}

//Create the edit page
if ($pagemode == 'edit') {

    $editarlem = new edit_arlem();
} else if ($pagemode == 'user') {
    //show only User arlems
    $arlemslist = search_result(true);
    generate_arlem_table($arlemslist, 1);
} else {

    //Teachers View
    if (has_capability('mod/arete:arlemfulllist', $context)) {
        $arlemslist = search_result(false);
        generate_arlem_table($arlemslist);
    }
}

/**
 * Create an array of needed ARLEMS
 * if search word not finding then find all user ARLEMS if $is_user_table is true otherwise return all ARLEMS for manager
 * @param bool $is_user_table The status of the user type
 * @return array The array with the search result based on user role
 */
function search_result($is_user_table) {

    $searchword = optional_param('qword', null, PARAM_TEXT);
    $sortingmode = optional_param('sort', 'timecreated', PARAM_TEXT);

    if (isset($searchword) && $searchword !== '') {
        require_sesskey();

        //Remove invalid characters
        $searchword = str_replace(array('\'', '"', ';', '{', '}', '[', ']', ':'), '', $searchword);
        //Search the jsons and return files if exists
        $arlemslist = mod_arete_search_arlems($searchword, $is_user_table, $sortingmode);
    } else if ($is_user_table) {
        $arlemslist = mod_arete_get_user_arlems($sortingmode);
    } else {
        $arlemslist = mod_arete_get_allarlems($sortingmode);
    }

    return $arlemslist;
}


/**
 * Generate the main ARLEM table
 * @global int $id The activity id
 * @global int $moduleid The activity module id
 * @param array $arlemslist The ARLEMs
 * @param int $userviewmode What type of user is viewing the page (1 = student)
 */
function generate_arlem_table($arlemslist, $userviewmode = 0) {

    global $id, $moduleid;

    //Initiated ata for using in javascript
    init($userviewmode);

    //Maximum item on each page
    $maxnumberonpage = 10;

    //Get the active page id from GET
    $pagenumber = optional_param('pnum', 1, PARAM_INT);

    // Split ARLEMs list to small lists
    $splitetlist = array_chunk($arlemslist, $maxnumberonpage);

    //Start at first page if pnum is not exist in the page url
    if ($pagenumber < 1) {
        $pagenumber = 1;
    } else if ($pagenumber > count($splitetlist)) {
        $pagenumber = count($splitetlist);
    }


    //Need to add a single line between assigned table and arlem table
    echo html_writer::empty_tag('br');

    //Do not display the table tile if there are no ARLEM
    if (!empty($arlemslist)) {
        //label that show the list of all arlems which  are available
        $availablearlemlabel = html_writer::start_tag('span', ['class' => 'titles']);
        $availablearlemlabel .= get_string('availabledarlem', 'arete');
        $availablearlemlabel .= html_writer::end_tag('span');
        echo $availablearlemlabel;
    }

    //Draw the searchbox
    echo searchbox();

    //Create the ARLEMs tables
    draw_table_for_teachers($splitetlist, $pagenumber, $id, $moduleid);

    // Create the pagination if arlemlist was not empty
    if (!empty($arlemslist)) {
        echo html_writer::empty_tag('br');

        $pagination = new pagination();
        echo $pagination->getPagination($splitetlist, $pagenumber, $id);
    }
}

echo $OUTPUT->footer();
