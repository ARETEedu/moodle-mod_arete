<?php

defined('MOODLE_INTERNAL') || die;


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        get_string('modulename', 'arete') => array(
                'functions' => array ('core_files_upload', 'core_user_get_users', 'core_user_add_user_private_files', 'core_files_get_files', 'core_enrol_get_users_courses', 'core_course_get_contents', 'core_course_get_course_module_by_instance'),
                'shortname' =>  'aretews', 
                'restrictedusers' => 0,
                'enabled'=>1,
                'uploadfiles'  => 1,
                'downloadfiles' => 1,
        )
);
