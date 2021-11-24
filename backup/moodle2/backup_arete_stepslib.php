<?php
// This file is part of the Augmented Reality Experience plugin (mod_arete) for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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