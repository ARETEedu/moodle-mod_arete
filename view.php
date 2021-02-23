<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/output/outputs.php');

//current user
global $USER;

//Get module id, course and moudle infos
$id = required_param('id', PARAM_INT); // Course Module ID.
$urlparams = array('id' => $id, 'name' => $name);
$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');

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


$PAGE->requires->css('/mod/arete/css/styles.css');  //pagination css file
//
//need to be login for this course
require_course_login($course, false, $cm);

//print Moodle header
echo $OUTPUT->header();


//id of this activity
$moduleid = $cm->instance;

//course context
$context = context_course::instance($course->id);

//get all ARLEMS from DB
$arlems_list = getAllArlems(); 


// if all arlem files is deleted, delete the activity too and redirect to the course page
if(count($arlems_list) == 0)
{
    echo '<script>alert("' . get_string('noarlemalert', 'arete') . '");</script>';
    //if no ARLEM files is exist in the DB delete the activity too
    arete_delete_activity($moduleid);
    
    //return to the course page
    redirect($CFG->wwwroot . '/course/view.php?id='. $course->id, null);
    return;
}


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
    //dont show on edit mode
    if($pagemode != "edit")
    {
        //add the role to the top of the advtivity
        $roleassignments = $DB->get_record('role_assignments', array('userid' => $USER->id)); 
        if(isset($roleassignments->roleid)){
               $role = $DB->get_record('role', array('id' => $roleassignments->roleid)); 
               echo '<div class="right">'. get_string('rolelabel', 'arete') . '<span id="role">' .$role->shortname . '</span></div>';
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


///////////Teachers View
if(has_capability('mod/arete:arlemfulllist', $context))
{

    //edit mode
    if($pagemode == "edit"){
        
        //create edit page
        $editArlem = new EditArlem();

    }else{ //view mode 
        
        //maximum item on each page
           $max_number_on_page = 10; 

           //get the active page id from GET
           $page_number = filter_input(INPUT_GET, 'pnum');//current page number

           //start at first page if pnum is not exist in the page url
           if(!isset($page_number) || $page_number < 1)
           {
               $page_number = 1;
           }

           // split ARLEMs list to small lists
           $splitet_list = array_chunk($arlems_list, $max_number_on_page); 


           //label that show the list of all arlems which  are available
           echo '<br><span class="titles">' . get_string('availabledarlem', 'arete') . '</span>';

           //create the ARLEMs tables
           draw_table_for_teachers($splitet_list, $page_number, $id, $moduleid);


           // create the pagination 
           $pagination = new pagination();
           echo $pagination->getPagination($splitet_list, $page_number, $id);




       //// for testing only (REMOVE on release)
       ////  Delete all test arlems 
       //    $arlemsList = getAllArlems( true);
       //    foreach ($arlemsList as $arlem) {
       //           deletePluginArlem($arlem->get_filename(), $arlem->get_itemid());
       //    }
       //    $arlemsList = getAllUserArlems( true, 2 ,true);
       //    foreach ($arlemsList as $arlem) {
       //        deleteUserArlem($arlem->get_filename(), $arlem->get_itemid(), 2);
       //    }
       ////        
        
    }
    
   
        
}



echo $OUTPUT->footer();


