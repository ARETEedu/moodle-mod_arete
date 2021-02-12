<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/output/arlemtable.php');
    
global $USER;

$id = required_param('id', PARAM_INT); // Course Module ID.

$urlparams = array('id' => $id, 'name' => $name);

$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');

$PAGE->set_url($url);
$PAGE->set_title(get_string('modulename', 'arete'));
$PAGE->requires->css('/mod/arete/css/pagination.css');  //pagination css file

require_course_login($course, false, $cm);

echo $OUTPUT->header();

$moduleid = $cm->instance;

//Print the description
echo '<h4>' . get_string('description', 'arete') . '</h4>';
$description = $DB->get_field('arete', 'intro', array('id' => $moduleid));
echo '<h5>'.$description.'</h5></br>';


$context = context_course::instance($course->id);


// if all arlem files is deleted, delete the activity too and redirect to the course page
$arlemlist = getAllArlems();
if(count($arlemlist) == 0)
{
    //    echo '<div style="text-align: center;">No contents has been found. <b>' . $cm->name . '</b> can be deleted.</div>';
    
    arete_delete_activity($moduleid);
    redirect($CFG->wwwroot . '/course/view.php?id='. $PAGE->course->id, array());
    return;
}
   
///////Students view and teacher view
if(has_capability('mod/arete:assignedarlemfile', $context) || has_capability('mod/arete:arlemfulllist', $context))
{
    //label that show this arlem is assinged to this activity
   echo '<h4>' . get_string('assignedarlem', 'arete') . '</h4>';
    
   $activity_arlem = $DB->get_record('arete_arlem', array('areteid' => $moduleid));
   $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $activity_arlem->arlemid));
   $fileinfo = $DB->get_record('files', array ('id' => $activity_arlem->arlemid, 'itemid' => $arleminfo->itemid));
   $arlemfile = getArlemByName($fileinfo->filename,  $fileinfo->itemid);
   
    echo html_writer::table(draw_arlem_table(array($arlemfile))); //student arlem
    //return;
}


///////////Teachers View
if(has_capability('mod/arete:arlemfulllist', $context))
{
    $max_number_on_page = 10; //maximum item on each page
    
    
    $page_number = filter_input(INPUT_GET, 'pnum');//current page number
    //start at first page if pnum is not exist in the page url
    if(!isset($page_number))
    {
        $page_number = 1;
    }
  
    
    $arlems_list = getAllArlems(); //all arlems
    $splitet_list = array_chunk($arlems_list, $max_number_on_page); //arlems splited to small list


    //label that show the list of all arlems which  are available
    echo '<br><h4>' . get_string('availabledarlem', 'arete') . '</h4>';
   
    echo '<form action="classes/save_assignment.php" method="post">';
    echo html_writer::table(draw_arlem_table($splitet_list[$page_number-1],  true)); //arlems table
    echo '<input type="hidden" id="returnurl" name="returnurl" value="'. $CFG->wwwroot .'/mod/arete/view.php?id='. $id . '&pnum=' . $page_number . '">';
    echo '<input type="hidden" id="moduleid" name="moduleid" value="'. $moduleid .'">';
    echo '<div style="float:right;"><input class="btn btn-primary" type="submit" value="Save"></div>'; //submit button
    echo '</form>';
    
    // pagination begin
    $p = html_writer::start_tag('div', array('class' => 'pagination'));

    
    $p .= html_writer::start_tag('a', array('href' => $page_number == 1 ? '#' : 'view.php?id=' . $id . '&pnum=' . strval($page_number-1) )); //back button
    $p .= 'Prev';
    $p .= html_writer::end_tag('a');
    
    for($i = 1; $i < count($splitet_list)+1; $i++)
    {
        //make diffrent colot for active page
        if($i == $page_number){
            $pageAttr = array('class' => 'btn btn-primary', 'href' => 'view.php?id='. $id . '&pnum=' . $i );
        }else{
            $pageAttr = array('href' => 'view.php?id='. $id . '&pnum=' . $i );
        }

        $p .= html_writer::start_tag('a', $pageAttr);
        $p .= $i;
        $p .= html_writer::end_tag('a');
    }

    $p .= html_writer::start_tag('a', array('href' => $page_number == count($splitet_list) ? '#' : 'view.php?id=' . $id . '&pnum=' . strval($page_number+1) )); //back button
    $p .= 'Next';
    $p .= html_writer::end_tag('a');
    
    $p .= html_writer::end_tag('div');
    
    echo $p;
    //// pagination ends
    
    //check the radio button of the assigend arlem on loading the page
    update_assignment($moduleid);
    
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

echo $OUTPUT->footer();


