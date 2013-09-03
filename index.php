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
 * @package    report_engagement
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/report/engagement/locallib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$userid = optional_param('userid', 0, PARAM_INT);

$pageparams = array('id' => $id);
if ($userid) {
    $pageparams['userid'] = $userid;
}

$PAGE->set_url('/report/engagement/index.php', $pageparams);
$PAGE->set_pagelayout('report');

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid), 'id, firstname, lastname, email', MUST_EXIST);
    $PAGE->navbar->add(fullname($user), new moodle_url('/report/engagement/index.php', $pageparams));
}

require_login($course);
$context = context_course::instance($course->id);
$PAGE->set_context($context);
$updateurl = new moodle_url('/report/engagement/edit.php', array('id' => $id));
$PAGE->set_button($OUTPUT->single_button($updateurl, get_string('updatesettings', 'report_engagement'), 'get'));
$PAGE->set_heading($course->fullname);

require_capability('report/engagement:view', $context);

if (!$userid) {
    add_to_log($course->id, "course", "report engagement", "report/engagement/index.php?id=$course->id", $course->id);
} else {
    add_to_log($course->id, "course", "report engagement",
        "report/engagement/index.php?id=$course->id&userid=$user->id", $course->id);
}

$stradministration = get_string('administration');
$strreports = get_string('reports');
$renderer = $PAGE->get_renderer('report_engagement');

echo $OUTPUT->header();

$heading = $userid ? 'userreport' : 'coursereport';
$info = new stdClass();
$info->course = $course->shortname;
if (isset($user)) {
    $info->user = fullname($user);
}
echo $OUTPUT->heading(get_string($heading, 'report_engagement', $info));

$pluginman = plugin_manager::instance();
$indicators = get_plugin_list('engagementindicator');
foreach ($indicators as $name => $path) {
    $plugin = $pluginman->get_plugin_info('engagementindicator_'.$name);
    if (!$plugin->is_enabled()) {
        unset($indicators[$name]);
    }
}

$weightings = $DB->get_records_menu('report_engagement', array('course' => $id), '', 'indicator, weight');

$data = array();
if (!$userid) { // Course report.
    foreach ($indicators as $name => $path) {
        if (file_exists("$path/indicator.class.php")) {
            require_once("$path/indicator.class.php");
            $classname = "indicator_$name";
            $indicator = new $classname($id);
            $indicatorrisks = $indicator->get_course_risks();
            $weight = isset($weightings[$name]) ? $weightings[$name] : 0;
            $total = 0;
            foreach ($indicatorrisks as $_user => $risk) {
                $data[$_user]["indicator_$name"]['raw'] = $risk->risk;
                $data[$_user]["indicator_$name"]['weight'] = $weight;
            }
        }
    }

    $tsort = optional_param('tsort', '', PARAM_ALPHANUMEXT);
    if ($tsort && isset($SESSION->flextable['engagement-course-report'])) {
        $settings = $SESSION->flextable['engagement-course-report'];
        if ($tsort == 'total') {
            uasort($data, 'report_engagement_sort_risks');
        } else if (preg_match('/^indicator_(.*)/', $tsort, $matches)) {
            uasort($data, 'report_engagement_sort_indicators');
        }
    } else {
        uasort($data, 'report_engagement_sort_risks');
    }

    echo $renderer->course_report(array_keys($indicators), $data);
} else { // User report.
    foreach ($indicators as $name => $path) {
        if (file_exists("$path/indicator.class.php")) {
            require_once("$path/indicator.class.php");
            $classname = "indicator_$name";
            $indicator = new $classname($id);
            $indicatorrisks = $indicator->get_risk($userid, $id);
            $weight = isset($weightings[$name]) ? $weightings[$name] : 0;
            foreach ($indicatorrisks as $risk) {
                $data["indicator_$name"] = $risk;
            }
        }
    }
    echo $renderer->user_report(array_keys($indicators), $data);
}

echo $OUTPUT->footer();
