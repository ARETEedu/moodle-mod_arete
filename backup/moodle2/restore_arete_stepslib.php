<?php
/**
* Structure step to restore one choice activity
*/
class restore_arete_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure() {
        $paths = array();

        $paths[] = new restore_path_element('arete', '/activity/arete');


        $paths[] = new restore_path_element('arete_arlem', '/activity/arete/arlem/areteinstance');
        
        $paths[] = new restore_path_element('arete_allarlems', '/activity/arete/arlem/areteinstance/allarlems/arlemfile');
        
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
    
    
    protected function process_arete($data) {
        
         global $DB;
        $data = (object)$data;
        
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // insert the record into DB
        $newitemid = $DB->insert_record('arete', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }


    
    protected function process_arete_arlem($data) {
        global $DB;
        $data = (object)$data;

        $data->areteid = $this->get_new_parentid('arete');
        
        if($data->userid != null){
                    $data->teacherid = $this->get_mappingid('user', $data->teacherid);
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $newitemid = $DB->insert_record('arete_arlem', $data);
        
//        $this->set_mapping('arete_areteinstance', $oldid, $newitemid);
    }
    
    
    
     protected function process_arete_allarlems($data) {
        global $DB;
        $data = (object)$data;
        
        $oldfileid = $data->fileid;
        
        //if the file is not exist
        if(empty($DB->get_record('arete_allarlems',array('fileid' => $oldfileid)))){
           $data->timecreated = $this->apply_date_offset($data->timecreated);
            $data->timemodified = $this->apply_date_offset($data->timemodified);
            $newitemid = $DB->insert_record('arete_allarlems', $data);
        }

    }
    
    protected function after_execute() {
        $this->add_related_files('mod_arete', 'intro', null);
        $this->add_related_files('mod_arete', 'arlems', null , 1);
        $this->add_related_files('mod_arete', 'thumbnail', null , 1);
    }
}