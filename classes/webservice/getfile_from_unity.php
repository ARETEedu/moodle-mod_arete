<?php

require_once('../../../../config.php');

//the variables which  are passed from Unity application
$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$userid = filter_input(INPUT_POST, 'userid' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


//if the file is received from Unity application
if (isset($_FILES['myfile'])){

    $filename = $_FILES['myfile']['name']; //file name
    $file = $_FILES['myfile']['tmp_name'];
     
    //convert the file to base64 string
    $file_base64 = base64_encode(file_get_contents($file)); 
    
    //To get file extension
    //$fileExt = pathinfo($img, PATHINFO_EXTENSION) ;
    

     $data = array('base64' => $file_base64, 'token' => $token, 'filename' => $filename, 'userid' => $userid);
    
     $ch = curl_init($CFG->wwwroot . '/mod/arete/classes/webservice/upload.php');
     curl_setopt($ch, CURLOPT_POST, true);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


     $response = curl_exec($ch);
                 
     if($response == true){

         echo $response;
        
        //OR move the actual file to the destination
        //    move_uploaded_file($tmpimg, $destination . $img );    
        
     }else{
         echo 'Error: ' . curl_error($ch);
     }
     
    curl_close($ch);
     
}
else{
	echo "[error] there is no data with name [myfile]";
        exit();
}

