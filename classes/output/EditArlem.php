<?php
require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

class EditArlem{

    var $itemid = '';
    var $pageId = '';
    var $pnum = '';
    
    
    /*
     * constructor will call other functions in this class
     */
    
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
        $arlemuserid = filter_input(INPUT_GET, 'user' );
        
        $this->itemid = $itemid;
        $this->pageId = $id;
        $this->pnum = $pnum;
                
        $context = context_course::instance($COURSE->id);
        $author = $DB->get_field('user', 'username', array('id' => $arlemuserid));

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
              $this->create_edit_UI($CFG->dirroot. '/mod/arete/temp' , $filename, true);
          }
          
        } else {
            
          //unable to unzip zip file
          echo 'File is damaged!';
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
            
            //add these once
            if($mainFolder == true){
              echo html_writer::start_tag('div' , array('id' => 'borderEditPage'));
              echo '<h3>' . get_string('arlemstructure', 'arete') . ' "' . pathinfo($filename, PATHINFO_FILENAME) . '"</h3><br><br>';
              echo '<form name="editform" action="' . $CFG->wwwroot. '/mod/arete/classes/updatefile.php' . '" method="post" enctype="multipart/form-data">'; 
            }

                echo '<ol>';
                foreach($ffs as $ff){
                    
                    if(is_dir($dir.'/'.$ff)){
                        echo html_writer::empty_tag('img', array('src' => $CFG->wwwroot. '/mod/arete/pix/folder.png', 'class' => 'editicon' ))  . '<b>' . $ff . '/</b><br>';
                        $this->create_edit_UI($dir.'/'.$ff, $filename);
                    }else{
                        
                        //create a temp file of this file and store in file system temp filearea
                        $tempfile = create_temp_files($dir.'/'.$ff, $ff);
                        $url = moodle_url::make_pluginfile_url($tempfile->get_contextid(), $tempfile->get_component(), $tempfile->get_filearea(), $tempfile->get_itemid(), $tempfile->get_filepath(), $tempfile->get_filename(), false);
                        echo $this->getIcon($ff) .'<a href="'. $url . '"  target="_blank">'  .$ff . '</a><br>';

                    }
                }
                echo '</ol>';
            
            //add these once
            if($mainFolder == true){
                $form = '<br><br>';
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
                $form .= '<br>';
                $form .= html_writer::empty_tag('input', array('type' => 'button', 'name' => 'saveBtn' , 'class' => 'btn btn-primary' ,'onClick' => 'checkFiles(this.form);', 'value' => get_string('savebutton', 'arete') )); 
                $form .= '&nbsp;&nbsp;';
                $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelBtn' , 'class' => 'btn btn-primary' , 'value' => get_string('cancelbutton', 'arete') ));
                $form .= html_writer::end_tag('form');
                $form .= html_writer::end_tag('div');
                
                echo $form;
                
                
                $this->MyJS();
            }
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
    
    

    
    function  MyJS(){
        echo '<script>
    /////check file for editing is selected start
    function checkFiles(form){
        if( document.getElementById("files").files.length === 0 ){
            alert("Please select at lease one file to update this activity");
            return;
        }else{
            form.submit();
        }
    };
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
    /////Custom file selector end </script>';
    }
}