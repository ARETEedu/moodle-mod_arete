<?php

require_once(dirname(__FILE__). '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$userid = filter_input(INPUT_POST, 'userid');
$itemid = filter_input(INPUT_POST, 'itemid');
$rating = filter_input(INPUT_POST, 'rating');
$onstart = filter_input(INPUT_POST, 'onstart');

if($onstart == 1){
    echo getVotes();
    die;
}


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



//calculate the avrage and update/insert into the allarlem table
$ratings = $DB->get_records('arete_rating', array('itemid' => $itemid));
$counter = 0;
foreach ($ratings as $r) {
    $counter += intval($r->rating);
}
$avragerate = floor($counter/ count($ratings));
$arlem_to_update = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
$arlem_to_update->rate = intval($avragerate);
$DB->update_record('arete_allarlems', $arlem_to_update);


//return the number of votes of the activity
function getVotes(){
    global $DB, $itemid;
    $votes = $DB->get_records_select('arete_rating', 'itemid = ' . $itemid . ' AND rating <> 0'  , null, 'timecreated DESC'); 
    return strval(count($votes));
}


echo getVotes();
