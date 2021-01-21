<?php

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');

$id = required_param('id', PARAM_INT); // Course Module ID.

$urlparams = array('id' => $id, 'name' => $name);

$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');

$PAGE->set_url($url);
$PAGE->set_title(get_string('modulename', 'arete'));

require_course_login($course, false, $cm);


echo $OUTPUT->header();

echo $OUTPUT->notification("This list is just for demo. (Abbas)",'notifysuccess');
echo '<h3><b>Arlem List</b></h3><br>';

$context = context_course::instance($course->id);

//students
if(has_capability('mod/arete:assignedarlemfile', $context))
{
   $arlems_of_this_module = $DB->get_records('arete_arlem', array('areteid' => $cm->instance));//$cm->instance is the id of this module
   
   $arlem_utilities = new mod_arete_arlems_utilities();
   
   foreach ($arlems_of_this_module as $arlem) 
   {
       $name = $arlem_utilities->get_arlemname_from_db($arlem->arlemid);
       $url = $arlem_utilities->get_arlemurl_from_db($arlem->arlemid);

       echo  '<a href="' .  $url . '">' . $name . '</a><br>';
   }
}


echo $OUTPUT->footer();


