<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_arete_mod_form extends moodleform_mod {
    public function definition() {
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}