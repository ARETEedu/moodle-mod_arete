<?php

require_once(dirname(__FILE__). '/../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');
require_once($CFG->libdir . '/pagelib.php');


defined('MOODLE_INTERNAL') || die();

global $PAGE;

$activityJSON = filter_input(INPUT_GET, 'activity');
$workplaceJSON = filter_input(INPUT_GET, 'workplace');

if(!isset($activityJSON) || !isset($workplaceJSON)){
    echo get_string('jsonnotfound', 'arete');
    die();
}

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('validator', 'arete'));
$PAGE->set_heading("Blank page");
$PAGE->set_url($CFG->wwwroot.'/mod/arete/validator.php');
$PAGE->requires->css('/mod/arete/css/styles.css');  //pagination css file

echo $OUTPUT->header();

$validator = html_writer::start_div('', array('id' => 'container'));
    $validator .= html_writer::start_tag('noscript');
        $validator .= 'JavaScript needs to be enabled';
    $validator .= html_writer::end_tag('noscript');
    $validator .= html_writer::start_tag('script', array('src' => new moodle_url($CFG->wwwroot . '/mod/arete/tools/arlem.js'),   'data-app-activity' => $activityJSON,  'data-app-workplace' => $workplaceJSON) );
    $validator .= html_writer::end_tag('script');
 $validator .= html_writer::end_div();

echo $validator;

echo $OUTPUT->footer();
