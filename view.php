<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/arlem_utilities.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
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
echo '<h5>'.$description.'</h5></br>';

$context = context_course::instance($course->id);


//Students
if(has_capability('mod/arete:assignedarlemfile', $context))
{
   $arlems_of_this_module = $DB->get_records('arete_arlem', array('areteid' => $moduleid));
   
   foreach ($arlems_of_this_module as $arlem) 
   {
//       $name = get_arlemname_from_db($arlem->arlemid);
//       $url = get_arlemurl_from_db($arlem->arlemid);

//       echo  '<a href="' .  $url . '">' . $name . '</a><br>';
   }
}



//Teachers
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
        
        $arlemFile = getArlem($data->arlem, $context);
        $update_record-> arlemid = $arlemFile->get_id();
        $update_record->timecreated = time();
        
        // update the record with this id. $data comes from update_form
        $DB->update_record('arete_arlem', $update_record);
        
////
        //creates a sample file
//        if(!isArlemExist( $data->arlem ,$context ))
//        {
//            createArlem($data->arlem,'this is a text file is create on' . time(), $context);
//        }
//       
//        //this will copy the file to temp folder
//        copyArlemToTemp($data->arlem, $context);
      
//        //this will delete the file
//        deleteArlem($data->arlem, $context);
        
 //     this will print all file in this filearea

        
//        get the real path by hash path
//        urlByHash('da39a3ee5e6b4b0d3255bfef95601890afd80709');

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


