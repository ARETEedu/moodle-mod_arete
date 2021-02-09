<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/assignmanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/mod_form.php');
require_once($CFG->dirroot.'/mod/arete/classes/update_form.php');

global $USER;

$id = required_param('id', PARAM_INT); // Course Module ID.

$urlparams = array('id' => $id, 'name' => $name);

$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');

$PAGE->set_url($url);
$PAGE->set_title(get_string('modulename', 'arete'));

require_course_login($course, false, $cm);

echo $OUTPUT->notification("This list is just for demo. (Abbas)",'notifysuccess');

echo $OUTPUT->header();

$moduleid = $cm->instance;

//Print the description
$description = $DB->get_field('arete', 'intro', array('id' => $moduleid));
echo '<h5>'.$description.'</h5></br>';

$context = context_course::instance($course->id);


 ///if all arlem files is deleted  
$arlemlist = getAllArlems();
if(count($arlemlist) == 0){
    echo "No contents has been found.";
    exit();
}
    

///////Students view
if(has_capability('mod/arete:assignedarlemfile', $context))
{
   $activity_arlem = $DB->get_record('arete_arlem', array('areteid' => $moduleid));
   
   $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $activity_arlem->arlemid));
   
   $fileinfo = $DB->get_record('files', array ('id' => $activity_arlem->arlemid, 'itemid' => $arleminfo->itemid));
   
   $arlemfile = getArlemByName($fileinfo->filename,  $fileinfo->itemid);
   
   if($arlemfile != null){
       $url = getArlemURL($arlemfile->get_filename(), $arlemfile->get_itemid());
       $name = $arlemfile->get_filename();
        echo  '<a href="' .  $url . '">' . $name . '</a><br>';
   }

}



///////////Teachers View
if(has_capability('mod/arete:arlemfulllist', $context))
{

   $mform = new update_form();

    if ($mform->is_cancelled()) //if form canceled
    {
        // form cancelled, redirect
        redirect($CFG->wwwroot . '/course/view.php?id='. $PAGE->course->id, array());
        return;
    } else if (($data = $mform->get_data()))  //if form submitted
    {
        
        $update_record = new stdClass();
        $update_record-> id = $DB->get_field('arete_arlem', 'id', array('areteid' => $moduleid ));
        $update_record-> areteid = $moduleid;
        $update_record-> arlemid = $data->arlemid;
        $update_record->timecreated = time();
        
//         update the record with this id. $data comes from update_form
        $DB->update_record('arete_arlem', $update_record);
        
        
////  Delete all test arlems for test (REMOVE)
//    $arlemsList = getAllArlems( true);
//    foreach ($arlemsList as $arlem) {
//           deletePluginArlem($arlem->get_filename(), $arlem->get_itemid());
//    }
////        
        
        
        $mform->display();
        
    } else //if no action has been done yet
    {
        // Form has not been submitted or there was an error
        // Just display the form
        $mform->set_data(array('id' => $id));
       
//        $arlems = getAllArlems($context);
//        foreach ($arlems as $arlem) {
//          echo   $arlem->get_filename();
//        }

        $mform->display();
    }
}



echo $OUTPUT->footer();


