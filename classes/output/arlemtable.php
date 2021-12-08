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
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

use html_writer, context_course, html_table, moodle_url;


defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->dirroot . '/mod/arete/classes/output/teacher_top_buttons.php');

$searchfield = filter_input(INPUT_GET, 'qword');

/**
 *
 * Print the activity table for the teachers which will show all available activities
 *
 * @param $splitet_list the list of activity files which should be shown in this page
 * @param $page_number page number from the pagination
 * @param $id course Module ID.
 * @param $moduleid id of the arete module
 *
 */
function draw_table_for_teachers($splitet_list, $page_number, $id, $moduleid) {

    global $searchfield;

    //if the user searched for somthing
    $searchquery = '';
    if (isset($searchfield)) {

        if (empty($splitet_list)) {
            $noARLEMMessage = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            $noARLEMMessage .= html_writer::start_div('alarm');
            $noARLEMMessage .= get_string('nomatchfound', 'arete') . '"' . $searchfield . '"';
            $noARLEMMessage .= html_writer::end_div();
            echo $noARLEMMessage;
            return;
        }

        $searchquery = '&qword=' . $searchfield;

        //if there are no arlem on this server at all
    } else {

        if (empty($splitet_list)) {
            $noARLEMMessage = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            $noARLEMMessage .= html_writer::start_div('alarm');
            $noARLEMMessage .= get_string('noarlemfound', 'arete');
            $noARLEMMessage .= html_writer::end_div();
            echo $noARLEMMessage;
            return;
        }
    }

    //the popup modal div
    echo add_popup_image_div();


    ///print the arlem table for the teachers
    $params = array(
        'splitet_list' => $splitet_list,
        'page_number' => $page_number,
        'moduleid' => $moduleid,
        'course_module_id' => $id,
        'searchquery' => $searchquery
    );
    $table_form = new teacher_top_buttons('classes/save_assignment.php', $params);

    //Form processing and displaying is done here
    if ($table_form->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($fromform = $table_form->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
    } else {
        //displays the form
        $table_form->display();
    }
    ///
}

/*
 * Add the needed div for showing pop up images when click on thumbnails

 */

function add_popup_image_div() {

    $popup = html_writer::start_tag('div', array('id' => 'modal'));
    $popup .= html_writer::start_tag('span', array('id' => 'modalImg'));
    $popup .= html_writer::start_tag('div', array('id' => 'modalTitle'));
    $popup .= html_writer::end_tag('div');
    $popup .= html_writer::empty_tag('img', array('class' => 'modalImage'));
    $popup .= html_writer::end_tag('span');
    $popup .= html_writer::end_tag('div');

    return $popup;
}

/**
 *
 * Print the activity table which shows only the assigned activity to the students
 *
 * @param $moduleid id of the arete module
 * @return if the table is printed return false
 *
 */
function draw_table_for_students($moduleid) {

    global $DB;
    //Get the assigned ARLEM from DB
    $activity_arlem = $DB->get_record('arete_arlem', array('areteid' => $moduleid));

    //Get the ARLEM id of the assigned from DB
    if ($activity_arlem != null) {
        $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $activity_arlem->arlemid));
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
 * @param $arlemslist the list of activity files which should be shown in this table
 * @param $tableid a unique id that you can use it later for assigning different CSS classes to this table
 * @param $show_radio_button if true assignment would be possible (this should be true only for the teacher table)
 * @return return a html table
 *
 */
function draw_table($arlemslist, $tableid, $teacherView = false, $moduleid = null) {
    global $USER, $CFG, $COURSE, $PAGE;

    $context = context_course::instance($COURSE->id);

    $table = new html_table();

    $date_title = $searchbox = get_string('datetitle', 'arete');
    $modified_date_title = get_string('modifieddatetitle', 'arete');
    $arlem_title = get_string('arlemtitle', 'arete');
    $arlem_thumbnail = get_string('arlemthumbnail', 'arete');
    $viewsTitle = get_string('viewstitle', 'arete');
    $size_title = get_string('sizetitle', 'arete');
    $author_title = get_string('authortitle', 'arete');
    $playtitle = get_string('playtitle', 'arete');
    $downloadtitle = get_string('downloadtitle', 'arete');
    $edit_title = get_string('editbutton', 'arete');
    $qr_title = get_string('qrtitle', 'arete');
    $public_title = get_string('publictitle', 'arete');
    $delete_title = get_string('deletetitle', 'arete');
    $assign_title = get_string('assigntitle', 'arete');
    $assignedby_title = get_string('assignedbytitle', 'arete');
    $rating_title = get_string('ratingtitle', 'arete');

    //sortable headers for the ARLEM tables
    if ($teacherView) {

        //Time created column header
        $sortMode = filter_input(INPUT_GET, 'sort');
        $default_orderIcon = filter_input(INPUT_GET, 'order') === 'ASC' ? ' ↑' : ' ↓';
        $timecreated_orderIcon = ($sortMode === 'timecreated' || !isset($sortMode)) ? $default_orderIcon : '';
        $date_title = html_writer::start_div('headers', array('id' => 'timecreated'));
        $timeCreatedOnClickParam = array('onclick' => 'reverse_sorting("' . 'timecreated' . '");');
        $date_title .= html_writer::start_tag('a', $timeCreatedOnClickParam);
        $date_title .= get_string('datetitle', 'arete') . $timecreated_orderIcon;
        $date_title .= html_writer::end_tag('a');
        $date_title .= html_writer::end_div();

        //The status of table order
        $orderIcon = filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓';

        //Time modifiled column header
        $timemodified_orderIcon = filter_input(INPUT_GET, 'sort') === 'timemodified' ? $orderIcon : '';
        $modified_date_title = html_writer::start_div('headers', array('id' => 'timemodified'));
        $timeModifiedOnClickParam = array('onclick' => 'reverse_sorting("' . 'timemodified' . '");');
        $modified_date_title .= html_writer::start_tag('a', $timeModifiedOnClickParam);
        $modified_date_title .= get_string('modifieddatetitle', 'arete') . $timemodified_orderIcon . html_writer::end_tag('a');
        $modified_date_title .= html_writer::end_div();

        //Filename column header
        $filename_orderIcon = filter_input(INPUT_GET, 'sort') === 'filename' ? $orderIcon : '';
        $arlem_title = html_writer::start_div('headers', array('id' => 'filename'));
        $filenameOnCLickParam = array('onclick' => 'reverse_sorting("' . 'filename' . '");');
        $arlem_title .= html_writer::start_tag('a', $filenameOnCLickParam);
        $arlem_title .= get_string('arlemtitle', 'arete') . $filename_orderIcon . html_writer::end_tag('a');
        $arlem_title .= html_writer::end_div();

        //Views Column header
        $views_orderIcon = filter_input(INPUT_GET, 'sort') === 'views' ? $orderIcon : '';
        $viewsTitle = html_writer::start_div('headers', array('id' => 'views'));
        $viewOnClickParam = array('onclick' => 'reverse_sorting("' . 'views' . '");');
        $viewsTitle .= html_writer::start_tag('a', $viewOnClickParam);
        $viewsTitle .= get_string('viewstitle', 'arete') . $views_orderIcon . html_writer::end_tag('a');
        $viewsTitle .= html_writer::end_div();

        //File size column header
        $filesize_orderIcon = filter_input(INPUT_GET, 'sort') === 'filesize' ? $orderIcon : '';
        $size_title = html_writer::start_div('headers', array('id' => 'filesize'));
        $filesizeOnClickParam = array('onclick' => 'reverse_sorting("' . 'filesize' . '");');
        $size_title .= html_writer::start_tag('a', $filesizeOnClickParam) . get_string('sizetitle', 'arete');
        $size_title .= $filesize_orderIcon . html_writer::end_tag('a');
        $size_title .= html_writer::end_div();

        //Author column header
        $author_orderIcon = filter_input(INPUT_GET, 'sort') === 'author' ? $orderIcon : '';
        $author_title = html_writer::start_div('headers', array('id' => 'author'));
        $authorOnClickParam = array('onclick' => 'reverse_sorting("' . 'author' . '");');
        $author_title .= html_writer::start_tag('a', $authorOnClickParam);
        $author_title .= get_string('authortitle', 'arete') . $author_orderIcon . html_writer::end_tag('a');
        $author_title .= html_writer::end_div();

        //rating column header
        $rate_orderIcon = filter_input(INPUT_GET, 'sort') === 'rate' ? $orderIcon : '';
        $rating_title = html_writer::start_div('headers', array('id' => 'rate'));
        $rateOnClickParam = array('onclick' => 'reverse_sorting("' . 'rate' . '");');
        $rating_title .= html_writer::start_tag('a', $rateOnClickParam);
        $rating_title .= get_string('ratingtitle', 'arete') . $rate_orderIcon . html_writer::end_tag('a');
        $rating_title .= html_writer::end_div();
    }


    //show the assign button only to teachers
    if ($teacherView) {
        $headerParams = array(
            $date_title,
            $modified_date_title,
            $arlem_title,
            $arlem_thumbnail,
            $viewsTitle,
            $size_title,
            $author_title,
            $playtitle,
            $downloadtitle,
            $edit_title,
            $qr_title,
            $public_title,
            $delete_title,
            $assign_title,
            $rating_title
        );
    } else {

        $headerParams = array(
            $date_title,
            $modified_date_title,
            $arlem_title,
            $arlem_thumbnail,
            $size_title,
            $author_title,
            $assignedby_title,
            $playtitle,
            $downloadtitle,
            $qr_title,
            $rating_title
        );
    }
    $table_headers = $headerParams;

    //remove radio buttons and delete button for the students
    foreach ($arlemslist as $arlem) {

        ///date
        $date = date('d.m.Y H:i ', $arlem->timecreated);

        ///modified date
        $modified_date = $arlem->timemodified == 0 ? get_string('neveredited', 'arete') : date('d.m.Y H:i ', $arlem->timemodified);


        ///arlem title
        if (!empty($arlem->title)) {
            $title = $arlem->title;
        } else {
            //if title is not exist get it from activity json string
            $title = json_decode($arlem->activity_json)->name;
        }

        ///thumbnail
        list($thumb_url, $css) = mod_arete_get_thumbnail($arlem->itemid);
        $thumbnail_img = html_writer::empty_tag('img', array('class' => $css, 'src' => $thumb_url, 'alt' => $title));


        ///number of views on app
        $views = mod_arete_get_views($arlem->itemid);

        ///file size
        $size = mod_arete_get_readable_filesize($arlem->filesize);


        ///author (photo, firstname, lastname
        $profileImageSize = 40;
        list($authoruser, $src) = mod_arete_getARLEMOwner($arlem, $PAGE);
        $profileImageParams = array(
            'class' => 'profileImg',
            'src' => $src,
            'alt' => get_string('profileimagealt', 'arete'),
            'width' => $profileImageSize,
            'height' => $profileImageSize
        );
        $authorImage = html_writer::empty_tag('img', $profileImageParams);
        $photo = html_writer::start_span('author') . $authorImage . '&nbsp';
        ///
        ///Author name
        $author = $photo . $authoruser->firstname . ' ' . $authoruser->lastname . html_writer::end_span();


        ///play button
        $play_url = mod_arete_getArlemURL($arlem->filename, $arlem->itemid);
        $playButtonImageParams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/playicon.png',
            'alt' => get_string('playbuttonalt', 'arete')
        );
        $playButtonImg = html_writer::start_tag('img', $playButtonImageParams);

        $playButtonParams = array(
            'name' => 'playBtn',
            'href' => $play_url
        );
        $play_button = html_writer::start_tag('a', $playButtonParams) . $playButtonImg . html_writer::end_tag('a');
        ///
        ///download button
        $download_url = mod_arete_getArlemURL($arlem->filename, $arlem->itemid, true);
        $downloadButtonImageParams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/downloadicon.png',
            'alt' => get_string('downloadbuttonalt', 'arete'),
        );
        $downloadButtonImg = html_writer::start_tag('img', $downloadButtonImageParams);

        $downloadButtonParams = array(
            'name' => 'dlBtn',
            'href' => $download_url
        );
        $dl_button = html_writer::start_tag('a', $downloadButtonParams) . $downloadButtonImg . html_writer::end_tag('a');
        ///
        ///edit button
        $queries = mod_arete_get_queries();
        $editButtonImageParams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/editicon.png',
            'alt' => get_string('editbuttonalt', 'arete')
        );

        $editButtonImage = html_writer::start_tag('img', $editButtonImageParams);

        $editPageURLParams = array(
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
        $editButtonParams = array(
            'name' => 'dlBtn' . $arlem->fileid,
            'href' => implode('', $editPageURLParams)
        );
        $edit_button = html_writer::start_tag('a', $editButtonParams) . $editButtonImage . html_writer::end_tag('a');
        ///
        ///qr code button
        $qrButtonImageParams = array(
            'class' => 'tableicons',
            'src' => $CFG->wwwroot . '/mod/arete/pix/qricon.png',
            'alt' => get_string('qrcodebuttonalt', 'arete')
        );
        $qrButtonImage = html_writer::start_tag('img', $qrButtonImageParams);

        $qrButtonParams = array(
            'name' => 'dlBtn' . $arlem->fileid,
            'href' => 'https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=' . $play_url,
            'target' => '_blank'
        );
        $qr_button = html_writer::start_tag('a', $qrButtonParams) . $qrButtonImage . html_writer::end_tag('a');
        ///
        ///public checkbox
        //send filename and itemid as value with (,) in between
        $publicCheckboxParams = array(
            'type' => 'checkbox',
            'id' => $arlem->fileid,
            'class' => 'publicCheckbox',
            'name' => 'publicarlemchecked[' . $arlem->fileid . '(,)' . $arlem->filename . '(,)' . $arlem->itemid . ']',
        );
        if ($arlem->upublic == 1) {
            $publicCheckboxParams['checked'] = '';
        }

        $publicHiddenParams = array(
            'type' => 'hidden',
            'name' => 'publicarlem[' . $arlem->fileid . '(,)' . $arlem->filename . '(,)' . $arlem->itemid . ']',
            'value' => '1'
        );
        $public_button = html_writer::empty_tag('input', $publicCheckboxParams);
        $public_hidden = html_writer::empty_tag('input', $publicHiddenParams);
        ///
        ///Delete checkbox
        //send id, filename and itemid as value with (,) in between
        $deleteCheckboxParams = array(
            'type' => 'checkbox',
            'class' => 'deleteCheckbox',
            'name' => 'deletearlem[]',
            'value' => $arlem->fileid . '(,)' . $arlem->filename . '(,)' . $arlem->itemid
        );
        $delete_button = html_writer::empty_tag('input', $deleteCheckboxParams);
        ///
        ///assign radio button
        $assignRadioButtonParams = array(
            'type' => 'radio',
            'id' => $arlem->itemid,
            'name' => 'arlem',
            'value' => $arlem->fileid
        );
        if ($teacherView == true && mod_arete_is_arlem_assigned($moduleid, $arlem->fileid)) {
            $assignRadioButtonParams['checked'] = '';
        }
        $assign_radio_btn = html_writer::empty_tag('input', $assignRadioButtonParams);
        ///
        //assigned by in student table
        $assignedby = get_string('notsetyet', 'arete');
        if ($teacherView == false) {
            $assignedby = mod_arete_get_who_assigned_ARLEM($arlem, $moduleid);
        }

//        $rating = 'Temporary disabled';
        $rating = generate_rating_stars($arlem->itemid, $teacherView);


        //Now fill the row
        if ($teacherView) {
            $table_row = array(
                $date,
                $modified_date,
                $title,
                $thumbnail_img,
                $views,
                $size,
                $author,
                $play_button,
                $dl_button,
                $edit_button,
                $qr_button,
                $public_button . $public_hidden,
                $delete_button,
                $assign_radio_btn,
                $rating
            );
        } else {
            $table_row = array(
                $date,
                $modified_date,
                $title,
                $thumbnail_img,
                $size,
                $author,
                $assignedby,
                $play_button,
                $dl_button,
                $qr_button,
                $rating
            );
        }

        //apply privacy system for teachers
        //only the owner and the manager can delete, chage privacy and edit files
        if ($teacherView) {
            // for the non author users in the teacher view (except admin)
            if ($USER->username != $authoruser->username && !has_capability('mod/arete:manageall', $context)) {
                //delete delete checkbox
                $index_of_delete_checkbox = array_search($delete_button, $table_row);
                if (isset($index_of_delete_checkbox)) {
                    $table_row[$index_of_delete_checkbox] = get_string('deletenotallow', 'arete');
                }

                //delete edit button
                $index_of_edit_button = array_search($edit_button, $table_row);
                if (isset($index_of_edit_button)) {
                    $table_row[$index_of_edit_button] = get_string('deletenotallow', 'arete');
                }
            }

            //Only the owner of file can make the file public
            if ($USER->username != $authoruser->username) {
                //delete public button
                $index_of_public_button = array_search($public_button . $public_hidden, $table_row);
                if (isset($index_of_public_button)) {
                    $table_row[$index_of_public_button] = get_string('deletenotallow', 'arete');
                }
            }
        }

        //fill the table
        $table->data[] = $table_row;
    }


    //prepare the table and return
    $table->id = $tableid;
    $table->attributes = array('class' => 'table-responsive');
    $table->head = $table_headers;

    return $table;
}

/**
 *
 * @param URL of view page where the ARLEM tables are displayed
 * @return A searchbox for arlems
 *
 */
function searchbox() {
    global $CFG, $searchfield;

    $queries = mod_arete_get_queries(true);
    $id = $queries['id'];
    $pagemode = $queries['mode'];
    $editing_mode = $queries['editing'];
    $sortingMode = $queries['sort'];
    $orderMode = $queries['order'];
    $pagenumber = $queries['pnum'];

    $params = array('id' => $id, 'sesskey' => sesskey());

    if (!empty($pagemode)) {
        $params['mode'] = $pagemode;
    }
    if (!empty($editing_mode)) {
        $params['editing'] = $editing_mode;
    }
    if (!empty($sortingMode)) {
        $params['sort'] = $sortingMode;
    }
    if (!empty($orderMode)) {
        $params['order'] = $orderMode;
    }
    if (!empty($pagenumber)) {
        $params['pnum'] = $pagenumber;
    }

    $action = new moodle_url($CFG->wwwroot . '/mod/arete/view.php');

    $searchbox = html_writer::start_div('', array('id' => 'searchbox'));
    $searchbox .= html_writer::start_tag('form', array('action' => $action, 'method' => 'get')); //create form
    foreach ($params as $key => $value) {
        $searchbox .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
    }

    $searchTextInputParams = array(
        'type' => 'text',
        'placeholder' => get_string('search', 'arete'),
        'name' => 'qword',
        'value' => isset($searchfield) ? $searchfield : ''
    );
    $searchbox .= html_writer::empty_tag('input', $searchTextInputParams);

    $searchSubmitButtonParams = array(
        'type' => 'submit',
        'class' => 'btn right',
        'value' => get_string('search', 'arete')
    );
    $searchbox .= html_writer::empty_tag('input', $searchSubmitButtonParams);
    $searchbox .= html_writer::end_tag('form');
    $searchbox .= html_writer::end_div();

    return $searchbox;
}

/**
 * Create top menu
 */
function Create_student_menu() {
    global $CFG;

    $menu = html_writer::start_div('', array('id' => 'studentmenu'));

    //Terms Of use Button
    $termOfUsesWindowParams = array(
        'menubar' => '1',
        'resizable' => '1',
        'width' => '600',
        'height' => '400'
    );

    $termOfUseURL = array(
        //url
        $CFG->wwwroot . '/mod/arete/termsofuse.html',
        //window title
        get_string('termsofuse', 'arete'),
        //window settings
        http_build_query($termOfUsesWindowParams, '', ',')
    );

    $termOfUseButtonParams = array
        (
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('termsofuse', 'arete'),
        'onclick' => 'window.open ("' . implode('","', $termOfUseURL) . '");'
    );
    $menu .= html_writer::empty_tag('input', $termOfUseButtonParams);

    //Calibration Marker button
    $calibrationImageParams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('calibrationmarker', 'arete'),
        'onclick' => 'forceDownload("' . $CFG->wwwroot . '/mod/arete/pix/CalibrationMarker.png");'
    );
    $menu .= html_writer::empty_tag('input', $calibrationImageParams);

    //download MirageXR app
    $mirageXRDownloadButtonParams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('downloadmiragexr', 'arete'),
        'onclick' => 'window.open ("https://wekit-ecs.com/community/notes");'
    );
    $menu .= html_writer::empty_tag('input', $mirageXRDownloadButtonParams);


    //new activity button
    $newActivityLuanchParams = array(
        'type' => 'button',
        'class' => 'menuitem',
        'value' => get_string('newactivity', 'arete'),
        'onclick' => 'window.open ("wekit://new" , "_self");'
    );
    $menu .= html_writer::empty_tag('input', $newActivityLuanchParams);

    $menu .= html_writer::end_div();

    $menu .= html_writer::empty_tag('br');

    return $menu;
}

/**
 * initiate some variable for using in table.js
 */
function init($userViewMode) {

    //The order of elements on this array is important
    $initFunctionParams = array(
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

    //init function exist in table.js
    $script .= '$(document).ready(function() { init(' . $userViewMode . ',"' . implode('","', $initFunctionParams) . '"); });';

    $script .= html_writer::end_tag('script');
    echo $script;
}

/**
 * Create a 5 star rating container
 */
function generate_rating_stars($itemid, $teacherView) {

    $idSuffix = $teacherView ? "" : "_studentView";

    $ratingSystem = html_writer::start_div('ratingcontainer');
    $ratingSystem .= html_writer::start_tag('select', array('class' => 'star-rating', 'id' => 'star_rating_' . $itemid . $idSuffix));
    $ratingSystem .= html_writer::start_tag('option ', array('value' => ''));
    $ratingSystem .= '' . html_writer::end_tag('option');

    //add five stars
    for ($i = 1; $i <= 5; $i++) {
        $iString = strval($i);
        $ratingSystem .= html_writer::start_tag('option ', array('value' => $iString));
        $ratingSystem .= $iString . html_writer::end_tag('option');
    }

    $ratingSystem .= html_writer::end_tag('select');
    //rating text
    $ratingSystem .= html_writer::start_div('ratingtext', array('id' => 'ratingtext_' . $itemid . $idSuffix));
    $ratingSystem .= html_writer::end_div();
    $ratingSystem .= html_writer::end_div();

    return $ratingSystem;
}