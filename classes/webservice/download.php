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

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

//the variables which  are passed by getfile_from_unity.php
$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

global $DB;

$parameters = array(
    'wstoken' => $token,
    'wsfunction' => 'core_files_get_files',
    'contextid' => context_system::instance()->id,
    'component' => get_string('component', 'arete'), 
    'filearea' => get_string('filearea', 'arete'), 
    'itemid' => random_int(100000000, 999999999), 
    'filepath' => '/', //should start with / and end with /
    'filename' => $filename ,
    'modified' => null,
    'contextlevel' => null,
    'instanceid' => null,
);


$serverurl = $CFG->wwwroot . '/webservice/rest/server.php' ;
$response = httpPost($serverurl , $parameters );


    /// REST CALL
    //send a post request
    function httpPost($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
        


    
    
    
