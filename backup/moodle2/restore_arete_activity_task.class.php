<?php

require_once($CFG->dirroot . '/mod/arete/backup/moodle2/restore_arete_stepslib.php');
class restore_arete_activity_task extends restore_activity_task {
    
    
    protected function define_my_settings() {
        // No particular settings for this activity
    }
    
    
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_arete_activity_structure_step('arete_structure', 'arete.xml'));
    }
    
    
    
    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('arete', array('intro'), 'arete');
        return $contents;
    }
    
    
    
    static public function define_decode_rules() {
        $rules = array();
        return $rules;
    }
}