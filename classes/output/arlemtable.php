<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

function draw_arlem_table($arlemslist, $tableid ,  $show_radio_button = false)
{
    global $DB, $USER, $CFG;

    $table = new html_table();
    
    $date_title = get_string('datetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $size_title = get_string('sizetitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $download_title = get_string('downloadtitle' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $delete_title = get_string('deletetitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');
    
    
    //show the assign button only to teachers
    $table_headers = array($date_title, $arlem_title, $size_title , $author_title,  $download_title,  $qr_title, $delete_title , $assign_title);
    //remove radio buttons and delete button for the students


    foreach ($arlemslist as $arlem) {

        $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $arlem->get_id(), 'itemid' => $arlem->get_itemid()));
        $authoruser = $DB->get_record('user', array('id' => $arleminfo->userid));
        

        //date
        $date =  date('m.d.Y', $arleminfo->timecreated);
        
        //arlem title
        $filename = pathinfo($arlem->get_filename(), PATHINFO_FILENAME);
        
        //file size
        $size = $arlem->get_filesize();
        
        if($size > 1000000){
            $size /= (1024*1024);
            $size = round($size,2);
            $size .= ' MB';
        }else if($size > 1024){
            $size /= 1024;
            $size = round($size,2);
            $size .= ' KB';
        }
        
        //author (photo, firstname, lastname
        $src = $CFG->wwwroot.'/pluginfile.php/'. context_user::instance($authoruser->id)->id .'/user/icon/';
        $photo = '<img  style = "border-radius: 50%;" src="'. $src . '" alt="profile picture" width="40" height="40">&nbsp;'; 
        $author = $photo. $authoruser->firstname . ' ' . $authoruser->lastname;
        
        //download button
        $url = getArlemURL($arlem->get_filename(), $arlem->get_itemid());
        $dl_button = '<input type="button" class="button dlbutton" id="dlbutton" name="dlBtn' . $arlem->get_id() . '" onclick="location.href=\''. $url . '\'" value="'. get_string('downloadbutton' , 'arete') . '">';
        
        //qr code button
        $qr_button = '<input type="button" class="button dlbutton" id="dlbutton" name="dlBtn' . $arlem->get_id() . '" onclick="window.open(\'https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl='. $url . '\')" value="'. get_string('qrbutton' , 'arete') . '">';
        
        //send id, filename and itemid as value with (,) between
        $delete_button = '<input type="checkbox" id="deleteCheckbox" name="deletearlem[]" value="'. $arlem->get_id() . '(,)' . $arlem->get_filename() . '(,)' . $arlem->get_itemid() . '"></input>'; 
        
        //assign radio button
        $assign_radio_btn = '<input type="radio" id="' . $arlem->get_itemid() . '" name="arlem" value="' . $arlem->get_id() . '">';

        
        //Now fill the row
        $table_row = array($date,  $filename  , $size,  $author ,  $dl_button , $qr_button,   $delete_button  , $assign_radio_btn);
        
        
        //for students
        if($show_radio_button == false){  
           
            //remove assign and delete columns if it is the student view
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
        //for teachers
        else 
        {
            //delete delete checkbox for the non author users in the teacher view
            if($USER->username != $authoruser->username)
            {
                $index_of_delete_button = array_search( $delete_button , $table_row);
                if(isset($index_of_delete_button)){
                    $table_row[$index_of_delete_button] = get_string('deletenotallow', 'arete');
                }
            }
        }


        

        //fill the table
        $table->id =  $tableid;
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