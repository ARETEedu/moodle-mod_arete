<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die();


global $DB;

$itemid = filter_input(INPUT_GET, 'itemid');

$activity = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

$filename = pathinfo($activity->filename, PATHINFO_FILENAME);

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('detailviewtitle', 'arete')) . ' "' . $filename . '"';
$PAGE->set_heading("Blank page");
$PAGE->set_url($CFG->wwwroot.'/mod/arete/detailview.php');
$PAGE->requires->css('/mod/arete/css/styles.css');  //pagination css file



echo $OUTPUT->header();

list($thumb_url, $css) = get_thumbnail($itemid);
        
$html = html_writer::start_tag('div', array('id' => 'detailview'));
$html .= '<span class="titles">' .get_string('detailviewtitle', 'arete').  ' "' . $filename . '"</span>';
$html .= html_writer::start_tag('div', array('id' => 'detailview-imgcontainer'));
$html .= html_writer::empty_tag('img', array ('src' => $thumb_url , 'alt' => 'test', 'id' => 'detailview-img' ));
$html .= html_writer::end_tag('div');
$html .= html_writer::start_tag('div', array('id' => 'detailview-detail'));

     $url = getArlemURL($activity->filename, $activity->itemid);

    //filename
    $html .= '<b>' . get_string('arlemtitle', 'arete') . ': </b>' . $filename;
    //time created
    $html .= '<br><b>' . get_string('datetitle', 'arete') . ': </b>' . date('m.d.Y H:i ', $activity->timecreated);
    //time modified
    $timeModified = $activity->timemodified == 0 ? get_string('neveredited', 'arete') : date('m.d.Y H:i ', $activity->timemodified);
    $html .= '<br><b>' . get_string('modifieddatetitle', 'arete') . ': </b>' . $timeModified;
    //size
    $html .= '<br><b>' . get_string('sizetitle', 'arete') . ': </b>' . get_readable_filesize($activity->filesize);
    //author
    list($authoruser, $src) =  getARLEMOwner($activity, $PAGE);
    $html .= '<br><b>' . get_string('authortitle', 'arete') . ': </b>' . $authoruser->firstname . ' ' . $authoruser->lastname ;
    
    //download button
    $html .= '<br><br><input type="button" class="button dlbutton"  name="dlBtn' . $activity->fileid . '" onclick="location.href=\''. $url . '\'" value="'. get_string('downloadbutton' , 'arete') . '">';
    
    //qr code button
    $html .= '&nbsp;&nbsp;<input type="button" class="button dlbutton"  name="dlBtn' . $activity->fileid . '" onclick="window.open(\'https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl='. $url . '\')" value="'. get_string('qrbutton' , 'arete') . '"><br>';
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

echo $html;

echo $OUTPUT->footer();
