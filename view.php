<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/output/outputs.php');

//current user
global $USER;

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


$PAGE->requires->css('/mod/arete/css/styles.css');  //pagination css file
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/table.js'));
        
//print Moodle header
echo $OUTPUT->header();


//id of this activity
$moduleid = $cm->instance;

//course context
$context = context_course::instance($course->id);


//Load ARLEMS
$searchword = filter_input(INPUT_GET, 'qword');
if(isset($searchword) && $searchword !== '')
{
    //search the jsons and return files if exists
    $arlems_list = search_arlems($searchword); 
}else{
    //get all ARLEMS from DB
    $arlems_list = getAllArlems(); 
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


            //need to add a single line between assigned table and arlem table
            echo '<br>'; 
            
            //Do not display the table tile if there are no ARLEM
            if(!empty($arlems_list)){
                 //label that show the list of all arlems which  are available
                 echo '<span class="titles">' . get_string('availabledarlem', 'arete') . '</span>';        
            }

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
    
   
        
}



echo $OUTPUT->footer();


