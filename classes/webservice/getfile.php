<?php

require_once('../../../../config.php');
$domainname = 'http://localhost/moodle';

$token = filter_input(INPUT_POST, 'token' , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


if (isset($_FILES['myimage'])){

    $img = $_FILES['myimage']['name'];
    $tmpimg = $_FILES['myimage']['tmp_name'];

    $destination = $CFG->dirroot.'/mod/arete/files/';

    //move the file to the destination
//    move_uploaded_file($tmpimg, $destination . $img );
     
    $image_base64 = base64_encode(file_get_contents($tmpimg)); 
    //To get file extension
    //$fileExt = pathinfo($img, PATHINFO_EXTENSION) ;
    
    
     $data = array('base64' => $image_base64, 'token' => $token);
    
     $ch = curl_init($domainname . '/mod/arete/classes/webservice/upload.php');
     curl_setopt($ch, CURLOPT_POST, true);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


     $response = curl_exec($ch);
     
     if($response == true){

         echo $response;
     }else{
         echo 'Error: ' . curl_error($ch);
     }
     
     curl_close($ch);
     
}
else{
	echo "[error] there is no data with name [myimage]";
        exit();
}

