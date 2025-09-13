<?php
// This file is part of Moodle - http://moodle.org/
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
 * Privacy Subsystem implementation for enrol_wallet.
 *
 * @package    availability_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_wallet\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy Subsystem for enrol_wallet implementing null_provider.
 *
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider {

    /**
     * Returns meta data about this system.
     * @param collection $collection The initialized collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) :collection {
        $collection->add_database_table('availability_wallet', [
            'userid'     => "privacy:metadata:availability_wallet:userid",
            'cost'       => "privacy:metadata:availability_wallet:cost",
            'courseid'   => "privacy:metadata:availability_wallet:courseid",
            'cmid'       => "privacy:metadata:availability_wallet:cmid",
            'sectionid'  => "privacy:metadata:availability_wallet:sectionid",
            ], "privacy:metadata:availability_wallet");

        return $collection;
    }

}
