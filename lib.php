<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/assignmanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

function arete_add_instance($data, $mform)
{
    global $DB;
   
    $data-> timecreated = time();
    $data-> timemodified = $data-> timecreated;

    $data->id = $DB->insert_record('arete', $data);
    
//    $formdata = $mform->get_data();
    
//get context using cource id if you need to get the files from somewhere else than user draft
//    $courseid = $COURSE->id;
//    $context = context_course::instance($courseid);
    
    //insert selected arlem files into arete_arlem which keeps the arlems of each module 
//    if(isset($formdata))
//    {
//        $arlems = new stdClass();
//        $arlems->areteid = $data->id;
//        $arlems->timecreated = time();
//
//        $arlems->arlemid = $formdata->arlemid;
//        $DB->insert_record("arete_arlem", $arlems);
//    }

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
    global $DB ;

    
    $data->id = $data->instance;
    $data->timemodified = time();

    $formdata = $mform->get_data();

    //insert the new assigned arlems or delete the unassigened one
    //if arlem is exits in mdl_files
    if(isset($formdata) && is_arlem_exist($formdata->arlemid))
    {
        $arlemid_in_moodle_db = $formdata->arlemid;
    
        //not assigned before
        if(!is_arlem_assigned($data->id, $arlemid_in_moodle_db))
        {
            $current_record_on_arete_arlem = $DB->get_record('arete_arlem' , array ('areteid' => $data->id));

            $arete_arlem = new stdClass();
            $arete_arlem->id = $current_record_on_arete_arlem->id;
            $arete_arlem->areteid = $current_record_on_arete_arlem->areteid;
            $arete_arlem->timecreated = $current_record_on_arete_arlem->timecreated;
            $arete_arlem->arlemid = $arlemid_in_moodle_db;
            $DB->update_record("arete_arlem" , $arete_arlem); 
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
function arete_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
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
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
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

