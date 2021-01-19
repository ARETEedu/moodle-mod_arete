<?php

require_once("../../config.php");

$id = required_param('id', PARAM_INT); // Course Module ID.

$urlparams = array('id' => $id, 'name' => $name);

$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');


$PAGE->set_url($url);

require_course_login($course, false, $cm);


echo $OUTPUT->header();

echo $OUTPUT->notification("This list is just for demo. (Abbas)",'notifysuccess');

echo '<h3><b>Arlem List</b></h3><br>';

$context = context_course::instance($course->id);
if(has_capability('mod/arete:arlemfulllist', $context))
{
   show_files(); 
}


echo $OUTPUT->footer();


function show_files(){
    
    $fileList = glob('files/*');
    
    foreach($fileList as $file){
        if(is_file($file)){
            $filename = explode("/", $file)[1];
            $out = html_writer::tag('a', $filename, array('href' => $file)); 
            $out .= html_writer::tag('br', null);
            echo $out;
        }   
    }
}