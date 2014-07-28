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
 * @package    report
 * @subpackage analtyics
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/engagement/locallib.php');
require_once($CFG->dirroot . '/mod/engagement/indicator/rendererbase.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Rendering methods for the engagement reports
 */
class report_engagement_renderer extends plugin_renderer_base {

    /**
     * course_report
     *
     * @param mixed $indicators
     * @param mixed $data
     * @access public
     * @return void
     */
    public function course_report($indicators, $data) {
        global $DB, $COURSE;
        if (empty($data)) {
            return '';
        }

        $table = new flexible_table('engagement-course-report');
        $table->define_baseurl(new moodle_url('/report/engagement/index.php', array('id' => $COURSE->id)));
        $headers = array();
        $columns = array();
        $headers[] = get_string('username');
        $columns[] = 'username';
        foreach ($indicators as $indicator) {
            $headers[] = get_string('pluginname', "engagementindicator_$indicator");
            $columns[] = "indicator_$indicator";
        }
        $headers[] = get_string('total');
        $columns[] = 'total';
        $table->define_headers($headers);
        $table->define_columns($columns);

        $table->sortable(true, 'total', SORT_DESC);
        $table->no_sorting('username');

        $table->column_class('username', 'student');
        foreach ($indicators as $indicator) {
            $table->column_class("indicator_$indicator", 'indicator');
        }
        $table->column_class('total', 'total');

        $table->set_attribute('id', 'engagement-course-report');
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->setup();

        foreach ($data as $user => $ind_data) {
            $row = array();

            $displayname = fullname($DB->get_record('user', array('id' => $user)));

            $url = new moodle_url('/report/engagement/index.php', array('id' => $COURSE->id, 'userid' => $user));
            $row[] = html_writer::link($url, $displayname);
            $total = 0;
            $total_raw = 0;
            foreach ($indicators as $indicator) {
                if (isset($ind_data["indicator_$indicator"]['raw'])) {
                    $ind_value = $ind_data["indicator_$indicator"]['raw'];
                    $weight = $ind_data["indicator_$indicator"]['weight'];
                } else {
                    $ind_value = 0;
                    $weight = 0;
                }
                $weighted_value = sprintf("%.0f%%", $ind_value * $weight * 100);
                $raw_value = sprintf("%.0f%%", 100 * $ind_value);
                $row[] = $weighted_value . " ($raw_value)";
                $total += $ind_value * $weight;
                $total_raw += $ind_value;
            }
            $row[] = sprintf("%.0f%%", $total * 100);
            $table->add_data($row);
        }

        $html = $this->output->notification(get_string('reportdescription', 'report_engagement'));
        ob_start();
        $table->finish_output();
        $html .= ob_get_clean();
        return $html;
    }

    /**
     * Renders indicator manager
     *
     * @return string HTML
     */
    public function display_indicator_list(core_plugin_manager $pluginman, $instances) {
        if (empty($instances)) {
            return '';
        }

        $table = new html_table();
        $table->id = 'plugins-control-panel';
        $table->attributes['class'] = 'generaltable generalbox boxaligncenter boxwidthwide';
        $table->head = array(
            get_string('displayname', 'core_plugin'),
            get_string('source', 'core_plugin'),
            get_string('version', 'core_plugin'),
            get_string('availability', 'core_plugin'),
            get_string('settings', 'core_plugin'),
        );
        $table->colclasses = array(
            'displayname', 'source', 'version', 'availability', 'settings',
        );

        foreach ($instances as $name => $path) {
            $plugin = $pluginman->get_plugin_info('engagementindicator_'.$name);

            $row = new html_table_row();
            $row->attributes['class'] = 'type-' . $plugin->type . ' name-' . $plugin->type . '_' . $plugin->name;

            if ($this->page->theme->resolve_image_location('icon', $plugin->type . '_' . $plugin->name)) {
                $icon = $this->output->pix_icon('icon', '', $plugin->type . '_' . $plugin->name,
                            array('class' => 'smallicon pluginicon'));
            } else {
                $icon = $this->output->pix_icon('spacer', '', 'moodle', array('class' => 'smallicon pluginicon noicon'));
            }
            if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                $msg = html_writer::tag('span', get_string('status_missing', 'core_plugin'), array('class' => 'notifyproblem'));
                $row->attributes['class'] .= ' missingfromdisk';
            } else {
                $msg = '';
            }
            $displayname  = $icon . ' ' . $plugin->displayname . ' ' . $msg;
            $displayname = new html_table_cell($displayname);

            if (report_engagement_is_core_indicator($name)) {
                $row->attributes['class'] .= ' standard';
                $source = new html_table_cell(get_string('sourcestd', 'core_plugin'));
            } else {
                $row->attributes['class'] .= ' extension';
                $source = new html_table_cell(get_string('sourceext', 'core_plugin'));
            }

            $version = new html_table_cell($plugin->versiondb);

            $isenabled = $plugin->is_enabled();
            if (is_null($isenabled)) {
                $availability = new html_table_cell('');
            } else if ($isenabled) {
                $row->attributes['class'] .= ' enabled';
                $icon = $this->output->pix_icon('i/hide', get_string('pluginenabled', 'core_plugin'));
                $availability = new html_table_cell($icon . ' ' . get_string('pluginenabled', 'core_plugin'));
            } else {
                $row->attributes['class'] .= ' disabled';
                $icon = $this->output->pix_icon('i/show', get_string('plugindisabled', 'core_plugin'));
                $availability = new html_table_cell($icon . ' ' . get_string('plugindisabled', 'core_plugin'));
            }

            $settingsurl = $plugin->get_settings_url();
            if (is_null($settingsurl)) {
                $settings = new html_table_cell('');
            } else {
                $settings = html_writer::link($settingsurl, get_string('settings', 'core_plugin'));
                $settings = new html_table_cell($settings);
            }

            $row->cells = array(
                $displayname, $source, $version, $availability, $settings
            );
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    public function user_report($indicators, $data) {
        global $CFG;
        $html = html_writer::start_tag('div', array('id' => 'report-engagement_userreport'));
        foreach ($indicators as $indicator) {
            require_once("$CFG->dirroot/mod/engagement/indicator/$indicator/renderer.php");
            $renderer = $this->page->get_renderer("engagementindicator_$indicator");
            $html .= $this->output->heading(get_string('pluginname', "engagementindicator_$indicator"), 1, 'userreport_heading');
            $html .= $renderer->user_report($data["indicator_$indicator"]);
        }
        $html .= html_writer::end_tag('div');
        return $html;
    }
}
