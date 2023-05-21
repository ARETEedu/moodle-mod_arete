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
 * @copyright  2023, Abbas Jafari & Fridolin Wild, Open University
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


$mode = optional_param('mode', null, PARAM_TEXT);
if (isset($mode) && $mode == 'vedit') {

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
class visual_edit_arlem {

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


        //Only the owner of the file and the manager can edit files
        $authorexist = isset($this->params['author']) && isset($author);
        $haseditpermission = $USER->username == $author || has_capability('mod/arete:manageall', $context);
        if (!$authorexist || !$haseditpermission) {
            echo $OUTPUT->notification(get_string('accessnotallow', 'arete'));
        } else {

            $alremData = $DB->get_record('arete_allarlems', array('itemid' => $this->params['itemid']));

            if (!empty($alremData)) {
                $this->create_edit_UI($alremData);
            }
        }
    }

    /**
     * This method will create the visual editing page
     * @param bool $alremData a record from database with all information about the current ARLEM activity
     * @return string The HTML code of the visual editing page
     */
    private function create_edit_UI($alremData) {

        global $CFG;
        
        //Activity json
        $activityjson = $alremData->activity_json;
        //Workplace json
        $workplacejson = $alremData->workplace_json;
        
        
        /*All other info can be retrived using $alremData in same way*/

        
        //userdirpath folder
        $userdirpath = $this->userdirpath;
        
        $returnpageurl = "$CFG->wwwroot/mod/arete/view.php?id={$this->params['id']}&pnum={$this->params['pnum']}&editing=on{$this->params['sort']}{$this->params['order']}{$this->params['qword']}";

        
        //The cancel button parameters
        $cancelbuttonparams = array(
            'type' => 'submit',
            'name' => 'cancelBtn',
            'class' => 'btn btn-primary',
            'onClick' => "location.href='{$returnpageurl}'",
            'value' => get_string('cancelbutton', 'arete')
        );
        $cancelButton = html_writer::empty_tag('input', $cancelbuttonparams);
            
        
        //Just for demo. Echo what you want, just keep the cancel button
        echo "<h2>This page is under construction</h2><br>"
        . "<h4>userdirpath</h4><div style=\"background-color:gray;color:white;overflow:scroll;\">{$userdirpath}</div><br><br>"
        . "<h4>Actiyivty Json</h4><div style=\"background-color:gray;color:white;overflow:scroll;\">{$activityjson}</div><br><br>"
        . "<h4>Workplace Json</h4><div style=\"background-color:gray;color:white;overflow:scroll;\">{$workplacejson}</div>"
        . "<br><br>{$cancelButton}";
    }

}
