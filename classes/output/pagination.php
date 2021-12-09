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

namespace mod_arete\output;

use html_writer;

defined('MOODLE_INTERNAL') || die;

class pagination {

    /**
     * creates a pagination for the ARLEMs list.
     *
     * @param $splitet_list a list which contains arrays with arlems in each page
     * @param $page_number id of the active page e.g. 1,2,3
     * @param $id id of the current activity
     * @return string HTML pagination
     */
    public function getPagination($splitet_list, $page_number, $id) {

        $queries = mod_arete_get_queries();
        $pagemode = $queries['mode'];
        $editingmode = $queries['editing'];
        $sortingmode = $queries['sort'];
        $ordermode = $queries['order'];
        $searchquery = $queries['qword'];

        $nav = html_writer::start_tag('div', array('class' => 'pagination'));

        $prevbuttonhrefparams = array(
            'id=' . $id,
            'pnum=' . strval($page_number - 1),
        );

        //Add page mode if exist (eg. =edit)
        if (!empty($pagemode)) {
            $prevbuttonhrefparams[] = $pagemode;
        }
        //Add editing mode if exist (eq. =on)
        if (!empty($editingmode)) {
            $prevbuttonhrefparams[] = $editingmode;
        }
        //Add searched word if exist
        if (!empty($searchquery)) {
            $prevbuttonhrefparams[] = $searchquery;
        }
        //Add sorting type if exist (eg. =views)
        if (!empty($sortingmode)) {
            $prevbuttonhrefparams[] = $sortingmode;
        }
        //Add order type if exist (eg. =ASC)
        if (!empty($ordermode)) {
            $prevbuttonhrefparams[] = $ordermode;
        }

        //back button
        $backbuttonhref = $page_number == 1 ? '#' : 'view.php?' . implode('&', $prevbuttonhrefparams);
        $nav .= html_writer::start_tag('a', array('href' => $backbuttonhref));
        $nav .= get_string('paginationprev', 'arete');
        $nav .= html_writer::end_tag('a');

        for ($i = 1; $i < count($splitet_list) + 1; $i++) {
            //replace pnum at index 1 by the new page number
            $prevbuttonhrefparams[1] = 'pnum=' . $i;

            //the new page url
            $pageAttr = array('href' => 'view.php?' . implode('&', $prevbuttonhrefparams));

            //make diffrent color for active page
            if ($i == $page_number) {
                $pageAttr += array('class' => 'btn btn-primary');
            }

            $nav .= html_writer::start_tag('a', $pageAttr);
            $nav .= $i;
            $nav .= html_writer::end_tag('a');
        }

        //replace pnum at index 1 by the new page number
        $prevbuttonhrefparams[1] = 'pnum=' . strval($page_number + 1);

        //next button
        $nextButtonHref = $page_number == count($splitet_list) ? '#' : 'view.php?' . implode('&', $prevbuttonhrefparams);
        $nav .= html_writer::start_tag('a', array('href' => $nextButtonHref));
        $nav .= get_string('paginationnext', 'arete');
        $nav .= html_writer::end_tag('a');

        $nav .= html_writer::end_tag('div');

        return $nav;
    }

}
