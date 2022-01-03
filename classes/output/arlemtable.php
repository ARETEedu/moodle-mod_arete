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
 * Prints the ARLEM table for both students and the teachers
 * All other menus like top menu is defined in this file
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\output;

use html_writer,
    context_course,
    html_table;

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/utilities.php");
require_once("$CFG->dirroot/mod/arete/classes/output/teacher_top_buttons.php");

$searchfield = filter_input(INPUT_GET, 'qword');

/**
 * Print the activity table for the teachers which will show all available activities
 *
 * @param array $splitetlist The list of activity files which should be shown in this page
 * @param int $pagenumber Page number from the pagination
 * @param int $id Course Module ID.
 * @param int $moduleid Id of the arete module
 */
function draw_table_for_teachers($splitetlist, $pagenumber, $id, $moduleid) {

    global $searchfield;

    //Check if the user searched for somthing
    $searchquery = '';
    if (isset($searchfield)) {

        if (empty($splitetlist)) {
            $startmesssage = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            $startmesssage .= html_writer::start_div('alarm');
            $startmesssage .= get_string('nomatchfound', 'arete') . "'{$searchfield}'";
            $startmesssage .= html_writer::end_div();
            echo $startmesssage;
            return;
        }

        $searchquery = "&qword=$searchfield";

        //Check if there are no arlem on this server at all
    } else {

        if (empty($splitetlist)) {
            $startmesssage = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            $startmesssage .= html_writer::start_div('alarm');
            $startmesssage .= get_string('noarlemfound', 'arete');
            $startmesssage .= html_writer::end_div();
            echo $startmesssage;
            return;
        }
    }

    //The popup modal div
    echo add_popup_image_div();


    //Print the arlem table for the teachers
    $params = array(
        'splitetlist' => $splitetlist,
        'pagenumber' => $pagenumber,
        'moduleid' => $moduleid,
        'coursemoduleid' => $id,
        'searchquery' => $searchquery
    );
    $tableform = new teacher_top_buttons('classes/save_assignment.php', $params);

    //Form processing and displaying is done here
    if ($tableform->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($fromform = $tableform->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
    } else {
        //Displays the form
        $tableform->display();
    }
}

/**
 * Add the needed div for showing pop up images when click on thumbnails
 * @return string $popupmodal The code for the modal which the thumbnails will be displayed on
 */
function add_popup_image_div() {

    $popupmodal = html_writer::start_tag('div', array('id' => 'modal'));
    $popupmodal .= html_writer::start_tag('span', array('id' => 'modalImg'));
    $popupmodal .= html_writer::start_tag('div', array('id' => 'modalTitle'));
    $popupmodal .= html_writer::end_tag('div');
    $popupmodal .= html_writer::empty_tag('img', array('class' => 'modalImage'));
    $popupmodal .= html_writer::end_tag('span');
    $popupmodal .= html_writer::end_tag('div');

    return $popupmodal;
}

/**
 * Print the activity table which shows only the assigned activity to the students
 *
 * @param int $moduleid Id of the arete module
 * @return bool Status of the table creation
 */
function draw_table_for_students($moduleid) {

    global $DB;
    //Get the assigned ARLEM from DB
    $activityarlem = $DB->get_record('arete_arlem', array('areteid' => $moduleid));

    //Get the ARLEM id of the assigned from DB
    if ($activityarlem != null) {
        $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $activityarlem->arlemid));
    }

    //print the assigned ARLEM in a single table row if it is exist
    if (isset($arleminfo) && $arleminfo != null) {
        echo html_writer::table(draw_table(array($arleminfo), 'assignedTable', false, $moduleid)); //student arlem
        return true;
    } else {
        return false;
    }
}

/**
 *
 * Print the activity table for the teachers which will show all available activities
 *
 * @param $arlemslist The list of activity files which should be shown in this table
 * @param $tableid A unique id that you can use it later for assigning different CSS classes to this table
 * @param $show_radio_button If true assignment would be possible (this should be true only for the teacher table)
 * @return object Return a Moodle table
 *
 */
function draw_table($arlemslist, $tableid, $teacherview = false, $moduleid = null) {
    global $USER, $CFG, $COURSE, $PAGE;

    $context = context_course::instance($COURSE->id);

    $table = new html_table();

    $datetitle = $searchbox = get_string('datetitle', 'arete');
    $modifieddatetitle = get_string('modifieddatetitle', 'arete');
    $arlemtitle = get_string('arlemtitle', 'arete');
    $arlemthumbnail = get_string('arlemthumbnail', 'arete');
    $viewstitle = get_string('viewstitle', 'arete');
    $sizetitle = get_string('sizetitle', 'arete');
    $authortitle = get_string('authortitle', 'arete');
    $playtitle = get_string('playtitle', 'arete');
    $downloadtitle = get_string('downloadtitle', 'arete');
    $edittitle = get_string('editbutton', 'arete');
    $qrtitle = get_string('qrtitle', 'arete');
    $publictitle = get_string('publictitle', 'arete');
    $deletetitle = get_string('deletetitle', 'arete');
    $assigntitle = get_string('assigntitle', 'arete');
    $assignedbytitle = get_string('assignedbytitle', 'arete');
    $ratingtitle = get_string('ratingtitle', 'arete');

    //Sortable headers for the ARLEM tables
    if ($teacherview) {

        //Time created column header
        $sortmode = filter_input(INPUT_GET, 'sort');
        $defaultorderIcon = filter_input(INPUT_GET, 'order') == 'ASC' ? ' ↑' : ' ↓';

        if ($sortmode == 'timecreated' || !isset($sortmode)) {
            $timecreatedorderIcon = $defaultorderIcon;
        } else {
            $timecreatedorderIcon = '';
        }

        $datetitle = html_writer::start_div('headers', array('id' => 'timecreated'));
        $timecreatedparams = array('onclick' => 'reverse_sorting("' . 'timecreated' . '");');
        $datetitle .= html_writer::start_tag('a', $timecreatedparams);
        $datetitle .= get_string('datetitle', 'arete') . $timecreatedorderIcon;
        $datetitle .= html_writer::end_tag('a');
        $datetitle .= html_writer::end_div();

        //The status of table order
        $ordericon = filter_input(INPUT_GET, 'order') == 'DESC' ? ' ↑' : ' ↓';

        //Time modifiled column header
        $timemodifiedorderIcon = filter_input(INPUT_GET, 'sort') == 'timemodified' ? $ordericon : '';
        $modifieddatetitle = html_writer::start_div('headers', array('id' => 'timemodified'));
        $timemodifiedparams = array('onclick' => 'reverse_sorting("timemodified");');
        $modifieddatetitle .= html_writer::start_tag('a', $timemodifiedparams);
        $modifieddatetitle .= get_string('modifieddatetitle', 'arete') . $timemodifiedorderIcon . html_writer::end_tag('a');
        $modifieddatetitle .= html_writer::end_div();

        //Filename column header
        $filenameorderIcon = filter_input(INPUT_GET, 'sort') == 'filename' ? $ordericon : '';
        $arlemtitle = html_writer::start_div('headers', array('id' => 'filename'));
        $filenameparams = array('onclick' => 'reverse_sorting("filename");');
        $arlemtitle .= html_writer::start_tag('a', $filenameparams);
        $arlemtitle .= get_string('arlemtitle', 'arete') . $filenameorderIcon . html_writer::end_tag('a');
        $arlemtitle .= html_writer::end_div();

        //Views Column header
        $viewsorderIcon = filter_input(INPUT_GET, 'sort') == 'views' ? $ordericon : '';
        $viewstitle = html_writer::start_div('headers', array('id' => 'views'));
        $viewparams = array('onclick' => 'reverse_sorting("views");');
        $viewstitle .= html_writer::start_tag('a', $viewparams);
        $viewstitle .= get_string('viewstitle', 'arete') . $viewsorderIcon . html_writer::end_tag('a');
        $viewstitle .= html_writer::end_div();

        //File size column header
        $filesizeorderIcon = filter_input(INPUT_GET, 'sort') == 'filesize' ? $ordericon : '';
        $sizetitle = html_writer::start_div('headers', array('id' => 'filesize'));
        $filesizeparams = array('onclick' => 'reverse_sorting("filesize");');
        $sizetitle .= html_writer::start_tag('a', $filesizeparams) . get_string('sizetitle', 'arete');
        $sizetitle .= $filesizeorderIcon . html_writer::end_tag('a');
        $sizetitle .= html_writer::end_div();

        //Author column header
        $authororderIcon = filter_input(INPUT_GET, 'sort') == 'author' ? $ordericon : '';
        $authortitle = html_writer::start_div('headers', array('id' => 'author'));
        $authorparams = array('onclick' => 'reverse_sorting("author");');
        $authortitle .= html_writer::start_tag('a', $authorparams);
        $authortitle .= get_string('authortitle', 'arete') . $authororderIcon . html_writer::end_tag('a');
        $authortitle .= html_writer::end_div();

        //rating column header
        $rateorderIcon = filter_input(INPUT_GET, 'sort') == 'rate' ? $ordericon : '';
        $ratingtitle = html_writer::start_div('headers', array('id' => 'rate'));
        $rateparams = array('onclick' => 'reverse_sorting("rate");');
        $ratingtitle .= html_writer::start_tag('a', $rateparams);
        $ratingtitle .= get_string('ratingtitle', 'arete') . $rateorderIcon . html_writer::end_tag('a');
        $ratingtitle .= html_writer::end_div();
    }


    //Show the assign button only to teachers
    if ($teacherview) {
        $headerparams = array(
            $datetitle,
            $modifieddatetitle,
            $arlemtitle,
            $arlemthumbnail,
            $viewstitle,
            $sizetitle,
            $authortitle,
            $playtitle,
            $downloadtitle,
            $edittitle,
            $qrtitle,
            $publictitle,
            $deletetitle,
            $assigntitle,
            $ratingtitle
        );
    } else {

        $headerparams = array(
            $datetitle,
            $modifieddatetitle,
            $arlemtitle,
            $arlemthumbnail,
            $sizetitle,
            $authortitle,
            $assignedbytitle,
            $playtitle,
            $downloadtitle,
            $qrtitle,
            $ratingtitle
        );
    }
    $tableheaders = $headerparams;

    //Remove radio buttons and delete button for the students
    foreach ($arlemslist as $arlem) {

        //Date information
        $date = date('d.m.Y H:i ', $arlem->timecreated);

        ///modified date
        $modifieddate = $arlem->timemodified == 0 ? get_string('neveredited', 'arete') : date('d.m.Y H:i ', $arlem->timemodified);


        //The file title information
        if (!empty($arlem->title)) {
            $title = $arlem->title;
        } else {
            //if title is not exist get it from activity json string
            $title = json_decode($arlem->activity_json)->name;
        }

        //The thumbnail information
        list($thumb_url, $css) = mod_arete_get_thumbnail($arlem->itemid);
        $thumbnailimage = html_writer::empty_tag('img', array('class' => $css, 'src' => $thumb_url, 'alt' => $title));


        //The number of all views of this file on app
        $views = mod_arete_get_views($arlem->itemid);

        //The file size information
        $size = mod_arete_get_readable_filesize($arlem->filesize);


        //The author information photo, firstname, lastname
        $profileimagesize = 40;
        list($authoruser, $src) = mod_arete_get_arlem_owner($arlem, $PAGE);
        $profileimageparams = array(
            'class' => 'profileImg',
            'src' => $src,
            'alt' => get_string('profileimagealt', 'arete'),
            'width' => $profileimagesize,
            'height' => $profileimagesize
        );
        
        //The author profile image
        $authorimage = html_writer::empty_tag('img', $profileimageparams);
        $photo = html_writer::start_span('author') . $authorimage . '&nbsp';
        
        //The image and the name of the author in a single line
        $author = $photo . $authoruser->firstname . ' ' . $authoruser->lastname . html_writer::end_span();

        //The play button information
        $playurl = mod_arete_get_arlem_url($arlem->filename, $arlem->itemid);
        $playbuttonimageparams = array(
            'class' => 'tableicons',
            'src' => "$CFG->wwwroot/mod/arete/pix/playicon.png",
            'alt' => get_string('playbuttonalt', 'arete')
        );
        $playbuttonimage = html_writer::start_tag('img', $playbuttonimageparams);
        $playbuttonparams = array(
            'name' => 'playBtn',
            'href' => $playurl
        );
        $playbutton = html_writer::start_tag('a', $playbuttonparams) . $playbuttonimage . html_writer::end_tag('a');

        //The download button information
        $downloadurl = mod_arete_get_arlem_url($arlem->filename, $arlem->itemid, true);
        $downloadbuttonimageparams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/downloadicon.png',
            'alt' => get_string('downloadbuttonalt', 'arete'),
        );
        $downloadbuttonimage = html_writer::start_tag('img', $downloadbuttonimageparams);
        $downloadbuttonparams = array(
            'name' => 'dlBtn',
            'href' => $downloadurl
        );
        $downloadbutton = html_writer::start_tag('a', $downloadbuttonparams) . $downloadbuttonimage . html_writer::end_tag('a');
        
        //The edit button information
        $queries = mod_arete_get_queries();
        $editbuttonimageparams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/editicon.png',
            'alt' => get_string('editbuttonalt', 'arete')
        );

        $editbuttonimage = html_writer::start_tag('img', $editbuttonimageparams);
        $editpageurlparams = array(
            $CFG->wwwroot . '/mod/arete/view.php?',
            $queries['id'],
            $queries['pnum'],
            $queries['sort'],
            $queries['order'],
            '&mode=edit',
            '&itemid=' . $arlem->itemid,
            '&author=' . $arlem->userid,
            '&sesskey=' . sesskey()
        );
        $editbuttonparams = array(
            'name' => 'dlBtn' . $arlem->fileid,
            'href' => implode('', $editpageurlparams)
        );
        $editbutton = html_writer::start_tag('a', $editbuttonparams) . $editbuttonimage . html_writer::end_tag('a');
        
        //The QR code button information
        $qrbuttonimageparams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/qricon.png',
            'alt' => get_string('qrcodebuttonalt', 'arete')
        );
        $qrbuttonimage = html_writer::start_tag('img', $qrbuttonimageparams);
        $qrbuttonparams = array(
            'name' => 'dlBtn' . $arlem->fileid,
            'href' => "https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl={$playurl}",
            'target' => '_blank'
        );
        $qrbutton = html_writer::start_tag('a', $qrbuttonparams) . $qrbuttonimage . html_writer::end_tag('a');
       
        //The checkbox for making a file public
        //The filename and itemid will be sent as value with (,) as the seperator
        $publiccheckboxparams = array(
            'type' => 'checkbox',
            'id' => $arlem->fileid,
            'class' => 'publicCheckbox',
            'name' => "publicarlemchecked[{$arlem->fileid}(,){$arlem->filename}(,){$arlem->itemid}]",
        );
        if ($arlem->upublic == 1) {
            $publiccheckboxparams['checked'] = '';
        }
        $publichiddenparams = array(
            'type' => 'hidden',
            'name' => "publicarlem[{$arlem->fileid}(,){$arlem->filename}(,){$arlem->itemid}]",
            'value' => '1'
        );
        $publicbutton = html_writer::empty_tag('input', $publiccheckboxparams);
        $publichidden = html_writer::empty_tag('input', $publichiddenparams);
        
        //The checkbox for deleting a file
        //The id, filename and itemid will be sent as value with (,) as the seperator
        $deletecheckboxparams = array(
            'type' => 'checkbox',
            'class' => 'deleteCheckbox',
            'name' => 'deletearlem[]',
            'value' => "{$arlem->fileid}(,){$arlem->filename}(,){$arlem->itemid}"
        );
        $deletebutton = html_writer::empty_tag('input', $deletecheckboxparams);
        
        //The assign radio button information
        $assignradiobuttonparams = array(
            'type' => 'radio',
            'id' => $arlem->itemid,
            'name' => 'arlem',
            'value' => $arlem->fileid
        );
        if ($teacherview == true && mod_arete_is_arlem_assigned($moduleid, $arlem->fileid)) {
            $assignradiobuttonparams['checked'] = '';
        }
        $assignradiobutton = html_writer::empty_tag('input', $assignradiobuttonparams);
        
        //The name of the user who assigned the file to the course
        $assignedby = get_string('notsetyet', 'arete');
        if ($teacherview == false) {
            $assignedby = mod_arete_get_who_assigned_arlem($arlem, $moduleid);
        }

        //The 5 starts rating informastion
        $rating = generate_rating_stars($arlem->itemid, $teacherview);

        //The array to hold all information of the a row
        if ($teacherview) {
            $tablerow = array(
                $date,
                $modifieddate,
                $title,
                $thumbnailimage,
                $views,
                $size,
                $author,
                $playbutton,
                $downloadbutton,
                $editbutton,
                $qrbutton,
                $publicbutton . $publichidden,
                $deletebutton,
                $assignradiobutton,
                $rating
            );
        } else {
            $tablerow = array(
                $date,
                $modifieddate,
                $title,
                $thumbnailimage,
                $size,
                $author,
                $assignedby,
                $playbutton,
                $downloadbutton,
                $qrbutton,
                $rating
            );
        }

        //Deleting the column which should not be visible for the normal users (Not teacher or admin)
        //Only the owner and the manager can delete, chage privacy and edit files
        if ($teacherview) {
            // For the non author users in the teacher view (except admin)
            if ($USER->username != $authoruser->username && !has_capability('mod/arete:manageall', $context)) {
                //Delete delete checkbox
                $indexofdeletecheckbox = array_search($deletebutton, $tablerow);
                if (isset($indexofdeletecheckbox)) {
                    $tablerow[$indexofdeletecheckbox] = get_string('deletenotallow', 'arete');
                }

                //Delete edit button
                $indexofeditbutton = array_search($editbutton, $tablerow);
                if (isset($indexofeditbutton)) {
                    $tablerow[$indexofeditbutton] = get_string('deletenotallow', 'arete');
                }
            }

            //Only the owner of file can make the file public
            if ($USER->username != $authoruser->username) {
                //Delete public button
                $indexofpublicbutton = array_search($publicbutton . $publichidden, $tablerow);
                if (isset($indexofpublicbutton)) {
                    $tablerow[$indexofpublicbutton] = get_string('deletenotallow', 'arete');
                }
            }
        }

        //Filling the table with the array which have kept the row information
        $table->data[] = $tablerow;
    }


    //Prepare the table and return
    $table->id = $tableid;
    $table->attributes = array('class' => 'table-responsive');
    $table->head = $tableheaders;

    return $table;
}

/**
 * The method will create a text input field with it's submit button
 * This search box will let the user to search between all ARLEM records
 * @global object $CFG Moodle config object
 * @global string $searchfield The searched word which is parsed from the URL
 * @return string HTML code of a searchbox which allow to search the table by title, activity_json or workplace_json
 */
function searchbox() {
    global $CFG, $searchfield;

    $queries = mod_arete_get_queries(true);
    $id = $queries['id'];
    $pagemode = $queries['mode'];
    $editingmode = $queries['editing'];
    $sortingmode = $queries['sort'];
    $ordermode = $queries['order'];
    $pagenumber = $queries['pnum'];

    $params = array('id' => $id, 'sesskey' => sesskey());

    if (!empty($pagemode)) {
        $params['mode'] = $pagemode;
    }
    if (!empty($editingmode)) {
        $params['editing'] = $editingmode;
    }
    if (!empty($sortingmode)) {
        $params['sort'] = $sortingmode;
    }
    if (!empty($ordermode)) {
        $params['order'] = $ordermode;
    }
    if (!empty($pagenumber)) {
        $params['pnum'] = $pagenumber;
    }

    $action = new \moodle_url("{$CFG->wwwroot}/mod/arete/view.php");

    $searchbox = html_writer::start_div('', array('id' => 'searchbox'));
    $searchbox .= html_writer::start_tag('form', array('action' => $action, 'method' => 'get')); //create form
    foreach ($params as $key => $value) {
        $searchbox .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
    }

    $searchtextinputparams = array(
        'type' => 'text',
        'placeholder' => get_string('search', 'arete'),
        'name' => 'qword',
        'value' => isset($searchfield) ? $searchfield : ''
    );
    $searchbox .= html_writer::empty_tag('input', $searchtextinputparams);

    $searchsubmitbuttonparams = array(
        'type' => 'submit',
        'class' => 'btn right',
        'value' => get_string('search', 'arete')
    );
    $searchbox .= html_writer::empty_tag('input', $searchsubmitbuttonparams);
    $searchbox .= html_writer::end_tag('form');
    $searchbox .= html_writer::end_div();

    return $searchbox;
}

/**
 * The method creates a menu on top of the student table
 * The menu contain some button for browsing different pages
 * @global object $CFG Moodle config object
 * @return string HTML code of the top menu
 */
function create_student_menu() {
    global $CFG;

    $menu = html_writer::start_div('', array('id' => 'studentmenu'));

    //Terms Of use Button
    $termOfuseswindowparams = array(
        'menubar' => '1',
        'resizable' => '1',
        'width' => '600',
        'height' => '400'
    );

    $termofuseurl = array(
        "{$CFG->wwwroot}/mod/arete/termsofuse.html",  //URL of the terms of use file
        get_string('termsofuse', 'arete'),  //The window title for terms of use
        http_build_query($termOfuseswindowparams, '', ',') //The window settings for terms of use
    );

    $termOfUseButtonParams = array
        (
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('termsofuse', 'arete'),
        'onclick' => 'window.open ("' . implode('","', $termofuseurl) . '");'
    );
    $menu .= html_writer::empty_tag('input', $termOfUseButtonParams);

    //Calibration Marker button parameters
    $calibrationimageparams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('calibrationmarker', 'arete'),
        'onclick' => 'forceDownload("' . $CFG->wwwroot . '/mod/arete/pix/CalibrationMarker.png");'
    );
    $menu .= html_writer::empty_tag('input', $calibrationimageparams);

    //The parameters of the download MirageXR app button
    $miragedownloadbuttonparams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('downloadmiragexr', 'arete'),
        'onclick' => 'window.open ("https://wekit-ecs.com/community/notes");'
    );
    $menu .= html_writer::empty_tag('input', $miragedownloadbuttonparams);

    //The parameteres of the new activity button
    $newactivityluanchparams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('newactivity', 'arete'),
        'onclick' => 'window.open ("wekit://new" , "_self");'
    );
    $menu .= html_writer::empty_tag('input', $newactivityluanchparams);

    $menu .= html_writer::end_div();

    $menu .= html_writer::empty_tag('br');

    return $menu;
}

/**
 * Initiate some variable for using in table.js
 * @param int $userviewmode 1 means the user is a student
 */
function init($userviewmode) {

    //The order of elements on this array is important
    $initfunctionparams = array(
        get_string('editmodeenabledbutton', 'arete'),
        get_string('editmodedisabledbutton', 'arete'),
        get_string('viewstitle', 'arete'),
        get_string('playtitle', 'arete'),
        get_string('downloadtitle', 'arete'),
        get_string('editbutton', 'arete'),
        get_string('qrtitle', 'arete'),
        get_string('publictitle', 'arete'),
        get_string('deletetitle', 'arete'),
        get_string('assigntitle', 'arete'),
        get_string('ratingtitle', 'arete'),
        get_string('scoretitle', 'arete'),
        get_string('votetitle', 'arete'),
        get_string('voteregistered', 'arete')
    );

    $script = html_writer::start_tag('script');

    //Init function exist in table.js
    $parameters = '"' . implode('","', $initfunctionparams) . '"';
    $script .= "$(document).ready(function() { init( {$userviewmode} , {$parameters}); });";

    $script .= html_writer::end_tag('script');
    echo $script;
}

/**
 * Create a 5 star rating container
 * @param int $itemid The id of the file
 * @param bool $teacherview Indicates the user is teacher or not
 * @return string The HTML code of the rating system
 */
function generate_rating_stars($itemid, $teacherview) {

    $idsuffix = $teacherview ? "" : "_studentView";

    $ratingsystem = html_writer::start_div('ratingcontainer');
    $ratingsystem .= html_writer::start_tag('select', array('class' => 'star-rating', 'id' => 'star_rating_' . $itemid . $idsuffix));
    $ratingsystem .= html_writer::start_tag('option ', array('value' => ''));
    $ratingsystem .= '' . html_writer::end_tag('option');

    //add five stars
    for ($i = 1; $i <= 5; $i++) {
        $itostring = strval($i);
        $ratingsystem .= html_writer::start_tag('option ', array('value' => $itostring));
        $ratingsystem .= $itostring . html_writer::end_tag('option');
    }

    $ratingsystem .= html_writer::end_tag('select');
    //rating text
    $ratingsystem .= html_writer::start_div('ratingtext', array('id' => 'ratingtext_' . $itemid . $idsuffix));
    $ratingsystem .= html_writer::end_div();
    $ratingsystem .= html_writer::end_div();

    return $ratingsystem;
}