<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete arete structure for backup, with file and id annotations
 */     
class backup_arete_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
//        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        $arete = new backup_nested_element('arete', array('id'), array(
                   'name', 'timecreated' ,'intro', 'introformat','timemodified'));

//               $allarlems = new backup_nested_element('allarlems');
//
//               $arlem = new backup_nested_element('arlemfile', array('id'), array(
//                   'contextid', 'fileid', 'userid', 'itemid', 'sessionid', 'filename', 'filesize', 'upublic', 'timecreated', 'timemodified'));

               $arete_arlems = new backup_nested_element('arlem');

               $areteinstance = new backup_nested_element('areteinstance', array('id'), array(
                   'areteid', 'arlemid', 'teacherid', 'timecreated'));
        
        // Build the tree
//        $arete->add_child($allarlems);
//        $allarlems->add_child($arlem);

        $arete->add_child($arete_arlems);
        $arete_arlems->add_child($areteinstance);
        
        // Define sources
        $arete->set_source_table('arete', array('id' => backup::VAR_ACTIVITYID));
//        $allarlems->set_source_table('arete_allarlems', array('id' => backup::VAR_ACTIVITYID), 'id ASC');
        $areteinstance->set_source_table('arete_arlem', array('areteid' => backup::VAR_PARENTID), 'id ASC');
        
        
        // Define id annotations
//         $areteinstance->annotate_ids('user', 'teacherid');

        // Define file annotations
//        $arete->annotate_files(get_string('component', 'arete'), get_string('filearea', 'arete'), null); // This file area does not have an itemid.
        
        // Return the root element (choice), wrapped into standard activity structure
        return $this->prepare_activity_structure($arete);
        
    }
}