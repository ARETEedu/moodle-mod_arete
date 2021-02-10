<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

function draw_arlem_table($arlemslist, $show_radio_button = false)
{
    global $DB;

    $table = new html_table();
    
    $date_title = get_string('datetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');
    
    
    //show the assign button only to teachers
    if($show_radio_button){
           $table->head = array($date_title, $arlem_title, $qr_title , $author_title, $assign_title);
    }else{
           $table->head = array($date_title, $arlem_title, $qr_title , $author_title);
    }
 
    
    
    
    
    foreach ($arlemslist as $arlem) {
        
        $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $arlem->get_id(), 'itemid' => $arlem->get_itemid()));
        $authoruser = $DB->get_record('user', array('id' => $arleminfo->userid));
        
        $url = getArlemURL($arlem->get_filename(), $arlem->get_itemid());
        $qr = '<a href="https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=' .  $url . '" target="_blank">'. get_string('qrtitle' , 'arete') . '</a>';
        
        
        if($show_radio_button){
            
            $assign_radio_btn = '<input type="radio" id="' . $arlem->get_id() . '" name="arlem" value="' . $arlem->get_id() . '">';
            $table->data[] = array( date('m.d.Y', $arleminfo->timecreated), '<a href="' .  $url . '">' . $arlem->get_filename() . '</a>' , $qr,  $authoruser->username, $assign_radio_btn);
        }else{
            $table->data[] = array( date('m.d.Y', $arleminfo->timecreated), '<a href="' .  $url . '">' . $arlem->get_filename() . '</a>' , $qr,  $authoruser->username);
        }

    }
    
    return $table;
}




//update assigned arlem when radio button is selected
function update_assignment($areteid){
    
    $arlemsList = getAllArlems();
     foreach ($arlemsList as $arlem) 
    {
        if(is_arlem_assigned($areteid, $arlem->get_id()))
        {
            echo '<script language= "javascript">radiobtn = document.getElementById("'. $arlem->get_id() .'");
                   radiobtn.checked = true;</script>';
        }
    }
}