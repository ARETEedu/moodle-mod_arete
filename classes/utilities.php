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

