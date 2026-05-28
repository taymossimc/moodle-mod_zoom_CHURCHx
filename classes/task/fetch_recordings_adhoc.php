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
 * Ad-hoc task that fetches Zoom recordings for a single activity on demand.
 *
 * Queued by the webhook handler when Zoom reports a recording is ready, so the
 * recording is pulled immediately instead of waiting for the scheduled task.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc wrapper around the get_meeting_recordings scheduled task.
 */
class fetch_recordings_adhoc extends \core\task\adhoc_task {

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_fetch_recordings_adhoc', 'mod_zoomyt');
    }

    /**
     * Run the recording fetch for the instance supplied in custom data.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/mod/zoomyt/classes/task/get_meeting_recordings.php');

        $customdata = $this->get_custom_data();
        $instanceid = !empty($customdata->instance_id) ? (int)$customdata->instance_id : null;

        if ($instanceid !== null) {
            mtrace('Ad-hoc recording fetch for instance: ' . $instanceid);
        } else {
            mtrace('Ad-hoc recording fetch for all instances.');
        }

        $task = new get_meeting_recordings();
        $task->execute_for_instance($instanceid);
    }
}
