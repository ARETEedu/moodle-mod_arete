<?php

//defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../config.php');

global $DB;

$homepageid = filter_input(INPUT_POST, 'homepageid' );
$areteid = filter_input(INPUT_POST, 'moduleid' );
$arlemid = filter_input(INPUT_POST, 'arlem' );

$update_record = new stdClass();
$update_record-> id = $DB->get_field('arete_arlem', 'id', array('areteid' => $areteid ));
$update_record-> areteid = $areteid;
$update_record-> arlemid =  $arlemid;
$update_record->timecreated = time();

//         update the record with this id. $data comes from update_form
$DB->update_record('arete_arlem', $update_record);

//redirect
redirect($CFG->wwwroot . '/mod/arete/view.php?id='. $homepageid, array());