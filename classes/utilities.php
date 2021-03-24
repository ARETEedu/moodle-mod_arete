<?php

require_once(dirname(__FILE__). '/../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

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
function is_arlem_exist($itemid){
    global $DB;
    
    if($DB->get_record('arete_allarlems' , array ('itemid' => $itemid)) !== null)
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


function get_temp_file($filename){
    
    $context = context_system::instance();
    $fs = get_file_storage();
         
    $fileinfo = array(
             'contextid'=>$context->id, 
             'component'=> get_string('component', 'arete') ,
             'filearea'=> 'temp',
             'filepath'=>'/',
             'filename'=>$filename,
             'timecreated'=>time()
           );
         
    
    //add the updated file to the file system
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], getItemID($fileinfo), $fileinfo['filepath'], $filename);
    
    return $file;
         
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



function get_readable_filesize($size){
    
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
        
        return $size;
}



/// REST CALL
//send a post request
function httpPost($url, $data){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}



