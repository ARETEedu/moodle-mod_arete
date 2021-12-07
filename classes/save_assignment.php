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

require_once(dirname(__FILE__). '/../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

global $DB, $USER,$COURSE;

$returnurl = filter_input(INPUT_POST, 'returnurl' );
$areteid = filter_input(INPUT_POST, 'moduleid' );
$arlemid = filter_input(INPUT_POST, 'arlem' );


//assign the activty
$update_record = new stdClass();
$update_record-> id = $DB->get_field('arete_arlem', 'id', array('areteid' => $areteid ));
$update_record-> areteid = $areteid;
$update_record-> arlemid =  $arlemid;
$update_record-> teacherid =  $USER->id;
$update_record->timecreated = time();

if(isset($areteid) && isset($arlemid)){
    $DB->update_record('arete_arlem', $update_record);
    
    //Get the assigned ARLEM
    $ARLEM = $DB->get_record('arete_allarlems', array('fileid' => $arlemid));

}

$moduleid = $DB->get_field('arete_arlem', 'id', array('areteid' => $areteid ));

//if the record  of this activity was deleted on arete_arlem create it again
if($moduleid == null && isset($areteid) && isset($arlemid))
{
    $item = new stdClass();
    $item->areteid = $areteid;
    $item->timecreated = time();
    $item-> teacherid =  $USER->id;
    $item->arlemid = $arlemid;
    $DB->insert_record("arete_arlem", $item);
}



///update the public privacy
//course context
$context = context_course::instance($COURSE->id);

if(isset($_POST['publicarlem'])){    

  // the value (publicarlem) is passed in hidden input's key, therefore
  // the value of the input itself is irrelevant ($dummy)
    foreach( $_POST['publicarlem'] as $value => $dummy){

        list($id ,$filename, $itemid) = explode('(,)', $value);


        //check if it is not deleted at the same edit session
        if(is_arlem_exist($itemid)){
            
            if(isset($_POST['publicarlemchecked'][$value]))
            {
                updateArlemObject($filename,  $itemid, array('upublic' => 1));
            }
            else{

                updateArlemObject($filename, $itemid, array('upublic' => 0));
            }
            
            //make the assigned ARLEM become public
            if(isset($ARLEM)){
               updateArlemObject($ARLEM->filename,  $ARLEM->itemid, array('upublic' => 1));
            }
        }
    }
}


//deleted the arlems which has checkbox checked
if(isset($_POST['deletearlem'])){
   foreach ($_POST['deletearlem'] as $arlem) {
       
        list($id ,$filename, $itemid) = explode('(,)', $arlem);

        if(is_arlem_exist($itemid)){
                deletePluginArlem($filename, $itemid);
        }

    } 
}


//redirect
redirect($returnurl, array());