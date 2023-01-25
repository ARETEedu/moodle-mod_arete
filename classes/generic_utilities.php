<?php

ini_set("log_errors", 1);
ini_set("error_log", "/C/xampp/htdocs/moodle/mod/arete/error.log");

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/arete/classes/move_arlem_from_draft.php');

/**
 * @param $url
 * @return string
 */
function format_thumbnail_url_for_table($url): string
{
    $partial_url_array = explode('/', $url->__toString());
    $partial_url_array[sizeof($partial_url_array) - 1] = 'thumbnail.jpg';
    $lower_limit = sizeof($partial_url_array) - 5;
    $higher_limit = sizeof($partial_url_array);
    $partial_url = implode('/',
        array_slice($partial_url_array, $lower_limit, $higher_limit));
    return $partial_url;
}


/**
 * @param $file the arlem
 * @param $content the content of the arlem
 * @param $CFG
 * @return string
 */
function get_thumbnail($file, $CFG): string
{
    //Get the thumbnail
    $thumbbase64 = '';

    $zip = new \ZipArchive;
    $res = $zip->open($file);
    if ($res === TRUE) {
        $root = "{$CFG->tempdir}/" . rand() . "/";
        $zip->extractTo($root);
        $zip->close();
        $all_of_them = array_slice(scandir($root),2);
        for ($item_index = 0; $item_index < sizeof($all_of_them); $item_index += 1) {
            $items_in_session = array();
            if (is_dir($root . '/' . $all_of_them[$item_index])){
                $items_in_session =  array_slice(scandir($root .'/'. $all_of_them[$item_index]),2);
            }
            for ($items_in_session_index = 0; $items_in_session_index < sizeof($items_in_session); $items_in_session_index += 1){
                if (strpos($items_in_session[$items_in_session_index], 'thumbnail.jpg') !== false) {
                    $thumbnail_item = $root .'/'. $all_of_them[$item_index] .'/'. $items_in_session[$items_in_session_index];
                    $thumbbase64 = base64_encode(file_get_contents($thumbnail_item));
                    break 2;
                }
            }
        }
        unset($root);
    }
    return $thumbbase64;
}


/**
 * Helper method that uploads thumbnail
 *
 * @param $token The token of the user whose id is passed
 * @param int $contextid The context id of the arlem
 * @param int $itemid the itemid of the arlem
 * @param $thumbnail the thumbnail daata
 * @param $userid The id of the user
 * @param $CFG the CFG
 * @return result array
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_arete_upload_thumbnail_all_parameters($token, int $contextid, int $itemid, $thumbnail, $userid, $CFG):array
{
    $result = array( 'text' => null, 'url' => null);
    $parameters = array(
        'wstoken' => $token,
        'wsfunction' => 'core_files_upload',
        'contextid' => $contextid,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $itemid,
        'filepath' => '/', //Should start with / and end with /
        'filename' => 'thumbnail.jpg',
        'filecontent' => $thumbnail,
        'contextlevel' => 'user',
        'instanceid' => $userid,
    );

    $serverurl = "{$CFG->wwwroot}/webservice/rest/server.php";
    $response = mod_arete_httpPost($serverurl, $parameters);

    if ($response == true) {
        //Move it to the plugin filearea
        $result = move_file_from_draft_area_to_arete($userid, $parameters['itemid'], context_system::instance()->id,
            get_string('component', 'arete'), 'thumbnail', $parameters['itemid']);

        //Delete file and the empty folder from user file area
        mod_arete_delete_user_arlem('thumbnail.jpg', $parameters['itemid'], true, $userid);
        mod_arete_delete_user_arlem('.', $parameters['itemid'], true, $userid);
    }
    return $result;
}