<?php
// This file is part of the Augmented Reality Experience plugin (mod_arete) for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');
require_once($CFG->libdir . '/pagelib.php');

defined('MOODLE_INTERNAL') || die;


$mode = filter_input(INPUT_GET, 'mode');
if(isset($mode) && $mode == 'edit'){
    echo '<link  rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css" />';
    echo '<link  rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/jsoneditor@9/dist/jsoneditor.min.css" />';
}


class EditArlem{

    var $params = array();
    var $userDirPath = '';
    /*
     * constructor will call other functions in this class
     */
    
    function __construct(){
        
        global $USER,$COURSE, $OUTPUT ,$DB ,$CFG,$PAGE;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/editor.js'));
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/arete/js/JsonValidatorController.js'));
        
        //
        //get all get queries of the edit page (true means only values not the keys)
        $queries = get_queries(true);
        
        if(isset($queries['id'])){
            $this->params['id'] = $queries['id'];
        }
        if(isset($queries['itemid'])){
            require_sesskey();
            $this->params['itemid'] = $queries['itemid'];
        }
        if(isset($queries['pnum'])){
            $this->params['pnum'] = $queries['pnum'];
        }
        if(isset($queries['sort'])){
            $this->params['sort'] = $queries['sort'];
        }
        if(isset($queries['editing'])){
            $this->params['editing'] = $queries['editing'];
        }
        if(isset($queries['order'])){
            $this->params['order'] = $queries['order'];
        }
        if(isset($queries['qword'])){
            $this->params['qword'] = $queries['qword'];
        }
        if(isset($queries['mode'])){
            $this->params['mode'] = $queries['mode'];
        }
        if(isset($queries['author'])){
            $this->params['author'] = $queries['author'];
        }

        $this->params['sesskey'] = sesskey();
              
        $context = context_course::instance($COURSE->id);
        $author = $DB->get_field('user', 'username', array('id' => $this->params['author']));
        
        //The user editing folder
        $path = '/mod/arete/temp/';
        $this->userDirPath = $CFG->dirroot . $path . strval($USER->id);
        
        //remove temp dir which is used on editing
          $tempDir = $this->userDirPath. '/';
          if(is_dir($tempDir)){
              deleteDir($tempDir);
        }
        
        //only the owner of the file and the manager can edit files
        if(!isset($this->params['author']) || !isset($author) || ($USER->username != $author &&
                !has_capability('mod/arete:manageall', $context))){
            echo $OUTPUT->notification(get_string('accessnotallow', 'arete'));

        }else{

            $filename = $DB->get_field('arete_allarlems', 'filename', array('itemid' => $this->params['itemid']));
            if(isset($filename)){
               $this->copy_arlem_to_temp($filename, $this->params['itemid']);
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
        
        global $CFG,$DB; 
        
        $activityJSON = '';
        $workplaceJSON = '';
        $activity_has_thumbnail = false;
        
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
                $validatorButtonParams = array(
                    'type' => 'button' ,
                    'id' => 'open-editor-button' ,
                    'value' => get_string('openvalidator', 'arete'),
                    'onClick' => 'toggle_validator();'
                );
                echo html_writer::empty_tag('input', $validatorButtonParams);
                
                $title = $DB->get_field('arete_allarlems', 'title', array('itemid' => $this->params['itemid']));
                echo '<h3>' . get_string('arlemstructure', 'arete') . ' "' . $title . '"</h3><br><br>';
                echo '<form name="editform" action="' . $CFG->wwwroot. '/mod/arete/classes/updatefile.php' .
                        '" method="post" enctype="multipart/form-data">'; 
            }

                echo '<ol>';
                foreach($ffs as $ff){
                    
                    //for folders
                    if(is_dir($dir.'/'.$ff)){
                        $imgParams = array(
                            'src' => $CFG->wwwroot. '/mod/arete/pix/folder.png',
                            'class' => 'editicon'
                        );
                        echo html_writer::empty_tag('img', $imgParams);
                        echo  '<b>' . $ff . '/</b><br>';
                        $this->create_edit_UI($dir.'/'.$ff, $filename);
                        
                    //for files    
                    }else{
                        
                        //create a temp file of this file and store in file system temp filearea
                        $tempfile = create_temp_files($dir.'/'.$ff, $ff);
                        
                        $url = moodle_url::make_pluginfile_url($tempfile->get_contextid(), $tempfile->get_component(),
                                $tempfile->get_filearea(), $tempfile->get_itemid(),
                                $tempfile->get_filepath(), $tempfile->get_filename(), false);
                        
                        echo $this->getIcon($ff) .'<a href="'. $url . '"  target="_blank">'  .$ff . '</a><br>';
                        
                        if($ff == 'thumbnail.jpg'){
                            $activity_has_thumbnail = true;
                        }
                            
                        //parse the url of the json file 
                        if((strcmp(pathinfo($ff, PATHINFO_EXTENSION), 'json') === 0)){
                            
                            //if it is activity json
                            if( strpos($ff, 'activity') !== false)
                            {
                                $activityJSON = GetURL(get_temp_file($ff));
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
            
            ///add these once
                
            $url =  $CFG->wwwroot. "/mod/arete/validator.php?activity=" . $activityJSON . '&workplace=' .  $workplaceJSON;
        
            if($mainFolder == true){
    
                $form = '<br><br>';
                $form .=  html_writer::start_tag('div' , array('id' => 'borderUpdateFile'));   
                $form .=  get_string('selectfiles','arete');
                $form .= '<br>';
                
                $uploaderParams = array(
                    'type' => 'file',
                    'name' => 'files[]',
                    'id' => 'files',
                    'value' => $this->params['id'],
                    'multiple' => 'multiple',
                    'class' => 'file-upload__input'
                );
                $form .= '<div class="file-upload">' .html_writer::empty_tag('input', $uploaderParams).'</div>'; 
                
                $uploadButtonParams = array(
                    'type' => 'button' ,
                    'class' => 'file-upload__button',
                    'value' => get_string('choosefilesbutton', 'arete')
                );
                $form .= html_writer::empty_tag('input', $uploadButtonParams) ;
                
                $form .= html_writer::start_span('span', array( 'class' => 'file-upload__label' )) .
                        get_string('nofileselected', 'arete') . html_writer::end_span();
                
                $form .= '<br><br>';
                
                //if activity has not thumbnail let the user know
                if(!$activity_has_thumbnail){
                    $form .= '*' . get_string('addthumbnail', 'arete');
                    $form .= '<br>';
                }
                
                 //warning icon
                $form .= html_writer::empty_tag('img', array('src' => $CFG->wwwroot. '/mod/arete/pix/warning.png',  'class' => 'icon'));
                $form .= '<span style="color: #ff0000">'.get_string('selectfileshelp', 'arete'). '</span>'; //warning
                $form .= '<br>';
                $form .= html_writer::end_tag('div');
                
                $sessionID = str_replace("-activity.json" , "" ,basename($activityJSON));
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sessionID', 'value' => $sessionID)); 
                
                $userDir = $this->userDirPath;
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userDirPath', 'value' => $userDir)); 
                
                //add all other existing parameters to the url
                foreach($this->params as $key => $value){
                    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value )); 
                }
                
                $form .= '<br><div class="saving-warning" style="display:none;"></div>';
                
                $saveButtonParams = array(
                    'type' => 'button',
                    'id' => 'edit_page_save_button',
                    'name' => 'saveBtn',
                    'class' => 'btn btn-primary',
                    'onClick' => 'checkFiles(this.form);',
                    'value' => get_string('savebutton', 'arete')
                );
                $form .= html_writer::empty_tag('input', $saveButtonParams); 
                
                $form .= '&nbsp;&nbsp;';
                
                $cancelButtonParams = array(
                    'type' => 'submit',
                    'name' => 'cancelBtn',
                    'class' => 'btn btn-primary',
                    'value' => get_string('cancelbutton', 'arete')
                );
                $form .= html_writer::empty_tag('input', $cancelButtonParams);
                
                $form .= '<br><div class="saving-warning" style="display:none; color:red;">' .
                        get_string('savewarning', 'arete') . '</div>';
                $form .= html_writer::end_tag('form');
                $form .= html_writer::end_tag('div');
                
                echo $form;
                
                
                ///JSON Validator Modal
                $this->Modal($activityJSON, $workplaceJSON);
                
                ///
            }
    }
    
    
    function Modal($activityJSON, $workplaceJSON){
        echo html_writer::start_div('validator', array('id' => 'validator-modal', 'role' => "dialog", 'data-backdrop' => "static"));
        echo html_writer::start_div('validator-content', array('id' => 'validator-modal-content'));
        $buttons = html_writer::start_div('text-right');
        
        $saveJsonButtonParams = array(
            'type' => 'button',
            'id' => 'saveJSON',
            'value' => get_string('validatorsave', 'arete'),
            'onClick' => 'On_Save_JSON_Pressed();'
        );
        $buttons .= html_writer::empty_tag('input', $saveJsonButtonParams);
        
        $validatorCloseButtonParams =  array(
            'type' => 'button',
            'value' => get_string('closevalidator', 'arete'),
            'onClick' => 'toggle_validator();'
        );
        $buttons .= html_writer::empty_tag('input',$validatorCloseButtonParams);
        
        $buttons .= html_writer::end_div();

        echo $buttons;

        $validator = html_writer::start_div('', array('id' => 'container'));
        $validator .= html_writer::start_tag('noscript');
        $validator .= 'JavaScript needs to be enabled';
        $validator .= html_writer::end_tag('noscript');
        
        $validatorScriptParams = array(
            'src' => new moodle_url('https://openarlem.github.io/arlem.js/arlem.js'),
            'data-app-activity-ref' => 'activityEditor',
            'data-app-workplace-ref' => 'workplaceEditor',
            'data-app-activity' => $activityJSON,
            'data-app-workplace' => $workplaceJSON
        );
        
        $validator .= html_writer::start_tag('script', $validatorScriptParams);
        $validator .= html_writer::end_tag('script');
        $validator .= html_writer::end_div();

        echo $validator;
        echo html_writer::end_div();
        echo html_writer::end_div();
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
                case 'tilt':
                    $type='tilt';
                    break;
                case 'txt':
                    $type='txt';
                case 'manifest':
                    $type='manifest';
                    break;
                case '':
                    $type='bundle';
                    break;
                default:
                    $type='unknow';
            }

            $iconImageParams = array(
                'src' => $CFG->wwwroot. '/mod/arete/pix/'. $type . '.png',
                'class' => 'editicon'
            );
            return html_writer::empty_tag('img', $iconImageParams) ;
    }

}