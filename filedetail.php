<?php

// This file is part of the Augmented Reality Experience plugin (mod_arete) for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file will be used by the arete block (latestaritems) to print an ARLEM file.
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//This classes will be used for the block view page
require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/utilities.php");

defined('MOODLE_INTERNAL') || die();

global $DB;

$itemid = filter_input(INPUT_GET, 'itemid');

$activity = $DB->get_record('arete_allarlems', array('itemid' => $itemid));

$filename = pathinfo($activity->filename, PATHINFO_FILENAME);

$title = get_string('detailviewtitle', 'arete');

$PAGE->set_context(context_system::instance());
$PAGE->set_title("$title \"$filename\"");
$PAGE->set_heading("Blank page");
$PAGE->set_url("$CFG->wwwroot/mod/arete/detailview.php");
$PAGE->requires->css('/mod/arete/css/styles.css');  //pagination css file


echo $OUTPUT->header();

list($thumb_url, $css) = mod_arete_get_thumbnail($itemid);

$html = html_writer::start_tag('div', array('id' => 'detailview'));
$html .= html_writer::start_tag('span', array('class' => 'titles'));
$html .= "$title \"$filename\"";
$html .= html_writer::end_tag('span');
$html .= html_writer::start_tag('div', array('id' => 'detailview-imgcontainer'));
$html .= html_writer::empty_tag('img', array('src' => $thumb_url, 'alt' => 'test', 'id' => 'detailview-img'));
$html .= html_writer::end_tag('div');
$html .= html_writer::start_tag('div', array('id' => 'detailview-detail'));

$url = mod_arete_get_arlem_url($activity->filename, $activity->itemid, true);

//Filename
$arlemtitle = get_string('arlemtitle', 'arete');
$html .= "<b>$arlemtitle: </b>$filename";
//Time created
$html .= html_writer::empty_tag('br');
$datetitle = get_string('datetitle', 'arete');
$date = date('m.d.Y H:i ', $activity->timecreated);
$html .= "<b>$datetitle: </b>$date";
//Time modified
$timeModified = $activity->timemodified == 0 ? get_string('neveredited', 'arete') : date('m.d.Y H:i ', $activity->timemodified);
$html .= html_writer::empty_tag('br');
$modifiedtitle = get_string('modifieddatetitle', 'arete');
$html .= "<b>$modifiedtitle: </b>$timeModified";
//Size
$html .= html_writer::empty_tag('br');
$sizetitle = get_string('sizetitle', 'arete');
$filesize = mod_arete_get_readable_filesize($activity->filesize);
$html .= "<b>$sizetitle: </b>$filesize";
//Author
list($authoruser, $src) = mod_arete_get_arlem_owner($activity, $PAGE);
$html .= html_writer::empty_tag('br');
$authortitle = get_string('authortitle', 'arete');
$html .= "<b>$authortitle: </b>$authoruser->firstname $authoruser->lastname";

//Download button
$html .= html_writer::empty_tag('br');
$html .= html_writer::empty_tag('br');
$downloadbuttonparams = array(
    'type' => 'button',
    'class' => 'button dlbutton',
    'name' => 'dlBtn' . $activity->fileid,
    'onclick' => "javascript:location.href='{$url}'",
    'value' => get_string('downloadbutton', 'arete')
);

$html .= html_writer::empty_tag('input', $downloadbuttonparams);

$html .= '&nbsp;&nbsp;';

//QR code button
$wekitprotocolurl = mod_arete_get_arlem_url($activity->filename, $activity->itemid);
$qrbuttonparams = array(
    'type' => 'button',
    'class' => 'button dlbutton',
    'name' => 'dlBtn' . $activity->fileid,
    'onclick' => "javascript:window.open('https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=$wekitprotocolurl')",
    'value' => get_string('qrtitle', 'arete')
);

$html .= html_writer::empty_tag('input', $qrbuttonparams);

$html .= html_writer::empty_tag('br');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

echo $html;

echo $OUTPUT->footer();
