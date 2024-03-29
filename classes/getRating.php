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
 * Getting the rating of a file
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete;

require_once(dirname(__FILE__) . '/../../../config.php');

defined('MOODLE_INTERNAL') || die;

$itemid = filter_input(INPUT_POST, 'itemid');


if (!isset($itemid)) {
    echo get_string('unabletogetrating', 'arete');
    exit;
}


$rating = $DB->get_records('arete_rating', array('itemid' => $itemid));

if (!empty($rating)) {

    $counter = 0;
    foreach ($rating as $r) {
        $counter += intval($r->rating);
    }

    $arlemrating = $counter / count($rating);


    $ratingobject->avrage = $arlemrating;
    $ratingobject->votes = count($rating);

    print_r(json_encode($ratingobject));
} else {
    $ratingobject->avrage = 0;
    $ratingobject->votes = 0;

    print_r(json_encode($ratingobject));
}