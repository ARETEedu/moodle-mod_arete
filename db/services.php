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
 * Arete external functions and service definitions.
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    get_string('modulename', 'arete') => array(
        'functions' => array(
            'core_files_upload',
            'core_user_get_users',
            'core_user_add_user_private_files',
            'core_files_get_files',
            'core_enrol_get_users_courses',
            'core_course_get_contents',
            'core_course_get_course_module_by_instance'
        ),
        'shortname' => 'aretews',
        'restrictedusers' => 0,
        'enabled' => 1,
        'uploadfiles' => 1,
        'downloadfiles' => 1,
    )
);