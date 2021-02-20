<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');


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
    
    $table = html_writer::start_tag('div');
    $table .= html_writer::start_tag('form', array('action' => 'classes/save_assignment.php', 'method' => 'post' )); //create form
    $table .= html_writer::table(draw_table($splitet_list[$page_number-1],'arlemTable',  true)); //arlems table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'returnurl', 'name' => 'returnurl', 'value' => $CFG->wwwroot .'/mod/arete/view.php?id='. $id . '&pnum=' . $page_number )); //return to this url after saving the table
    $table .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'moduleid', 'name' => 'moduleid', 'value' => $moduleid )); //id of the current arete module
    $table .= html_writer::start_tag('div');
    $table .= html_writer::empty_tag('input', array('type' => 'button', 'class' => 'btn btn-primary right' ,'onClick' => 'confirmSubmit(this.form);', 'value' =>  get_string('savebutton', 'arete') )); //bottom save button 
    $table .= html_writer::end_tag('div');
    $table .= html_writer::end_tag('form');
    $table .= html_writer::end_tag('div');
    echo $table;
    
    //check and set the radio button of the assigend arlem on loading the page
    update_assignment($moduleid, $splitet_list);
    
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
            echo html_writer::table(draw_table(array($arleminfo), 'assignedTable')); //student arlem
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
function draw_table($arlemslist, $tableid ,  $show_radio_button = false)
{
    global $DB, $USER, $CFG, $COURSE,$PAGE;

    $context = context_course::instance($COURSE->id);

    $table = new html_table();

    $date_title = get_string('datetitle' , 'arete');
    $arlem_title = get_string('arlemtitle' , 'arete');
    $size_title = get_string('sizetitle' , 'arete');
    $author_title = get_string('authortitle' , 'arete');
    $download_title = get_string('downloadtitle' , 'arete');
    $edit_title = get_string('editbutton' , 'arete');
    $qr_title = get_string('qrtitle' , 'arete');
    $delete_title = get_string('deletetitle' , 'arete');
    $assign_title = get_string('assigntitle' , 'arete');


    //show the assign button only to teachers
    $table_headers = array($date_title, $arlem_title, $size_title , $author_title,  $download_title, $edit_title,  $qr_title, $delete_title , $assign_title);
    //remove radio buttons and delete button for the students


    foreach ($arlemslist as $arlem) {

        if(isset($arlem->userid) ){
           $authoruser = $DB->get_record('user', array('id' => $arlem->userid)); 
        }



        //date
        $date =  date('m.d.Y H:i ', $arlem->timecreated);

        //arlem title
        $filename = pathinfo($arlem->filename, PATHINFO_FILENAME);

        //file size
        $size = $arlem->filesize;
        
        if($size > 1000000000){
            $size /= pow(1024 ,3);
            $size = round($size,2);
            $size .= ' GB';
        }
        else if($size > 1000000){
            $size /= pow(1024 ,2);
            $size = round($size,2);
            $size .= ' MB';
        }else if($size > 1024){
            $size /= 1024;
            $size = round($size,2);
            $size .= ' KB';
        }else{
            $size = $size/1024;
            $size = round($size,2);
            $size .= ' KB';
        }

        //author (photo, firstname, lastname
        $user_picture=new user_picture($authoruser);
        $src=$user_picture->get_url($PAGE);
        $photo = '<img  style = "border-radius: 50%;" src="'. $src . '" alt="profile picture" width="40" height="40">&nbsp;'; 
        $author = $photo. $authoruser->firstname . ' ' . $authoruser->lastname;

        //download button
        $url = getArlemURL($arlem->filename, $arlem->itemid);
        $dl_button = '<input type="button" class="button dlbutton" id="dlbutton" name="dlBtn' . $arlem->fileid . '" onclick="location.href=\''. $url . '\'" value="'. get_string('downloadbutton' , 'arete') . '">';
        
        //edit button
        $page_number = filter_input(INPUT_GET, 'pnum' );//page number from pagination
        $id = required_param('id', PARAM_INT); // Course Module ID.
        $edit_button = '<input type="button" class="button dlbutton" id="editbutton" name="editBtn' . $arlem->fileid . '" onclick="window.open(\''. $CFG->wwwroot .'/mod/arete/view.php?id='. $id . '&pnum=' . $page_number . '&mode=edit&itemid='. $arlem->itemid . '&user=' . $arlem->userid . '\', \'_self\')" value="'. get_string('editbutton' , 'arete') . '">';

        //qr code button
        $qr_button = '<input type="button" class="button dlbutton" id="dlbutton" name="dlBtn' . $arlem->fileid . '" onclick="window.open(\'https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl='. $url . '\')" value="'. get_string('qrbutton' , 'arete') . '">';

        //send id, filename and itemid as value with (,) between
        $delete_button = '<input type="checkbox" id="deleteCheckbox" name="deletearlem[]" value="'. $arlem->fileid . '(,)' . $arlem->filename . '(,)' . $arlem->itemid . '"></input>'; 

        //assign radio button
        $assign_radio_btn = '<input type="radio" id="' . $arlem->itemid . '" name="arlem" value="' . $arlem->fileid . '">';


        //Now fill the row
        $table_row = array($date,  $filename  , $size,  $author ,  $dl_button ,$edit_button,  $qr_button,   $delete_button  , $assign_radio_btn);


        //for students
        if($show_radio_button == false){  

            //remove assign columns if it is the student view
            if(array_search( $assign_title, $table_headers) !== null ){
                unset($table_headers[array_search( $assign_title, $table_headers)]);
            }

            if(array_search( $assign_radio_btn, $table_row) !== null){
                unset($table_row[array_search( $assign_radio_btn, $table_row)]);
            }
            
            
            //remove delete columns if it is the student view
            if(array_search( $delete_title, $table_headers) !== null){
                unset($table_headers[array_search( $delete_title, $table_headers)]);
            }

            if(array_search( $delete_button , $table_row) !== null){
                unset($table_row[array_search( $delete_button , $table_row)]);
            }
            
            
            //remove edit columns if it is the student view
            if(array_search( $edit_title, $table_headers) !== null){
                unset($table_headers[array_search( $edit_title, $table_headers)]);
            }

            if(array_search( $edit_button , $table_row) !== null){
                unset($table_row[array_search( $edit_button , $table_row)]);
            }
        }
        //for teachers
        else 
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
            }
        }


        //fill the table
        $table->id =  $tableid;
        $table->attributes = array('class' => 'table-responsive');
        $table->head = $table_headers;
        $table->data[] = $table_row;

    }
    
    //a javascript function which will confirm file deletion
    printConfirmationJS();
    
    return $table;
}


/**
 * 
 * On table load check the radio button of the assigned activity
 * 
 * @param $areteid current arete module id
 * 
 */

function update_assignment($areteid, $splitet_list){

     foreach ($splitet_list[0] as $arlem) 
    {

        if(is_arlem_assigned($areteid, $arlem->fileid))
        {
            echo '<script language= "javascript">radiobtn = document.getElementById("'. $arlem->itemid .'");
                   radiobtn.checked = true;</script>';
        }
    }
}



function printConfirmationJS(){
    
echo '<script>
function confirmSubmit(form)
{
    var checked = document.querySelectorAll(\'input#deleteCheckbox:checked\');

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