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
 * Saving the changes on the main table (privacy, assignment and deleting of the ARLEMs)
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

use stdClass,
    context_course;
use function mod_arete\webservices\mod_arete_delete_arlem_from_other_tables;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once( "$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once( "$CFG->dirroot/mod/arete/classes/utilities.php");

defined('MOODLE_INTERNAL') || die;

global $DB, $USER, $COURSE;

$returnurl = filter_input(INPUT_POST, 'returnurl');
$areteid = filter_input(INPUT_POST, 'moduleid');
$arlemid = filter_input(INPUT_POST, 'arlem');


//assign the activty
$updaterecord = new stdClass();
$updaterecord->id = $DB->get_field('arete_arlem', 'id', array('areteid' => $areteid));
$updaterecord->areteid = $areteid;
$updaterecord->arlemid = $arlemid;
$updaterecord->teacherid = $USER->id;
$updaterecord->timecreated = time();

if (isset($areteid) && isset($arlemid)) {
    $DB->update_record('arete_arlem', $updaterecord);

    //Get the assigned ARLEM
    $arlem = $DB->get_record('arete_allarlems', array('fileid' => $arlemid));
}

$moduleid = $DB->get_field('arete_arlem', 'id', array('areteid' => $areteid));

//If the record  of this activity was deleted on arete_arlem create it again
if ($moduleid == null && isset($areteid) && isset($arlemid)) {
    $item = new stdClass();
    $item->areteid = $areteid;
    $item->timecreated = time();
    $item->teacherid = $USER->id;
    $item->arlemid = $arlemid;
    $DB->insert_record("arete_arlem", $item);
}



//Update the public privacy
//Course context
$context = context_course::instance($COURSE->id);

if (isset($_POST['publicarlem'])) {

    // The value (publicarlem) is passed in hidden input's key, therefore
    // The value of the input itself is irrelevant ($dummy)
    foreach ($_POST['publicarlem'] as $value => $dummy) {

        list($id, $filename, $itemid) = explode('(,)', $value);


        //Check if it is not deleted at the same edit session
        if (mod_arete_is_arlem_exist($itemid)) {

            if (isset($_POST['publicarlemchecked'][$value])) {
                mod_arete_update_arlem_object($filename, $itemid, array('upublic' => 1));
            } else {

                mod_arete_update_arlem_object($filename, $itemid, array('upublic' => 0));
            }

            //Make the assigned ARLEM become public
            if (isset($arlem)) {
                mod_arete_update_arlem_object($arlem->filename, $arlem->itemid, array('upublic' => 1));
            }
        }
    }
}


//Deleted the arlems which has checkbox checked
if (isset($_POST['deletearlem'])) {
    foreach ($_POST['deletearlem'] as $arlem) {

        list($id, $filename, $itemid) = explode('(,)', $arlem);

        if (mod_arete_is_arlem_exist($itemid)) {
            $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
            mod_arete_delete_arlem_from_plugin($filename, $itemid);
            mod_arete_delete_arlem_from_other_tables($DB,$file->sessionid, $file->itemid, $file->fileID);

        }
    }
}


//Redirect
redirect($returnurl, array());
