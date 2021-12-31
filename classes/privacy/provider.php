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
 * Privacy Subsystem implementation for mod_arete.
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\privacy;

use core_privacy\local\metadata\collection;


defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider{

    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
                'arete_arlem',
                [
                    'areteid' => 'privacy:metadata:arete_allarlems:areteid',
                    'arlemid' => 'privacy:metadata:arete_allarlems:arlemid',
                    'teacherid' => 'privacy:metadata:arete_allarlems:teacherid',
                    'timecreated' => 'privacy:metadata:arete_allarlems:timecreated',
                ],
                'privacy:metadata:arete_arlem'
        );


        $collection->add_database_table(
                'arete_allarlems',
                [
                    'contextid' => 'privacy:metadata:arete_allarlems:contextid',
                    'fileid' => 'privacy:metadata:arete_allarlems:fileid',
                    'userid' => 'privacy:metadata:arete_allarlems:userid',
                    'itemid' => 'privacy:metadata:arete_allarlems:itemid',
                    'sessionid' => 'privacy:metadata:arete_allarlems:sessionid',
                    'filename' => 'privacy:metadata:arete_allarlems:filename',
                    'views' => 'privacy:metadata:arete_allarlems:views',
                    'title' => 'privacy:metadata:arete_allarlems:title',
                    'filesize' => 'privacy:metadata:arete_allarlems:filesize',
                    'upublic' => 'privacy:metadata:arete_allarlems:upublic',
                    'rate' => 'privacy:metadata:arete_allarlems:rate',
                    'workplace_json' => 'privacy:metadata:arete_allarlems:workplace_json',
                    'activity_json' => 'privacy:metadata:arete_allarlems:activity_json',
                    'timecreated' => 'privacy:metadata:arete_allarlems:timecreated',
                    'timemodified' => 'privacy:metadata:arete_allarlems:timemodified',
                ],
                'privacy:metadata:arete_allarlems'
        );

        $collection->add_database_table(
                'arete_rating',
                [
                    'userid' => 'privacy:metadata:arete_allarlems:userid',
                    'itemid' => 'privacy:metadata:arete_allarlems:itemid',
                    'rating' => 'privacy:metadata:arete_allarlems:rating',
                    'timecreated' => 'privacy:metadata:arete_allarlems:timecreated',
                ],
                'privacy:metadata:arete_rating'
        );


        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {arete} a ON a.id = cm.instance
            INNER JOIN {arete_arlem} ar ON ar.areteid = a.id
            INNER JOIN {arete_allarlems} aa ON aa.id = ar.arlemid
                 WHERE aa.userid = :userid";

        $params = [
            'modname' => 'arete',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all choice answers.
        $sql = "SELECT ca.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {arete} a ON a.id = cm.instance
                  JOIN {arete_arlem} ar ON ar.areteid = a.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'arete',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

}
