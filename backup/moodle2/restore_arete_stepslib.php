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
 * Structure step to restore one arete activity
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one arete activity
 */
class restore_arete_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the tables
     * @return array The structure array
     */
    protected function define_structure() {
        $paths = array();

        $paths[] = new restore_path_element('arete', '/activity/arete');


        $paths[] = new restore_path_element('arete_arlem', '/activity/arete/arlem/areteinstance');

        $paths[] = new restore_path_element('arete_allarlems',
                '/activity/arete/arlem/areteinstance/allarlems/arlemfile');

        $paths[] = new restore_path_element('arete_rating',
                '/activity/arete/arlem/areteinstance/allarlems/arlemfile/ratings/rating');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the arete table
     * @global object $DB Moodle database API access
     * @param object $data Data needs to be restored
     */
    protected function process_arete($data) {

        global $DB;
        $data = (object) $data;

        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // insert the record into DB
        $newitemid = $DB->insert_record('arete', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore the data to arete_arlem table
     * @global type $DB Moodle database API access
     * @param type $data $data Data needs to be restored
     */
    protected function process_arete_arlem($data) {
        global $DB;
        $data = (object) $data;

        $data->areteid = $this->get_new_parentid('arete');

        if ($data->userid != null) {
            $data->teacherid = $this->get_mappingid('user', $data->teacherid);
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $newitemid = $DB->insert_record('arete_arlem', $data);
    }

    /**
     * Restore the data to arete_allarlems table
     * @global object $DB Moodle database API access
     * @param object $data Data needs to be restored
     */
    protected function process_arete_allarlems($data) {
        global $DB;
        $data = (object) $data;

        $oldfileid = $data->fileid;

        //if the file does not exist
        if (empty($DB->get_record('arete_allarlems', array('fileid' => $oldfileid)))) {
            $data->timecreated = $this->apply_date_offset($data->timecreated);
            $data->timemodified = $this->apply_date_offset($data->timemodified);
            $newitemid = $DB->insert_record('arete_allarlems', $data);
        }
    }

    /**
     * Restore the data to the rating table
     * @global object $DB Moodle database API access
     * @param object $data Data needs to be restored
     */
    protected function process_arete_rating($data) {
        global $DB;
        $data = (object) $data;

        $olduserid = $data->userid;
        $olditemid = $data->itemid;

        //if the file does not exist
        if (empty($DB->get_record('arete_rating', array('userid' => $olduserid, 'itemid' => $olditemid)))) {
            $data->timecreated = $this->apply_date_offset($data->timecreated);
            $newitemid = $DB->insert_record('arete_rating', $data);
        }
    }

    /**
     * All need to be executed after restoring
     */
    protected function after_execute() {
        $this->add_related_files('mod_arete', 'intro', null);
        $this->add_related_files('mod_arete', 'arlems', null, 1);
        $this->add_related_files('mod_arete', 'thumbnail', null, 1);
    }

}