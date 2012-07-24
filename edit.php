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

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/edit_form.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course ID
$url = new moodle_url('/report/analytics/edit.php', array('id' => $id));
$reporturl = new moodle_url('/report/analytics/index.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

$strpluginname = get_string('pluginname', 'report_analytics');

$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('reports'));
$PAGE->navbar->add($strpluginname, $reporturl);
$PAGE->navbar->add(get_string('updatesettings', 'report_analytics'), $url);
$PAGE->set_title("$course->shortname: $strpluginname");
$PAGE->set_heading($course->fullname);

$indicators = get_plugin_list('analyticsindicator');
$mform = new report_analytics_edit_form(null, array('id' => $id, 'indicators' => $indicators));

$message = '';
if ($mform->is_cancelled()) {
} else if ($formdata = $mform->get_data()) {
    $message = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
    $weights = array();
    foreach (array_keys($indicators) as $indicator) {
        $key = "weighting_$indicator";
        $weights[$indicator] = isset($formdata->$key) ? $formdata->$key : 0;
    }

    //TODO: Process generic settings

    // Process thresholds and other indicator specific settings
    $configdata = array();
    foreach (array_keys($indicators) as $indicator) {
        $indicatorfile = "$CFG->dirroot/mod/analytics/indicator/$indicator/locallib.php";
        if (file_exists($indicatorfile)) {
            require_once($indicatorfile);
            $func = "analyticsindicator_{$indicator}_process_edit_form";
            $configdata[$indicator] = $func($formdata);
        }
    }
    report_analytics_update_indicator($id, $weights, $configdata);
}

// Get current values and populate form
$data = array();
if ($indicators = $DB->get_records('report_analytics', array('course' => $id))) {
    foreach ($indicators as $indicator) {
        $data["weighting_{$indicator->indicator}"] = $indicator->weight * 100;
        $configdata = unserialize(base64_decode($indicator->configdata));
        if (is_array($configdata)) {
            $data = array_merge($data, $configdata);
        }
    }
}
$mform->set_data($data);

add_to_log($course->id, 'course', 'report analytics edit', "report/analytics/edit.php?id=$id", $course->id);

echo $OUTPUT->header();
echo $message;
$mform->display();
echo $OUTPUT->footer();
