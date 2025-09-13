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
 * Wallet condition.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_wallet;

use cm_info;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\local\entities\cm;
use enrol_wallet\local\entities\section;
use enrol_wallet\local\coupons\coupons;
use core_availability\info;
use core_availability\info_module;
use core_availability\info_section;
use core_availability\condition as core_condition;
use enrol_wallet\form\applycoupon_form;
use enrol_wallet\local\urls\actions;
use moodle_url;
/**
 * Wallet condition.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends core_condition {
    /** @var float the cost required.*/
    protected $cost;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        $this->cost = $structure->cost;
    }

    /**
     * Create object to be saved representing this condition.
     */
    public function save() {
        return (object)['type' => 'wallet', 'cost' => $this->cost];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param float $cost The limit of views for users
     * @return stdClass Object representing condition
     */
    public static function get_json($cost = 0) {
        return (object)['type' => 'wallet', 'cost' => (float)$cost];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        global $DB;
        $context = $info->get_context();

        $cost = $this->cost;
        if (empty ($cost) || !is_numeric($cost) || $cost <= 0) {
            return true;
        }

        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $allow = $this->is_cm_available($userid, $info);
        } else {
            // Assuming section.
            $allow = $this->is_section_available($userid, $info);
        }

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Check the availability of course module.
     * @param int $userid
     * @param info_module $info
     * @return bool
     */
    private function is_cm_available($userid, $info) {
        global $DB;
        $cmid = $info->get_course_module()->id;

        $records = $DB->get_records('availability_wallet', ['userid' => $userid, 'cmid' => $cmid]);
        if (empty($records)) {
            return false;
        }
        return $this->is_payment_sufficient($records);
    }

    /**
     * Check the availability of a section.
     * @param int $userid
     * @param info_section $info
     * @return bool
     */
    private function is_section_available($userid, $info) {
        global $DB;
        $sectionid = $info->get_section()->id;
        $records = $DB->get_records('availability_wallet', ['userid' => $userid, 'sectionid' => $sectionid]);
        if (empty($records)) {
            return false;
        }
        return $this->is_payment_sufficient($records);
    }

    /**
     * Check is payments were sufficient for this thing.
     * @param array[object] $records
     */
    private function is_payment_sufficient($records) {
        $total = 0;
        foreach ($records as $record) {
            $total += $record->cost;
        }
        return $total >= $this->cost;
    }

    /**
     * Creates a fake instance to check the discount.
     * @param int $courseid
     * @param int $someid cmid or sectionid
     * @param \context $context
     * @return array
     */
    private function create_parameters($courseid, $someid, $context) {
        $params = [
            'id'           => 0,
            'cost'         => $this->cost,
            'courseid'     => $courseid,
            'contextid'    => $context->id,
            'contextlevel' => $context->contextlevel,
            'sesskey'      => sesskey(),
        ];

        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $params['cmid'] = $someid;
        } else {
            // Assuming section.
            $params['sectionid'] = $someid;
        }
        return $params;
    }
    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies).
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, info $info) {
        global $USER, $DB, $OUTPUT, $CFG, $PAGE;
        $context = $info->get_context();
        require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
        $cost = $this->cost;
        if (empty($cost) || !is_numeric($cost) || $cost <= 0) {
            return get_string('invalidcost', 'availability_wallet');
        }

        if ($info instanceof cm_info) {
            $someid = $info->get_course_module()->id;
            $helper = new cm($someid);
        } else if ($info instanceof section_info) {
            $someid = $info->get_section()->id;
            $helper = new section($someid);
        } else {
            $type = gettype($info);
            debugging("Invalid \$info parameter passed to get_description '$type' passed");
            return '';
        }
        $bal = new balance(0, $helper->get_category_id());
        $balance = $bal->get_valid_balance();

        $params = $this->create_parameters($info->get_course()->id, $someid, $context);
        $costafter = $helper->get_cost_after_discount($cost);

        $curr = get_config('enrol_wallet', 'currency');
        $curr = !empty($curr) ? $curr : '';
        $a = new \stdClass;
        if ($cost == $costafter) {
            $a->cost = $cost . ' ' . $curr;
        } else {
            $a->cost = "<del>$cost $curr</del> $costafter $curr";
        }

        $a->balance = "$balance $curr";

        // No enough balance.
        if ($costafter > $balance) {
            return get_string('insufficientbalance', 'availability_wallet', $a);
        }

        if ($this->is_available($not, $info, false, $USER->id)) {
            return get_string('already_paid', 'availability_wallet', $a);
        }
        $resettheme = false;
        property_exists($PAGE, 'context');
        $rc = new \ReflectionProperty($PAGE, '_context');
        $rc->setAccessible(true);
        if (!$rc->isInitialized($PAGE)) {
            $PAGE->set_context($context);
            $resettheme = true;
        }
        // Pay button.
        $label = get_string('paybuttonlabel', 'availability_wallet');
        $url = new moodle_url('/availability/condition/wallet/process.php', $params);

        $a->paybutton = $OUTPUT->single_button($url, $label, 'post', ['primary' => true]);

        // Coupon form.
        $data = (object)['instance' => (object)$params];
        $couponaction = actions::APPLY_COUPON->url();
        $couponform = new applycoupon_form($couponaction, $data);
        if ($couponform->is_cancelled()) {
            coupons::unset_session_coupon();
        }

        if ($submitteddata = $couponform->get_data()) {
            $couponform->process_coupon_data($submitteddata);
        }

        $a->couponform = $couponform->render();
        if ($resettheme) {
            $PAGE->reset_theme_and_output();
        }

        if ($not) {
            return get_string('eithernotdescription', 'availability_wallet', $a);
        } else {
            return get_string('eitherdescription', 'availability_wallet', $a);
        }
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return gmdate('Y-m-d H:i:s');
    }
}
