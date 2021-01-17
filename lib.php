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