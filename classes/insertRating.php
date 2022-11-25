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
 * Insert the rate of a file
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;
use stdClass;

require_once(dirname(__FILE__) . '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$userid = optional_param('userid', null, PARAM_INT);
$itemid = optional_param('itemid', null, PARAM_INT);
$rating = optional_param('rating', null, PARAM_INT);
$onstart = required_param('onstart', PARAM_BOOL);

if ($onstart == 1) {
    echo get_votes();
    die;
}


if (!isset($userid) || !isset($itemid) || !isset($rating)) {
    echo get_string('unabletosetrating', 'arete');
    exit;
}


$currentrating = $DB->get_record('arete_rating', array('userid' => $userid, 'itemid' => $itemid));

if ($currentrating != null) {
    $currentrating->rating = $rating;
    $DB->update_record('arete_rating', $currentrating);
} else {
    $ratingdata = new stdClass();
    $ratingdata->userid = $userid;
    $ratingdata->itemid = $itemid;
    $ratingdata->rating = $rating;
    $ratingdata->timecreated = time();
    $DB->insert_record('arete_rating', $ratingdata);
}


//Calculate the avrage and update/insert into the allarlem table
$ratings = $DB->get_records('arete_rating', array('itemid' => $itemid));
$counter = 0;
foreach ($ratings as $r) {
    $counter += intval($r->rating);
}
$avragerate = floor($counter / count($ratings));
$arlemtoupdate = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
$arlemtoupdate->rate = intval($avragerate);
$DB->update_record('arete_allarlems', $arlemtoupdate);

/**
 * Return the number of votes of the activity
 * @global object $DB The Moodle database object
 * @global string $itemid The item id
 * @return string The total vote number of this file
 */
function get_votes() {
    global $DB, $itemid;
    $params = [$itemid, 0];
    $sql = 'itemid = ? AND rating <> ?';
    $votes = $DB->get_records_select('arete_rating', $sql, $params, 'timecreated DESC');
    return strval(count($votes));
}

echo get_votes();
