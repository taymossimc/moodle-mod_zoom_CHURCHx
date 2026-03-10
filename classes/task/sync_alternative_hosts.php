<?php
// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
 * Task: sync_alternative_hosts
 *
 * Scheduled task to sync course instructors as alternative hosts for all Zoom meetings.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/zoomyt/lib.php');
require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');

use core\task\scheduled_task;
use moodle_exception;

/**
 * Scheduled task to sync course instructors as alternative hosts.
 */
class sync_alternative_hosts extends scheduled_task {
    /**
     * Returns name of task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sync_alternative_hosts', 'mod_zoomyt');
    }

    /**
     * Syncs course instructors as alternative hosts for all Zoom meetings.
     *
     * @return boolean
     */
    public function execute() {
        global $DB;

        // Check if the feature is enabled.
        $config = get_config('zoomyt');
        if (empty($config->autoaddinstructorsashosts)) {
            mtrace('Auto-add instructors as alternative hosts is disabled. Skipping task.');
            return true;
        }

        try {
            $service = zoomyt_webservice();
        } catch (moodle_exception $exception) {
            mtrace('Skipping task - ' . $exception->getMessage());
            return false;
        }

        mtrace('Starting to sync alternative hosts for Zoom meetings...');

        // Get all active Zoom meetings.
        $zoommeetings = $DB->get_records('zoomyt', ['exists_on_zoom' => ZOOM_MEETING_EXISTS]);
        $updatedcount = 0;
        $skippedcount = 0;
        $errorcount = 0;

        foreach ($zoommeetings as $zoom) {
            mtrace("Processing Zoom meeting: {$zoom->name} (ID: {$zoom->meeting_id})");

            try {
                // Get instructor emails for this course.
                $instructoremails = zoomyt_get_course_instructor_emails($zoom->course);

                if (empty($instructoremails)) {
                    mtrace("  => No instructors found for course {$zoom->course}, skipping.");
                    $skippedcount++;
                    continue;
                }

                // Get the host email to exclude.
                $hostemail = null;
                if (!empty($zoom->host_id)) {
                    try {
                        $hostuser = zoomyt_get_user($zoom->host_id);
                        $hostemail = $hostuser->email ?? null;
                    } catch (moodle_exception $e) {
                        // Ignore if we can't get the host user.
                        mtrace("  => Warning: Could not retrieve host user info.");
                    }
                }

                // Merge with existing alternative hosts (validates against Zoom, creates Basic users if needed).
                $existinghosts = $zoom->alternative_hosts ?? '';
                $newhosts = zoomyt_merge_alternative_hosts($existinghosts, $instructoremails, $hostemail, $zoom->course);

                // Check if anything changed.
                if ($newhosts === $existinghosts) {
                    mtrace("  => No changes needed, alternative hosts already in sync.");
                    $skippedcount++;
                    continue;
                }

                mtrace("  => Updating alternative hosts: {$newhosts}");

                // Update only alternative hosts on Zoom (targeted PATCH).
                $service->update_meeting_hosts($zoom->meeting_id, $zoom->webinar ?? false, $newhosts);

                // Update in database.
                $DB->set_field('zoomyt', 'alternative_hosts', $newhosts, ['id' => $zoom->id]);

                mtrace("  => Successfully updated alternative hosts.");
                $updatedcount++;

            } catch (moodle_exception $e) {
                mtrace("  !! Error updating meeting {$zoom->meeting_id}: " . $e->getMessage());
                $errorcount++;
            }
        }

        mtrace("Finished syncing alternative hosts.");
        mtrace("  Updated: {$updatedcount}");
        mtrace("  Skipped: {$skippedcount}");
        mtrace("  Errors: {$errorcount}");

        return true;
    }
}
