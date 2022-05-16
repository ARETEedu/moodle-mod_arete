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
 * Prints the editing page and the update form
 * This file includes the JSON validator modal as well.
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\output;

use html_writer,
    moodle_url,
    context_course;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once("$CFG->dirroot/mod/arete/classes/filemanager.php");
require_once("$CFG->dirroot/mod/arete/classes/utilities.php");
require_once("$CFG->libdir/pagelib.php");

defined('MOODLE_INTERNAL') || die;


$mode = filter_input(INPUT_GET, 'mode');
if (isset($mode) && $mode == 'edit') {

    $bootstriplinkparams = array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css'
    );
    echo html_writer::empty_tag('link', $bootstriplinkparams);

    $jsoneditorlinkparams = array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => 'https://cdn.jsdelivr.net/npm/jsoneditor@9/dist/jsoneditor.min.css'
    );
    echo html_writer::empty_tag('link', $jsoneditorlinkparams);
    ;
}