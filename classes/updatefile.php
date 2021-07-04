<?php

require_once(dirname(__FILE__). '/../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

defined('MOODLE_INTERNAL') || die;

$itemid = filter_input(INPUT_POST, 'itemid');
$pageId = filter_input(INPUT_POST, 'pageId');
$pnum = filter_input(INPUT_POST, 'pnum');
$sorting = filter_input(INPUT_POST, 'sort');
$order = filter_input(INPUT_POST, 'order');
$searchQuery = filter_input(INPUT_POST, 'qword');
$userDirPath = filter_input(INPUT_POST, 'userDirPath');

//if cancel button is pressed

global $USER;


$activityJSON = '';
$workplaceJSON = '';
$numberOfUpdatedFiles = 0;

$qword = isset($searchQuery) && $searchQuery != '' ? '&qword=' . $searchQuery : '';
$sortingMode = isset($sorting) && $sorting != '' ? '&sort=' . $sorting : '';
$orderMode = isset($order) && $order != '' ? '&order=' . $order : '';

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



if(!empty(array_filter($_FILES['files']['name']))) { 
    // Loop through each file in files[] array 
    foreach ($_FILES['files']['tmp_name'] as $key => $value) { 
        $file_tmpname = $_FILES['files']['tmp_name'][$key]; 
        $file_name = $_FILES['files']['name'][$key]; 
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); 

        $result = replace_file($userDirPath, $file_name, $file_ext, $file_tmpname , true);
    }
}




function replace_file($dir, $file_name, $file_ext, $file_tmpname, $mainDir = false){
        
    global $DB, $itemid,$activityJSON,$workplaceJSON,  $numberOfUpdatedFiles;
    
        $ffs = scandir($dir);

            unset($ffs[array_search('.', $ffs, true)]);
            unset($ffs[array_search('..', $ffs, true)]);

            // prevent empty ordered elements
            if (count($ffs) < 1){
               return; 
            }
            
            //replace files with same name and extension
            foreach($ffs as $ff){
                if($file_name == $ff && pathinfo($ff, PATHINFO_EXTENSION) ==  $file_ext){
                    move_uploaded_file($file_tmpname, $dir. '/' . $ff);
                    
                    if((strcmp(pathinfo($ff, PATHINFO_EXTENSION), 'json') === 0)){

                        //if it is activity json
                        if( strpos($ff, 'activity') !== false)
                        {
                            $activityJSON = file_get_contents ($dir. '/' . $ff, FILE_USE_INCLUDE_PATH);
                        }
                        //if it is workplace jason
                        else if(strpos($ff, 'workplace') !== false)
                        {
                            $workplaceJSON = file_get_contents ($dir. '/' . $ff, FILE_USE_INCLUDE_PATH);
                        }
                    }
                    
                    $numberOfUpdatedFiles ++;
                }
                

                //include all files in subfolders
                if(is_dir($dir.'/'.$ff)){
                    replace_file($dir.'/'.$ff, $file_name, $file_ext, $file_tmpname);
                }
            }
            
            
            //only once at the end
            if($mainDir == true){
               $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
                zipFiles($file); 
            }

    }


    
    
    function zipFiles($arlem)
    {
        global $userDirPath ;
        // Get real path for our folder
        $rootPath = $userDirPath ;

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($rootPath . '/' . $arlem->filename , ZipArchive::CREATE | ZipArchive::OVERWRITE);

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
            if (!$file->isDir() && $filename != $arlem->filename)
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
                

                //update thumbnail
                if($filename == 'thumbnail.jpg' ){
                    updateThumbnail($filePath);  
                }
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        upload_new_zip($rootPath .'/' . $arlem->filename, $arlem->filename);
    }
    
    
    
    function upload_new_zip($filepath, $filename){

        global $itemid ,$DB,$pageId ,$pnum, $CFG, $userDirPath,$activityJSON,$workplaceJSON,$numberOfUpdatedFiles;
        

         //get the file which need to be updated
         $existingArlem = getArlemByName($filename, $itemid);
         $oldfileid = $existingArlem->get_id();
         
         //use the same date if file exist
         if(isset($existingArlem)){
             
             $Date = $existingArlem->get_timecreated();
             $existingArlem->delete(); //delete the old file
         }else{
             $Date = time();
         }

         //add the updated file to the file system
         $newArlem =  upload_custom_file($filepath, $filename, $itemid, $Date); 
         
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
         
         //update timemodified only if at least one file is updated
         if($numberOfUpdatedFiles != 0){
              $parameters += array('timemodified' => time());
         }

         //update the table now
         updateArlemObject($filename, $itemid, $parameters);
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