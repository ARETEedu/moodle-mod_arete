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
 * This contains functions and classes that will be used by scripts in arete module
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../config.php');

/**
 * Delete an activity.
 *
 * @param int $id The id of arete from arete DB
 */
function arete_delete_activity($id) {


    global $DB;
    if ($DB->get_record('arete', array('id' => $id)) !== null) {
        $DB->delete_records('arete', array('id' => $id));

        if ($DB->get_record('arete_arlem', array('areteid' => $id)) !== null) {
            $DB->delete_records('arete_arlem', array('areteid' => $id));
        }
    }
}