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
 * @package    availability_wallet.
 * @copyright  2023 Mohammad Farouk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../config.php');

require_login();

$cost = required_param('cost', PARAM_NUMBER);
$courseid = required_param('courseid', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);
$contextlevel = required_param('contextlevel', PARAM_INT);

$context = get_context_info_array($contextid);

$url = new moodle_url('/course/view.php', ['id' => $courseid]);
if (confirm_sesskey()) {
    global $USER, $DB;

    $data = [
        'userid' => $USER->id,
        'courseid' => $courseid,
        'cmid' => (!empty($cmid)) ? $cmid : null,
        'sectionid' => (!empty($sectionid)) ? $sectionid : null,
        'cost' => $cost,
        'timecreated' => time(),
    ];
    $DB->insert_record('availability_wallet', $data);

    $coursename = get_course($courseid)->fullname;
    if (!empty($cmid)) {
        list($course, $module) = get_course_and_cm_from_cmid($cmid);

        $name = $course->fullname;
        $name .= ': ';
        $name .= get_string('module', 'availability_wallet');
        $name .= '(' . $module->name . ')';

    } else if (!empty($sectionid)) {
        $section = $DB->get_record('course_sections', ['id' => $sectionid]);
        $course = get_course($courseid);

        $name = $course->fullname;
        $name .= ': ';
        $name .= get_string('section');
        $name .= (!empty($section->name)) ? "($section->name)" : "($section->section)";
    }

    enrol_wallet\transactions::debit($USER->id, $cost, $name);

    $msg = get_string('success', 'availability_wallet');
    redirect($url, $msg);
}
