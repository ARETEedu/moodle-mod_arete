<?php

require_once('../../../../config.php');

$itemid = filter_input(INPUT_POST, 'itemid');

global $DB;

if(isset($itemid)){
    
    $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
    
    if($file !== null && !empty($file)){
        echo "true";
    }else{
        echo "false";
    }
}else{
    
    echo "false";
}