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
 * Prints a particular instance of Augmented Reality Experience plugin
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../../../config.php');

$activityString = filter_input(INPUT_POST, 'activityJson');
$workplaceString= filter_input(INPUT_POST, 'workplaceJSON');


$activityJsonObj = json_decode($activityString);

$activityJSONPath = $CFG->dirroot.'/mod/arete/temp/'. strval($USER->id) . '/' . $activityJsonObj->id . '-activity.json';
file_put_contents($activityJSONPath , $activityString);

$workplaceJSONPath = $CFG->dirroot.'/mod/arete/temp/'. strval($USER->id) . '/' . $activityJsonObj->id . '-workplace.json';
file_put_contents($workplaceJSONPath , $workplaceString);

echo get_string('validatorsavemsg', 'arete');