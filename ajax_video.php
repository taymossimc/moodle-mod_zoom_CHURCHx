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
 * AJAX handler for video metadata operations.
 *
 * @package    mod_zoomyt
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$action = required_param('action', PARAM_ALPHA);
$videoid = required_param('videoid', PARAM_INT);

// Get the video and associated zoom activity.
$video = $DB->get_record('zoomyt_videos', ['id' => $videoid], '*', MUST_EXIST);
$zoom = $DB->get_record('zoomyt', ['id' => $video->zoomid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('zoomyt', $zoom->id, $zoom->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Require login and sesskey.
require_login($zoom->course, true, $cm);
require_sesskey();

// Most actions require teacher capability; trackprogress is for all enrolled users.
if ($action !== 'trackprogress') {
    require_capability('mod/zoomyt:addinstance', $context);
}

header('Content-Type: application/json');

$result = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'update':
            // Update video title and description.
            $title = required_param('title', PARAM_TEXT);
            $description = optional_param('description', '', PARAM_RAW);

            // Update local database.
            $update = new stdClass();
            $update->id = $video->id;
            $update->title = clean_param($title, PARAM_TEXT);
            $update->description = clean_param($description, PARAM_CLEANHTML);
            $update->timemodified = time();
            $DB->update_record('zoomyt_videos', $update);

            // Sync to YouTube if connected.
            if (!empty($video->youtube_video_id)) {
                require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');
                $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($zoom->id);
                if ($ytservice && $ytservice->is_configured()) {
                    try {
                        $ytservice->update_video_metadata($video->youtube_video_id, $update->title, $update->description);
                    } catch (Exception $e) {
                        // Log but don't fail the request.
                        debugging('Failed to sync to YouTube: ' . $e->getMessage(), DEBUG_DEVELOPER);
                    }
                }
            }

            $result = [
                'success' => true,
                'message' => get_string('video_updated', 'zoomyt'),
                'title' => $update->title,
                'description' => $update->description,
            ];
            break;

        case 'syncfromyt':
            // Sync metadata from YouTube.
            if (empty($video->youtube_video_id)) {
                throw new moodle_exception('novideoid', 'zoomyt');
            }

            require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');
            $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($zoom->id);
            if (!$ytservice || !$ytservice->is_configured()) {
                throw new moodle_exception('youtube_not_configured', 'zoomyt');
            }

            $ytservice->sync_video_from_youtube($video->id);

            // Also fetch caption languages.
            try {
                $captions = $ytservice->get_video_captions($video->youtube_video_id);
                $languages = array_column($captions, 'language');
                $DB->set_field('zoomyt_videos', 'caption_languages', implode(',', $languages), ['id' => $video->id]);
            } catch (Exception $e) {
                // Caption fetching is optional.
                debugging('Failed to fetch captions: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }

            // Get updated record.
            $updated = $DB->get_record('zoomyt_videos', ['id' => $video->id]);

            $result = [
                'success' => true,
                'message' => get_string('video_synced', 'zoomyt'),
                'title' => $updated->title,
                'description' => $updated->description,
                'thumbnail_url' => $updated->thumbnail_url,
                'visibility' => $updated->visibility,
                'caption_languages' => $updated->caption_languages,
            ];
            break;

        case 'get':
            // Get video details for editing.
            $result = [
                'success' => true,
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description ?? '',
                    'visibility' => $video->visibility,
                    'youtube_video_id' => $video->youtube_video_id,
                    'youtube_url' => $video->youtube_url,
                    'thumbnail_url' => $video->thumbnail_url,
                    'caption_languages' => $video->caption_languages ?? '',
                ],
            ];
            break;

        case 'trackprogress':
            // Track video watch progress (available to all enrolled users).
            $watchedseconds = required_param('watchedseconds', PARAM_INT);
            $videoduration = required_param('videoduration', PARAM_INT);
            $lastposition = required_param('lastposition', PARAM_INT);

            $userid = $USER->id;
            $now = time();

            // Get existing progress record.
            $existing = $DB->get_record('zoomyt_video_progress', [
                'videoid' => $videoid,
                'userid' => $userid,
            ]);

            if ($existing) {
                // Accumulate watched seconds.
                $existing->watchedseconds += max(0, $watchedseconds);
                $existing->videoduration = max($existing->videoduration, $videoduration);
                $existing->lastposition = $lastposition;

                // Calculate percentage.
                if ($existing->videoduration > 0) {
                    $existing->percentcomplete = min(100, round(($existing->watchedseconds / $existing->videoduration) * 100));
                }

                $existing->timemodified = $now;
                $DB->update_record('zoomyt_video_progress', $existing);
                $percentcomplete = $existing->percentcomplete;
            } else {
                // Create new record.
                $record = new stdClass();
                $record->videoid = $videoid;
                $record->userid = $userid;
                $record->watchedseconds = max(0, $watchedseconds);
                $record->videoduration = max(0, $videoduration);
                $record->lastposition = $lastposition;
                $record->percentcomplete = $videoduration > 0
                    ? min(100, round(($record->watchedseconds / $record->videoduration) * 100))
                    : 0;
                $record->timecreated = $now;
                $record->timemodified = $now;
                $DB->insert_record('zoomyt_video_progress', $record);
                $percentcomplete = $record->percentcomplete;
            }

            // Check and update activity completion if watch percent rule is configured.
            if (!empty($zoom->completionwatchpercent) && $zoom->completionwatchpercent > 0) {
                $completion = new completion_info($DB->get_record('course', ['id' => $zoom->course]));
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
                }
            }

            $result = [
                'success' => true,
                'percentcomplete' => $percentcomplete,
            ];
            break;

        default:
            throw new moodle_exception('invalidaction', 'zoomyt');
    }
} catch (Exception $e) {
    $result = [
        'success' => false,
        'message' => $e->getMessage(),
    ];
}

echo json_encode($result);
