<?php

defined('MOODLE_INTERNAL') || die;

//$function = array
//(
//        'moodle_webservice_get_token' => array(
//            'classname' => 'core_webservice_external',
//            'methodname' => 'get_user_token',
//            'classpath' => '/login/token.php',
//            'description' => 'Return token',
//            'type' => 'read',
//      ),  
//);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        get_string('modulename', 'arete') => array(
                'functions' => array ('core_files_upload', 'core_user_get_users', 'core_user_add_user_private_files', 'core_files_get_files'),
                'shortname' =>  'aretews', 
                'restrictedusers' => 0,
                'enabled'=>1,
                'uploadfiles'  => 1,
                'downloadfiles' => 1,
        )
);
