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
 * Availability Wallet lib.
 *
 * @package    availability_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Check if the cost passed to the process page is the same as the cost
  * defined in at least one of the conditions.
  * @param stdClass $conditions the availability tree.
  * @param float $cost the cost passed to the process page.
  */
function availability_wallet_check_cost($conditions, $cost) {

    foreach ($conditions->c as $child) {
        if (!empty($child->c) && !empty($child->op)) {
            if (availability_wallet_check_cost($child, $cost)) {
                return true;
            }
        } else if ($child->type === 'wallet') {
            if ($cost == $child->cost) {
                return true;
            }
        }
    }
    return false;
}
