<?php

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
$id = required_param('id', PARAM_INT); // Course Module ID.
$urlparams = array('id' => $id);
$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');


//need to be login for this course
require_course_login($course, false, $cm);


//check if we are in edit mode
$pagemode = filter_input(INPUT_GET, 'mode' );
if(!isset($pagemode)){
    $pagemode = '';
    
}

//page configuration
$PAGE->set_url($url);
if($pagemode == 'edit'){
    $PAGE->set_title(get_string('editpagetitle', 'arete'));
}else{
    $PAGE->set_title(get_string('modulename', 'arete'));
}


$PAGE->requires->css('/mod/arete/css/styles.css');  //custom css file
$PAGE->requires->css('/mod/arete/assets/star-rating/dist/star-rating.css');  //rating css file

$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/assets/star-rating/dist/star-rating.js')); //for rating stars
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/table.js'));

//$userMenuNode = $PAGE->navigation->add(get_string('youraractivities', 'arete'), new moodle_url($CFG->wwwroot . '/mod/arete/view.php?id='. $id . '&mode=user'), navigation_node::TYPE_CUSTOM);
//$userMenuNode->make_active();

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
        echo '<span class="titles">' . get_string('editpagetitle', 'arete') . '</span><br><br>';
        
    }else{
        //Print the description
          echo '<span class="titles">' . get_string('description', 'arete') . '</span>';
          $description = $DB->get_field('arete', 'intro', array('id' => $moduleid));
          echo '<h5>'.$description.'</h5></br>';     
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

        if(isset($rolestr)){
               echo '<div class="right">'. get_string('rolelabel', 'arete') . '<span id="role">' . $rolestr . '</span></div>';
        }else{
                echo '<div class="right">'. get_string('roleundefined', 'arete') . '</div>';
        }


         //label that show this arlem is assinged to this activity
        echo '<span class="titles">' . get_string('assignedarlem', 'arete') . '</span>';

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
   
   $searchword = filter_input(INPUT_GET, 'qword');
   $sortingMode = filter_input(INPUT_GET, 'sort');
   
   if(!isset($sortingMode)){
       $sortingMode = "timecreated";
   }
   
   
    if(isset($searchword) && $searchword !== '')
    {
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
    $page_number = filter_input(INPUT_GET, 'pnum');//current page number

    
    // split ARLEMs list to small lists
    $splitet_list = array_chunk($arlems_list, $max_number_on_page); 
    
    
    //start at first page if pnum is not exist in the page url
    if(!isset($page_number) || $page_number < 1)
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
          echo '<span class="titles">' . get_string('availabledarlem', 'arete') . '</span>';        
     }

     //show the tab buttons
     echo create_tabs(count($arlems_list), $userViewMode );

    
    //Draw the searchbox
     echo searchbox($id);

    //create the ARLEMs tables
    draw_table_for_teachers($splitet_list, $page_number, $id, $moduleid);
    
    // create the pagination if arlemlist was not empty
    if(!empty($arlems_list)){
         $pagination = new pagination();
         echo '<br>' . $pagination->getPagination($splitet_list, $page_number, $id);
    }

}
    

echo $OUTPUT->footer();


