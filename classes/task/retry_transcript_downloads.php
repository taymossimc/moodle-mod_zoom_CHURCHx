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
 * Task: retry_transcript_downloads
 *
 * Scheduled task to retry downloading transcripts from YouTube for videos
 * where the initial attempt found no captions (YouTube may still have been processing).
 *
 * Uses an increasing backoff strategy and a maximum retry count to avoid
 * hammering YouTube indefinitely.
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

/**
 * Scheduled task to retry transcript downloads for uploaded YouTube videos.
 */
class retry_transcript_downloads extends scheduled_task {

    /**
     * Maximum number of retry attempts before giving up.
     * At ~3 hour intervals, 12 retries covers ~36 hours after upload,
     * which is more than enough for YouTube to process captions.
     */
    const MAX_RETRIES = 12;

    /**
     * Minimum age of a video (in seconds) before we attempt transcript download.
     * YouTube typically needs 15-60 minutes to generate auto-captions.
     * We wait at least 1 hour.
     */
    const MIN_AGE_SECONDS = 3600;

    /**
     * Minimum time (in seconds) between retry attempts for the same video.
     * We use an increasing backoff: base interval * (retry_count + 1),
     * capped at 6 hours.
     */
    const BASE_INTERVAL_SECONDS = 3600; // 1 hour base.

    /**
     * Maximum interval between retries (6 hours).
     */
    const MAX_INTERVAL_SECONDS = 21600;

    /**
     * Maximum videos to process per run to avoid overloading.
     */
    const BATCH_SIZE = 20;

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_retry_transcript_downloads', 'mod_zoomyt');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting transcript download retry task...');

        $now = time();

        // Find uploaded videos where:
        // - transcript_downloaded = 0 (not yet successfully downloaded)
        // - youtube_video_id is not null (video is on YouTube)
        // - status = 'uploaded'
        // - transcript_retry_count < MAX_RETRIES
        // - video is at least MIN_AGE_SECONDS old (give YouTube time to process)
        // - enough time has passed since last attempt (backoff)
        $sql = "SELECT zyv.*, z.course
                FROM {zoomyt_videos} zyv
                JOIN {zoomyt} z ON z.id = zyv.zoomid
                WHERE zyv.status = :status
                  AND zyv.transcript_downloaded = 0
                  AND zyv.youtube_video_id IS NOT NULL
                  AND zyv.transcript_retry_count < :maxretries
                  AND zyv.timecreated < :minage
                ORDER BY zyv.transcript_retry_count ASC, zyv.timecreated ASC";

        $params = [
            'status' => 'uploaded',
            'maxretries' => self::MAX_RETRIES,
            'minage' => $now - self::MIN_AGE_SECONDS,
        ];

        $videos = $DB->get_records_sql($sql, $params, 0, self::BATCH_SIZE);

        if (empty($videos)) {
            mtrace('No videos need transcript retry.');
            return;
        }

        mtrace('Found ' . count($videos) . ' candidate videos for transcript retry.');

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($videos as $video) {
            // Calculate the backoff interval for this video.
            $retrycount = (int)$video->transcript_retry_count;
            $lastattempt = (int)$video->transcript_last_attempt;

            // Increasing backoff: base * (retry_count + 1), capped at MAX_INTERVAL.
            $requiredinterval = min(
                self::BASE_INTERVAL_SECONDS * ($retrycount + 1),
                self::MAX_INTERVAL_SECONDS
            );

            // Skip if not enough time has passed since last attempt.
            if ($lastattempt > 0 && ($now - $lastattempt) < $requiredinterval) {
                $skipped++;
                continue;
            }

            mtrace('  Attempting transcript download for video ' . $video->id .
                   ' "' . $video->title . '" (attempt ' . ($retrycount + 1) . '/' . self::MAX_RETRIES . ')');

            try {
                // Get the course module for this zoom activity.
                $cm = get_coursemodule_from_instance('zoomyt', $video->zoomid, $video->course);
                if (!$cm) {
                    mtrace('    Skipping: course module not found.');
                    $this->record_attempt($video->id, $retrycount);
                    $failed++;
                    continue;
                }

                // Get YouTube service for this activity.
                $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($video->zoomid);
                if (!$ytservice || !$ytservice->is_configured()) {
                    mtrace('    Skipping: YouTube service not configured for activity.');
                    $this->record_attempt($video->id, $retrycount);
                    $failed++;
                    continue;
                }

                // Attempt to download transcripts.
                $result = $ytservice->download_and_store_transcripts($video->id, $cm->id);

                // Record the attempt regardless of outcome.
                $this->record_attempt($video->id, $retrycount);

                if ($result) {
                    mtrace('    SUCCESS: Downloaded transcripts for "' . $video->title . '"');
                    $downloaded++;
                } else {
                    $remaining = self::MAX_RETRIES - $retrycount - 1;
                    mtrace('    No transcripts available yet. ' . $remaining . ' retries remaining.');
                    $failed++;
                }
            } catch (\Exception $e) {
                mtrace('    ERROR: ' . $e->getMessage());
                $this->record_attempt($video->id, $retrycount);
                $failed++;
            }
        }

        mtrace('Transcript retry task complete: ' . $downloaded . ' downloaded, ' .
               $failed . ' still pending, ' . $skipped . ' skipped (backoff).');
    }

    /**
     * Record a transcript download attempt for a video.
     *
     * @param int $videoid The video ID.
     * @param int $currentretrycount The current retry count (before increment).
     */
    protected function record_attempt(int $videoid, int $currentretrycount): void {
        global $DB;

        $DB->execute(
            "UPDATE {zoomyt_videos}
                SET transcript_retry_count = :retrycount,
                    transcript_last_attempt = :lastattempt
              WHERE id = :id",
            [
                'retrycount' => $currentretrycount + 1,
                'lastattempt' => time(),
                'id' => $videoid,
            ]
        );
    }
}
