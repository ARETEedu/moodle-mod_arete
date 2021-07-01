<?php

require_once(dirname(__FILE__). '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$itemid = filter_input(INPUT_POST, 'itemid');


if(!isset($itemid)){
    echo 'Unable to get the rating record!';
    exit;
}


$rating = $DB->get_records('arete_rating', array('itemid' => $itemid));

if(!empty($rating)){

    $counter = 0;
    foreach ($rating as $r) {
        $counter += intval($r->rating);
    }

    $arlemRating = $counter/ count($rating);
    

    $ratingObject->avrage = $arlemRating;
    $ratingObject->votes = count($rating);
    
    print_r(json_encode($ratingObject));
}else{
    $ratingObject->avrage = 0;
    $ratingObject->votes = 0;
    
    print_r(json_encode($ratingObject));
}