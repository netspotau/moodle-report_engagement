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
 * Upgrades for engagement
 *
 * @package    report_engagement
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_report_engagement_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

if ($oldversion < 2015052101) {
	// Conditionally create table for report_engagement_generic.
	if (!$dbman->table_exists('report_engagement_generic')) {
		$dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/report/engagement/db/install.xml', 'report_engagement_generic');
	}
	// Engagement savepoint reached.
	upgrade_plugin_savepoint(true, 2015052101, 'report', 'engagement');
}
	
    return true;
}
