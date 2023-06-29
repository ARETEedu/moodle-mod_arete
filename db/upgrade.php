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
 * This file keeps track of upgrades to the arete module
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_arete_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.
    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.
    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.
    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.
    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2022121202) {
        $table = new xmldb_table('arete_allarlems');
        $field = new xmldb_field('thumbnail', XMLDB_TYPE_TEXT);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // arete_allarlems savepoint reached.
        upgrade_mod_savepoint(true, 2022121202, 'arete');
    }

    if ($oldversion < 2021112500) {
        // Define field title to be added to arete_allarlems.
        $table = new xmldb_table('arete_allarlems');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'views');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // arete_allarlems savepoint reached.
        upgrade_mod_savepoint(true, 2021112500, 'arete');
    }

    // Automatically generated Moodle v3.10.0 release upgrade line.
    // Put any upgrade step following this.
    return true;



}
