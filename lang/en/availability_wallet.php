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
 * Language strings.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['ajaxerror'] = 'Error contacting server';
$string['description'] = 'Restriction by payment by wallet balance.';
$string['eithernotdescription'] = 'A cost of {$a->cost} already payed. Your current balance is {$a->balance}<br>';
$string['eitherdescription'] = 'You need to pay {$a->cost} for access. Your current balance is {$a->balance} {$a->paybutton} {$a->couponform}';
$string['fieldlabel'] = 'cost';
$string['insufficientbalance'] = 'Insufficient balance to access, {$a->cost} required while your balance is {$a->balance}';
$string['invalidcost'] = 'ERROR: Invalid cost, please enter a valid cost.';
$string['module'] = 'Module';
$string['noid'] = 'No course module or section provided';
$string['paybuttonlabel'] = 'Pay by wallet';
$string['pluginname'] = 'Restriction by wallet payment.';
$string['title'] = 'Wallet payment';
$string['success'] = 'Payment successful';
