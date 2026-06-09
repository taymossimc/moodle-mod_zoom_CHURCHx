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
 * Scheduled task to sync Zoom cloud recordings to YouTube.
 *
 * @package    mod_zoomyt
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');

/**
 * Sync Zoom recordings to YouTube task.
 */
class sync_recordings_to_youtube extends \core\task\scheduled_task {

    /** @var int Maximum videos to process per run */
    const MAX_VIDEOS_PER_RUN = 5;

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_recordings_youtube', 'zoomyt');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        // get_custom_data() is only available on adhoc_task, not scheduled_task.
        // This class is registered as a scheduled task (db/tasks.php), so guard the
        // call to avoid a fatal "undefined method" error on PHP 8 during cron runs.
        $customdata = null;
        if (method_exists($this, 'get_custom_data')) {
            $customdata = $this->get_custom_data();
        }

        if (!empty($customdata->instance_id)) {
            mtrace('YouTube sync triggered by webhook for instance: ' . $customdata->instance_id);
            $this->execute_for_instance((int)$customdata->instance_id);
        } else {
            $this->execute_for_instance(null);
        }
    }

    /**
     * Execute YouTube sync for a specific instance or all instances.
     *
     * @param int|null $instanceid Specific zoom instance ID, or null for all.
     */
    public function execute_for_instance(?int $instanceid = null) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');
        require_once($CFG->dirroot . '/mod/zoomyt/classes/category_settings.php');

        mtrace('Starting Zoom to YouTube sync task...');

        // Get temp directory and check space.
        $tempdir = $this->get_temp_directory();
        if (!$tempdir) {
            mtrace('ERROR: Temporary directory not available or not writable.');
            return;
        }

        $maxspace = get_config('zoomyt', 'temp_storage_limit') ?: 5368709120; // 5GB default.
        $availablespace = disk_free_space($tempdir);

        if ($availablespace < 1073741824) { // Less than 1GB.
            mtrace('WARNING: Less than 1GB available in temp directory. Skipping sync.');
            return;
        }

        // Find recordings that need to be synced.
        $recordings = $this->get_pending_recordings($instanceid);
        mtrace('Found ' . count($recordings) . ' recordings to process.');

        $processed = 0;
        foreach ($recordings as $recording) {
            if ($processed >= self::MAX_VIDEOS_PER_RUN) {
                mtrace('Reached maximum videos per run limit.');
                break;
            }

            try {
                $this->process_recording($recording, $tempdir);
                $processed++;
            } catch (\Exception $e) {
                mtrace('ERROR processing recording ' . $recording->id . ': ' . $e->getMessage());
                $this->mark_recording_failed($recording, $e->getMessage());
            }
        }

        mtrace('Processed ' . $processed . ' recordings.');

        // Sync transcripts for uploaded videos that don't have them yet.
        $this->sync_transcripts($instanceid);

        // Synthesize and attach per-language interpretation audio tracks.
        $this->process_pending_audiotracks($instanceid);

        // Clean up old Zoom recordings.
        $this->cleanup_old_zoom_recordings();

        mtrace('Zoom to YouTube sync task completed.');
    }

    /**
     * Sync transcripts from YouTube for uploaded videos.
     *
     * @param int|null $instanceid Specific zoom instance ID, or null for all.
     */
    protected function sync_transcripts(?int $instanceid = null) {
        global $DB;

        mtrace('Checking for videos needing transcript sync...');

        // Find uploaded videos without transcripts.
        $params = ['status' => 'uploaded', 'transcript_downloaded' => 0];
        $instancesql = '';
        if ($instanceid !== null) {
            $instancesql = ' AND zoomid = :zoomid';
            $params['zoomid'] = $instanceid;
        }

        $sql = "SELECT zyv.*, z.course
                FROM {zoomyt_videos} zyv
                JOIN {zoomyt} z ON z.id = zyv.zoomid
                WHERE zyv.status = :status
                  AND zyv.transcript_downloaded = :transcript_downloaded
                  AND zyv.youtube_video_id IS NOT NULL
                  {$instancesql}
                ORDER BY zyv.timecreated DESC
                LIMIT 10"; // Limit to avoid overloading.

        $videos = $DB->get_records_sql($sql, $params);

        if (empty($videos)) {
            mtrace('No videos need transcript sync.');
            return;
        }

        mtrace('Found ' . count($videos) . ' videos for transcript sync.');

        foreach ($videos as $video) {
            try {
                // Get the course module for this zoom activity.
                $cm = get_coursemodule_from_instance('zoomyt', $video->zoomid, $video->course);
                if (!$cm) {
                    continue;
                }

                // Get YouTube service for this activity.
                $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($video->zoomid);
                if (!$ytservice || !$ytservice->is_configured()) {
                    continue;
                }

                // Also sync metadata from YouTube.
                $ytservice->sync_video_from_youtube($video->id);

                // Download transcripts.
                $result = $ytservice->download_and_store_transcripts($video->id, $cm->id);

                // Record the attempt for retry tracking.
                $retrycount = (int)($video->transcript_retry_count ?? 0);
                $DB->execute(
                    "UPDATE {zoomyt_videos}
                        SET transcript_retry_count = :retrycount,
                            transcript_last_attempt = :lastattempt
                      WHERE id = :id",
                    [
                        'retrycount' => $retrycount + 1,
                        'lastattempt' => time(),
                        'id' => $video->id,
                    ]
                );

                if ($result) {
                    mtrace('  Downloaded transcripts for video: ' . $video->title);
                } else {
                    // No transcripts available yet - the retry_transcript_downloads task
                    // will pick this up later with backoff.
                    mtrace('  No transcripts available yet for: ' . $video->title .
                           ' (retry task will try again later)');
                }
            } catch (\Exception $e) {
                mtrace('  Error syncing transcripts for video ' . $video->id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the temporary directory for downloads.
     *
     * @return string|null Path to temp directory or null if not available.
     */
    protected function get_temp_directory(): ?string {
        global $CFG;

        $tempdir = get_config('zoomyt', 'temp_directory');
        if (empty($tempdir)) {
            $tempdir = $CFG->tempdir . '/zoomyt_videos';
        }

        if (!is_dir($tempdir)) {
            if (!mkdir($tempdir, 0755, true)) {
                return null;
            }
        }

        if (!is_writable($tempdir)) {
            return null;
        }

        return $tempdir;
    }

    /**
     * Persistent path where a synthesized interpretation audio track is archived
     * when it cannot be attached to YouTube automatically (for manual upload via
     * YouTube Studio's Languages tab).
     *
     * @param int $videoid The zoomyt_videos id.
     * @param string $lang BCP-47 language code.
     * @return string Absolute file path.
     */
    protected function audiotrack_archive_path(int $videoid, string $lang): string {
        return zoomyt_audiotrack_archive_path($videoid, $lang);
    }

    /**
     * Get recordings that need to be synced to YouTube.
     *
     * @param int|null $instanceid Optional specific zoom instance ID.
     * @return array Array of recording objects.
     */
    protected function get_pending_recordings(?int $instanceid = null): array {
        global $DB;

        // Find Zoom recordings that haven't been uploaded to YouTube yet.
        $params = [];
        $instancefilter = '';
        if ($instanceid !== null) {
            $instancefilter = 'AND z.id = :instanceid';
            $params['instanceid'] = $instanceid;
        }

        // Exclude recordings that already have a successfully uploaded video.
        $sql = "SELECT zmr.*, z.id as zoomid, z.course, z.name as session_name,
                       z.yt_primary_language, zmd.start_time as session_time
                FROM {zoomyt_meeting_recordings} zmr
                JOIN {zoomyt} z ON z.id = zmr.zoomid
                JOIN {zoomyt_meeting_details} zmd ON zmd.uuid = zmr.meetinguuid
                WHERE NOT EXISTS (
                    SELECT 1 FROM {zoomyt_videos} zyv
                    WHERE zyv.recordingid = zmr.id AND zyv.status IN ('uploaded', 'deleted')
                )
                  AND (zmr.recordingtype IN ('active_speaker', 'shared_screen_with_speaker_view', 
                                             'shared_screen_with_gallery_view', 'gallery_view')
                       OR zmr.recordingtype LIKE 'shared_screen_with_speaker_view%'
                       OR zmr.recordingtype LIKE 'shared_screen_with_gallery_view%'
                       OR zmr.recordingtype LIKE 'active_speaker%'
                       OR zmr.recordingtype LIKE 'gallery_view%')
                  AND zmr.showrecording = 1
                  $instancefilter
                ORDER BY zmr.recordingstart ASC";

        $allrecordings = $DB->get_records_sql($sql, $params, 0, 50);

        // Group by meeting UUID and prioritize recording types.
        $bymeeting = [];
        foreach ($allrecordings as $rec) {
            $key = $rec->zoomid . '_' . $rec->meetinguuid;
            if (!isset($bymeeting[$key])) {
                $bymeeting[$key] = [];
            }
            $bymeeting[$key][] = $rec;
        }

        // Select best recording for each meeting.
        $selected = [];
        // Priority order for recording types (base types, variants like "(CC)" will match the base).
        $priority = ['active_speaker', 'shared_screen_with_speaker_view', 'shared_screen_with_gallery_view', 'gallery_view'];

        foreach ($bymeeting as $recordings) {
            $best = null;
            $bestpriority = 999;

            foreach ($recordings as $rec) {
                // Check for exact match or prefix match (e.g., "shared_screen_with_speaker_view(CC)").
                $idx = false;
                foreach ($priority as $i => $type) {
                    if ($rec->recordingtype === $type || strpos($rec->recordingtype, $type) === 0) {
                        $idx = $i;
                        break;
                    }
                }
                if ($idx !== false && $idx < $bestpriority) {
                    $best = $rec;
                    $bestpriority = $idx;
                }
            }

            if ($best) {
                $selected[] = $best;
            }
        }

        return $selected;
    }

    /**
     * Process a single recording.
     *
     * @param object $recording The recording to process.
     * @param string $tempdir Temporary directory for downloads.
     */
    protected function process_recording(object $recording, string $tempdir): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');
        require_once($CFG->dirroot . '/mod/zoomyt/classes/category_settings.php');

        mtrace('Processing recording: ' . $recording->name . ' (ID: ' . $recording->id . ')');

        // Get YouTube service for this activity (checks activity -> category -> site).
        $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($recording->zoomid);
        if (!$ytservice || !$ytservice->is_configured()) {
            mtrace('  YouTube not configured for activity ' . $recording->zoomid . ', skipping.');
            return;
        }

        // Get course for visibility settings.
        $course = $DB->get_record('course', ['id' => $recording->course], 'id, category', MUST_EXIST);

        // Reuse any existing video record for this recording to prevent duplicates.
        $existingvideos = $DB->get_records('zoomyt_videos', ['recordingid' => $recording->id], 'id ASC');
        $video = null;

        if (!empty($existingvideos)) {
            // If there's already an uploaded copy, skip entirely.
            foreach ($existingvideos as $ev) {
                if ($ev->status === 'uploaded') {
                    mtrace('  Already uploaded (video ID: ' . $ev->id . '), skipping.');
                    return;
                }
            }

            // Reuse the first non-uploaded record; delete any extra duplicates.
            $first = true;
            foreach ($existingvideos as $ev) {
                if ($first) {
                    $video = $ev;
                    $first = false;
                } else {
                    mtrace('  Removing duplicate video record ID: ' . $ev->id);
                    $DB->delete_records('zoomyt_videos', ['id' => $ev->id]);
                }
            }

            mtrace('  Retrying previously failed upload (video ID: ' . $video->id . ')');
            $video->status = 'downloading';
            $video->error_message = null;
            $video->timemodified = time();
            $DB->update_record('zoomyt_videos', $video);
        } else {
            $video = new \stdClass();
            $video->zoomid = $recording->zoomid;
            $video->recordingid = $recording->id;
            $video->meetinguuid = $recording->meetinguuid;
            $video->zoom_recording_id = $recording->zoomrecordingid;
            $video->title = $recording->session_name;
            $video->description = 'Recorded session from ' . userdate($recording->session_time);
            $video->zoom_session_time = $recording->session_time;
            $video->status = 'downloading';
            $video->timecreated = time();
            $video->timemodified = time();

            $catsettings = new \mod_zoomyt\category_settings($course->category);
            $settings = $catsettings->get_effective_settings();
            $video->visibility = $settings->yt_default_visibility ?? get_config('zoomyt', 'youtube_default_visibility') ?? 'unlisted';

            $videoid = $DB->insert_record('zoomyt_videos', $video);
            $video->id = $videoid;
        }

        // Download the recording.
        mtrace('  Downloading from Zoom...');
        $localpath = $tempdir . '/zoom_recording_' . $recording->id . '.mp4';

        try {
            $this->download_zoom_recording($recording, $localpath);
        } catch (\Exception $e) {
            $video->status = 'failed';
            $video->error_message = 'Download failed: ' . $e->getMessage();
            $video->timemodified = time();
            $DB->update_record('zoomyt_videos', $video);
            throw $e;
        }

        // Update status to uploading.
        $video->status = 'uploading';
        $video->timemodified = time();
        $DB->update_record('zoomyt_videos', $video);

        // Upload to YouTube.
        mtrace('  Uploading to YouTube...');
        try {
            $primarylanguage = $this->resolve_primary_language($recording);
            $result = $ytservice->upload_video(
                $localpath,
                $video->title,
                $video->description,
                $video->visibility,
                null,
                $primarylanguage
            );

            $video->youtube_video_id = $result->id;
            $video->youtube_url = $result->url;
            $video->thumbnail_url = $result->thumbnail_url;
            $video->duration = $result->duration;
            $video->status = 'uploaded';
            $video->timemodified = time();
            $DB->update_record('zoomyt_videos', $video);

            mtrace('  Uploaded successfully: ' . $result->url);

            // Log the event.
            $event = \mod_zoomyt\event\video_uploaded_to_youtube::create([
                'context' => \context_course::instance($recording->course),
                'objectid' => $video->id,
                'other' => [
                    'youtube_video_id' => $result->id,
                    'zoom_recording_id' => $recording->id,
                ],
            ]);
            $event->trigger();

        } catch (\Exception $e) {
            $video->status = 'failed';
            $video->error_message = 'Upload failed: ' . $e->getMessage();
            $video->timemodified = time();
            $DB->update_record('zoomyt_videos', $video);
            throw $e;
        } finally {
            // Delete local file.
            if (file_exists($localpath)) {
                unlink($localpath);
                mtrace('  Deleted local file.');
            }
        }
    }

    /**
     * Download a Zoom recording to local file.
     *
     * @param object $recording The recording info.
     * @param string $localpath Local path to save to.
     */
    protected function download_zoom_recording(object $recording, string $localpath): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/zoomyt/classes/webservice.php');

        // Check available space.
        $tempdir = dirname($localpath);
        $maxspace = get_config('zoomyt', 'temp_storage_limit') ?: 5368709120;
        $availablespace = disk_free_space($tempdir);

        if ($availablespace < $maxspace * 0.1) {
            throw new \moodle_exception('insufficient_disk_space', 'zoomyt');
        }

        // Get download URL from Zoom.
        $downloadurl = $recording->externalurl;

        // Get Zoom access token for authenticated download.
        $service = \zoomyt_webservice();
        $accesstoken = $service->get_download_access_token();

        // Append access token to URL if not already present.
        if (!empty($accesstoken) && strpos($downloadurl, 'access_token') === false) {
            $separator = (strpos($downloadurl, '?') !== false) ? '&' : '?';
            $downloadurl .= $separator . 'access_token=' . $accesstoken;
        }

        mtrace('  Download URL: ' . substr($downloadurl, 0, 100) . '...');
        mtrace('  Access token present: ' . (!empty($accesstoken) ? 'yes (' . strlen($accesstoken) . ' chars)' : 'no'));

        // Use Moodle's download_file_content which handles file downloads properly.
        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 10,
            'CURLOPT_TIMEOUT' => 3600, // 1 hour timeout for large files.
        ]);

        // Download to file using Moodle's method.
        $result = $curl->download_one($downloadurl, null, [
            'filepath' => $localpath,
            'timeout' => 3600,
            'followlocation' => true,
            'maxredirs' => 10,
        ]);

        if ($curl->get_errno()) {
            if (file_exists($localpath)) {
                unlink($localpath);
            }
            throw new \moodle_exception('download_failed', 'zoomyt', '', 'CURL error: ' . $curl->error);
        }

        $info = $curl->get_info();
        mtrace('  HTTP response: ' . $info['http_code']);

        if ($info['http_code'] !== 200) {
            // Read the error response if file exists and is small.
            if (file_exists($localpath)) {
                $filesize = filesize($localpath);
                if ($filesize > 0 && $filesize < 10000) {
                    $errorcontent = file_get_contents($localpath);
                    mtrace('  Error response: ' . substr($errorcontent, 0, 500));
                }
                unlink($localpath);
            }
            throw new \moodle_exception('download_failed', 'zoomyt', '', 'HTTP ' . $info['http_code']);
        }

        if (!file_exists($localpath)) {
            throw new \moodle_exception('download_failed', 'zoomyt', '', 'File was not created');
        }

        $filesize = filesize($localpath);
        mtrace('  Downloaded ' . round($filesize / 1048576, 2) . ' MB (' . $filesize . ' bytes)');

        if ($filesize < 1000) {
            // File is suspiciously small, might be an error page.
            $content = file_get_contents($localpath);
            mtrace('  Warning: File is very small. Content: ' . substr($content, 0, 500));
            unlink($localpath);
            throw new \moodle_exception('download_failed', 'zoomyt', '', 'Downloaded file is too small (' . $filesize . ' bytes)');
        }
    }

    /**
     * Mark a recording as failed.
     *
     * @param object $recording The recording.
     * @param string $message Error message.
     */
    protected function mark_recording_failed(object $recording, string $message): void {
        global $DB;

        // Check if there's already a video record.
        $video = $DB->get_record('zoomyt_videos', ['recordingid' => $recording->id]);

        if ($video) {
            $video->status = 'failed';
            $video->error_message = $message;
            $video->timemodified = time();
            $DB->update_record('zoomyt_videos', $video);
        }
    }

    /**
     * Resolve the BCP-47 primary language designation for a recording's video.
     *
     * Uses the activity's yt_primary_language override if set, otherwise the
     * course language, otherwise the site default language.
     *
     * @param object $recording Recording row (must include yt_primary_language, course).
     * @return string|null BCP-47 language code, or null if none could be resolved.
     */
    protected function resolve_primary_language(object $recording): ?string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');

        $lang = trim((string) ($recording->yt_primary_language ?? ''));

        if ($lang === '') {
            // Fall back to the course language.
            $courselang = $DB->get_field('course', 'lang', ['id' => $recording->course]);
            $lang = trim((string) $courselang);
        }

        if ($lang === '') {
            // Fall back to the site default language.
            $lang = !empty($CFG->lang) ? $CFG->lang : 'en';
        }

        return zoomyt_moodle_lang_to_bcp47($lang);
    }

    /** @var int Maximum audio tracks to synthesize/attach per run. */
    const MAX_AUDIOTRACKS_PER_RUN = 5;

    /**
     * Synthesize per-language interpretation audio tracks and attach them to the
     * uploaded YouTube videos.
     *
     * @param int|null $instanceid Specific zoom instance ID, or null for all.
     */
    protected function process_pending_audiotracks(?int $instanceid = null): void {
        global $CFG, $DB;

        if (empty(get_config('zoomyt', 'enable_multilang_audio'))) {
            mtrace('Skipping interpretation audio tracks: enable_multilang_audio is disabled.');
            return;
        }

        require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');
        require_once($CFG->dirroot . '/mod/zoomyt/classes/audio_processor.php');
        require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');

        $processor = new \mod_zoomyt\audio_processor();
        if (!$processor->is_available()) {
            mtrace('Skipping audio tracks: ffmpeg/ffprobe not available.');
            return;
        }

        mtrace('Checking for interpretation audio tracks to build...');

        // Find uploaded videos that have interpretation recordings for their meeting.
        $params = [];
        $instancesql = '';
        if ($instanceid !== null) {
            $instancesql = ' AND zyv.zoomid = :zoomid';
            $params['zoomid'] = $instanceid;
        }

        $sql = "SELECT DISTINCT zyv.*
                  FROM {zoomyt_videos} zyv
                  JOIN {zoomyt_meeting_recordings} zmr
                    ON zmr.zoomid = zyv.zoomid
                   AND zmr.meetinguuid = zyv.meetinguuid
                   AND zmr.recordingtype = :interptype
                 WHERE zyv.status = 'uploaded'
                   AND zyv.youtube_video_id IS NOT NULL
                   $instancesql
              ORDER BY zyv.timecreated DESC";
        $params['interptype'] = 'audio_interpretation';

        $videos = $DB->get_records_sql($sql, $params, 0, 20);
        if (empty($videos)) {
            mtrace('No videos need interpretation audio tracks.');
            return;
        }

        $tempdir = $this->get_temp_directory();
        if (!$tempdir) {
            mtrace('Skipping audio tracks: temp directory not available.');
            return;
        }

        $built = 0;
        foreach ($videos as $video) {
            if ($built >= self::MAX_AUDIOTRACKS_PER_RUN) {
                mtrace('Reached maximum audio tracks per run limit.');
                break;
            }
            try {
                $built += $this->build_audiotracks_for_video($video, $processor, $tempdir,
                    self::MAX_AUDIOTRACKS_PER_RUN - $built);
            } catch (\Exception $e) {
                mtrace('  ERROR building audio tracks for video ' . $video->id . ': ' . $e->getMessage());
            }
        }

        mtrace('Built ' . $built . ' interpretation audio tracks.');
    }

    /**
     * Build and attach interpretation audio tracks for one uploaded video.
     *
     * @param object $video The zoomyt_videos row.
     * @param \mod_zoomyt\audio_processor $processor The ffmpeg processor.
     * @param string $tempdir Temp directory for downloads/outputs.
     * @param int $limit Maximum number of tracks to build in this call.
     * @return int Number of tracks successfully attached.
     */
    protected function build_audiotracks_for_video(object $video, \mod_zoomyt\audio_processor $processor,
            string $tempdir, int $limit): int {
        global $DB;

        $zoom = $DB->get_record('zoomyt', ['id' => $video->zoomid]);
        if (!$zoom) {
            return 0;
        }

        $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($video->zoomid);
        if (!$ytservice || !$ytservice->is_configured()) {
            return 0;
        }

        // Interpretation recordings for this session.
        $interprecordings = $DB->get_records('zoomyt_meeting_recordings', [
            'zoomid' => $video->zoomid,
            'meetinguuid' => $video->meetinguuid,
            'recordingtype' => 'audio_interpretation',
        ], 'recordingstart ASC');

        if (empty($interprecordings)) {
            return 0;
        }

        // Determine the floor audio source: prefer audio_only, else the uploaded video recording.
        $floorrecording = $DB->get_record('zoomyt_meeting_recordings', [
            'zoomid' => $video->zoomid,
            'meetinguuid' => $video->meetinguuid,
            'recordingtype' => 'audio_only',
        ]);
        if (!$floorrecording && !empty($video->recordingid)) {
            $floorrecording = $DB->get_record('zoomyt_meeting_recordings', ['id' => $video->recordingid]);
        }
        if (!$floorrecording) {
            mtrace('  No floor audio source for video ' . $video->id . ', skipping.');
            return 0;
        }

        // Candidate languages configured on the activity (used when Zoom does not
        // expose the language on the recording file).
        $candidatelangs = zoomyt_get_activity_interpretation_languages($zoom);
        $primarylang = $this->resolve_primary_language((object) [
            'yt_primary_language' => $zoom->yt_primary_language ?? null,
            'course' => $zoom->course,
        ]);
        // The floor (default) track already carries the primary language; alternate
        // tracks should be the other configured languages.
        $candidatelangs = array_values(array_diff($candidatelangs, [$primarylang]));

        $floorpath = $tempdir . '/zoomyt_floor_' . $video->id . '.src';
        $havefloor = false;
        $built = 0;

        try {
            foreach ($interprecordings as $interp) {
                if ($built >= $limit) {
                    break;
                }

                // Resolve the language for this interpretation recording.
                $lang = trim((string) ($interp->language ?? ''));
                if ($lang === '') {
                    if (count($interprecordings) === 1 && count($candidatelangs) === 1) {
                        $lang = $candidatelangs[0];
                    }
                }
                if ($lang === '') {
                    mtrace('  Cannot determine language for interpretation recording ' . $interp->id .
                        ' (assign it manually); skipping.');
                    continue;
                }

                // The default (floor) track already carries the primary language;
                // an interpretation channel in that language would collide with it.
                if ($lang === $primarylang) {
                    mtrace('  Interpretation recording ' . $interp->id . ' is the primary language (' .
                        $lang . '); covered by the default track, skipping.');
                    continue;
                }

                // Skip if this track is already attached, or already synthesized and
                // archived for manual upload (YouTube currently has no public API to
                // attach multi-language audio tracks).
                $existing = $DB->get_record('zoomyt_video_audiotracks',
                    ['videoid' => $video->id, 'language' => $lang]);
                if ($existing && $existing->status === 'attached') {
                    continue;
                }
                if ($existing && $existing->status === 'synthesized'
                        && file_exists($this->audiotrack_archive_path($video->id, $lang))) {
                    mtrace('  ' . $lang . ' track for video ' . $video->id .
                        ' already synthesized and awaiting manual upload, skipping.');
                    continue;
                }

                $track = $existing ?: (object) [
                    'videoid' => $video->id,
                    'language' => $lang,
                    'source_recordingid' => $interp->id,
                    'status' => 'pending',
                    'timecreated' => time(),
                ];
                $track->source_recordingid = $interp->id;
                $track->status = 'synthesizing';
                $track->error_message = null;
                $track->timemodified = time();
                if (!empty($track->id)) {
                    $DB->update_record('zoomyt_video_audiotracks', $track);
                } else {
                    $track->id = $DB->insert_record('zoomyt_video_audiotracks', $track);
                }

                // Download the floor source once.
                if (!$havefloor) {
                    mtrace('  Downloading floor audio for video ' . $video->id . '...');
                    $this->download_zoom_recording($floorrecording, $floorpath);
                    $havefloor = true;
                }

                // Download the interpreter track.
                $interppath = $tempdir . '/zoomyt_interp_' . $interp->id . '.m4a';
                mtrace('  Downloading interpreter (' . $lang . ') for recording ' . $interp->id . '...');
                $this->download_zoom_recording($interp, $interppath);

                // Synthesize the ducked language track.
                $outpath = $tempdir . '/zoomyt_track_' . $video->id . '_' . $lang . '.m4a';
                $offset = (float) (($interp->recordingstart ?? 0) - ($floorrecording->recordingstart ?? 0));
                mtrace('  Synthesizing ' . $lang . ' track (offset ' . $offset . 's)...');

                $ok = $processor->synthesize_ducked_track($floorpath, $interppath, $outpath, $offset);

                if (!$ok) {
                    $track->status = 'failed';
                    $track->error_message = 'ffmpeg synthesis failed';
                    $track->timemodified = time();
                    $DB->update_record('zoomyt_video_audiotracks', $track);
                    @unlink($interppath);
                    continue;
                }

                // Attach to YouTube.
                $track->status = 'uploading';
                $track->timemodified = time();
                $DB->update_record('zoomyt_video_audiotracks', $track);

                try {
                    mtrace('  Attaching ' . $lang . ' audio track to YouTube...');
                    $result = $ytservice->upload_audio_track($video->youtube_video_id, $outpath, $lang);
                    $track->youtube_audiotrack_id = $result->id;
                    $track->status = 'attached';
                    $track->error_message = null;
                    $track->timemodified = time();
                    $DB->update_record('zoomyt_video_audiotracks', $track);
                    $built++;
                    mtrace('  Attached ' . $lang . ' audio track.');
                } catch (\Exception $e) {
                    // The synthesis succeeded, so archive the track instead of discarding
                    // it: it can be uploaded manually in YouTube Studio (Languages tab),
                    // and we avoid re-downloading/re-synthesizing on every cron run.
                    $archivepath = $this->audiotrack_archive_path($video->id, $lang);
                    if (file_exists($outpath) && @rename($outpath, $archivepath)) {
                        $track->status = 'synthesized';
                        $track->error_message = 'Attach failed: ' . $e->getMessage() .
                            ' -- synthesized track saved for manual upload: ' . $archivepath;
                        mtrace('  Attach failed for ' . $lang . ': ' . $e->getMessage());
                        mtrace('  Saved synthesized ' . $lang . ' track for manual upload: ' . $archivepath);
                    } else {
                        $track->status = 'failed';
                        $track->error_message = 'Attach failed: ' . $e->getMessage();
                        mtrace('  Attach failed for ' . $lang . ': ' . $e->getMessage());
                    }
                    $track->timemodified = time();
                    $DB->update_record('zoomyt_video_audiotracks', $track);
                } finally {
                    if (file_exists($outpath)) {
                        @unlink($outpath);
                    }
                    if (file_exists($interppath)) {
                        @unlink($interppath);
                    }
                }
            }
        } finally {
            if (file_exists($floorpath)) {
                @unlink($floorpath);
            }
        }

        return $built;
    }

    /**
     * Clean up old Zoom cloud recordings.
     */
    protected function cleanup_old_zoom_recordings(): void {
        global $DB;

        mtrace('Checking for old Zoom recordings to delete...');

        // Get videos that have been uploaded and are past the retention period.
        $sql = "SELECT zyv.*, zcs.zoom_recording_delete_days, z.course
                FROM {zoomyt_videos} zyv
                JOIN {zoomyt} z ON z.id = zyv.zoomid
                JOIN {course} c ON c.id = z.course
                LEFT JOIN {zoomyt_category_settings} zcs ON zcs.categoryid = c.category AND zcs.inherit = 0
                WHERE zyv.status = 'uploaded'
                  AND zyv.zoom_recording_deleted = 0
                  AND zyv.youtube_video_id IS NOT NULL";

        $videos = $DB->get_records_sql($sql);
        $deleted = 0;

        foreach ($videos as $video) {
            // Get effective delete days setting.
            $deletedays = $video->zoom_recording_delete_days;
            if ($deletedays === null) {
                $deletedays = get_config('zoomyt', 'zoom_recording_delete_days');
            }

            if (empty($deletedays)) {
                continue; // Don't delete.
            }

            $deletethreshold = time() - ($deletedays * 86400);

            if ($video->timecreated < $deletethreshold) {
                try {
                    // TODO: Call Zoom API to delete the recording.
                    // For now, just mark it as deleted.
                    $video->zoom_recording_deleted = 1;
                    $video->timemodified = time();
                    $DB->update_record('zoomyt_videos', $video);
                    $deleted++;
                    mtrace('  Marked recording as deleted for video ID: ' . $video->id);
                } catch (\Exception $e) {
                    mtrace('  ERROR deleting recording for video ID ' . $video->id . ': ' . $e->getMessage());
                }
            }
        }

        mtrace('Marked ' . $deleted . ' Zoom recordings for deletion.');
    }
}
