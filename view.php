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

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/output/outputs.php');

defined('MOODLE_INTERNAL') || die;


//current user
global $USER;

//let javascript knows about the userid
echo '<script>window.userid =' . $USER->id . '</script>';

//Get module id, course and moudle infos
// Course Module ID.
$id = required_param('id', PARAM_INT); 
$urlparams = array('id' => $id);
$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');


//need to be login for this course
require_course_login($course, false, $cm);


//check if we are in edit mode
$pagemode = optional_param('mode', '', PARAM_TEXT); 

//page configuration
$PAGE->set_url($url);
if($pagemode == 'edit'){
    $PAGE->set_title(get_string('editpagetitle', 'arete'));
}else{
    $PAGE->set_title(get_string('modulename', 'arete'));
}

//custom css file
$PAGE->requires->css('/mod/arete/css/styles.css');  
//rating css file
$PAGE->requires->css('/mod/arete/assets/star-rating/dist/star-rating.css');  

//for rating stars
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/assets/star-rating/dist/star-rating.js')); 
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/table.js'));


//print Moodle header
echo $OUTPUT->header();

//id of this activity
$moduleid = $cm->instance;

//course context
$context = context_course::instance($course->id);

//every body view
if(has_capability('mod/arete:view', $context)){

    //edit mode
    if( $pagemode == "edit"){
        $editpageTitle = html_writer::start_tag('span' , ['class' => 'titles']);
        $editpageTitle .= get_string('editpagetitle', 'arete');
        $editpageTitle .= html_writer::end_tag('span');
        $editpageTitle .= html_writer::empty_tag('br');
        $editpageTitle .= html_writer::empty_tag('br');
        echo $editpageTitle;
        
    }else{
        //Print the description
        $descriptionLabel = html_writer::start_tag('span' , ['class' => 'titles']);
        $descriptionLabel .= get_string('description', 'arete');
        $descriptionLabel .= html_writer::end_tag('span');
        echo $descriptionLabel;
        $description = $DB->get_field('arete', 'intro', array('id' => $moduleid));
        echo '<h5>'.$description.'</h5>';  
        echo html_writer::empty_tag('br');
    }

}

///////Students view and teacher view
if(has_capability('mod/arete:assignedarlemfile', $context) || has_capability('mod/arete:arlemfulllist', $context))
{
    //initiated ata for using in javascript
    init(0);
    
    //create the top menu(not on edit/structure page)
    if($pagemode != "edit")
    {
        echo Create_student_menu();    
    }

    //dont show on edit mode and when on user page
    if($pagemode != "edit" && $pagemode != "user")
    {
        //add the role to the top of the advtivity
        $roles = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            $rolestr[] = role_get_name($role, $context);
        }
        $rolestr = implode(', ', $rolestr);

        //Print the role of the user
        if(isset($rolestr)){
            $role = html_writer::start_tag('div' , ['class' => 'right']);
            $role .= get_string('rolelabel', 'arete');
            $role .= html_writer::start_tag('span' , ['id' => 'role']);
            $role .= $rolestr;
            $role .= html_writer::end_tag('span');
            $role .= html_writer::end_tag('div');
            echo $role;
        }else{
            //undefined
            $role = html_writer::start_tag('div' , ['class' => 'right']);
            $role .= get_string('roleundefined', 'arete');
            $role .= html_writer::end_tag('div');
            echo $role;
        }

        //The label of assinged ARLEM
        $assignedLabel .= html_writer::start_tag('span' , ['class' => 'titles']);
        $assignedLabel .= get_string('assignedarlem', 'arete');
        $assignedLabel .= html_writer::end_tag('span');
        echo $assignedLabel;

        //print assigned table
        $result = draw_table_for_students($moduleid);

         //show notification if no arlem is assigned
        if($result == false){
            echo $OUTPUT->notification(get_string('notassignedyer' , 'arete'));
        }
    }
}

//create edit page
if($pagemode == "edit")
{

    $editArlem = new EditArlem();
}
else if($pagemode == "user")
{
    //show only User arlems
    $arlems_list = search_result(true);
    generate_arlem_table($arlems_list, 1);
}
else{
    
    ///////////Teachers View
    if(has_capability('mod/arete:arlemfulllist', $context)){
        $arlems_list = search_result(false);
        generate_arlem_table($arlems_list);

    }
   
}

//Create an array of needed ARLEMS
//if search word not findign then find all user arlems if $is_user_table is true otherwise return all arlems for manager
function search_result($is_user_table){
   
   $searchword = optional_param('qword', null, PARAM_TEXT);
   $sortingMode = optional_param('sort', 'timecreated', PARAM_TEXT);
   
    if(isset($searchword) && $searchword !== '')
    {
        require_sesskey();
        
        //remove invalid characters
        $searchword = str_replace(array("'", '"', ';', '{', '}', '[', ']', ':'), '', $searchword);
        //search the jsons and return files if exists
        $arlems_list = search_arlems($searchword, $is_user_table, $sortingMode); 
    }else if($is_user_table){
        $arlems_list = getAllUserArlems($sortingMode);
    }else{
        $arlems_list = getAllArlems($sortingMode);
    }
    
    return $arlems_list;
}


function generate_arlem_table($arlems_list, $userViewMode = 0){
    
    global $id, $moduleid;
    
    //initiated ata for using in javascript
    init($userViewMode);

    //maximum item on each page
    $max_number_on_page = 10; 

    //get the active page id from GET
    $page_number = optional_param('pnum', 1, PARAM_INT);
    
    // split ARLEMs list to small lists
    $splitet_list = array_chunk($arlems_list, $max_number_on_page); 
    
    //start at first page if pnum is not exist in the page url
    if($page_number < 1)
    {
        $page_number = 1;
    }else if($page_number > count($splitet_list)){
        $page_number =  count($splitet_list);
    }
    

    //need to add a single line between assigned table and arlem table
    echo '<br>'; 

    //Do not display the table tile if there are no ARLEM
    if(!empty($arlems_list)){
          //label that show the list of all arlems which  are available
        $availableArlemLabel = html_writer::start_tag('span' , ['class' => 'titles']);
        $availableArlemLabel .= get_string('availabledarlem', 'arete');
        $availableArlemLabel .= html_writer::end_tag('span');
        echo $availableArlemLabel;        
     }

    //Draw the searchbox
    echo searchbox();

    //create the ARLEMs tables
    draw_table_for_teachers($splitet_list, $page_number, $id, $moduleid);
    
    // create the pagination if arlemlist was not empty
    if(!empty($arlems_list)){
        $pagination = new pagination();
        echo html_writer::empty_tag('br') . $pagination->getPagination($splitet_list, $page_number, $id);
    }

}
    
echo $OUTPUT->footer();


