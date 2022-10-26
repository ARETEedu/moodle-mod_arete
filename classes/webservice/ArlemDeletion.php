<?php

namespace mod_arete\webservices;

class ArlemDeletion
{
    /**
     * Delete arlem from tables other than plugin
     *
     * @param \moodle_database $DB the database
     * @param $sessionid the session id of the arlem
     * @param $itemid the item id of the arlem
     * @param $fileid the fileId of the arlem
     * @return void
     * @throws \dml_exception If something goes wrong in the database
     */
    public function mod_arete_delete_arlem_from_other_tables(\moodle_database $DB, $sessionid, $itemid, $fileid): void
    {
        $DB->delete_records('arete_allarlems', array('sessionid' => $sessionid, 'itemid' => $itemid));
        $DB->delete_records('arete_arlem', array('arlemid' => $fileid));
        $DB->delete_records('arete_rating', array('itemid' => $itemid));
    }
}