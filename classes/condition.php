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

use enrol_wallet\transactions;
/**
 * Wallet condition.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
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
     * @param \core_availability\info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;
        $context = $info->get_context();

        $cost = $this->cost;
        if (empty ($cost) || !is_numeric($cost) || $cost <= 0) {
            return true;
        }

        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $allow = $this->is_cm_available($userid, $info->get_course_module()->id);
        } else {
            // Assuming section.
            $allow = $this->is_section_available($userid, $info->get_section()->id);
        }

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Check the availability of course module.
     * @param int $userid
     * @param int $cmid
     * @return bool
     */
    private function is_cm_available($userid, $cmid) {
        global $DB;
        return $DB->record_exists('availability_wallet', ['userid' => $userid, 'cmid' => $cmid]);
    }

    /**
     * Check the availability of a section.
     * @param int $userid
     * @param int $sectionid
     * @return bool
     */
    private function is_section_available($userid, $sectionid) {
        global $DB;
        return $DB->record_exists('availability_wallet', ['userid' => $userid, 'sectionid' => $sectionid]);
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies).
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param \core_availability\info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $USER, $DB, $OUTPUT, $CFG;
        require_once($CFG->dirroot.'/enrol/wallet/locallib.php');
        $cost = $this->cost;
        if (empty($cost) || !is_numeric($cost) || $cost <= 0) {
            return get_string('invalidcost', 'availability_wallet');
        }

        $balance = transactions::get_user_balance($USER->id);
        $context = $info->get_context();

        $params = [
            'cost'         => $cost,
            'courseid'     => $info->get_course()->id,
            'contextid'    => $context->id,
            'contextlevel' => $context->contextlevel,
        ];

        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $params['cmid'] = $info->get_course_module()->id;
        } else {
            // Assuming section.
            $params['sectionid'] = $info->get_section()->id;
        }

        $wallet = enrol_get_plugin('wallet');
        $coupon = $wallet->check_discount_coupon();
        $costafter = $wallet->get_cost_after_discount($USER->id, (object)$params, $coupon);

        $curr = get_config('enrol_wallet', 'currency');
        $curr = !empty($curr) ? $curr : '';
        $a = new \stdClass;
        if ($cost == $costafter) {
            $a->cost = $cost . ' ' . $curr;
        } else {
            $a->cost = "<del>$cost $curr</del> $costafter $curr";
            $params['cost'] = $costafter;
        }

        $a->balance = "$balance $curr";

        // No enough balance.
        if ($costafter > $balance) {
            return get_string('insufficientbalance', 'availability_wallet', $a);
        }

        // Pay button.
        $label = get_string('paybuttonlabel', 'availability_wallet');
        $url = new \moodle_url('/availability/condition/wallet/process.php', $params);
        $a->paybutton = $OUTPUT->single_button($url, $label, 'post', ['primary' => true]);

        // Coupon form.
        $actionurl = new \moodle_url('/enrol/wallet/extra/action.php');
        $data = (object)['instance' => (object)$params];
        $couponform = new \enrol_wallet\form\applycoupon_form(null, $data);
        if ($couponform->is_cancelled()) {
            $_SESSION['coupon'] = '';
            unset($coupon);
        }
        if ($submitteddata = $couponform->get_data()) {
            enrol_wallet_process_coupon_data($submitteddata);
        }
        ob_start();
        $couponform->display();
        $a->couponform = ob_get_clean();

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
