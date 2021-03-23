<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/arete/backup/moodle2/backup_arete_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/arete/backup/moodle2/backup_arete_settingslib.php');
/**
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_arete_activity_task extends backup_activity_task {
 
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
 
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_arete_activity_structure_step('arete_structure', 'arete.xml'));
    }
 
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        
        global $CFG;
        $base = preg_quote($CFG->wwwroot, "/");
        
        // Link to wiki view by moduleid
        $search = "/(" . $base . "\/mod\/arete\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ARETEVIEWBYID*$2@$', $content);
        return $content;
    }
}