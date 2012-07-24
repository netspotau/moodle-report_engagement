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
 * Displays indicator reports for a chosen course
 *
 * @package    report_analytics
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class report_analytics_edit_form extends moodleform {

    protected function definition() {
        global $CFG, $OUTPUT;

        $mform =& $this->_form;
        $indicators = $this->_customdata['indicators'];

        $mform->addElement('hidden', 'id', $this->_customdata['id']);

        //TODO: general course-level report settings
        $mform->addElement('header', 'general', get_string('pluginname', 'report_analytics'));

        $mform->addElement('header', 'weightings', get_string('weighting', 'report_analytics'));
        $mform->addElement('static', 'weightings_desc', get_string('indicator', 'report_analytics'));
        foreach ($indicators as $name => $path) {
            $grouparray = array();
            $grouparray[] =& $mform->createElement('text', "weighting_$name", '', array('size' => 3));
            $grouparray[] =& $mform->createElement('static', '', '', '%');
            $mform->addGroup($grouparray, "weight_group_$name", get_string('pluginname', "analyticsindicator_$name"), '&nbsp;', false);
        }

        $pluginman = plugin_manager::instance();
        $instances = get_plugin_list('analyticsindicator');
        foreach ($indicators as $name => $path) {
            $plugin = $pluginman->get_plugin_info('analyticsindicator_'.$name);
            $file = "$CFG->dirroot/mod/analytics/indicator/$name/thresholds_form.php";
            if (file_exists($file) && $plugin->is_enabled()) {
                require_once($file);
                $class = "analyticsindicator_{$name}_thresholds_form";
                $subform = new $class();
                $mform->addElement('header', 'general', get_string('pluginname', "analyticsindicator_$name"));
                $subform->definition_inner($mform);
            }
        }

        $this->add_action_buttons();
    }

    // form verification
    function validation($data) {
        $mform =& $this->_form;

        $errors = array();
        $indicators = get_plugin_list('analyticsindicator');
        $sum = 0;
        foreach ($indicators as $indicator => $path) {
            $key = "weighting_$indicator";
            if (isset($data[$key]) && (!is_numeric($data[$key]) || $data[$key] > 100 || $data[$key] < 0)) {
                $errors["weight_group_$indicator"] = get_string('weightingmustbenumeric', 'report_analytics');
                continue;
            }
            if (isset($data[$key])) {
                $sum += $data[$key];
            }
        }

        if ($sum != 100) {
            $errors['weightings_desc'] = get_string('weightingsumtoonehundred', 'report_analytics');
        }

        return $errors;
    }
}
