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
 * This file is used to manage analytics indicators
 *
 * @package    report
 * @subpackage analytics
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

$contextid = optional_param('contextid', 0, PARAM_INT);

$url = new moodle_url('/report/analytics/manage_indicators.php', array('contextid' => $contextid));

$context = context::instance_by_id($contextid);

require_capability('report/analytics:manage', $context);

$PAGE->set_url($url);
$PAGE->set_context($context);
if ($context->contextlevel == CONTEXT_COURSE) {
    $PAGE->set_pagelayout('incourse');
} else {
    //if at sitelevel, setup adminexternalpage?
    $PAGE->set_pagelayout('admin');
}

/// Security: make sure we're allowed to do this operation
if ($context->contextlevel == CONTEXT_COURSE) {
    $course = $DB->get_record('course', array('id' => $context->instanceid), '*', MUST_EXIST);
    require_login($course, false);
} else if ($context->contextlevel == CONTEXT_SYSTEM) {
    require_login();
} else {
    print_error('invalidcontext');
}

$PAGE->navbar->add(get_string('pluginname', 'report_analytics'));
$PAGE->navbar->add(get_string('manageindicators', 'report_analytics'), $url);

/// Display page header
$PAGE->set_title(get_string('pluginname', 'report_analytics'));
$PAGE->set_heading(get_string('pluginname', 'report_analytics'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageindicators', 'report_analytics'));

$renderer = $PAGE->get_renderer('report_analytics');
if (false) {
} else {
    //TODO: Fetching indicator data will need to obey heirarchy in future
    //      Need a table for course specific settings...
    $pluginman = plugin_manager::instance();
    $instances = get_plugin_list('analyticsindicator');
    echo $renderer->display_indicator_list($pluginman, $instances);
}

echo $OUTPUT->footer();
