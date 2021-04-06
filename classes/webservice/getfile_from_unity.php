<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');

//the variables which  are passed from Unity application
$token = filter_input(INPUT_POST, 'token');
$userid = filter_input(INPUT_POST, 'userid');
$sessionid = filter_input(INPUT_POST, 'sessionid');
$public = filter_input(INPUT_POST, 'public');
$updatefile = filter_input(INPUT_POST, 'updatefile');
$activityJson = filter_input(INPUT_POST, 'activity');
$workplaceJson = filter_input(INPUT_POST, 'workplace');


//if file exist and still user not confirmed updating of the file
if(is_sessionid_exist($sessionid)){
    if($updatefile == '0'){
        
        //if the user is owner of the file update otherwise clone
        if(is_user_owner_of_file($userid,$sessionid)){
            echo 'Error: File exist, update';
        }else{
            echo 'Error: File exist, clone';
        }
    }
    else //update or clone
    {
        process(); 
    }

}else // file not exsit at all
{
    process();
}


/**
 * After checking the file existancy and ownership do the uploading
 */
function process(){
    
    global $CFG, $token, $userid, $sessionid, $public, $updatefile, $activityJson, $workplaceJson;
    
    //if the file is received from Unity application
    if (isset($_FILES['myfile'])){

        $filename = $_FILES['myfile']['name']; //file name
        $file = $_FILES['myfile']['tmp_name'];

        //convert the file to base64 string
        $file_base64 = base64_encode(file_get_contents($file)); 

        //To get file extension
        //$fileExt = pathinfo($img, PATHINFO_EXTENSION) ;


        //Get the thumbnail
        $thumb_base64 = '';
        if(isset($_FILES['thumbnail'])){
            $thumbnail = $_FILES['thumbnail']['tmp_name'];
            //convert the thumbnail  to base64 string
            $thumb_base64 = base64_encode(file_get_contents($thumbnail)); 
        }

        //check public key if exist and is true
        if(isset($public) && $public == 1){
            $public_upload_privacy = 1;  
        }
        else
        {
            $public_upload_privacy = 0;
        }

         $data = array('base64' => $file_base64, 'token' => $token, 'filename' => $filename, 'userid' => $userid, 'sessionid' => $sessionid, 'thumbnail' => $thumb_base64,
             'public' => $public_upload_privacy, 'updatefile' => $updatefile , 'activity' => $activityJson, 'workplace' => $workplaceJson);

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


}
