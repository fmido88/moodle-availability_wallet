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
 * Front-end class.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_wallet;

/**
 * Front-end class.
 *
 * @package availability_wallet
 * @copyright 2023 Mohammad Farouk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * This plugin was tested with several core plugins and relies on events which has crud = "r".
     *
     * There are issues with the Wiki, Label and Book modules.
     * For details see: https://github.com/danielneis/moodle-availability_wallet/issues/2
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        $allow = true;

        if (!empty($cm)) {
            $info = new \core_availability\info_module($cm);
        } else if (!empty($section)) {
            $info = new \core_availability\info_section($section);
        }

        // Check if there is previously wallet instance.
        if (!empty($info)) {
            try {
                if ($tree = $info->get_availability_tree()) {
                    $wallet = $tree->get_all_children('availability_wallet\condition');
                    if (!empty($wallet)) {
                        $allow = false;
                    } else {
                        $allow = true;
                    }
                } else {
                    $allow = true;
                }
            } catch (\coding_exception $c) {
                $allow = true;
            }
        }

        return $allow;
    }

    /**
     * Gets a list of string identifiers (in the plugin's language file) that
     * are required in JavaScript for this plugin. The default returns nothing.
     *
     * You do not need to include the 'title' string (which is used by core) as
     * this is automatically added.
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return ['ajaxerror', 'fieldlabel'];
    }
}
