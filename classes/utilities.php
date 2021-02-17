<?php

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