<?php

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');
require_once($CFG->dirroot.'/mod/arete/mod_form.php');
require_once($CFG->dirroot.'/mod/arete/classes/update_form.php');

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
echo '<h5>'.$description.'<h5></br>';

echo '<h4><b>'. get_string('arleminactivity' , 'arete') .'</b></h4><br>';

$context = context_course::instance($course->id);


$arlem_utilities = new mod_arete_arlems_utilities();

//Students
if(has_capability('mod/arete:assignedarlemfile', $context))
{
   $arlems_of_this_module = $DB->get_records('arete_arlem', array('areteid' => $moduleid));
   
   foreach ($arlems_of_this_module as $arlem) 
   {
       $name = $arlem_utilities->get_arlemname_from_db($arlem->arlemid);
       $url = $arlem_utilities->get_arlemurl_from_db($arlem->arlemid);

       echo  '<a href="' .  $url . '">' . $name . '</a><br>';
   }
}



//Teachers
if(has_capability('mod/arete:arlemfulllist', $context))
{
   $mform = new update_form();

    if ($mform->is_cancelled()) 
    {
        // form cancelled, redirect
        redirect($CFG->wwwroot . '/course/view.php?id='. $PAGE->course->id, array());
        return;
    } else if (($data = $mform->get_data())) 
    {
        foreach ($selectedfiles as $arlem) 
        {
           $data->id = $DB->get_field('arete_arlem', 'id', array('areteid' => $moduleid, 'arlemid' => $arlem->id ));
           $data->areteid = $moduleid;
           $data->arlemid = $arlem->id;
           // form has been submitted
           update_assignment($data);
        }

    } else 
    {
        // Form has not been submitted or there was an error
        // Just display the form
        $mform->set_data(array('id' => $id));
            
        $mform->display();
    }
}



echo $OUTPUT->footer();


