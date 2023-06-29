<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot . '/mod/arete/classes/move_arlem_from_draft.php');
require_once($CFG->dirroot . '/mod/arete/classes/generic_utilities.php');

global $DB, $CFG;

//retrieve arlem
$records = $DB->get_recordset_sql('SELECT * FROM {arete_allarlems} WHERE thumbnail IS NULL');
foreach ($records as $record) {

    $itemid = $record->itemid;
    $contextid = $record->contextid;
    $arlem_from_table = $record->filename;
    $userid = $record->userid;

    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'component' => 'mod_arete',      // usually = table name
        'filearea' => 'arlems',             // usually = table name
        'itemid' => $itemid,                // usually = ID of row in table
        'contextid' => $contextid,          // ID of context
        'filepath' => '/',                  // any path beginning and ending in /
        'filename' => $arlem_from_table);   // any filename

    // Get file
    $arlem = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

    // Read contents
    if ($arlem) {
        $root = "zip/". rand() . "/";
        $copied = $arlem->copy_content_to_temp($root);

        if ($copied) {
            //retrieve thumbnail from arlem
            $thumbnail = get_thumbnail($copied, $CFG);

            if (isset($thumbnail) && $thumbnail != '') {
                $service_record = $DB->get_record('external_services', ['component' => 'mod_arete']);
                $token_record = $DB->get_record('external_tokens',
                    ['externalserviceid' => $service_record->id,
                        'userid' => $userid]);
                $token = $token_record->token;
                $context = context_user::instance($userid);
                $user_contextid = $context->id;
                $thumbnail_itemid =  random_int(100000000, 999999999);

                //upload thumbnail
                $result = mod_arete_upload_thumbnail_all_parameters($token, $user_contextid, $thumbnail_itemid, $thumbnail, $userid, $CFG);
                $url = $result['url'];
                $partial_url = format_thumbnail_url_for_table($url);

                $dataobject = array(
                    'id' => $record->id,
                    'thumbnail' => $partial_url
                );
                //Add url to table
                $DB->update_record('arete_allarlems', $dataobject);
            }
            unset($copied);
        }
    } else {
        // file doesn't exist - do something

    }
}

