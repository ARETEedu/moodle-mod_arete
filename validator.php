<?php

require_once(dirname(__FILE__). '/../../config.php');
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
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css" rel="stylesheet" />';
echo '<link href="https://cdn.jsdelivr.net/npm/jsoneditor@9/dist/jsoneditor.min.css" rel="stylesheet" />';

$menu = html_writer::start_div('text-right');
$menu .= html_writer::empty_tag('input', array('type' => 'button' , 'value' => get_string('closevalidator', 'arete'), 'onClick' => 'javascript:window.close("","_parent","");'));
$menu .= html_writer::end_div();

echo $menu;


$validator = html_writer::start_div('', array('id' => 'container'));
    $validator .= html_writer::start_tag('noscript');
        $validator .= 'JavaScript needs to be enabled';
    $validator .= html_writer::end_tag('noscript');
    $validator .= html_writer::start_tag('script', array('src' => new moodle_url('https://openarlem.github.io/arlem.js/arlem.js'),   'data-app-activity' => $activityJSON,  'data-app-workplace' => $workplaceJSON) );
    $validator .= html_writer::end_tag('script');
 $validator .= html_writer::end_div();

echo $validator;

echo $OUTPUT->footer();
