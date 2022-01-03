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
 * This classes will be called using webservice and will
 * get the authentication information and check if the
 * valid token is available for the user
 *
 * @package    mod_arete
 * @copyright  2021, Abbas Jafari & Fridolin Wild, Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_arete\webservices;

require_once('../../../../config.php');
require_once("{$CFG->dirroot}/mod/arete/classes/utilities.php");

defined('MOODLE_INTERNAL') || die;

/**
 * Get the authentication info from Rest and look for
 * the valid token, if exist send it back
 */
class autentication {

    var $token;
    var $service = 'aretews';
    var $domain;

    /**
     * @param string $domain The Moodle server which is sent from MirageXR app
     */
    function __construct($domain) {
        $this->domain = $domain;
    }

    /**
     * Request token for the user and return token if is available
     * @param string $username The username which is sent from MirageXR app
     * @param string $password The password which is sent from MirageXR app
     * @return string Token if exist
     */
    function request_token($username, $password) {

        $loginparams = array(
            'username' => $username,
            'password' => $password,
            'service' => $this->service
        );
        $response = mod_arete_httpPost("{$this->domain}/login/token.php" , $loginparams);

        $this->token = json_decode($response)->{'token'};

        return $this->get_token();
    }

    /**
     * Return the token of the user
     * @return string Token if exist
     */
    function get_token() {
        if (isset($this->token) && $this->token != '') {
            return $this->token;
        } else {
            return '';
        }
    }

}
