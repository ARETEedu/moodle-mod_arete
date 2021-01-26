<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');


function arete_add_instance($data, $mform)
{
    global $DB;
    
    $data-> timecreated = time();
    $data-> timemodified = $data-> timecreated;

    $data->id = $DB->insert_record('arete', $data);
    
    $formdata = $mform->get_data();
    
    $idfinder = new mod_arete_arlems_utilities();
    
    //insert selected arlem files into arete_arlem which keeps the arlems of each module 
    if(isset($formdata))
    {
        $arlems = new stdClass();
        $arlems->areteid = $data->id;
        $arlems->timecreated = time();
        $arlems->arlemid = $idfinder->get_arlemid_from_db($formdata->arlem);
        $DB->insert_record("arete_arlem", $arlems);
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
function arete_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    $formdata = $mform->get_data();
    
    $utilities = new mod_arete_arlems_utilities();

    //insert the new assigned arlems or delete those one which is unassigened
    if(isset($formdata))
    {
        //not assigned before
        if(!$utilities->is_arlem_assigned($data->id, $utilities->get_arlemid_from_db($formdata->arlem)))
        {
            $arlems = new stdClass();
            $arlems->areteid = $data->id;
            $arlems->timecreated = time();
            $arlems->arlemid = $utilities->get_arlemid_from_db($formdata->arlem);
            $DB->insert_record("arete_arlem", $arlems); 
        }

        //delete the remove assigned arlems
        $arlems_on_db = $DB->get_records('arete_allarlems');
        foreach ($arlems_on_db as $arlem) 
        {
            if($arlem->name != $data->arlem)
            {
                $DB->delete_records("arete_arlem",array('arlemid' => $utilities->get_arlemid_from_db($arlem->name)) ); 
            }
        }
    }

    $DB->update_record("arete", $data);
        
    return $data->id;
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
    if (! $DB->delete_records("arete_arlem", array("areteid"=>$arete->id))) {
        $result = false;
    }

    return $result;
}


