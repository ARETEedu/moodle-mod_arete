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

namespace mod_arete\output;

use html_writer,
    moodle_url,
    context_course;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->libdir . '/pagelib.php');

defined('MOODLE_INTERNAL') || die;


$mode = filter_input(INPUT_GET, 'mode');
if (isset($mode) && $mode == 'edit') {

    $bootstriplinkparams = array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css'
    );
    echo html_writer::empty_tag('link', $bootstriplinkparams);

    $jsoneditorlinkparams = array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => 'https://cdn.jsdelivr.net/npm/jsoneditor@9/dist/jsoneditor.min.css'
    );
    echo html_writer::empty_tag('link', $jsoneditorlinkparams);
    ;
}

class edit_arlem {

    var $params = array();
    var $userdirpath = '';

    /*
     * constructor will call other functions in this class
     */

    function __construct() {

        global $USER, $COURSE, $OUTPUT, $DB, $CFG, $PAGE;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/editor.js'));
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/JsonValidatorController.js'));

        //
        //get all get queries of the edit page (true means only values not the keys)
        $queries = mod_arete_get_queries(true);

        if (isset($queries['id'])) {
            $this->params['id'] = $queries['id'];
        }
        if (isset($queries['itemid'])) {
            require_sesskey();
            $this->params['itemid'] = $queries['itemid'];
        }
        if (isset($queries['pnum'])) {
            $this->params['pnum'] = $queries['pnum'];
        }
        if (isset($queries['sort'])) {
            $this->params['sort'] = $queries['sort'];
        }
        if (isset($queries['editing'])) {
            $this->params['editing'] = $queries['editing'];
        }
        if (isset($queries['order'])) {
            $this->params['order'] = $queries['order'];
        }
        if (isset($queries['qword'])) {
            $this->params['qword'] = $queries['qword'];
        }
        if (isset($queries['mode'])) {
            $this->params['mode'] = $queries['mode'];
        }
        if (isset($queries['author'])) {
            $this->params['author'] = $queries['author'];
        }

        $this->params['sesskey'] = sesskey();

        $context = context_course::instance($COURSE->id);
        $author = $DB->get_field('user', 'username', array('id' => $this->params['author']));

        //The user editing folder
        $path = '/mod/arete/temp/';
        $this->userDirPath = $CFG->dirroot . $path . strval($USER->id);

        //remove temp dir which is used on editing
        $tempdir = $this->userDirPath . '/';
        if (is_dir($tempdir)) {
            mod_arete_deleteDir($tempdir);
        }

        //only the owner of the file and the manager can edit files
        $authorexist = isset($this->params['author']) && isset($author);
        $haseditpermission = $USER->username == $author || has_capability('mod/arete:manageall', $context);
        if (!$authorexist || !$haseditpermission) {
            echo $OUTPUT->notification(get_string('accessnotallow', 'arete'));
        } else {
            $filename = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $this->params['itemid']));
            if (isset($filename)) {
                $this->copy_arlem_to_temp($filename, $this->params['itemid']);
            }
        }
    }

    function copy_arlem_to_temp($filename, $itemid) {

        $tempdirpath = $this->userDirPath;
        if (!file_exists($tempdirpath)) {
            mkdir($tempdirpath, 0777, true);
        }
        mod_arete_copy_arlem_to_temp($filename, $itemid);

        $this->unzip_arlem($filename);
    }

    function unzip_arlem($filename) {
        $path = $this->userDirPath . '/';
        $zip = new \ZipArchive;
        $res = $zip->open($path . $filename);
        if ($res === TRUE) {
            $zip->extractTo($path);
            $zip->close();


            if (unlink($path . $filename)) { //check the zip file can be deleted if so delete it
                //create edit view
                $this->create_edit_UI($this->userDirPath, $filename, true);
            }
        } else {

            //unable to unzip zip file
            echo get_string('filedamage', 'arete');
        }
    }

    function create_edit_UI($dir, $filename, $mainfolder = false) {

        global $CFG, $DB;

        $activityjson = '';
        $workplacejson = '';
        $thumbnailexist = false;

        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1) {
            return;
        }

        //add these only once
        if ($mainfolder == true) {
            echo html_writer::start_tag('div', array('id' => 'borderEditPage'));
            $validatorbuttonparams = array(
                'type' => 'button',
                'id' => 'open-editor-button',
                'value' => get_string('openvalidator', 'arete'),
                'onClick' => 'toggle_validator();'
            );
            echo html_writer::empty_tag('input', $validatorbuttonparams);

            $title = $DB->get_field('arete_allarlems', 'title', array('itemid' => $this->params['itemid']));

            //get the activity title from json string if it is not exist in the table
            if (empty($title)) {
                $activityJsonString = $DB->get_field('arete_allarlems', 'activity_json', array('itemid' => $this->params['itemid']));
                $title = json_decode($activityJsonString)->name;
            }

            echo '<h3>' . get_string('arlemstructure', 'arete') . ' "' . $title . '"</h3>';

            echo html_writer::empty_tag('br');
            echo html_writer::empty_tag('br');

            $editformparams = array(
                'name' => 'editform',
                'action' => $CFG->wwwroot . '/mod/arete/classes/updatefile.php',
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            );

            //Start the edit form
            echo html_writer::start_tag('form', $editformparams);
        }

        echo '<ol>';
        foreach ($ffs as $ff) {

            //for folders
            if (is_dir($dir . '/' . $ff)) {
                $imageparams = array(
                    'src' => $CFG->wwwroot . '/mod/arete/pix/folder.png',
                    'class' => 'editicon'
                );
                echo html_writer::empty_tag('img', $imageparams);
                echo '<b>' . $ff . '/</b>';
                echo html_writer::empty_tag('br');
                $this->create_edit_UI($dir . '/' . $ff, $filename);

                //for files
            } else {

                //create a temp file of this file and store in file system temp filearea
                $tempfile = mod_arete_create_temp_files($dir . '/' . $ff, $ff);

                $url = moodle_url::make_pluginfile_url($tempfile->get_contextid(), $tempfile->get_component(),
                                $tempfile->get_filearea(), $tempfile->get_itemid(),
                                $tempfile->get_filepath(), $tempfile->get_filename(), false);

                //Print the icon and file
                $filerow = $this->get_icon($ff);
                $filerow .= html_writer::start_tag('a', array('href' => $url, 'target' => '_blank'));
                $filerow .= $ff;
                $filerow .= html_writer::end_tag('a');
                $filerow .= html_writer::empty_tag('br');
                echo $filerow;

                if ($ff == 'thumbnail.jpg') {
                    $thumbnailexist = true;
                }

                //parse the url of the json file
                if ((strcmp(pathinfo($ff, PATHINFO_EXTENSION), 'json') === 0)) {

                    //if it is activity json
                    if (strpos($ff, 'activity') !== false) {
                        $activityjson = mod_arete_get_url(mod_arete_get_temp_file($ff));
                    }
                    //if it is workplace jason
                    else if (strpos($ff, 'workplace') !== false) {
                        $workplacejson = mod_arete_get_url(mod_arete_get_temp_file($ff));
                    }
                }
            }
        }
        echo '</ol>';

        ///add these once

        $url = $CFG->wwwroot . "/mod/arete/validator.php?activity=" . $activityjson . '&workplace=' . $workplacejson;

        if ($mainfolder == true) {

            $form = html_writer::empty_tag('br');
            $form .= html_writer::empty_tag('br');
            $form .= html_writer::start_tag('div', array('id' => 'borderUpdateFile'));
            $form .= get_string('selectfiles', 'arete');
            $form .= html_writer::empty_tag('br');



            $form .= html_writer::start_div('file-upload');
            $uploaderparams = array(
                'type' => 'file',
                'name' => 'files[]',
                'id' => 'files',
                'value' => $this->params['id'],
                'multiple' => 'multiple',
                'class' => 'file-upload__input'
            );
            $form .= html_writer::empty_tag('input', $uploaderparams);
            $form .= html_writer::end_div();

            //Choose File button
            $uploadbuttonparams = array(
                'type' => 'button',
                'class' => 'file-upload__button',
                'value' => get_string('choosefilesbutton', 'arete')
            );
            $form .= html_writer::empty_tag('input', $uploadbuttonparams);

            $form .= html_writer::empty_tag('br');
            $form .= html_writer::start_span('file-upload__label');
            $form .= get_string('nofileselected', 'arete');
            $form .= html_writer::end_span();

            $form .= html_writer::empty_tag('br');
            $form .= html_writer::empty_tag('br');

            //if activity has not thumbnail let the user know
            if (!$thumbnailexist) {
                $form .= '*' . get_string('addthumbnail', 'arete');
                $form .= html_writer::end_tag('br');
            }

            //warning icon
            $form .= html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/mod/arete/pix/warning.png', 'class' => 'icon'));
            $form .= html_writer::start_span(null, ['style' => 'color: #ff0000;']);
            $form .= get_string('selectfileshelp', 'arete');
            $form .= html_writer::end_span(); //warning
            $form .= html_writer::end_tag('br');
            $form .= html_writer::end_tag('div');

            $sessionid = str_replace("-activity.json", "", basename($activityjson));
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sessionID', 'value' => $sessionid));

            $userdir = $this->userDirPath;
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userDirPath', 'value' => $userdir));

            //add all other existing parameters to the url
            foreach ($this->params as $key => $value) {
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
            }

            $form .= html_writer::end_tag('br');
            $form .= html_writer::start_div('saving-warning', ['style' => 'display:none;']);
            $form .= html_writer::end_tag('div');

            //save button
            $savebuttonparams = array(
                'type' => 'button',
                'id' => 'edit_page_save_button',
                'name' => 'saveBtn',
                'class' => 'btn btn-primary',
                'onClick' => 'checkFiles(this.form);',
                'value' => get_string('savebutton', 'arete')
            );
            $form .= html_writer::empty_tag('input', $savebuttonparams);

            $form .= '&nbsp;&nbsp;';

            //cancel button
            $cancelbuttonparams = array(
                'type' => 'submit',
                'name' => 'cancelBtn',
                'class' => 'btn btn-primary',
                'value' => get_string('cancelbutton', 'arete')
            );
            $form .= html_writer::empty_tag('input', $cancelbuttonparams);

            $form .= html_writer::end_tag('br');

            //save waring text
            $form .= html_writer::start_div('saving-warning', ['style' => 'display:none; color:red;']);
            $form .= get_string('savewarning', 'arete');
            $form .= html_writer::end_div();

            //close the editing form and the main div
            $form .= html_writer::end_tag('form');
            $form .= html_writer::end_tag('div');

            //print the edit page
            echo $form;


            ///JSON Validator Modal
            $this->Modal($activityjson, $workplacejson);

            ///
        }
    }

    function Modal($activityjson, $workplacejson) {

        echo html_writer::start_div('validator', array('id' => 'validator-modal', 'role' => "dialog", 'data-backdrop' => "static"));
        echo html_writer::start_div('validator-content', array('id' => 'validator-modal-content'));
        $buttons = html_writer::start_div('text-right');

        $savejsonbuttonparams = array(
            'type' => 'button',
            'id' => 'saveJSON',
            'value' => get_string('validatorsave', 'arete'),
            'onClick' => 'On_Save_JSON_Pressed();'
        );
        $buttons .= html_writer::empty_tag('input', $savejsonbuttonparams);

        $validatorclosebuttonparams = array(
            'type' => 'button',
            'value' => get_string('closevalidator', 'arete'),
            'onClick' => 'toggle_validator();'
        );
        $buttons .= html_writer::empty_tag('input', $validatorclosebuttonparams);

        $buttons .= html_writer::end_div();

        echo $buttons;

        $validator = html_writer::start_div('', array('id' => 'container'));
        $validator .= html_writer::start_tag('noscript');
        $validator .= 'JavaScript needs to be enabled';
        $validator .= html_writer::end_tag('noscript');

        $validatorscriptparams = array(
            'src' => new moodle_url('https://openarlem.github.io/arlem.js/arlem.js'),
            'data-app-activity-ref' => 'activityEditor',
            'data-app-workplace-ref' => 'workplaceEditor',
            'data-app-activity' => $activityjson,
            'data-app-workplace' => $workplacejson
        );

        $validator .= html_writer::start_tag('script', $validatorscriptparams);
        $validator .= html_writer::end_tag('script');
        $validator .= html_writer::end_div();

        echo $validator;
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    function get_icon($filepath) {
        global $CFG;
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'json':
                $type = 'json';
                break;
            case 'png':
                $type = 'png';
                break;
            case 'wav':
                $type = 'wav';
                break;
            case 'mp3':
                $type = 'mp3';
                break;
            case 'avi':
                $type = 'avi';
                break;
            case 'mp4':
                $type = 'mp4';
                break;
            case 'jpg':
                $type = 'jpg';
                break;
            case 'jpeg':
                $type = 'jpeg';
                break;
            case 'gltf':
                $type = 'gltf';
                break;
            case 'bin':
                $type = 'bin';
                break;
            case 'tilt':
                $type = 'tilt';
                break;
            case 'txt':
                $type = 'txt';
            case 'manifest':
                $type = 'manifest';
                break;
            case '':
                $type = 'bundle';
                break;
            default:
                $type = 'unknow';
        }

        $iconimageparams = array(
            'src' => $CFG->wwwroot . '/mod/arete/pix/' . $type . '.png',
            'class' => 'editicon'
        );
        return html_writer::empty_tag('img', $iconimageparams);
    }

}
