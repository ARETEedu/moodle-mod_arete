<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

function draw_arlem_table($arlemslist, $show_radio_button = false)
{
    global $DB, $USER;

    $table = new html_table();
    
    $date_title = get_string('datetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $delete_title = get_string('deletetitle' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');
    
    
    //show the assign button only to teachers
    $table_headers = array($date_title, $arlem_title, $delete_title, $qr_title , $author_title, $assign_title);
    //remove radio buttons and delete button for the students


    foreach ($arlemslist as $arlem) {
        
        $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $arlem->get_id(), 'itemid' => $arlem->get_itemid()));
        $authoruser = $DB->get_record('user', array('id' => $arleminfo->userid));
        
        $url = getArlemURL($arlem->get_filename(), $arlem->get_itemid());
        $qr = '<a href="https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=' .  $url . '" target="_blank">'. get_string('qrtitle' , 'arete') . '</a>';
        
        
        $assign_radio_btn = '<input type="radio" id="' . $arlem->get_itemid() . '" name="arlem" value="' . $arlem->get_id() . '">';
        
        //send id, filename and itemid as value with _ between
        $delete_button = '<input type="checkbox" id="deleteCheckbox" name="deletearlem[]" value="'. $arlem->get_id() . '(,)' . $arlem->get_filename() . '(,)' . $arlem->get_itemid() . '"></input>'; 
        
        $table_row = array( date('m.d.Y', $arleminfo->timecreated), '<a href="' .  $url . '">' . $arlem->get_filename() . '</a>' , $delete_button , $qr,  $authoruser->username, $assign_radio_btn);
        
        
        //remove assign and delete columns if it is the student view
        if($show_radio_button == false){  
           
            if(array_search( $assign_title, $table_headers) !== null ){
                unset($table_headers[array_search( $assign_title, $table_headers)]);
            }

            if(array_search( $delete_title, $table_headers) !== null){
                unset($table_headers[array_search( $delete_title, $table_headers)]);
            }
            
            if(array_search( $assign_radio_btn, $table_row) !== null){
                unset($table_row[array_search( $assign_radio_btn, $table_row)]);
            }
            
            if(array_search( $delete_button , $table_row) !== null){
                unset($table_row[array_search( $delete_button , $table_row)]);
            }
        }

        //delete delete checkbox for the non author users in the teacher view
        if($USER->username != $authoruser->username && $show_radio_button == true)
        {
            $index_of_delete_button = array_search( $delete_button , $table_row);
            if(isset($index_of_delete_button)){
                $table_row[$index_of_delete_button] = get_string('deletenotallow', 'arete');
            }
        }
        

        //fill the table
        $table->id = 'arlemTable';
        $table->head = $table_headers;
        $table->data[] = $table_row;

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
            echo '<script language= "javascript">radiobtn = document.getElementById("'. $arlem->get_itemid() .'");
                   radiobtn.checked = true;</script>';
        }
    }
}