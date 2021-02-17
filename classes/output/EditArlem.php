<?php
require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

class EditArlem{

    var $itemid = '';
    
    function __construct(){
        
        global $USER,$COURSE, $OUTPUT ,$DB ,$CFG;

         //remove temp dir which is used on editing
          $tempDir = $CFG->dirroot. '/mod/arete/temp/';
          if(is_dir($tempDir)){
              deleteDir($tempDir);
         }
          
          
        $id = filter_input(INPUT_GET, 'id' );
        $pnum = filter_input(INPUT_GET, 'pnum' );
        $itemid = filter_input(INPUT_GET, 'itemid' );
        $arlemuser = filter_input(INPUT_GET, 'user' );
        
        $this->itemid = $itemid;
        
        $context = context_course::instance($COURSE->id);

        if(!isset($arlemuser) || ($USER->username != $arlemuser && !has_capability('mod/arete:manageall', $context))){
            echo $OUTPUT->notification(get_string('accessnotallow', 'arete'));
        }else{

            $filename = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $itemid));
            if(isset($filename)){
               $this->copy_arlem_to_temp($filename, $itemid);
            }

        }

    }
    

    function copy_arlem_to_temp($filename, $itemid){
        global $CFG;
        
         $path_to_temp = $CFG->dirroot. '/mod/arete/temp';
                if (!file_exists($path_to_temp)) {
                    mkdir($path_to_temp, 0777, true);
                }
                copyArlemToTemp($filename, $itemid);
                
                $this->unzip_arlem($filename);
    }
    
    
    
    function unzip_arlem($filename){
        global $CFG;
        $path = $CFG->dirroot. '/mod/arete/temp/';
        $zip = new ZipArchive;
        $res = $zip->open($path. $filename);
        if ($res === TRUE) {
          $zip->extractTo($path);
          $zip->close();
          
          
          if (unlink($path. $filename)) //check the zip file can be deleted if so delete it
          {
              //create edit view
              $this->create_edit_UI($CFG->dirroot. '/mod/arete/temp' , true);
          }
          
        } else {
            
          //unable to unzip zip file
          echo 'File is damaged!';
        }
        
    }

    
    function create_edit_UI($dir, $mainFolder = false){
        
        global $CFG; 
        
        $ffs = scandir($dir);

            unset($ffs[array_search('.', $ffs, true)]);
            unset($ffs[array_search('..', $ffs, true)]);

            // prevent empty ordered elements
            if (count($ffs) < 1){
               return; 
            }
            
            if($mainFolder == true){
              echo '<form name="editform" action="' . $CFG->wwwroot. '/mod/arete/classes/updatefile.php' . '" method="post" enctype="multipart/form-data">';
            }
                echo '<ol>';
                foreach($ffs as $ff){
 

                    if(is_dir($dir.'/'.$ff)){
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>'  .$ff . '</b><br>';
                        $this->create_edit_UI($dir.'/'.$ff);
                    }else{
;                        echo '&nbsp;&nbsp;&nbsp;&nbsp;-><a href="'. $dir.'/'.$ff . '">'  .$ff . '</a><br>';
                    }

                }
                echo '</ol>';
            
            if($mainFolder == true){
                echo '<input type="file" name="files[]" multiple="multiple"><br><br>';
                echo '<input type="hidden" name="itemid" value='. $this->itemid . '>';
                echo '<input type="submit" value="Save" name="submit" >';
                echo '</form>';
            }
    }
}