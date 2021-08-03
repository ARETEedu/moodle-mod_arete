<?php

require_once(dirname(__FILE__). '/../../../config.php');

$activityString = filter_input(INPUT_POST, 'activityJson');
$workplaceString= filter_input(INPUT_POST, 'workplaceJSON');


$activityJsonObj = json_decode($activityString);

$activityJSONPath = $CFG->dirroot.'/mod/arete/temp/'. strval($USER->id) . '/' . $activityJsonObj->id . '-activity.json';
file_put_contents($activityJSONPath , $activityString);

$workplaceJSONPath = $CFG->dirroot.'/mod/arete/temp/'. strval($USER->id) . '/' . $activityJsonObj->id . '-workplace.json';
file_put_contents($workplaceJSONPath , $workplaceString);

echo get_string('validatorsavemsg', 'arete');