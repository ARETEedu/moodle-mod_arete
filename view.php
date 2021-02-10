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

require_course_login($course, false, $cm);

echo $OUTPUT->notification(get_string('modulename', 'arete') . " demo version.",'notifysuccess');

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
   
///////Students view
if(has_capability('mod/arete:assignedarlemfile', $context))
{
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
    echo '<form action="'. $CFG->wwwroot.'/mod/arete/classes/save_assignment.php' . '" method="post">';
    echo html_writer::table(draw_arlem_table(getAllArlems(),  true)); //teacher arlem
    echo '<input type="hidden" id="homepageid" name="homepageid" value="'. $id .'">';
    echo '<input type="hidden" id="moduleid" name="moduleid" value="'. $moduleid .'">';
    echo '<input class="btn btn-primary" type="submit" value="Save">';
    echo '</form>';
    
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


