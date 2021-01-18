<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

function arete_add_instance($data, $mform)
{
    global $DB;
    
    $data-> timecreated = time();
    $data-> timemodified = $data-> timecreated;
    
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
function arete_update_instance($arete) {
    global $DB;

    $arete->id = $arete->instance;
    $arete->timemodified = time();


    return $DB->update_record("arete", $arete);
}

function arete_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               
            return false;

            
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