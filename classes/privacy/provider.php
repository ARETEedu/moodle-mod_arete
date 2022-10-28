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
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();


/**
 * Implementation of the privacy subsystem plugin provider for the arete activity module.
 *
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
    */
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
            INNER JOIN {arete_allarlems} aa ON aa.fileid = ar.arlemid
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

        // Fetch all arete arlems.
        $sql = "SELECT aa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {arete} a ON a.id = cm.instance
                  JOIN {arete_arlem} ar ON ar.areteid = a.id
                  JOIN {arete_allarlems} aa ON aa.fileid = a.arlemid
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'arete',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       aa.fileid as fileid,
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {arete} a ON a.id = cm.instance
            INNER JOIN {arete_arlem} ar ON ar.areteid = a.id
            INNER JOIN {arlem_allarlems} aa ON aa.fileid = ar.arlemid
            INNER JOIN {arlem_rating} av ON av.userid = :userid
                 WHERE c.id {$contextsql}
                       AND aa.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'arete', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;


        $lastcmid = null;

        $areteresults = $DB->get_recordset_sql($sql, $params);
        foreach ($areteresults as $result) {
            // If we've moved to a new arete, then write the last arete data and reinit the arete data array.
            if ($lastcmid != $result->cmid) {
                if (!empty($aretedata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_arete_data_for_user($aretedata, $context, $user);
                }
                $aretedata = [
                    'answer' => [],
                    'timemodified' => \core_privacy\local\request\transform::datetime($result->timemodified),
                ];
            }
            $aretedata['arlem'][] = $result->arlem;
            $lastcmid = $result->cmid;
        }
        $areteresults->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($aretedata)) {
            $context = \context_module::instance($lastcmid);
            self::export_arete_data_for_user($aretedata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single arete activity, along with any generic data or area files.
     *
     * @param array $aretedata the personal data to export for the arete.
     * @param \context_module $context the context of the arete.
     * @param \stdClass $user the user record
     */
    protected static function export_arete_data_for_user(array $aretedata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the arete.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with arete data and write it.
        $contextdata = (object) array_merge((array) $contextdata, $aretedata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('arete', $context->instanceid)) {
            $DB->delete_records('arete_arlem', ['areteid' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }

            $files = $DB->get_records('arete_allarlems', ['userid' => $userid]);
            if (!empty($files)) {
                foreach ($files as $file) {
                    $DB->delete_records('arete_allarlems', ['fileid' => $file['fileid']]);
                    $DB->delete_records('arete_arlem', ['areteid' => $instanceid, 'arlemid' => $file['fileid']]);
                    $DB->delete_records('arete_rating', ['userid' => $userid]);
                }
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('arete', $context->instanceid);

        if (!$cm) {
            // Only arete module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $files = $DB->get_records('arete_allarlems', ['userid' => $usersql]);
        if (!empty($files)) {
            foreach ($files as $file) {
                $DB->delete_records('arete_allarlems', ['fileid' => $file['fileid']]);
                $DB->delete_records('arete_arlem', ['areteid' => $cm->instance, 'arlemid' => $file['fileid']]);
                $DB->delete_records('arete_rating', ['userid' => $usersql]);
            }
        }

    }

}
