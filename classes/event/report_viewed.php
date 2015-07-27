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
 * The report_engagement report viewed event.
 *
 * @package    report_engagement
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_engagement\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The report_engagement report viewed event class.
 *
 * @package    report_engagement
 * @since      Moodle 2.7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreportviewed', 'report_engagement');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if (!isset($this->other['foruser'])) {
            return "The user with id '$this->userid' viewed the engagement anayltics report for the course with id '$this->courseid'.";
        } else {
            return "The user with id '$this->userid' viewed the engagement analytics report for the course with id '$this->courseid' for user with id '{$this->other['foruser']}'.";
        }
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        if (!isset($this->other['foruser'])) {
            return new \moodle_url('/report/engagement/index.php', array('id' => $this->courseid));
        } else {
            return new \moodle_url('/report/engagement/index.php', array('id' => $this->courseid, 'userid' => $this->other['foruser']));
        }
    }

    /**
     * custom validations.
     *
     * @throws \coding_exception when validation fails.
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if ($this->contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('Context level must be CONTEXT_COURSE.');
        }
    }
}
