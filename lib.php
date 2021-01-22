<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');


function arete_add_instance($data, $mform)
{
    global $DB , $selectedfiles;
    
    $data-> timecreated = time();
    $data-> timemodified = $data-> timecreated;

    $data->id = $DB->insert_record('arete', $data);
    
    $formdata = $mform->get_data();
    
    $idfinder = new mod_arete_arlems_utilities();
    
    //insert selected arlem files into arete_arlem which keeps the arlems of each module 
    if(isset($formdata))
    {
        foreach ($selectedfiles as $file) {
            $arlems = new stdClass();
            $arlems->areteid = $data->id;
            $arlems->timecreated = time();
            $arlems->arlemid = $idfinder->get_arlemid_from_db($file);
            $DB->insert_record("arete_arlem", $arlems);
        }
    }


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
function arete_update_instance($arete) {
    global $DB;

    $arete->id = $arete->instance;
    $arete->timemodified = time();


    return $DB->update_record("arete", $arete);
}



function arete_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               
            return true;
        case FEATURE_SHOW_DESCRIPTION:
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

    
    if (! $arete = $DB->get_record('arete', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records here.

    if (! $DB->delete_records('arete', array('id' => $arete->id))) {
        $result = false;
    }
    
    
    //delete records from arete_arlem table
//    if (! $DB->delete_records("arete_arlem", array("id"=>"$data->id"))) {
//        $result = false;
//    }

    return $result;
}


