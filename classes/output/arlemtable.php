<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

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

    global $CFG;
    
    //the popup modal div
    echo add_popup_image_div();
    
    $table = html_writer::start_tag('div');
    $table .= html_writer::start_tag('form', array('action' => 'classes/save_assignment.php', 'method' => 'post' )); //create form
    $table .= html_writer::table(draw_table($splitet_list[$page_number-1],'arlemTable',  true, $moduleid)); //arlems table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'returnurl', 'name' => 'returnurl', 'value' => $CFG->wwwroot .'/mod/arete/view.php?id='. $id . '&pnum=' . $page_number )); //return to this url after saving the table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'moduleid', 'name' => 'moduleid', 'value' => $moduleid )); //id of the current arete module
    $table .= html_writer::start_tag('div');
    $table .= html_writer::empty_tag('input', array('type' => 'button', 'class' => 'btn btn-primary right' ,'onClick' => 'confirmSubmit(this.form);', 'value' =>  get_string('savebutton', 'arete') )); //bottom save button 
    $table .= html_writer::end_tag('div');
    $table .= html_writer::end_tag('form');
    $table .= html_writer::end_tag('div');
    echo $table;
    
    //a javascript function which will confirm file deletion
    printConfirmationJS();
    
    

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
    global $DB, $USER, $CFG, $COURSE,$PAGE;

    $context = context_course::instance($COURSE->id);

    $table = new html_table();

    $date_title = get_string('datetitle' , 'arete');
    $modified_date_title = get_string('modifieddatetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $arlem_thumbnail = get_string('arlemthumbnail' , 'arete');
    $size_title = get_string('sizetitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $download_title = get_string('downloadtitle' , 'arete');
    $edit_title = get_string('editbutton' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $public_title = get_string('publictitle' , 'arete');
    $delete_title = get_string('deletetitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');
    $assignedby_title= get_string('assignedbytitle' , 'arete');


    //show the assign button only to teachers

    if($teacherView){
        $table_headers = array($date_title, $modified_date_title, $arlem_title, $arlem_thumbnail,  $size_title , $author_title,  $download_title, $edit_title,  $qr_title, $public_title,  $delete_title , $assign_title);
    }else{
        $table_headers = array($date_title, $modified_date_title, $arlem_title, $arlem_thumbnail,  $size_title , $author_title, $assignedby_title,  $download_title,  $qr_title);
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

        
        //file size
        $size = get_readable_filesize($arlem->filesize);
        
        
        //author (photo, firstname, lastname
        list($authoruser, $src) = getARLEMOwner($arlem, $PAGE);
        $photo = '<span class="author"><img  class="profileImg" src="'. $src . '" alt="profile picture" width="40" height="40">&nbsp;'; 
        $author = $photo. $authoruser->firstname . ' ' . $authoruser->lastname . '</span>';

        
        //download button
        $url = getArlemURL($arlem->filename, $arlem->itemid);
        $dl_button = '<input type="button" class="button dlbutton"  name="dlBtn' . $arlem->fileid . '" onclick="location.href=\''. $url . '\'" value="'. get_string('downloadbutton' , 'arete') . '">';
        
        
        //edit button
        $page_number = filter_input(INPUT_GET, 'pnum' );//page number from pagination
        $id = required_param('id', PARAM_INT); // Course Module ID.
        $edit_button = '<input type="button" class="button dlbutton"  name="editBtn' . $arlem->fileid . '" onclick="window.open(\''. $CFG->wwwroot .'/mod/arete/view.php?id='. $id . '&pnum=' . $page_number . '&mode=edit&itemid='. $arlem->itemid . '&user=' . $arlem->userid . '\', \'_self\')" value="'. get_string('editbutton' , 'arete') . '">';

        
        //qr code button
        $qr_button = '<input type="button" class="button dlbutton"  name="dlBtn' . $arlem->fileid . '" onclick="window.open(\'https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl='. $url . '\')" value="'. get_string('qrbutton' , 'arete') . '">';

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

        
        //Now fill the row
        if($teacherView){
            $table_row = array($date, $modified_date,  $filename, $thumbnail_img  , $size,  $author ,  $dl_button ,$edit_button,  $qr_button, $public_button . $public_hidden,   $delete_button  , $assign_radio_btn);
        }else{
            $table_row = array($date, $modified_date,  $filename, $thumbnail_img  , $size,  $author , $assignedby,  $dl_button ,  $qr_button);
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





function printConfirmationJS(){

    echo '<script>

        var modalEle = document.querySelector("#modal");
        var modalImage = document.querySelector(".modalImage");
        var modalTitle = document.querySelector("#modalTitle");
        Array.from(document.querySelectorAll(".ImgThumbnail")).forEach(item => {
           item.addEventListener("click", event => {

            const pathArray = event.target.src.split("/");
            const lastIndex = pathArray.length - 1;

            //dont show no-thumbnail
            if(pathArray[lastIndex] != "no-thumbnail.jpg"){
                 modalEle.style.display = "block";
                 modalImage.src = event.target.src;
                 modalTitle.innerHTML = event.target.alt;
            }

           });
        });
        document.querySelector("#modalImg").addEventListener("click", () => {
           modalEle.style.display = "none";
        });

        document.querySelector("#modal").addEventListener("click", () => {
           modalEle.style.display = "none";
        });

        </script>';


    //ARLEM delete confirmation
    echo '<script>

    function confirmSubmit(form)
    {
        var checked = document.querySelectorAll(\'input.deleteCheckbox:checked\');

        if (checked.length === 0) {

                form.submit();
        } else {

    if (confirm("Are you sure you want to delete these files?")) {
         form.submit();
                }
        }
    }

    </script>';

}