<?php

require_once("../../config.php");

$id = required_param('id', PARAM_INT); // Course Module ID.

$urlparams = array('id' => $id, 'name' => $name);

$url = new moodle_url('/mod/arete/view.php', $urlparams);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'arete');

require_login($course, true, $cm);
$PAGE->set_url($url);

echo $OUTPUT->header();
echo 'The ARLEM file should be placed somewhere here!';
echo $OUTPUT->footer();