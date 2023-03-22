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
 * Get the data needed by MirageXR from database
 * and send it to the app. This file can be
 * used to to delete or update an ARLEM file too
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\webservices;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/utilities.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/webservice/arlem_deletion.php');
require_once($CFG->dirroot . '/mod/arete/classes/webservice/arlem_archive.php');


$request = required_param('request', PARAM_RAW);
$itemid = optional_param('itemid', null, PARAM_INT);
$sessionid = optional_param('sessionid', null, PARAM_RAW);
$userid = optional_param('userid', null, PARAM_INT);
$token = optional_param('token', null, PARAM_RAW);

global $DB, $CFG;

//Check the request and do what needs be done
switch ($request) {
    case 'arlemlist':
        $arlem_archive = new arlem_archive();
        $arlem_archive->get_arlem_list($CFG, $DB, $userid, $token);
        break;
    
    case 'deleteArlem':
        $arlem_archive = new arlem_archive();
        $arlem_archive->delete_arlem($DB, $itemid, $sessionid, $token);
        break;
    
    case 'updateViews':
        $arlem_archive = new arlem_archive();
        $arlem_archive-> update_views($DB, $itemid, $token);
        break;
    
    default:
        //Will be check on the app, therefore needs to be hardcoded
        print_r('Error: request is NULL');
        break;
}


