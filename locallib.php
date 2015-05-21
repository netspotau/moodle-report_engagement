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
 * Output rendering of engagement report
 *
 * @package    report_engagement
 * @copyright  2012 NetSpot Pty Ltd, 2015 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Generic settings
function report_engagement_get_generic_settings_list() {
	return array('queryspecifydatetime', 'querystartdatetime', 'queryenddatetime');
}
function report_engagement_get_generic_settings_records($courseid) {
	global $DB;
	$generic_settings = report_engagement_get_generic_settings_list();
	list($generic_settings_insql, $generic_settings_inparams) = $DB->get_in_or_equal($generic_settings, SQL_PARAMS_NAMED);
	$generic_settings_queryparams = array('courseid' => $courseid);
	$generic_settings_sql = "SELECT id, name, value FROM {report_engagement_generic} WHERE courseid = :courseid AND name $generic_settings_insql";
	$generic_settings_params = array_merge($generic_settings_inparams, $generic_settings_queryparams);
	return $DB->get_records_sql($generic_settings_sql, $generic_settings_params);
}

function report_engagement_sort_indicators($a, $b) {
    global $SESSION;
    $tsort = required_param('tsort', PARAM_ALPHANUMEXT);
    $sort = isset($SESSION->flextable['engagement-course-report']->sortby[$tsort]) ?
                $SESSION->flextable['engagement-course-report']->sortby[$tsort] : SORT_DESC;
    if ($a[$tsort] == $b[$tsort]) {
        return 0;
    }
    if ($sort != SORT_ASC) {
        return $a[$tsort] < $b[$tsort] ? -1 : 1;
    } else {
        return $a[$tsort] > $b[$tsort] ? -1 : 1;
    }
}

function report_engagement_sort_risks($a, $b) {
    global $SESSION;
    $sort = isset($SESSION->flextable['engagement-course-report']->sortby['total']) ?
                $SESSION->flextable['engagement-course-report']->sortby['total'] : SORT_DESC;
    $asum = $bsum = 0;
    foreach ($a as $name => $values) {
        $asum += $values['raw'] * $values['weight'];
    }
    foreach ($b as $name => $values) {
        $bsum += $values['raw'] * $values['weight'];
    }
    if ($asum == $bsum) {
        return 0;
    }
    if ($sort != SORT_ASC) {
        return $asum < $bsum ? -1 : 1;
    } else {
        return $asum > $bsum ? -1 : 1;
    }
}

function report_engagement_update_indicator($courseid, $new_weights, $configdata = array()) {
    global $DB;

    $weights = array();
    if ($weightrecords = $DB->get_records('report_engagement', array('course' => $courseid))) {
        foreach ($weightrecords as $record) {
            $weights[$record->indicator] = $record;
        }
    }
    foreach ($new_weights as $indicator => $weight) {
        $weight = $weight / 100;
        if (!isset($weights[$indicator])) {
            $record = new stdClass();
            $record->course = $courseid;
            $record->indicator = $indicator;
            $record->weight = $weight;
            if (isset($configdata[$indicator])) {
                $record->configdata = base64_encode(serialize($configdata[$indicator]));
            }
            $DB->insert_record('report_engagement', $record);
        } else {
            $weights[$indicator]->weight = $weight;
            if (isset($configdata[$indicator])) {
                $weights[$indicator]->configdata = base64_encode(serialize($configdata[$indicator]));
            }
            $DB->update_record('report_engagement', $weights[$indicator]);
        }
    }
}
