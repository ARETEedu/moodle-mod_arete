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
 * Getting the authentication information from Mirage-XR app
 * and send a succeed message if the token is valid
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\webservices;

require_once 'autentication.php';

$username = required_param('username', PARAM_USERNAME);
$password = required_param('password', PARAM_RAW);
$domain = required_param('domain', PARAM_URL);

$autentication = new autentication($domain);
$token = $autentication->request_token($username, $password);

if (isset($token) && $token != '') {
    echo "succeed,{$token}";
} else {
    //Will be check on the app, therefore needs to be hardcoded
    echo ('User login faild');
}