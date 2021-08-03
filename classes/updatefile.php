<?php

require_once(dirname(__FILE__). '/../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

$itemid = filter_input(INPUT_POST, 'itemid');
$sessionID = filter_input(INPUT_POST, 'sessionID');
$pageId = filter_input(INPUT_POST, 'pageId');
$pnum = filter_input(INPUT_POST, 'pnum');
$sorting = filter_input(INPUT_POST, 'sort');
$order = filter_input(INPUT_POST, 'order');
$searchQuery = filter_input(INPUT_POST, 'qword');
$userDirPath = filter_input(INPUT_POST, 'userDirPath');

global $USER;


$activityJSON = '';
$workplaceJSON = '';
$numberOfUpdatedFiles = 0;

$qword = isset($searchQuery) && $searchQuery != '' ? '&qword=' . $searchQuery : '';
$sortingMode = isset($sorting) && $sorting != '' ? '&sort=' . $sorting : '';
$orderMode = isset($order) && $order != '' ? '&order=' . $order : '';

//if cancel button is pressed
if (filter_input(INPUT_POST, 'cancelBtn') !== null) {

          //remove temp dir which is used on editing
          $tempDir = $userDirPath. '/';
          if(is_dir($tempDir)){
              deleteDir($tempDir);
         }
         
         //return to the first page
         redirect($CFG->wwwroot .'/mod/arete/view.php?id='. $pageId . '&pnum=' . $pnum . '&editing=on' . $sortingMode . $orderMode . $qword);
         
         return;
} 


//replace user selected files
if(!empty(array_filter($_FILES['files']['name']))) { 

    // Loop through each file in files[] array 
    foreach ($_FILES['files']['tmp_name'] as $key => $value) { 

        $file_tmpname = $_FILES['files']['tmp_name'][$key]; 
        $file_name = $_FILES['files']['name'][$key]; 
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); 

        $result = replace_file($userDirPath, $file_name, $file_ext, $file_tmpname , false);
    }
}


///replace the new json file after editing in jason validator

$activityJson_will_updated_by_user = in_array( $sessionID . '-activity.json' , array_filter($_FILES['files']['name']));
$workplaceJson_will_updated_by_user = in_array( $sessionID . '-workplace.json' , array_filter($_FILES['files']['name']));

//replcace activity json if user does not select it manually to update
if(!$activityJson_will_updated_by_user){
    replace_file($userDirPath, $sessionID . '-activity' , 'json', $sessionID . '-activity.json' , $workplaceJson_will_updated_by_user ? false : true);
}
//replcace workplace json if user does not select it manually to update
if( !$workplaceJson_will_updated_by_user){
    replace_file($userDirPath, $sessionID . '-workplace' , 'json', $sessionID . '-workplace.json' , true);
}

///


/**
 * replace old files with new files in temp folder before zipping them a
 * @global type $DB
 * @global type $itemid
 * @global type $activityJSON
 * @global type $workplaceJSON
 * @global int $numberOfUpdatedFiles
 * @param type $dir
 * @param type $file_name
 * @param type $file_ext
 * @param type $file_tmpname
 * @param type $mainDir
 * @return type
 */
function replace_file($dir, $file_name, $file_ext, $file_tmpname, $is_lastFile = false){
        
    global  $DB,$itemid, $numberOfUpdatedFiles,$userDirPath;
    
        $ffs = scandir($dir);

            unset($ffs[array_search('.', $ffs, true)]);
            unset($ffs[array_search('..', $ffs, true)]);

            // prevent empty ordered elements
            if (count($ffs) < 1){
               return; 
            }
            
            //replace files with same name and extension
            foreach($ffs as $ff){

                //thumbnail file can be uploaded even if it not exist already
                if(!in_array('thumbnail.jpg', $ffs) && $file_name == "thumbnail.jpg"){
                    move_uploaded_file($file_tmpname, $userDirPath . '/thumbnail.jpg' );
                    $numberOfUpdatedFiles ++;
                
                //other selected file need to have a similar file in the zip file to be replaced
                }else if($file_name == $ff && pathinfo($ff, PATHINFO_EXTENSION) ==  $file_ext){
                    move_uploaded_file($file_tmpname, $dir. '/' . $ff);

                    $numberOfUpdatedFiles ++;
                }

                //include all files in subfolders
                else if(is_dir($dir.'/'.$ff)){
                    replace_file($dir.'/'.$ff, $file_name, $file_ext, $file_tmpname);
                }
            }
            
            
            //only once at the end. create zip file after all file are replaced
            if($is_lastFile == true){
               $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
                zipFiles($file); 
            }

    }

    
/**
 * Create the zip file for this ARLEM file and replace it in file system
 * @global type $userDirPath the user folder inside temp folder where all files including the new files are located there
 * @param type $arlem ARLEM object (from all_arlem table)
 */    
    function zipFiles($arlem)
    {
        global $userDirPath,$sessionID ,$activityJSON, $workplaceJSON;
        // Get real path for our folder
        $rootPath = $userDirPath ;


        //get JSON data from files
        $activityJSON = file_get_contents ($userDirPath . '/' . $sessionID . '-activity.json' , FILE_USE_INCLUDE_PATH);
        $workplaceJSON = file_get_contents ($userDirPath . '/' . $sessionID . '-workplace.json', FILE_USE_INCLUDE_PATH);
        
        $newFileName = json_decode($activityJSON)->name . '.zip';
        
        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($rootPath . '/' . $newFileName , ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );


        foreach ($files as $name => $file)
        {
            $path_parts = pathinfo($name);
            $filename = $path_parts['basename'];

            // Skip directories (they would be added automatically) and the zipfile itself
            if (!$file->isDir() && $filename != $newFileName)
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);

                //update thumbnail
                if($filename === 'thumbnail.jpg' ){
                    updateThumbnail($filePath);  
                }
            }
        }
        
        //add JSON files to the new zip file
        $zip->addFile($userDirPath . '/' . $sessionID . '-activity.json',  $sessionID . '-activity.json');
        $zip->addFile($userDirPath . '/' . $sessionID . '-workplace.json', $sessionID . '-workplace.json');

        // Zip archive will be created only after closing object
        $zip->close();

        upload_new_zip($rootPath .'/' . $newFileName, $arlem->filename , $newFileName);
    }
    
    
    /**
     * Upload the new zip file into the file system
     * @global type $itemid
     * @global type $DB
     * @global type $pageId
     * @global type $pnum
     * @global type $CFG
     * @global type $userDirPath
     * @global string $activityJSON
     * @global string $workplaceJSON
     * @global int $numberOfUpdatedFiles
     * @global type $sortingMode
     * @global type $orderMode
     * @global type $qword
     * @param type $filepath
     * @param type $filename
     */
    function upload_new_zip($filepath, $oldFileName, $newFileName){

        global $itemid ,$DB,$pageId ,$pnum, $CFG, $userDirPath,$activityJSON,$workplaceJSON,$numberOfUpdatedFiles;
      
        
         //get the file which need to be updated
         $existingArlem = getArlemByName($oldFileName, $itemid);
         $oldfileid = $existingArlem->get_id();
         
         //use the same date if file exist
         if(isset($existingArlem)){
             
             $Date = $existingArlem->get_timecreated();
             $existingArlem->delete(); //delete the old file
         }else{
             $Date = time();
         }

         //add the updated file to the file system
         $newArlem =  upload_custom_file($filepath, $newFileName, $itemid, $Date); 
         
         //the new file id
         $newArlemID = $newArlem->get_id();
                

         ///update the record of the file in allarlems table
         //the common records
         $parameters = array(
            'fileid' => $newArlemID,
            'timecreated' => $Date,
            'filesize' => $newArlem->get_filesize(),
         );
         
         //update activity_json if updated
         if($activityJSON !== '')
         {
             $parameters += array('activity_json' => $activityJSON);
         }
         
         //update workplace_json if updated
         if($workplaceJSON !== '')
         {
             $parameters += array('workplace_json' => $workplaceJSON);
         }
         
         //get the 
         $arlem_data = $DB->get_records('arete_allarlems', array('itemid' => $itemid ));
                  
         //update timemodified only if at least one file is updated or json files are edited
         if($numberOfUpdatedFiles != 0 || $arlem_data['activity_json']  != $activityJSON ||  $arlem_data['workplace_json']  != $workplaceJSON){
              $parameters += array('timemodified' => time());
         }

         //update the file name
        $parameters += array('filename' => $newFileName);
                       
         //update the table now
         updateArlemObject($oldFileName , $itemid, $parameters);
         ///

         
         //update the record of the file in arete_arlem table
         $activities_that_use_this_arlem = $DB->get_records('arete_arlem', array('arlemid' => $oldfileid) );
         foreach ($activities_that_use_this_arlem as $activity) {
            $activity->arlemid = $newArlemID; //this is the id of the new file
            $activity->timecreated = $Date;
            $DB->update_record('arete_arlem', $activity);
         }

         //remove temp dir which is used on editing
          $tempDir = $userDirPath. '/';
          if(is_dir($tempDir)){
              deleteDir($tempDir);
         }
         
         global $sortingMode, $orderMode, $qword;
         //return to the first page
         redirect($CFG->wwwroot .'/mod/arete/view.php?id='. $pageId . '&pnum=' . $pnum . '&editing=on' . $sortingMode . $orderMode . $qword);

    }
    
    
    /*
     * Delete the old thumbnail and create a new one
     * 
     * @param $filePath path to the new thumbnail
     */
    function updateThumbnail($filePath){
        global $itemid;
        
       $context = context_system::instance()->id;
       $fs = get_file_storage();
       
        $file_record = array('contextid'=>$context, 'component'=> get_string('component', 'arete'), 'filearea'=>'thumbnail',
                'itemid'=> $itemid, 'filepath'=>'/', 'filename'=>'thumbnail.jpg',
                'timecreated'=>time(), 'timemodified'=>time());
        
       $old_thumbnail = $fs->get_file( $context, $file_record['component'], 'thumbnail', $itemid, '/', 'thumbnail.jpg');

        if($old_thumbnail){
            $old_thumbnail->delete();
        }
        

        $fs->create_file_from_pathname($file_record, $filePath);
        
    }
    
    
    
    function copy_Activity_Workplace_JSON(){
        
    }