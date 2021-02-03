<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_arlems_utilities.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

function arete_add_instance($data, $mform)
{
    global $DB;
   
    $data-> timecreated = time();
    $data-> timemodified = $data-> timecreated;

    $data->id = $DB->insert_record('arete', $data);
    
    $formdata = $mform->get_data();
    
//get context using cource id
    $courseid = optional_param('course', null, PARAM_INT);
    $context = context_course::instance($courseid);
    
    //insert selected arlem files into arete_arlem which keeps the arlems of each module 
    if(isset($formdata))
    {
        $arlems = new stdClass();
        $arlems->areteid = $data->id;
        $arlems->timecreated = time();
        
        $arlemFile = getArlem($formdata->arlem, $context);
        $arlems->arlemid = $arlemFile->get_id();
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
function mod_arete_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false; 
    }
 
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'expectedfilearea' && $filearea !== 'anotherexpectedfilearea') {
        return false;
    }
 
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
 
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/arete:view', $context)) {
        return false;
    }
 
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
