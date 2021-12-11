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
 * Defines backup_arete_activity_task class
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/arete/backup/moodle2/backup_arete_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/arete/backup/moodle2/backup_arete_settingslib.php');

/**
 * Arete backup task that provides all the settings and steps to perform one
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