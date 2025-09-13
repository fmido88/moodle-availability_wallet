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
 * Process payment for availability wallet
 *
 * @package    availability_wallet
 * @copyright  2023 Mohammad Farouk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
// Notice and warnings cases a double payments in case of refreshing the page.
set_debugging(DEBUG_NONE);
require_once(__DIR__.'/lib.php');

use enrol_wallet\local\entities\cm;
use enrol_wallet\local\entities\section;
use enrol_wallet\local\wallet\balance_op;

$cost         = required_param('cost', PARAM_NUMBER);
$courseid     = required_param('courseid', PARAM_INT);
$contextid    = required_param('contextid', PARAM_INT);
$cmid         = optional_param('cmid', null, PARAM_INT);
$sectionid    = optional_param('sectionid', null, PARAM_INT);
$contextlevel = required_param('contextlevel', PARAM_INT);

$context = get_context_info_array($contextid);

require_login($courseid);
require_sesskey();

$url = new moodle_url('/course/view.php', ['id' => $courseid]);

if (!empty($cmid)) {
    $helper = new cm($cmid);
} else if (!empty($sectionid)) {
    $helper = new section($sectionid);
} else {
    $msg = get_string('noid', 'availability_wallet');
    redirect($url, $msg, null, 'error');
}

// This function will validate and check the passed $cost if it is really one of the costs...
// ... in the conditions of the cm or section.
$costafter = $helper->get_cost_after_discount($cost);

if (is_null($costafter)) {
    $msg = get_string('paymentnotenought', 'availability_wallet');
    redirect($url, $msg, null, 'error');
}

$data = [
    'userid'      => $USER->id,
    'courseid'    => $courseid,
    'cmid'        => (!empty($cmid)) ? $cmid : null,
    'sectionid'   => (!empty($sectionid)) ? $sectionid : null,
    'cost'        => $cost, // The cost before discount.
    'timecreated' => time(),
];
$DB->insert_record('availability_wallet', $data);

$coursename = $helper->get_course()->fullname;
if (!empty($cmid)) {
    $module = $helper->cm;
    $name = $coursename;
    $name .= ': ';
    $name .= get_string('module', 'availability_wallet');
    $name .= '(' . $module->name . ')';

    $op = balance_op::create_from_cm($cm);
    $by = balance_op::D_CM_ACCESS;
} else if (!empty($sectionid)) {
    $section = $helper->section;
    $name = $coursename;
    $name .= ': ';
    $name .= get_string('section');
    $name .= (!empty($section->name)) ? "($section->name)" : "($section->section)";

    $op = balance_op::create_from_section($section);
    $by = balance_op::D_SECTION_ACCESS;
}

$desc = get_string('debitdesc', 'availability_wallet', $name);
$op->debit($costafter, $by, $cmid ?? $sectionid, $desc);

if (!empty($helper->couponutil->code) && $costafter < $cost) {
    $helper->couponutil->mark_coupon_used();
}

$msg = get_string('success', 'availability_wallet');
redirect($url, $msg);
