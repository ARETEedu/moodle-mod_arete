<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

$searchfield = filter_input(INPUT_GET, 'qword');

/**
 * 
 * Print the activity table for the teachers which will show all available activities
 * 
 * @param $splitet_list the list of activity files which should be shown in this page
 * @param $page_number page number from the pagination
 * @param $id course Module ID.
 * @param $moduleid id of the arete module
 * 
 */
function draw_table_for_teachers($splitet_list, $page_number, $id, $moduleid){

    global $CFG, $searchfield;
    
    //if the user searched for somthing
    $searchquery= '';
    if(isset($searchfield)){
        
        if(empty($splitet_list)){
            
            $noARLEMMessage = '<br><br>' . html_writer::start_div('alarm');
            $noARLEMMessage .= get_string('nomatchfound', 'arete') . '"' . $searchfield . '"';
            $noARLEMMessage .= html_writer::end_div();
            echo $noARLEMMessage;
            return;
        }
        
        $searchquery = '&qword=' . $searchfield;
        
    //if there are no arlem on this server at all
    }else{
        
        if(empty($splitet_list)){
            $noARLEMMessage = '<br><br>' . html_writer::start_div('alarm');
            $noARLEMMessage .= get_string('noarlemfound', 'arete');
            $noARLEMMessage .= html_writer::end_div();
            echo $noARLEMMessage;
            return;
        }
        
    }
    
    //the popup modal div
    echo add_popup_image_div();
    
    $table = html_writer::start_tag('div');
    $table .= html_writer::start_tag('form', array('action' => 'classes/save_assignment.php', 'id' => 'tableDataForm' , 'method' => 'post' )); //create form
    $table .= html_writer::table(draw_table($splitet_list[$page_number-1],'arlemTable',  true, $moduleid)); //arlems table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'returnurl', 'name' => 'returnurl', 'value' => $CFG->wwwroot .'/mod/arete/view.php?id='. $id . $searchquery . '&pnum=' . $page_number . '&editing=on')); //return to this url after saving the table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'moduleid', 'name' => 'moduleid', 'value' => $moduleid )); //id of the current arete module
    $table .= html_writer::end_tag('form');
    $table .= html_writer::end_tag('div');
    echo $table;
}


/*
 * Add the needed div for showing pop up images when click on thumbnails

 */
function add_popup_image_div(){
            
        $popup  = html_writer::start_tag('div', array( 'id' => 'modal' ));
            $popup  .= html_writer::start_tag('span', array('id' => 'modalImg'));
                $popup  .= html_writer::start_tag('div',array('id' => 'modalTitle'));
                $popup  .= html_writer::end_tag('div');
                $popup  .= html_writer::empty_tag('img', array( 'class' => 'modalImage'));
            $popup  .= html_writer::end_tag('span');
        $popup  .= html_writer::end_tag('div');
        
        return $popup;
}



/**
 * 
 * Print the activity table which shows only the assigned activity to the students
 * 
 * @param $moduleid id of the arete module
 * @return if the table is printed return false
 * 
 */
function draw_table_for_students($moduleid){

        global $DB;
         //Get the assigned ARLEM from DB
        $activity_arlem = $DB->get_record('arete_arlem', array('areteid' => $moduleid));

     //Get the ARLEM id of the assigned from DB
        if($activity_arlem != null){
             $arleminfo = $DB->get_record('arete_allarlems', array('fileid' => $activity_arlem->arlemid));
        }
        
        //print the assigned ARLEM in a single table row if it is exist
        if(isset($arleminfo) && $arleminfo != null){
            echo html_writer::table(draw_table(array($arleminfo), 'assignedTable', false, $moduleid)); //student arlem
            return true;
        }else{
            return false;
        }
}


/**
 * 
 * Print the activity table for the teachers which will show all available activities
 * 
 * @param $arlemslist the list of activity files which should be shown in this table
 * @param $tableid a unique id that you can use it later for assigning different CSS classes to this table
 * @param $show_radio_button if true assignment would be possible (this should be true only for the teacher table)
 * @return return a html table
 * 
 */
function draw_table($arlemslist, $tableid ,  $teacherView = false, $moduleid = null)
{
    global $USER, $CFG, $COURSE,$PAGE;

    $context = context_course::instance($COURSE->id);

    $table = new html_table();

    $date_title =  $searchbox = get_string('datetitle' , 'arete');
    $modified_date_title = get_string('modifieddatetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $arlem_thumbnail = get_string('arlemthumbnail' , 'arete');
    $viewsTitle = get_string('viewstitle' , 'arete');
    $size_title = get_string('sizetitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $playtitle = get_string('playtitle' , 'arete');
    $downloadtitle = get_string('downloadtitle' , 'arete');
    $edit_title = get_string('editbutton' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $public_title = get_string('publictitle' , 'arete');
    $delete_title = get_string('deletetitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');
    $assignedby_title= get_string('assignedbytitle' , 'arete');
    $rating_title = get_string('ratingtitle' , 'arete');

    //sortable headers for teachers
    if($teacherView){
        $sortMode = filter_input(INPUT_GET, 'sort');
        $orderIcon = $sortMode === 'timecreated' || !isset($sortMode) ? filter_input(INPUT_GET, 'order') === 'ASC' ? ' ↑' : ' ↓' : '';
        $date_title = '<div class="headers" id="timecreated">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'timecreated' . '");')) . get_string('datetitle' , 'arete') . $orderIcon . html_writer::end_tag('a') . '</div>';
        
        $orderIcon = filter_input(INPUT_GET, 'sort') === 'timemodified' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';
        $modified_date_title = '<div class="headers" id="timemodified">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("'  . 'timemodified' . '");')) . get_string('modifieddatetitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>';
        
        $orderIcon = filter_input(INPUT_GET, 'sort') === 'filename' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';
        $arlem_title = '<div class="headers" id="filename">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'filename' . '");')) . get_string('arlemtitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>';
        
        $orderIcon = filter_input(INPUT_GET, 'sort') === 'views' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';
        $viewsTitle = '<div class="headers" id="views">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'views' . '");')) . get_string('viewstitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>';
        
        $orderIcon = filter_input(INPUT_GET, 'sort') === 'filesize' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';
        $size_title = '<div class="headers" id="filesize">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'filesize' . '");')) . get_string('sizetitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>'; 

        $orderIcon = filter_input(INPUT_GET, 'sort') === 'author' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';  
        $author_title = '<div class="headers" id="author">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'author' . '");')) . get_string('authortitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>'; 
        
        $orderIcon = filter_input(INPUT_GET, 'sort') === 'rate' ? filter_input(INPUT_GET, 'order') === 'DESC' ? ' ↑' : ' ↓' : '';  
        $rating_title = '<div class="headers" id="rate">' . html_writer::start_tag('a', array('onclick' => 'reverse_sorting("' . 'rate' . '");')) . get_string('ratingtitle' , 'arete') . $orderIcon . html_writer::end_tag('a'). '</div>'; 
    }


    //show the assign button only to teachers
    if($teacherView){
        $table_headers = array($date_title, $modified_date_title, $arlem_title, $arlem_thumbnail, $viewsTitle,  $size_title , $author_title,  $playtitle, $downloadtitle, $edit_title,  $qr_title, $public_title,  $delete_title , $assign_title, $rating_title);
    }else{
        $table_headers = array($date_title, $modified_date_title, $arlem_title, $arlem_thumbnail,  $size_title , $author_title, $assignedby_title,  $playtitle, $downloadtitle,  $qr_title, $rating_title);
    }
    //remove radio buttons and delete button for the students

    foreach ($arlemslist as $arlem) {

        //date
        $date =  date('m.d.Y H:i ', $arlem->timecreated);

        //modified date
        $modified_date = $arlem->timemodified == 0 ? get_string('neveredited', 'arete') :  date('m.d.Y H:i ',  $arlem->timemodified);
        

        //arlem title
        $filename = pathinfo($arlem->filename, PATHINFO_FILENAME);
        
        
        //thumbnail
        list($thumb_url, $css) = get_thumbnail($arlem->itemid);
        $thumbnail_img  = html_writer::empty_tag('img', array('class' => $css, 'src' => $thumb_url, 'alt' => $filename));

        
        //number of views on app
        $views = get_views($arlem->itemid);
        
        //file size
        $size = get_readable_filesize($arlem->filesize);
        
        
        //author (photo, firstname, lastname
        list($authoruser, $src) = getARLEMOwner($arlem, $PAGE);
        $photo = '<span class="author"><img  class="profileImg" src="'. $src . '" alt="profile picture" width="40" height="40">&nbsp;'; 
        $author = $photo. $authoruser->firstname . ' ' . $authoruser->lastname . '</span>';

        
        //play button
        $play_url = getArlemURL($arlem->filename, $arlem->itemid);
        $play_button = '<a name="playBtn" href ="'. $play_url . '"><img class="tableicons" src="' . $CFG->wwwroot .'/mod/arete/pix/playicon.png" alt="Launch on device"></a>';
      
        //download button
        $download_url = getArlemURL($arlem->filename, $arlem->itemid, true);
        $dl_button = '<a name="dlBtn" href ="'. $download_url . '"><img class="tableicons" src="' . $CFG->wwwroot .'/mod/arete/pix/downloadicon.png" alt="Download Activity"></a>';
        
        
        //edit button
        $queries = get_queries(true);
        $edit_button = '<a name="dlBtn'. $arlem->fileid .'" href ="'. $CFG->wwwroot .'/mod/arete/view.php?id='. $queries['id'] . '&pnum=' . $queries['pnum'] . '&sort=' . $queries['sort'] . '&order=' . $queries['order'] .
                '&editing=' . $queries['editing'] . '&mode=edit&itemid='. $arlem->itemid . '&user=' . $arlem->userid . '"><img class="tableicons" src="' . $CFG->wwwroot .'/mod/arete/pix/editicon.png" alt="Edit Activity"></a>';

        
        //qr code button
        $qr_button = '<a name="dlBtn' . $arlem->fileid . '" href ="https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl='. $download_url . '" target="_blank"><img class="tableicons" src="' . $CFG->wwwroot .'/mod/arete/pix/qricon.png" alt="QR Code"></a>';

        //send filename and itemid as value with (,) between
        if($arlem->upublic == 1){ $checked = 'checked';} else {$checked = '';}
        $public_button = '<input type="checkbox" id="'. $arlem->fileid . '" class="publicCheckbox" name="publicarlemchecked['. $arlem->fileid . '(,)'  . $arlem->filename . '(,)' . $arlem->itemid . ']" '. $checked . '></input>'; 
        $public_hidden = '<input   type="hidden"  name="publicarlem['. $arlem->fileid . '(,)'  . $arlem->filename . '(,)' . $arlem->itemid . ']"  value="1"></input>'; 
        
        //send id, filename and itemid as value with (,) between
        $delete_button = '<input type="checkbox" class="deleteCheckbox" name="deletearlem[]" value="'. $arlem->fileid . '(,)' . $arlem->filename . '(,)' . $arlem->itemid . '"></input>'; 

        
        //assign radio button
        $checked = '';
        if($teacherView == true){
            if(is_arlem_assigned($moduleid, $arlem->fileid)){ $checked = ' checked';}
        }
        $assign_radio_btn = '<input type="radio" id="' . $arlem->itemid . '" name="arlem" value="' . $arlem->fileid .' " '. $checked . ' >';

        
        
        //assigned by in student table
        $assignedby = get_string('notsetyet', 'arete');
        if($teacherView == false){ 
            $assignedby = get_who_assigned_ARLEM($arlem, $moduleid);
        }

//        $rating = 'Temporary disabled';
        $rating = generate_rating_stars($arlem->itemid,$teacherView);

                
        //Now fill the row
        if($teacherView){
            $table_row = array($date, $modified_date,  $filename, $thumbnail_img, $views  , $size,  $author ,  $play_button , $dl_button, $edit_button,  $qr_button, $public_button . $public_hidden,   $delete_button  , $assign_radio_btn, $rating);
        }else{
            $table_row = array($date, $modified_date,  $filename, $thumbnail_img  , $size,  $author , $assignedby,  $play_button, $dl_button,  $qr_button, $rating);
        }



        //apply privacy system for teachers
        //only the owner and the manager can delete, chage privacy and edit files
        if($teacherView)
        {
            // for the non author users in the teacher view
            if($USER->username != $authoruser->username && !has_capability('mod/arete:manageall', $context))
            {
                //delete delete checkbox
                $index_of_delete_checkbox = array_search( $delete_button , $table_row);
                if(isset($index_of_delete_checkbox)){
                    $table_row[$index_of_delete_checkbox] = get_string('deletenotallow', 'arete');
                }
                
                //delete edit button
                $index_of_edit_button = array_search( $edit_button , $table_row);
                if(isset($index_of_edit_button)){
                    $table_row[$index_of_edit_button] = get_string('deletenotallow', 'arete');
                }
                
                //delete public button
                $index_of_public_button = array_search( $public_button.$public_hidden , $table_row);
                if(isset($index_of_public_button)){
                    $table_row[$index_of_public_button] = get_string('deletenotallow', 'arete');
                }
            }
            
        }

        //fill the table
        $table->data[] = $table_row;
    }

    
     //prepare the table and return
    $table->id =  $tableid;
    $table->attributes = array('class' => 'table-responsive');
    $table->head = $table_headers;

    return $table;
}


/**
 * 
 *@param URL of view page where the ARLEM tables are displayed
 *@return A searchbox for arlems
 * 
 */
function searchbox($pageid){
    global $CFG,$searchfield;

    $queries = get_queries();
    $pagemode = $queries['mode'];
    $editing_mode = $queries['editing'];
    $sortingMode = $queries['sort'];
    $orderMode = $queries['order'];
    
    $searchbox = html_writer::start_div('', array('id' => 'searchbox'));
    $searchbox .= html_writer::start_tag('form', array('action' => $CFG->wwwroot .'/mod/arete/view.php?id='. $pageid . $pagemode . $editing_mode . $sortingMode. $orderMode, 'method' => 'get' )); //create form
    $searchbox .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id' , 'value' => $pageid )); 
    $searchbox .= html_writer::empty_tag('input', array('type' => 'text' , 'placeholder' => get_string('search', 'arete') , 'name' => 'qword' , 'value' => isset($searchfield) ? $searchfield : '')); 
    $searchbox .= html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'btn btn-warning right' , 'value' =>  get_string('search', 'arete') )); 
    $searchbox .= html_writer::end_tag('form');
    $searchbox .= html_writer::end_div();
    
    return $searchbox;
}


/**
 * 
 *@param URL of view page where the ARLEM tables are displayed
 *@return A searchbox for arlems
 * 
 */
function create_tabs($arlem_num){
    
    //do not show the buttons if the list is empty
    if($arlem_num == null || $arlem_num == 0){
        return;
    }
    
    $tabs = '<br>';
    $tabs .= html_writer::start_div('', array('id' => 'tabs'));
    
    //editModeButton
    $tabs .= html_writer::empty_tag('input', array('type' => 'button' , 'id' => 'editModeButton', 'value' => get_string('editmodedisabledbutton', 'arete') , 'onclick' => 'edit_mode_toggle(true, window.location.href.includes("&editing=on"));' )); 
    
    //savebutton
    $tabs .= html_writer::empty_tag('input', array('type' => 'button', 'id' => 'saveButton', 'class' => 'btn btn-primary right' ,
        'onClick' => 'confirmSubmit(document.getElementById("tableDataForm"));', 'value' =>  get_string('savebutton', 'arete'))); //bottom save button 
    
    $tabs .= html_writer::end_div();

    
    return $tabs;
}




/**
 * Create student menu bar
 */
function Create_student_menu(){
    global $CFG;
    
    $menu .= html_writer::start_div('', array('id' => 'studentmenu'));

    //Terms Of use Button
    $onclick = 'window.open ("'. $CFG->wwwroot.'/mod/arete/termsofuse.html' . '","Terms of Uses","menubar=1,resizable=1,width=600,height=400");';
    $menu .= html_writer::empty_tag('input', array('type' => 'button' , 'class' => 'menuitem' , 'value' => get_string('termsofuse', 'arete') , 'onclick' => $onclick )); 
    
    //Calibration Marker button
    $menu .= html_writer::empty_tag('input', array('type' => 'button' , 'class' => 'menuitem' ,'value' => get_string('calibrationmarker', 'arete') , 'onclick' => 'forceDownload("'. $CFG->wwwroot.'/mod/arete/pix/CalibrationMarker.png");' )); 
    
    //download MirageXR app
    $menu .= html_writer::empty_tag('input', array('type' => 'button' , 'class' => 'menuitem' ,'value' => get_string('downloadmiragexr', 'arete') , 'onclick' => 'window.open ("https://wekit-ecs.com/community/notes");' )); 
    
    
    //new activity button
    $menu .= html_writer::empty_tag('input', array('type' => 'button' , 'class' => 'menuitem' ,'value' => get_string('newactivity', 'arete') , 'onclick' => 'window.open ("wekit://new" , "_self");' )); 
    
    
    $menu .= html_writer::end_div();

    $menu .= '<br>';
    
    return $menu;
}


/**
 * initiate some variable for using in table.js
 */
function init($userViewMode){
    
    echo '<script>$(document).ready(function() { init(' . $userViewMode . ', "' . get_string('editmodeenabledbutton', 'arete') . '" , "' 
    . get_string('editmodedisabledbutton', 'arete'). '" , "'. get_string('viewstitle', 'arete') . '" , "'. get_string('playtitle', 'arete') . '" , "'. get_string('downloadtitle', 'arete') . '" , "'. get_string('editbutton', 'arete'). '" , "' . get_string('qrtitle', 'arete') .
    '","'  . get_string('publictitle', 'arete') . '","' . get_string('deletetitle', 'arete') . '","' . get_string('assigntitle', 'arete') . '","' . get_string('ratingtitle', 'arete') . '","' . get_string('scoretitle', 'arete') .
            '","' . get_string('votetitle', 'arete') . '","' . get_string('voteregistered', 'arete') . '" ); });</script>';
}



/**
 * Create a 5 star rating container
 */
function generate_rating_stars($itemid, $teacherView){
    
    $idSuffix = $teacherView ? "" : "_studentView";
    
    $ratingSystem = html_writer::start_div('ratingcontainer');    
        $ratingSystem .= html_writer::start_tag('select', array('class' => 'star-rating', 'id' => 'star_rating_' . $itemid . $idSuffix  ));
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '')); $ratingSystem .= '' . html_writer::end_tag('option');
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '1')); $ratingSystem .= '1' . html_writer::end_tag('option');
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '2')); $ratingSystem .= '2' . html_writer::end_tag('option');
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '3')); $ratingSystem .= '3' . html_writer::end_tag('option');
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '4')); $ratingSystem .= '4' . html_writer::end_tag('option');
            $ratingSystem .= html_writer::start_tag('option ', array('value' => '5')); $ratingSystem .= '5' . html_writer::end_tag('option');
        $ratingSystem .= html_writer::end_tag('select');
        $ratingSystem .= html_writer::start_div('ratingtext', array('id' => 'ratingtext_'. $itemid . $idSuffix)); $ratingSystem .= html_writer::end_div(); //rating text
    $ratingSystem .= html_writer::end_div();
   
    return $ratingSystem;
}
