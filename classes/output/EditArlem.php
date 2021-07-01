<?php
require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

class EditArlem{

    var $itemid = '';
    var $pageId = '';
    var $pnum = '';
    var $userDirPath = '';
    
    /*
     * constructor will call other functions in this class
     */
    
    function __construct(){
        
        global $USER,$COURSE, $OUTPUT ,$DB ,$CFG,$PAGE;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/editor.js'));

        $id = filter_input(INPUT_GET, 'id' );
        $pnum = filter_input(INPUT_GET, 'pnum' );
        $itemid = filter_input(INPUT_GET, 'itemid' );
        $arlemuserid = filter_input(INPUT_GET, 'user' );
        
        $this->itemid = $itemid;
        $this->pageId = $id;
        $this->pnum = $pnum;
                
        $context = context_course::instance($COURSE->id);
        $author = $DB->get_field('user', 'username', array('id' => $arlemuserid));

        
        //The user editing folder
        $path = '/mod/arete/temp/';
        $this->userDirPath = $CFG->dirroot . $path . strval($USER->id);
        
        //remove temp dir which is used on editing
          $tempDir = $this->userDirPath. '/';
          if(is_dir($tempDir)){
              deleteDir($tempDir);
        }
        
        //only the owner of the file and the manager can edit files
        if(!isset($arlemuserid) || !isset($author) || ($USER->username != $author && !has_capability('mod/arete:manageall', $context))){
            echo $OUTPUT->notification(get_string('accessnotallow', 'arete'));

        }else{

            $filename = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $itemid));
            if(isset($filename)){
               $this->copy_arlem_to_temp($filename, $itemid);
            }

        }

    }
    

    function copy_arlem_to_temp($filename, $itemid){

         $path_to_temp = $this->userDirPath;
                if (!file_exists($path_to_temp)) {
                    mkdir($path_to_temp, 0777, true);
                }
                copyArlemToTemp($filename, $itemid);
                
                $this->unzip_arlem($filename);
    }
    
    
    
    function unzip_arlem($filename){
        $path = $this->userDirPath. '/';
        $zip = new ZipArchive;
        $res = $zip->open($path. $filename);
        if ($res === TRUE) {
          $zip->extractTo($path);
          $zip->close();
          
          
          if (unlink($path. $filename)) //check the zip file can be deleted if so delete it
          {
              //create edit view
              $this->create_edit_UI($this->userDirPath , $filename, true);
          }
          
        } else {
            
          //unable to unzip zip file
          echo get_string('filedamage', 'arete');
        }
        
    }

    
    function create_edit_UI($dir, $filename, $mainFolder = false){
        
        global $CFG; 
        
        $ffs = scandir($dir);

            unset($ffs[array_search('.', $ffs, true)]);
            unset($ffs[array_search('..', $ffs, true)]);

            // prevent empty ordered elements
            if (count($ffs) < 1){
               return; 
            }
            
            //add these only once
            if($mainFolder == true){
              echo html_writer::start_tag('div' , array('id' => 'borderEditPage'));
              echo '<h3>' . get_string('arlemstructure', 'arete') . ' "' . pathinfo($filename, PATHINFO_FILENAME) . '"</h3><br><br>';
              echo '<form name="editform" action="' . $CFG->wwwroot. '/mod/arete/classes/updatefile.php' . '" method="post" enctype="multipart/form-data">'; 
            }

                echo '<ol>';
                foreach($ffs as $ff){
                    
                    //for folders
                    if(is_dir($dir.'/'.$ff)){
                        echo html_writer::empty_tag('img', array('src' => $CFG->wwwroot. '/mod/arete/pix/folder.png', 'class' => 'editicon' ))  . '<b>' . $ff . '/</b><br>';
                        $this->create_edit_UI($dir.'/'.$ff, $filename);
                        
                    //for files    
                    }else{
                        
                        //create a temp file of this file and store in file system temp filearea
                        $tempfile = create_temp_files($dir.'/'.$ff, $ff);
                        
                        $url = moodle_url::make_pluginfile_url($tempfile->get_contextid(), $tempfile->get_component(), $tempfile->get_filearea(), $tempfile->get_itemid(), $tempfile->get_filepath(), $tempfile->get_filename(), false);
                        
                        echo $this->getIcon($ff) .'<a href="'. $url . '"  target="_blank">'  .$ff . '</a><br>';
                        
                        //parse the url of the json file 
                        if((strcmp(pathinfo($ff, PATHINFO_EXTENSION), 'json') === 0)){
                            
                            //if it is activity json
                            if( strpos($ff, 'activity') !== false)
                            {
                                $activityJSON_URL = GetURL(get_temp_file($ff));
                            }
                            //if it is workplace jason
                            else if(strpos($ff, 'workplace') !== false)
                            {
                                $workplaceJSON = GetURL(get_temp_file($ff));
                            }
                        }
                    }
                }
                echo '</ol>';
            
            //add these once
                
            list($activityJSON, $workplaceJSON) = $this->JSONsURL($ff);
            $url =  $CFG->wwwroot. "/mod/arete/validator.php?activity=" . $activityJSON . '&workplace=' .  $workplaceJSON;
                            
            if($mainFolder == true){
                $form =  html_writer::empty_tag('input', array('type' => 'button' ,'style' => 'margin-left: 40px' ,  'value' => get_string('openvalidator', 'arete'), 'onClick' => 'javascript:window.open("' . $url . '");'));
                $form .= '<br><br>';
                $form .=  html_writer::start_tag('div' , array('id' => 'borderUpdateFile'));   
                $form .= get_string('selectfiles','arete');
                $form .= '<br>';
                $form .= '<div class="file-upload">' .html_writer::empty_tag('input', array('type' => 'file', 'name' => 'files[]', 'id' => 'files', 'value' => $this->pageId , 'multiple' => 'multiple', 'class' => 'file-upload__input' )).'</div>'; 
                $form .= html_writer::empty_tag('input', array('type' => 'button' , 'class' => 'file-upload__button' , 'value' => get_string('choosefilesbutton', 'arete'))) ;
                $form .= html_writer::start_span('span', array( 'class' => 'file-upload__label' )) . get_string('nofileselected', 'arete') . html_writer::end_span() ;
                $form .= '<br><br>';
                $form .= html_writer::empty_tag('img', array('src' => $CFG->wwwroot. '/mod/arete/pix/warning.png',  'class' => 'icon')); //warning icon
                $form .= '<span style="color: #ff0000">'.get_string('selectfileshelp', 'arete'). '</span>'; //warning
                $form .= '<br>';
                $form .= html_writer::end_tag('div');
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'itemid', 'value' => $this->itemid )); 
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pageId', 'value' => $this->pageId )); 
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pnum', 'value' => $this->pnum )); 
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userDirPath', 'value' => $this->userDirPath )); 
                $form .= '<br>';
                $form .= html_writer::empty_tag('input', array('type' => 'button', 'name' => 'saveBtn' , 'class' => 'btn btn-primary' ,'onClick' => 'checkFiles(this.form);', 'value' => get_string('savebutton', 'arete') )); 
                $form .= '&nbsp;&nbsp;';
                $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelBtn' , 'class' => 'btn btn-primary' , 'value' => get_string('cancelbutton', 'arete') ));
                $form .= html_writer::end_tag('form');
                $form .= html_writer::end_tag('div');
                
                echo $form;

            }
    }
    
    
    
    /*
     * return the path of activity and workplace jsons from file API
     * 
     */
    function JSONsURL($jsonFilename){

        $activityJSON_URL = null;
        $workplaceJSON_URL = null;
        
        if (strpos($jsonFilename, 'activity') !== false) {
            
            $workplaceFilename = str_replace("activity","workplace",$jsonFilename);
            
            $activityFile = get_temp_file($jsonFilename);
            $workplaceFile = get_temp_file($workplaceFilename);
            
            
            $activityJSON_URL = GetURL($activityFile);
                    
            if($workplaceFile){
               $workplaceJSON_URL = GetURL($workplaceFile);
            }
            

        }else if (strpos($jsonFilename, 'workplace') !== false){
            
            $activityFilename = str_replace("workplace","activity",$jsonFilename);
            
            $activityFile = get_temp_file($activityFilename);
            $workplaceFile = get_temp_file($jsonFilename);
            
            if($activityFile){
                $activityJSON_URL = GetURL($activityFile);
            }
           
            $workplaceJSON_URL = GetURL($workplaceFile);

        }
        
        return array($activityJSON_URL, $workplaceJSON_URL);
        
    }
    
    
    
    function getIcon($filepath)
    {
        global $CFG;
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);

        switch($extension){
                case 'json':
                    $type='json';
                    break;
                case 'png':
                    $type='png';
                    break;
                case 'wav':
                    $type='wav';
                    break;
                case 'mp3':
                    $type='mp3';
                    break;
                case 'avi':
                    $type='avi';
                    break;
                case 'mp4':
                    $type='mp4';
                    break;
                case 'jpg':
                    $type='jpg';
                    break;
                case 'jpeg':
                    $type='jpeg';
                    break;
                case 'gltf':
                    $type='gltf';
                    break;
                case 'bin':
                    $type='bin';
                    break;
                case 'txt':
                    $type='txt';
                    break;
                default:
                    $type='unknow';
            }

            return html_writer::empty_tag('img', array('src' => $CFG->wwwroot. '/mod/arete/pix/'. $type . '.png',  'class' => 'editicon')) ;
    }

}