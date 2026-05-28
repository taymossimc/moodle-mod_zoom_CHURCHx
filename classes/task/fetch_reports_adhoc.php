<?php
// This file is part of the Zoom YT plugin for Moodle - http://moodle.org/
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
 * Ad-hoc task that fetches Zoom meeting participant reports on demand.
 *
 * Queued by the webhook handler when a meeting ends, so attendance data is
 * refreshed promptly instead of waiting for the scheduled task.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc wrapper around the get_meeting_reports scheduled task.
 */
class fetch_reports_adhoc extends \core\task\adhoc_task {

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_fetch_reports_adhoc', 'mod_zoomyt');
    }

    /**
     * Run the meeting report fetch.
     *
     * The underlying scheduled task pulls reports for all recently ended
     * meetings since the last run, which covers the meeting that triggered
     * the webhook.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/mod/zoomyt/classes/task/get_meeting_reports.php');

        $customdata = $this->get_custom_data();
        if (!empty($customdata->instance_id)) {
            mtrace('Ad-hoc report fetch triggered for instance: ' . (int)$customdata->instance_id);
        }

        $task = new get_meeting_reports();
        $task->execute();
    }
}
