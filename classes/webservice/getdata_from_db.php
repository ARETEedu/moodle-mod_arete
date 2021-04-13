<?php

    require_once(dirname(__FILE__). '/../../../../config.php');
    require_once($CFG->dirroot.'/mod/arete/classes/utilities.php');
    
    $request = filter_input(INPUT_POST, 'request');
    $itemid = filter_input(INPUT_POST, 'itemid');
    $userid = filter_input(INPUT_POST, 'userid');
    $token = filter_input(INPUT_POST, 'token');

    //what we need to send back to Unity
    switch ($request){
        case "arlemlist":
            get_arlem_list();
            break;
        default:
            print_r('Error: request is NULL');
            break;
    }
    
    
    
    /**
     * Get  ARLEMs from all_arlems table
     * 
     */
    function get_arlem_list(){
        
        global $DB,$userid,$token;

        
        if(isset($userid) && isset($token)){
            
            //the moudules that the user enrolled to their activities
            $USER_moduleIDs = get_user_arete_modules_ids();
            
            //if the user is enrolled atleast to one activity which contains arete module
            if(!empty($USER_moduleIDs)){
                
                //save all arete ids which user are enrolled to
                $unsorted_arlems =  $DB->get_records_select('arete_allarlems', 'upublic = 1 OR userid = ' . $userid  , null, 'timecreated DESC');  //only public and for the user
                //All arlems with the ARLEMs which are assigne to the courses that user is enrolled to on top of the list
                $arlems = sorted_arlemList_by_user_assigned($unsorted_arlems, $USER_moduleIDs);
                
                print_r(json_encode($arlems));   
                return;
            }
        }

        $arlems =  $DB->get_records('arete_allarlems' , array('upublic' => 1 ), 'timecreated DESC');  //only public and for the user
        //
        //add author name to ARLEM file
        foreach ($arlems as $arlem) {
            $arlem->author = find_author($arlem);
        }
        
        print_r(json_encode($arlems));   
    }

    
    
    /**
     * parse arete modules of a single course
     * 
     * @param course id
     * 
     * @return An array with module instance id of the course
     */
    function user_courses_contains_arete($courseID){
    
    global $CFG,$token;

    $response = httpPost($CFG->wwwroot . '/webservice/rest/server.php' , array('wstoken' => $token , 'moodlewsrestformat' => 'json', 'wsfunction' => 'core_course_get_contents', 'courseid' => $courseID ) );
    
    $arete_modules = array();
    foreach (json_decode($response) as $items) {

        $modules = $items->modules;
        
        foreach ($modules as $mod) {
            if(strcmp($mod->modname , get_string('modname', 'arete')) === 0 ){
                $arete_modules[] = $mod;
            }
        }

    }
    
    return $arete_modules;
    
    }
    
    
    /**
     * Get the courses which the user is enrolled to
     * 
     * @return An array with the arete modules ids of courses which the user is enrolled to
     */
    function get_user_arete_modules_ids(){
       
        global $CFG,$token,$userid;

        $response = httpPost($CFG->wwwroot . '/webservice/rest/server.php' , array('wstoken' => $token , 'moodlewsrestformat' => 'json', 'wsfunction' => 'core_enrol_get_users_courses', 'userid' => $userid ) );
        
        $USER_moduleIDs = array();
        
        foreach (json_decode($response) as $course) {
            $arete_modules = user_courses_contains_arete($course->id);
            foreach ($arete_modules as $arete) {
                $USER_moduleIDs[] = $arete->instance;
            }
        }
        
        return $USER_moduleIDs;
        
    }
    
    

    /**
     * Bring the ARLEMs which are assigned to logged in user on top of the list
     * 
     * @param $arlemList an unsorted ARLEM list
     * @param $user_arete_list list of arete ids which are assigned to the courses which user is enrolled to
     * @return Sorted ARLEM list with ARLEMS of user enrolled courses are on top
     */
    function sorted_arlemList_by_user_assigned($arlemList,$user_arete_list)
    {
        global $DB;

        //get arete_arlems which user is enrolled to its areteid
        $arete_arlem_list = $DB->get_records_select('arete_arlem', 'areteid IN (' . implode(',', $user_arete_list) . ')'  , null);

        $temp_arlemList = $arlemList;


        if (!empty($arlemList) && !empty($user_arete_list)) {
            $newArray = array();

            foreach ($arlemList as $arlem) {

                foreach ($arete_arlem_list as $arete_arlem) {

                    if($arlem->fileid == $arete_arlem->arlemid){
                        
                        //find and add the deadline 
                        $areteid_of_this_arlem = $arete_arlem->areteid;
                        $arlem->deadline = get_course_deadline_by_arete_id($areteid_of_this_arlem);
                        
                        //add the user enrolled arete at the begging of the list
                        $newArray[] = $arlem; 

                        if(in_array($arlem, $temp_arlemList)){

                            $index = array_search($arlem, $temp_arlemList);
                            unset($temp_arlemList[$index]); //remove this item from arlem list
                        }

                    }
                }
            }

            $final_list = array();
            $merged_list = array_merge($newArray, $temp_arlemList);


            foreach ($merged_list as $arlem) {
                //add author name to ARLEM file
                $arlem->author = find_author($arlem);
                
                $final_list[$arlem->id] = $arlem;
            }
            return $final_list;
        }
    }
    
    
    
    /**
     * Get the deadline of a course which this module is a part of it
     * 
     * @global $areteid the arete module instance id

     * @return the deadline date in a specific format
     */
    function get_course_deadline_by_arete_id($areteid){
        
        global $CFG,$token,$DB;
        
        $response = httpPost($CFG->wwwroot . '/webservice/rest/server.php' , array('wstoken' => $token , 'moodlewsrestformat' => 'json', 'wsfunction' => 'core_course_get_course_module_by_instance', 'module' => 'arete' , 'instance' =>  $areteid) );

        $info = json_decode( $response);
        
        $deadline = $DB->get_field('course', 'enddate', array('id' => $info->cm->course));

        return date('m.d.Y H:i ', $deadline);
    }
    
    
    
    /**
     * Get the info of thee ARLEms author
     * 
     * @global $arlem Arlem from allarlem table
     * 
     * @return First name and last name of the author
     */
    function find_author($arlem){
        
        global $DB;
        //add author name to ARLEM file
        $authoruser = $DB->get_record('user', array('id' => $arlem->userid));
        return $authoruser->firstname . ' ' .$authoruser->lastname;
        
    }