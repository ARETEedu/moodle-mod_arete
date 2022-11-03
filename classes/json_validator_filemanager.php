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
 * Create temporary ARLEM JSONs files for serving them to the validator
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

require_once(dirname(__FILE__) . '/../../../config.php');

//Getting the JSONs strings
$activitystring = filter_input(INPUT_POST, 'activityJson');
$workplacestring = filter_input(INPUT_POST, 'workplaceJSON');


$activityjsonobject = json_decode($activitystring);

$activityjsonpath = "{$CFG->tempdir}/$USER->id/$activityjsonobject->id-activity.json";
file_put_contents($activityjsonpath, $activitystring);

$workplacejsonpath = "{$CFG->tempdir}/$USER->id/$activityjsonobject->id-workplace.json";
file_put_contents($workplacejsonpath, $workplacestring);

echo get_string('validatorsavemsg', 'arete');
