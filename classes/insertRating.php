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

namespace mod_arete;

require_once(dirname(__FILE__) . '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$userid = filter_input(INPUT_POST, 'userid');
$itemid = filter_input(INPUT_POST, 'itemid');
$rating = filter_input(INPUT_POST, 'rating');
$onstart = filter_input(INPUT_POST, 'onstart');

if ($onstart == 1) {
    echo getVotes();
    die;
}


if (!isset($userid) || !isset($itemid) || !isset($rating)) {
    echo get_string('unabletosetrating', 'arete');
    ;
    exit;
}



$currentRating = $DB->get_record('arete_rating', array('userid' => $userid, 'itemid' => $itemid));

if ($currentRating != null) {
    $currentRating->rating = $rating;
    $DB->update_record('arete_rating', $currentRating);
} else {
    $ratingData = new stdClass();
    $ratingData->userid = $userid;
    $ratingData->itemid = $itemid;
    $ratingData->rating = $rating;
    $ratingData->timecreated = time();
    $DB->insert_record('arete_rating', $ratingData);
}



//calculate the avrage and update/insert into the allarlem table
$ratings = $DB->get_records('arete_rating', array('itemid' => $itemid));
$counter = 0;
foreach ($ratings as $r) {
    $counter += intval($r->rating);
}
$avragerate = floor($counter / count($ratings));
$arlem_to_update = $DB->get_record('arete_allarlems', array('itemid' => $itemid));
$arlem_to_update->rate = intval($avragerate);
$DB->update_record('arete_allarlems', $arlem_to_update);

//return the number of votes of the activity
function getVotes() {
    global $DB, $itemid;
    $params = [$itemid, 0];
    $sql = 'itemid = ? AND rating <> ?';
    $votes = $DB->get_records_select('arete_rating', $sql, $params, 'timecreated DESC');
    return strval(count($votes));
}

echo getVotes();