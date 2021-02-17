<?php

require_once(dirname(__FILE__). '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

//get the list of arlem files which is assig
function is_arlem_assigned($areteid, $arlemid)
{
    global $DB;

    $arlems = $DB->get_records('arete_arlem', array('areteid' => $areteid, 'arlemid' => $arlemid));

    if(empty($arlems)){
        return false;
    }

    return true;
}



//Returns true if arlem exist
function is_arlem_exist($fileid){
    global $DB;
    
    if($DB->get_record('arete_allarlems' , array ('fileid' => $fileid)) !== null)
    {
        return true;
    }
    
    return false;

}


function deleteDir($dir)
{
    //delete all files from temp filearea
    delete_all_temp_file();
    
   if (substr($dir, strlen($dir)-1, 1) != '/'){
         $dir .= '/';
   }

   if ($handle = opendir($dir))
   {
       while ($obj = readdir($handle))
       {
           if ($obj != '.' && $obj != '..')
           {
               if (is_dir($dir.$obj))
               {
                   if (!deleteDir($dir.$obj)){
                       return false;
                   }
                       
               }
               elseif (is_file($dir.$obj))
               {
                   if (!unlink($dir.$obj)){
                        return false;
                   }
                      
               }
           }
       }

       closedir($handle);

       if (!@rmdir($dir)){
           return false;
       }

       return true;
   }
   return false;
}


function create_temp_files($filepath, $filename){
    
    $context = context_system::instance();
    $fs = get_file_storage();
         
    $fileinfo = array(
             'contextid'=>$context->id, 
             'component'=> get_string('component', 'arete') ,
             'filearea'=> 'temp',
             'itemid'=> random_int(0, 1000), 
             'filepath'=>'/',
             'filename'=>$filename,
             'timecreated'=>time()
           );
         
    
    //add the updated file to the file system
    $tempfile = $fs->create_file_from_pathname($fileinfo, $filepath);
    
    return $tempfile;
         
}



function delete_all_temp_file(){
    
    global $DB;

    $fs = get_file_storage();

    // Get temp files
    $temps = $DB->get_records('files', array('component' => get_string('component', 'arete'), 'filearea' => 'temp'));
    
    foreach ($temps as $temp) {
        $tempfile = $fs->get_file($temp->contextid, get_string('component', 'arete'), 'temp', $temp->itemid, '/', $temp->filename);
        
        if($tempfile){
            $tempfile->delete();
        }
    }

}


function myJS(){
    global $CFG;
//confirm before submit
echo '<script  type="text/javascript">
    
/////confirm arlem delete start
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
/////confirm arlem delete end


/////check file for editing is selected start
function checkFiles(form){
                    if( document.getElementById("files").files.length == 0 ){
                        alert("' . get_string('filenotselectedalert' , 'arete') . ' ");
                        return;
                    }else{
                        form.submit();
                    }
                }
/////check file for editing is selected end



/////Custom file selector start
Array.prototype.forEach.call(
  document.querySelectorAll(".file-upload__button"),
  function(button) {
    const hiddenInput = button.parentElement.querySelector(
      ".file-upload__input"
    );
    const label = button.parentElement.querySelector(".file-upload__label");
    const defaultLabelText = "No file(s) selected";

    // Set default text for label
    label.textContent = defaultLabelText;
    label.title = defaultLabelText;

    button.addEventListener("click", function() {
      hiddenInput.click();
    });

    hiddenInput.addEventListener("change", function() {
      const filenameList = Array.prototype.map.call(hiddenInput.files, function(
        file
      ) {
        return file.name;
      });

      label.textContent = filenameList.join(", ") || defaultLabelText;
      label.title = label.textContent;
    });
  }
);
/////Custom file selector end


</script>';

}
