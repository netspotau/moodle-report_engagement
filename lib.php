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
 * Libs, public API.
 *
 * @package    report_engagement
 * @author     Adam Olley <adam.olley@netspot.com.au>
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Since 2.4 we need to have the plugin class declared early.
require_once($CFG->dirroot . '/report/engagement/locallib.php');

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_engagement_extend_navigation_course($navigation, $course, $context) {
    if ($course->id != SITEID && has_capability('report/engagement:view', $context)) {
        $url = new moodle_url('/report/engagement/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_engagement'), $url,
                         navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

function report_engagement_get_course_summary($courseid) {
    global $CFG, $DB;

    $risks = array();

    // TODO: We want this to rely on enabled indicators in the course...
    $pluginman = core_plugin_manager::instance();
    $instances = get_plugin_list('engagementindicator');
    $weightings = $DB->get_records_menu('report_engagement', array('course' => $courseid), '', 'indicator, weight');
    if (!$weightings && count($instances)) {
        // Setup default weightings, all equal.
        $weight = sprintf('%.2f', 1 / count($instances));
        foreach ($instances as $name => $path) {
            $record = new stdClass();
            $record->course = $courseid;
            $record->indicator = $name;
            $record->weight = $weight;
            $record->configdata = null;
            $wid = $DB->insert_record('report_engagement', $record);
            $weightings[$name] = $weight;
        }
    }
    foreach ($instances as $name => $path) {
        $plugin = $pluginman->get_plugin_info('engagementindicator_'.$name);
        if ($plugin->is_enabled() && file_exists("$path/indicator.class.php")) {
            require_once("$path/indicator.class.php");
            $classname = "indicator_$name";
            $indicator = new $classname($courseid);
            $indicatorrisks = $indicator->get_course_risks();
            $weight = isset($weightings[$name]) ? $weightings[$name] : 0;
            foreach ($indicatorrisks as $userid => $risk) {
                if (!isset($risks[$userid])) {
                    $risks[$userid] = 0;
                }
                $risks[$userid] += $risk->risk * $weight;
            }
        }
    }
    return $risks;
}

/**
 * report_engagement_get_risk_level
 *
 * @param mixed $risk
 * @access public
 * @return array    array of values for which different risk levels take effect
 */
function report_engagement_get_risk_level($risk) {
    global $DB;
    // TODO: accept some instance of an overall record for the course...
    return $risk == 0 ? 0 : ceil($risk * 100 / 20) - 1;
}

/**
 * Is an indicator an engagement core supported indicator?
 *
 * @param string $indicator the indicator shortname
 * @access public
 * @return bool true if a core indicator, otherwise false
 */
function report_engagement_is_core_indicator($indicator) {
    $core = array('login', 'assessment', 'forum');
    $core = array_flip($core);
    return isset($core[$indicator]);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_engagement_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                         => get_string('page-x', 'pagetype'),
        'report-*'                  => get_string('page-report-x', 'pagetype'),
        'report-engagement-*'        => get_string('page-report-engagement-x',  'report_engagement'),
        'report-engagement-index'    => get_string('page-report-engagement-index',  'report_engagement'),
        'report-engagement-course'   => get_string('page-report-engagement-user',  'report_engagement'),
        'report-engagement-user'     => get_string('page-report-engagement-user',  'report_engagement'),
    );
    return $array;
}

function report_engagement_cron() {
    global $DB;

    $cachettl = get_config('engagement', 'cachettl');
    if (!$cachettl) {
        // Default to 5 mins if not configured.
        $cachettl = 300;
    }

    $now = time();
    $expirytime = $now - $cachettl;

    // Delete all cache records older than $expirytime.
    $DB->delete_records_select('engagement_cache', "timemodified < $expirytime");

    return true;
}
