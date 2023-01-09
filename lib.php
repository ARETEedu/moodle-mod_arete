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
 * It contains the great majority of functions defined by Moodle
 * that are mandatory to develop a module.
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');

/**
 * @param $url
 * @return string
 */
function format_thumbnail_url_for_table($url): string
{
    $partial_url_array = explode('/', $url->__toString());
    $partial_url_array[sizeof($partial_url_array) - 1] = 'thumbnail.jpg';
    $lower_limit = sizeof($partial_url_array) - 5;
    $higher_limit = sizeof($partial_url_array);
    $partial_url = implode('/',
        array_slice($partial_url_array, $lower_limit, $higher_limit));
    return $partial_url;
}


/**
 * @param $file the arlem
 * @param $content the content of the arlem
 * @param $CFG
 * @return string
 */
function get_thumbnail($file, $CFG): string
{
    //Get the thumbnail
    $thumbbase64 = '';

    $zip = new \ZipArchive;
    $res = $zip->open($file);
    if ($res === TRUE) {
        $root = "{$CFG->tempdir}/" . rand() . "/";
        $zip->extractTo($root);
        $zip->close();
        $all_of_them = array_slice(scandir($root),2);
        for ($item_index = 0; $item_index < sizeof($all_of_them); $item_index += 1) {
            $items_in_session = array();
            if (is_dir($root . '/' . $all_of_them[$item_index])){
                $items_in_session =  array_slice(scandir($root .'/'. $all_of_them[$item_index]),2);
            }
            for ($items_in_session_index = 0; $items_in_session_index < sizeof($items_in_session); $items_in_session_index += 1){
                if (strpos($items_in_session[$items_in_session_index], 'thumbnail.jpg') !== false) {
                    $thumbnail_item = $root .'/'. $all_of_them[$item_index] .'/'. $items_in_session[$items_in_session_index];
                    $thumbbase64 = base64_encode(file_get_contents($thumbnail_item));
                    break 2;
                }
            }
        }
        unset($root);
    }
    return $thumbbase64;
}

/**
 * Helper method that uploads thumbnail
 *
 * @param $token The token of the user whose id is passed
 * @param int $contextid The context id of the arlem
 * @param int $itemid the itemid of the arlem
 * @param $thumbnail the thumbnail daata
 * @param $userid The id of the user
 * @param $CFG the CFG
 * @return result array
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_arete_upload_thumbnail_all_parameters($token, int $contextid, int $itemid, $thumbnail, $userid, $CFG)
{
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_upload',
        'contextid' => $contextid,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $itemid,
        'filepath' => '/', //Should start with / and end with /
        'filename' => 'thumbnail.jpg',
        'filecontent' => $thumbnail,
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    $serverurl = "{$CFG->wwwroot}/webservice/rest/server.php";
    $response = mod_arete_httpPost($serverurl, $parameters);

    if ($response == true) {
        //Move it to the plugin filearea
        $result = move_file_from_draft_area_to_arete($userid, $parameters['itemid'], context_system::instance()->id,
            get_string('component', 'arete'), 'thumbnail', $parameters['itemid']);

        //Delete file and the empty folder from user file area
        mod_arete_delete_user_arlem('thumbnail.jpg', $parameters['itemid'], true, $userid);
        mod_arete_delete_user_arlem('.', $parameters['itemid'], true, $userid);
    }
    return $result;
}

function arete_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    $data->id = $DB->insert_record('arete', $data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $php
 * @return bool
 */
function arete_update_instance($data, $mform) {
    global $DB;


    $data->id = $data->instance;
    $data->timemodified = time();

    $DB->update_record("arete", $data);

    return $data->id;
}

function arete_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
            
        case FEATURE_SHOW_DESCRIPTION:
            return true;
            
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default: return null;
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function arete_delete_instance($id) {
    global $DB;


    if (!$arete = $DB->get_record('arete', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.

    if (!$DB->delete_records('arete', array('id' => $arete->id))) {
        $result = false;
    }



    //delete records from arete_arlem table
    if (!$DB->delete_records("arete_arlem", array("areteid" => $arete->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Serve the files from the mod_arete file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function arete_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
//    require_login($course, true, $cm);
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_arete', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
