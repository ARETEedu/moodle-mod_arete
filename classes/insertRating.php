<?php

require_once(dirname(__FILE__). '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$userid = filter_input(INPUT_POST, 'userid');
$itemid = filter_input(INPUT_POST, 'itemid');
$rating = filter_input(INPUT_POST, 'rating');

if(!isset($userid) || !isset($itemid) || !isset($rating)){
    echo 'Unable to set your rating record!';
    exit;
}



$currentRating = $DB->get_record('arete_rating', array('userid' => $userid , 'itemid' => $itemid));

if($currentRating != null){
    $currentRating->rating = $rating;
    $DB->update_record('arete_rating', $currentRating);
    
}else{
    $ratingData = new stdClass();
    $ratingData->userid = $userid;
    $ratingData->itemid = $itemid;
    $ratingData->rating = $rating;
    $ratingData->timecreated = time();
    $DB->insert_record('arete_rating', $ratingData); 
}

echo 'Rating is updated';
