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
 * Prints the editing page and the update form
 * This file includes the JSON validator modal as well.
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
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/utilities.php");
require_once("$CFG->libdir/pagelib.php");

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

/**
 * Print the list of the files in the zip file
 * Also print the form for updating the file
 */
class edit_arlem {

    var $params = array();
    var $userdirpath = '';

    /**
     * Initiates the edit page 
     * @global object $USER The user object
     * @global object $COURSE The course object
     * @global object $OUTPUT The output object for accessing to the Moodle header and footer
     * @global object $DB The database object
     * @global object $CFG The Moodle config object
     * @global object $PAGE The current page object
     */
    public function __construct() {

        global $USER, $COURSE, $OUTPUT, $DB, $CFG, $PAGE;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/editor.js'));
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/JsonValidatorController.js'));

        //Get all get queries of the edit page (true means only values not the keys)
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
        $this->userdirpath = $CFG->dirroot . $path . strval($USER->id);

        //Remove temp dir which is used on editing
        $tempdir = "{$this->userdirpath}/";
        if (is_dir($tempdir)) {
            mod_arete_deleteDir($tempdir);
        }

        //Only the owner of the file and the manager can edit files
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

    /**
     * On editing a file, this method will move the zip file 
     * from the file system to a temporary folder inside the plugin folder
     * @param string $filename The filename of the zip file
     * @param int $itemid The itemid in all_arlems table
     */
    private function copy_arlem_to_temp($filename, $itemid) {

        $tempdirpath = $this->userdirpath;
        if (!file_exists($tempdirpath)) {
            mkdir($tempdirpath, 0777, true);
        }
        mod_arete_copy_arlem_to_temp($filename, $itemid);

        $this->unzip_arlem($filename);
    }

    /**
     * This will unzip the zipfile given by name
     * @param string $filename The filename of the zipfile
     */
    private function unzip_arlem($filename) {
        $path = "{$this->userdirpath}/";
        $zip = new \ZipArchive;
        $res = $zip->open($path . $filename);
        if ($res === TRUE) {
            $zip->extractTo($path);
            $zip->close();


            if (unlink($path . $filename)) { //check the zip file can be deleted if so delete it
                //Create the edit view
                $this->create_edit_UI($this->userdirpath, $filename, true);
            }
        } else {

            //Unable to unzip zip file
            echo get_string('filedamage', 'arete');
        }
    }

    /**
     * This method will create the edit page and 
     * all the UI elements.
     * @global object $CFG The Moodle config object
     * @global object $DB The Moodle database object
     * @param string $dir The directory path of the unzipped file inside the temp folder
     * @param string $filename The filename
     * @param bool $mainfolder Status of the folder which is scanned. true if it is the root
     * @return string The HTML code of the edit page
     */
    private function create_edit_UI($dir, $filename, $mainfolder = false) {

        global $CFG, $DB;

        $activityjson = '';
        $workplacejson = '';
        $thumbnailexist = false;

        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // Prevent empty ordered elements
        if (count($ffs) < 1) {
            return;
        }

        //Add these only once
        if ($mainfolder == true) {
            
            /*echo html_writer::start_tag('div', array('id' => 'borderEditPage'));
            $newEditorParams = array(
                'type' => 'button',
                'id' => 'open-editor-button',
                'value' => 'New Editor',
                'onClick' => 'toggle_validator();'
            );
            echo html_writer::empty_tag('input', $newEditorParams);
            echo html_writer::end_tag('div');*/


            echo html_writer::start_tag('div', array('id' => 'borderEditPage'));

            $visualEditorbuttonparams = array(
                'type' => 'button',
                'id' => 'open-editor-button',
                'value' => 'Open Visual Editor', // change it to support all languages ,lang/./arete.php, get_string()
                'onClick' => 'toggle_visual_editor();'
            );
            echo html_writer::empty_tag('input', $visualEditorbuttonparams);

            $validatorbuttonparams = array(
                'type' => 'button',
                'id' => 'open-editor-button',
                'value' => get_string('openvalidator', 'arete'),
                'onClick' => 'toggle_validator();'
            );
            echo html_writer::empty_tag('input', $validatorbuttonparams);

            $title = $DB->get_field('arete_allarlems', 'title', array('itemid' => $this->params['itemid']));

            //Get the activity title from json string if it is not exist in the table
            if (empty($title)) {
                $activityJsonString = $DB->get_field('arete_allarlems', 'activity_json', array('itemid' => $this->params['itemid']));
                $title = json_decode($activityJsonString)->name;
            }

            $structurelabel = get_string('arlemstructure', 'arete');
            echo "<h4>{$structurelabel} \"{$title}\"</h4>";

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

            //If it has found a folder
            if (is_dir($dir . '/' . $ff)) {
                $imageparams = array(
                    'src' => $CFG->wwwroot . '/mod/arete/pix/folder.png',
                    'class' => 'editicon'
                );
                echo html_writer::empty_tag('img', $imageparams);
                echo "<b>{$ff}/</b>";
                echo html_writer::empty_tag('br');
                $this->create_edit_UI("{$dir}/{$ff}", $filename);

                
            } else { //If it has found a file

                //Create a temp file of this file and store in file system temp filearea
                $tempfile = mod_arete_create_temp_files("{$dir}/{$ff}", $ff);

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

                //Parse the url of the json file
                if ((strcmp(pathinfo($ff, PATHINFO_EXTENSION), 'json') === 0)) {

                    //If it is the activity json
                    if (strpos($ff, 'activity') !== false) {
                        $activityjson = mod_arete_get_url(mod_arete_get_temp_file($ff));
                    }
                    //If it is the workplace json
                    else if (strpos($ff, 'workplace') !== false) {
                        $workplacejson = mod_arete_get_url(mod_arete_get_temp_file($ff));
                    }
                }
            }
        }
        echo '</ol>';

        //The update form will be started from here
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

            //The paramaters of the "select files" button
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

            //If the activity has not thumbnail, let the user know
            if (!$thumbnailexist) {
                $form .= '*' . get_string('addthumbnail', 'arete');
                $form .= html_writer::end_tag('br');
            }

            //The warning icon which will be displaced beside the warning
            //if no file is selected yet
            $form .= html_writer::empty_tag('img', array('src' => $CFG->wwwroot . '/mod/arete/pix/warning.png', 'class' => 'icon'));
            $form .= html_writer::start_span(null, ['style' => 'color: #ff0000;']);
            $form .= get_string('selectfileshelp', 'arete');
            $form .= html_writer::end_span();
            $form .= html_writer::end_tag('br');
            $form .= html_writer::end_tag('div');

            $sessionid = str_replace("-activity.json", "", basename($activityjson));
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sessionID', 'value' => $sessionid));

            $userdir = $this->userdirpath;
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userDirPath', 'value' => $userdir));

            //Add all other existing parameters to the url
            foreach ($this->params as $key => $value) {
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
            }

            $form .= html_writer::end_tag('br');
            $form .= html_writer::start_div('saving-warning', ['style' => 'display:none;']);
            $form .= html_writer::end_tag('div');

            //The save button parameters
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

            //The cancel button parameters
            $cancelbuttonparams = array(
                'type' => 'submit',
                'name' => 'cancelBtn',
                'class' => 'btn btn-primary',
                'value' => get_string('cancelbutton', 'arete')
            );
            $form .= html_writer::empty_tag('input', $cancelbuttonparams);

            $form .= html_writer::end_tag('br');

            //The saving warning for when the json files aare edited
            $form .= html_writer::start_div('saving-warning', ['style' => 'display:none; color:red;']);
            $form .= get_string('savewarning', 'arete');
            $form .= html_writer::end_div();

            $form .= html_writer::end_tag('form');
            $form .= html_writer::end_tag('div');

            //Print the edit page 
            echo $form;

            $this->visualEditor($activityjson, $workplacejson);

            //JSON Validator Modal
            $this->Modal($activityjson, $workplacejson);
        }
    }

    private function visualEditor($activityjson, $workplacejson)
    {
        echo html_writer::start_div('visualEditor', array('id' => 'visualEditor'));
        echo html_writer::start_div('visualEditorContent', array('id' => 'visualEditorContent'));

        $buttons = html_writer::start_div('text-right');

        $validatorclosebuttonparams = array(
            'type' => 'button',
            'value' => 'close', // change it to support all languages ,lang/./arete.php, get_string()
            'onClick' => 'toggle_visual_editor();'
        );
        $buttons .= html_writer::empty_tag('input', $validatorclosebuttonparams);

        $buttons .= html_writer::end_div();
        echo $buttons;
        
        // $savejsonbuttonparams = array(
        //     'type' => 'button',
        //     'id' => 'saveJSON',
        //     'value' => get_string('validatorsave', 'arete'),
        //     'onClick' => 'On_Save_JSON_Pressed();'
        // );
        // $buttons .= html_writer::empty_tag('input', $savejsonbuttonparams);

        $validator = html_writer::start_div('', array('id' => 'visualEditorContainer'));
        $validator .= html_writer::start_tag('noscript');
        $validator .= 'JavaScript needs to be enabled';
        $validator .= html_writer::end_tag('noscript');

        $newEditorTestParams = array(
            'src' => 'classes/output/visualEditor.js',
            'name' => 'Nick',
            // 'activityjson' => $activityjson,
            // 'workplacejson' => $workplacejson
        );

        $validator .= html_writer::start_tag('script', $newEditorTestParams);
        $validator .= html_writer::end_tag('script');
        $validator .= html_writer::end_div();

        echo $validator;
        echo html_writer::end_div();
        echo html_writer::end_div();
    }



    //Edit or replace this (Modal)
    /**
     * This will create a modal where the JSON validator will be displayed on
     * @param string $activityjson The activity JSON string
     * @param string $workplacejson The workplace JSON string
     */
    private function Modal($activityjson, $workplacejson) {

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

        //JSON validator parameters
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

    
    /**
     * Find and return the location of the icons of every file format
     * @global object $CFG The Moodle config object
     * @param string $filepath The path of the file
     * @return string The HTML code of the image of the file extension icon
     */
    private function get_icon($filepath) {
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
                break;
            
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
            'src' => "{$CFG->wwwroot}/mod/arete/pix/{$type}.png",
            'class' => 'editicon'
        );
        return html_writer::empty_tag('img', $iconimageparams);
    }

}
