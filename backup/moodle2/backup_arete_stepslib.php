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

        //arete_arlem
        $arete_arlems = new backup_nested_element('arlem');

        $areteinstance = new backup_nested_element('areteinstance', array('id'), array(
            'areteid', 'arlemid', 'teacherid', 'timecreated'));


        //all_arlems
        $allarlems = new backup_nested_element('allarlems');

        $arlem = new backup_nested_element('arlemfile', array('id'), array(
            'contextid', 'fileid', 'userid', 'itemid', 'sessionid', 'filename', 'views', 'filesize', 'upublic', 'activity_json', 'workplace_json', 'timecreated', 'timemodified'));
        
        //rating
        $ratings = new backup_nested_element('ratings');
        $rating = new backup_nested_element('rating' , array('id'), array('userid', 'itemid', 'rating' , 'timecreated'));
               
        // Build the tree
        $arete->add_child($arete_arlems);
        $arete_arlems->add_child($areteinstance);
        
        $areteinstance->add_child($allarlems);
        $allarlems->add_child($arlem);
        
        $arlem->add_child($ratings);
        $ratings->add_child($rating);
        
        // Define sources
        $arete->set_source_table('arete', array('id' => backup::VAR_ACTIVITYID));
        $areteinstance->set_source_table('arete_arlem', array('areteid' => backup::VAR_PARENTID), 'id ASC');
        $arlem->set_source_table('arete_allarlems', array('fileid' => '../../arlemid'));
        $rating->set_source_table('arete_rating', array('itemid' => '../../itemid'));
        
        // Define id annotations
         $areteinstance->annotate_ids('user', 'teacherid');
         
        // Define file annotations
        $arlem->annotate_files('mod_arete', 'arlems', 'itemid', 1); // This file area does not have an itemid.
        $arlem->annotate_files('mod_arete', 'thumbnail', 'itemid', 1); // This file area does not have an itemid.
        //
        // Return the root element (choice), wrapped into standard activity structure
        return $this->prepare_activity_structure($arete);
        
    }
}