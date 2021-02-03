<?php

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/arete/classes/arlems/mod_arete_update_arlems_list.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot.'/mod/arete/classes/filemanager.php');

$arlemsList = array();

class mod_arete_mod_form extends moodleform_mod {
    
    public function definition() {
        global $CFG, $arlemsList;
        
        $courseid = optional_param('course', null, PARAM_INT);
        $context = context_course::instance($courseid);

        /////test remove this
        if(!isArlemExist( 'test.gnt' ,$context ))
        {
            createArlem('test.gnt','this is a text file is create on' . time(), $context);
        }
        
//        deleteArlem('test.gnt', $context);
        
        /////
        
        $mform = $this->_form;
        
        $config = get_config('page');
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('arname', 'arete'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $this->standard_intro_elements(get_string('description', 'arete'));
        
//-------------------------------------------------------------------------------
        
        $mform->addElement('header', 'title', get_string('arlemsectiontitle', 'arete'));
        
        $mform->addElement('static', 'arlemlisttitle', get_string('arlemradiobuttonlabel', 'arete'));
         
        //get the list of arlem files from 
        $arlemsList = getAllArlems($context);
        
        $arlemsGroup = array();
        foreach($arlemsList as $arlem){
             $arlemsGroup[] = $mform->createElement('radio', 'arlem' , '', $arlem->get_filename(), $arlem->get_filename());
        }
        $mform->addGroup($arlemsGroup, 'arlemsButtons', '', array(' <br> '), false);

        if(isset($arlemsList[1])){
            $mform->setDefault('arlem', $arlemsList[1]->get_filename()); //set the first element as default
        }
//-------------------------------------------------------------------------------

        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'page'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'page'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'page'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printheading', get_string('printheading', 'page'));
        $mform->setDefault('printheading', $config->printheading);
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'page'));
        $mform->setDefault('printintro', $config->printintro);
        $mform->addElement('advcheckbox', 'printlastmodified', get_string('printlastmodified', 'page'));
        $mform->setDefault('printlastmodified', $config->printlastmodified);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'page'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'page'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'page'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }
//        
//-------------------------------------------------------

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function definition_after_data() 
    {    
       global $arlemsList;
        
       $mform = $this->_form;
        
       parent::definition_after_data(); 
//       
//       $utilities = new mod_arete_arlems_utilities();
//
//       foreach ($arlemsList as $arlem) 
//        {
//            if($utilities->is_arlem_assigned($this->_instance, $arlem->id))
//            {
//                $mform->setDefault('arlem', $arlem->get_filename());
//            }
//        }
    }
    
}