<?php

require_once(dirname(__FILE__). '/../../../config.php');


$target_dir = $CFG->dirroot. '/mod/arete/temp';

if(isset($_POST['itemid'])){
    $itemid = $_POST['itemid'];
}


if(isset($_POST['submit'])){
    
    if(!empty(array_filter($_FILES['files']['name']))) { 
        // Loop through each file in files[] array 
        foreach ($_FILES['files']['tmp_name'] as $key => $value) { 
            $file_tmpname = $_FILES['files']['tmp_name'][$key]; 
            $file_name = $_FILES['files']['name'][$key]; 
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); 
            
            $result = replace_file($target_dir, $file_name, $file_ext, $file_tmpname );
        }
    }
    
}



function replace_file($dir, $file_name, $file_ext, $file_tmpname, $mainDir = false){
        
    global $DB, $itemid;
    
        $ffs = scandir($dir);

            unset($ffs[array_search('.', $ffs, true)]);
            unset($ffs[array_search('..', $ffs, true)]);

            // prevent empty ordered elements
            if (count($ffs) < 1){
               return; 
            }
            
            foreach($ffs as $ff){
                if($file_name == $ff && pathinfo($ff, PATHINFO_EXTENSION) ==  $file_ext){
                    move_uploaded_file($file_tmpname, $dir. '/' . $ff);
                }

                if(is_dir($dir.'/'.$ff)){
                    replace_file($dir.'/'.$ff, $file_name, $file_ext, $file_tmpname);
                }
            }
            
            //only once at the end
            $file = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
            zipFiles($file);
    }

    
    function zipFiles($arlem)
    {
        global $CFG;
        // Get real path for our folder
        $rootPath = $CFG->dirroot. '/mod/arete/temp';

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($rootPath .'/' . $arlem->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

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
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
    }
    
    
    
    function upload_new_zip(){
        
    }