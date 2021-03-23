<?php

require_once($CFG->dirroot . '/mod/arete/backup/moodle2/restore_arete_stepslib.php');
class restore_arete_activity_task extends restore_activity_task {
    
    
    
    /**
 * wiki restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
    
    
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_arete_activity_structure_step('arete_structure', 'arete.xml'));
    }
    
    
     /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('arete', array('intro'), 'arete');
        return $contents;
    }
    
    
     /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('ARETEVIEWBYID', '/mod/arete/view.php?id=$1', 'course_module');
        return $rules;
    }
}