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
 * Upgrades for analytics
 *
 * @package    report_analytics
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_report_analytics_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012061400) {
        if (!$dbman->table_exists('report_analytics_weighting')) {
            $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/report/analytics/db/install.xml', 'report_analytics_weighting');
        }
        upgrade_plugin_savepoint(true, 2012061400, 'report', 'analytics');
    }

    if ($oldversion < 2012062000) {
        if ($dbman->table_exists('report_analytics_weighting')) {
            $table = new xmldb_table('report_analytics_weighting');
            $field = new xmldb_field('configdata', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'weight');
            $dbman->add_field($table, $field);
            $dbman->rename_table($table, 'report_analytics');
        }
        upgrade_plugin_savepoint(true, 2012062000, 'report', 'analytics');
    }
}
