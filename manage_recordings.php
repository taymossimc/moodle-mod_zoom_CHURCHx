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
 * Manage recordings page for teachers.
 *
 * Shows all Zoom sessions and their recording/YouTube status.
 *
 * @package    mod_zoomyt
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$videoid = optional_param('videoid', 0, PARAM_INT);

// Get course module.
$cm = get_coursemodule_from_id('zoomyt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$zoom = $DB->get_record('zoomyt', ['id' => $cm->instance], '*', MUST_EXIST);

$context = context_module::instance($cm->id);

// Require login and capability.
require_login($course, true, $cm);
require_capability('mod/zoomyt:addinstance', $context);

// Set up the page.
$PAGE->set_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]);
$PAGE->set_title(format_string($zoom->name) . ' - ' . get_string('manage_recordings', 'zoomyt'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Handle actions.
if ($action === 'togglevisibility' && $videoid) {
    require_sesskey();
    $video = $DB->get_record('zoomyt_videos', ['id' => $videoid, 'zoomid' => $zoom->id], '*', MUST_EXIST);
    $DB->set_field('zoomyt_videos', 'visible', $video->visible ? 0 : 1, ['id' => $videoid]);
    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Handle sync recordings action - run both get_meeting_reports and get_meeting_recordings tasks.
if ($action === 'syncrecordings') {
    require_sesskey();
    require_once($CFG->dirroot . '/mod/zoomyt/classes/task/get_meeting_reports.php');
    require_once($CFG->dirroot . '/mod/zoomyt/classes/task/get_meeting_recordings.php');

    $messages = [];
    $errors = [];

    // First, sync meeting reports (session data).
    try {
        $task = new \mod_zoomyt\task\get_meeting_reports();
        $task->execute_for_instance($zoom->id);
        $messages[] = get_string('sync_reports_success', 'zoomyt');
    } catch (Exception $e) {
        $errors[] = get_string('sync_reports_error', 'zoomyt', $e->getMessage());
    }

    // Then, sync recordings.
    try {
        $task = new \mod_zoomyt\task\get_meeting_recordings();
        $task->execute_for_instance($zoom->id);
        $messages[] = get_string('sync_recordings_success', 'zoomyt');
    } catch (Exception $e) {
        $errors[] = get_string('sync_recordings_error', 'zoomyt', $e->getMessage());
    }

    foreach ($messages as $msg) {
        \core\notification::success($msg);
    }
    foreach ($errors as $err) {
        \core\notification::error($err);
    }

    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Handle retry of a single failed video upload.
if ($action === 'retryupload' && $videoid) {
    require_sesskey();
    require_once($CFG->dirroot . '/mod/zoomyt/classes/task/sync_recordings_to_youtube.php');

    $video = $DB->get_record('zoomyt_videos', ['id' => $videoid, 'zoomid' => $zoom->id, 'status' => 'failed'], '*', MUST_EXIST);

    // Clear the error message; process_recording() will reset status to 'downloading'.
    $DB->set_field('zoomyt_videos', 'error_message', null, ['id' => $video->id]);

    try {
        $task = new \mod_zoomyt\task\sync_recordings_to_youtube();
        $task->execute_for_instance($zoom->id);
        \core\notification::success(get_string('retry_upload_success', 'zoomyt'));
    } catch (Exception $e) {
        \core\notification::error(get_string('retry_upload_error', 'zoomyt', $e->getMessage()));
    }

    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Handle sync to YouTube action.
if ($action === 'syncyoutube') {
    require_sesskey();
    require_once($CFG->dirroot . '/mod/zoomyt/classes/task/sync_recordings_to_youtube.php');
    require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');

    try {
        $task = new \mod_zoomyt\task\sync_recordings_to_youtube();
        // Run just for this specific zoom instance.
        $task->execute_for_instance($zoom->id);
        \core\notification::success(get_string('sync_youtube_success', 'zoomyt'));
    } catch (Exception $e) {
        \core\notification::error(get_string('sync_youtube_error', 'zoomyt', $e->getMessage()));
    }

    // Also sync metadata and transcripts from YouTube for uploaded videos.
    try {
        $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($zoom->id);
        if ($ytservice && $ytservice->is_configured()) {
            // Get all uploaded videos for this activity.
            $uploadedvideos = $DB->get_records('zoomyt_videos', [
                'zoomid' => $zoom->id,
                'status' => 'uploaded',
            ]);

            foreach ($uploadedvideos as $video) {
                // Sync metadata from YouTube (title, description, thumbnail, visibility).
                $ytservice->sync_video_from_youtube($video->id);

                // Download transcripts if not already downloaded.
                if (empty($video->transcript_downloaded)) {
                    $ytservice->download_and_store_transcripts($video->id, $cm->id);
                }
            }
        }
    } catch (Exception $e) {
        // Don't fail the whole sync if transcript download fails.
        debugging('Transcript sync error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Handle delete-from-YouTube action. Deletes the video on YouTube but keeps the
// Moodle record (marked as deleted) so the Zoom session stays listed and the
// recording is not re-uploaded by the scheduled task.
if ($action === 'deleteyoutube' && $videoid) {
    require_sesskey();
    require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');

    $video = $DB->get_record('zoomyt_videos', ['id' => $videoid, 'zoomid' => $zoom->id], '*', MUST_EXIST);

    $deleted = true;
    if (!empty($video->youtube_video_id)) {
        try {
            $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($zoom->id);
            if ($ytservice && $ytservice->is_configured()) {
                $ytservice->delete_video($video->youtube_video_id);
            }
        } catch (Exception $e) {
            $deleted = false;
            \core\notification::error(get_string('delete_youtube_error', 'zoomyt', $e->getMessage()));
        }
    }

    if ($deleted) {
        $update = new stdClass();
        $update->id = $video->id;
        $update->status = 'deleted';
        $update->youtube_video_id = null;
        $update->youtube_url = null;
        $update->thumbnail_url = null;
        $update->visible = 0;
        $update->timemodified = time();
        $DB->update_record('zoomyt_videos', $update);

        \core\notification::success(get_string('delete_youtube_success', 'zoomyt'));
    }

    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Handle add-YouTube-video action. Adds an existing YouTube video to the session
// recordings list (it does not upload to YouTube).
if ($action === 'addyoutube') {
    require_sesskey();
    require_once($CFG->dirroot . '/mod/zoomyt/classes/youtube_service.php');

    $rawurl = required_param('youtubeurl', PARAM_RAW);
    $ytvideoid = \mod_zoomyt\youtube_service::extract_video_id($rawurl);

    if (empty($ytvideoid)) {
        \core\notification::error(get_string('add_video_invalid_url', 'zoomyt'));
        redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
    }

    // The youtube_video_id column is globally unique; avoid a duplicate insert.
    if ($DB->record_exists('zoomyt_videos', ['youtube_video_id' => $ytvideoid])) {
        \core\notification::warning(get_string('add_video_already_exists', 'zoomyt'));
        redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
    }

    $now = time();
    $record = new stdClass();
    $record->zoomid = $zoom->id;
    $record->recordingid = null;
    $record->meetinguuid = 'manual-' . uniqid();
    $record->zoom_recording_id = null;
    $record->youtube_video_id = $ytvideoid;
    $record->youtube_url = 'https://www.youtube.com/watch?v=' . $ytvideoid;
    $record->title = get_string('manual_video_default_title', 'zoomyt');
    $record->description = '';
    $record->thumbnail_url = 'https://img.youtube.com/vi/' . $ytvideoid . '/mqdefault.jpg';
    $record->duration = 0;
    $record->visibility = 'unlisted';
    $record->status = 'uploaded';
    $record->zoom_recording_deleted = 0;
    $record->visible = 1; // Visible to students by default.
    $record->zoom_session_time = $now;
    $record->timecreated = $now;
    $record->timemodified = $now;

    // Enrich from YouTube where possible (title, description, thumbnail, etc.).
    $ytservice = \mod_zoomyt\youtube_service::get_instance_for_activity($zoom->id);
    $configured = $ytservice && $ytservice->is_configured();
    if ($configured) {
        try {
            $info = $ytservice->get_video_info($ytvideoid);
            if (!empty($info->title)) {
                $record->title = $info->title;
            }
            $record->description = $info->description ?? '';
            if (!empty($info->thumbnail_url)) {
                $record->thumbnail_url = $info->thumbnail_url;
            }
            $record->duration = (int)($info->duration ?? 0);
            if (!empty($info->visibility)) {
                $record->visibility = $info->visibility;
            }
            if (!empty($info->published_at)) {
                $publishedts = strtotime($info->published_at);
                if ($publishedts) {
                    $record->zoom_session_time = $publishedts;
                }
            }
        } catch (Exception $e) {
            debugging('Add YouTube video: could not fetch metadata: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    $newid = $DB->insert_record('zoomyt_videos', $record);

    // Best-effort: record available caption languages and download transcripts.
    if ($configured) {
        try {
            $captions = $ytservice->get_video_captions($ytvideoid);
            $languages = array_filter(array_column($captions, 'language'));
            if (!empty($languages)) {
                $DB->set_field('zoomyt_videos', 'caption_languages', implode(',', $languages), ['id' => $newid]);
            }
        } catch (Exception $e) {
            debugging('Add YouTube video: could not fetch captions: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        try {
            $ytservice->download_and_store_transcripts($newid, $cm->id);
        } catch (Exception $e) {
            debugging('Add YouTube video: could not fetch transcripts: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    \core\notification::success(get_string('add_video_success', 'zoomyt'));
    redirect(new moodle_url('/mod/zoomyt/manage_recordings.php', ['id' => $id]));
}

// Get all videos for this activity.
require_once($CFG->dirroot . '/mod/zoomyt/classes/output/video_gallery.php');
$videos = \mod_zoomyt\output\video_gallery::get_all_videos_for_management($zoom->id, $cm->id);

// Get Zoom meeting recordings that haven't been synced yet.
// Use CONCAT to create a unique key for each row (uuid + recordingid).
$sql = "SELECT CONCAT(zmd.uuid, '-', COALESCE(zmr.id, 0)) as uniquekey,
               zmd.uuid, zmd.meeting_id, zmd.start_time, zmd.end_time, 
               zmd.duration, zmd.topic, zmd.total_minutes, zmd.participants_count, zmd.zoomid,
               zmr.id as recordingid, zmr.recordingtype, zmr.recordingstart
        FROM {zoomyt_meeting_details} zmd
        JOIN {zoomyt} z ON z.id = zmd.zoomid
        LEFT JOIN {zoomyt_meeting_recordings} zmr ON zmr.meetinguuid = zmd.uuid
        WHERE z.id = ?
        ORDER BY zmd.start_time DESC";
$meetings = $DB->get_records_sql($sql, [$zoom->id]);

// Group meetings by UUID.
$meetingdata = [];
foreach ($meetings as $meeting) {
    $uuid = $meeting->uuid;
    if (!isset($meetingdata[$uuid])) {
        $meetingdata[$uuid] = [
            'uuid' => $uuid,
            'topic' => $meeting->topic ?? $zoom->name,
            'start_time' => $meeting->start_time,
            'duration' => $meeting->duration,
            'has_recording' => false,
            'recording_types' => [],
        ];
    }
    if (!empty($meeting->recordingid)) {
        $meetingdata[$uuid]['has_recording'] = true;
        $meetingdata[$uuid]['recording_types'][] = $meeting->recordingtype;
    }
}

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_recordings', 'zoomyt'));

// Back link.
$backurl = new moodle_url('/mod/zoomyt/view.php', ['id' => $cm->id]);
echo html_writer::tag('p', html_writer::link($backurl, '&laquo; ' . get_string('back')));

// Sync action buttons.
$syncrecordingsurl = new moodle_url('/mod/zoomyt/manage_recordings.php', [
    'id' => $id,
    'action' => 'syncrecordings',
    'sesskey' => sesskey(),
]);
$syncyoutubeurl = new moodle_url('/mod/zoomyt/manage_recordings.php', [
    'id' => $id,
    'action' => 'syncyoutube',
    'sesskey' => sesskey(),
]);

echo html_writer::start_div('mb-3');
echo html_writer::link($syncrecordingsurl,
    '<i class="fa fa-refresh"></i> ' . get_string('sync_recordings_button', 'zoomyt'),
    ['class' => 'btn btn-outline-primary mr-2']
);
echo html_writer::link($syncyoutubeurl,
    '<i class="fa fa-youtube-play"></i> ' . get_string('sync_youtube_button', 'zoomyt'),
    ['class' => 'btn btn-outline-danger mr-2']
);
echo html_writer::tag('button',
    '<i class="fa fa-plus"></i> ' . get_string('add_youtube_video', 'zoomyt'),
    [
        'type' => 'button',
        'class' => 'btn btn-outline-success',
        'data-toggle' => 'modal',
        'data-target' => '#addVideoModal',
    ]
);
echo html_writer::end_div();

// Multi-language interpretation audio track status (read-only summary).
if (!empty(get_config('zoomyt', 'enable_multilang_audio'))) {
    $tracksql = "SELECT zat.id, zat.language, zat.status, zat.error_message,
                        zv.title, zv.youtube_video_id
                   FROM {zoomyt_video_audiotracks} zat
                   JOIN {zoomyt_videos} zv ON zv.id = zat.videoid
                  WHERE zv.zoomid = ?
               ORDER BY zv.timecreated DESC, zat.language ASC";
    $audiotracks = $DB->get_records_sql($tracksql, [$zoom->id]);

    if (!empty($audiotracks)) {
        echo $OUTPUT->heading(get_string('audiotracks_heading', 'zoomyt'), 4);
        $table = new html_table();
        $table->head = [
            get_string('video', 'zoomyt'),
            get_string('language'),
            get_string('status'),
            get_string('error'),
        ];
        $table->attributes['class'] = 'generaltable';
        foreach ($audiotracks as $track) {
            $statusclass = $track->status === 'attached' ? 'badge-success'
                : ($track->status === 'failed' ? 'badge-danger' : 'badge-secondary');
            $statuscell = html_writer::tag('span', s($track->status), ['class' => 'badge ' . $statusclass]);
            $table->data[] = [
                format_string($track->title),
                s($track->language),
                $statuscell,
                $track->status === 'failed' ? s($track->error_message) : '',
            ];
        }
        echo html_writer::table($table);
    }
}

// Add-YouTube-video modal (kept outside the videos block so it is always available).
echo '
<div class="modal fade" id="addVideoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" action="' . (new moodle_url('/mod/zoomyt/manage_recordings.php'))->out(false) . '">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . get_string('add_youtube_video', 'zoomyt') . '</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="' . $id . '">
                    <input type="hidden" name="action" value="addyoutube">
                    <input type="hidden" name="sesskey" value="' . sesskey() . '">
                    <div class="form-group">
                        <label for="add-youtube-url">' . get_string('youtube_url', 'zoomyt') . '</label>
                        <input type="url" class="form-control" id="add-youtube-url" name="youtubeurl"
                               placeholder="https://www.youtube.com/watch?v=..." required>
                        <small class="form-text text-muted">' . get_string('add_youtube_video_help', 'zoomyt') . '</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">' . get_string('cancel') . '</button>
                    <button type="submit" class="btn btn-success">' . get_string('add') . '</button>
                </div>
            </div>
        </form>
    </div>
</div>';

// YouTube Videos Section.
echo $OUTPUT->heading(get_string('sessions_uploaded_youtube', 'zoomyt'), 3);

if (empty($videos)) {
    echo html_writer::tag('p', get_string('no_videos', 'zoomyt'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('description'),
        get_string('session_date', 'zoomyt'),
        get_string('duration', 'zoomyt'),
        get_string('youtube_status', 'zoomyt'),
        get_string('transcript', 'zoomyt'),
        get_string('visibility', 'zoomyt'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'table table-striped';
    $table->id = 'zoomyt-videos-table';

    foreach ($videos as $video) {
        $row = new html_table_row();
        $row->id = 'video-row-' . $video->id;

        // Title - make it editable with a click.
        $titlecell = html_writer::span(
            s($video->title),
            'editable-title',
            [
                'data-videoid' => $video->id,
                'data-field' => 'title',
                'title' => get_string('click_to_edit', 'zoomyt'),
                'style' => 'cursor: pointer; border-bottom: 1px dashed #007bff;',
            ]
        );
        if ($video->has_youtube) {
            $titlecell .= ' ' . html_writer::link($video->youtube_url, '<i class="fa fa-external-link"></i>', [
                'target' => '_blank',
                'title' => get_string('view_on_youtube', 'zoomyt'),
                'class' => 'text-muted small',
            ]);
        }

        // Description - editable.
        $desctext = !empty($video->description) ? s(substr($video->description, 0, 100)) . (strlen($video->description) > 100 ? '...' : '') : '-';
        $descriptioncell = html_writer::span(
            $desctext,
            'editable-description',
            [
                'data-videoid' => $video->id,
                'data-field' => 'description',
                'title' => get_string('click_to_edit', 'zoomyt'),
                'style' => 'cursor: pointer; border-bottom: 1px dashed #007bff;',
            ]
        );

        // Status with badges for visibility and captions.
        $statusbadges = [];

        // YouTube visibility badge.
        $visibilityclass = 'badge-secondary';
        $visibilitytext = ucfirst($video->visibility ?? 'unknown');
        if ($video->visibility === 'public') {
            $visibilityclass = 'badge-success';
        } else if ($video->visibility === 'unlisted') {
            $visibilityclass = 'badge-info';
        } else if ($video->visibility === 'private') {
            $visibilityclass = 'badge-warning';
        }
        $statusbadges[] = html_writer::span($visibilitytext, 'badge ' . $visibilityclass);

        // Caption language badges.
        $captionlangs = !empty($video->caption_languages) ? explode(',', $video->caption_languages) : [];
        foreach ($captionlangs as $lang) {
            $lang = trim(strtoupper($lang));
            if ($lang) {
                $statusbadges[] = html_writer::span('CC ' . $lang, 'badge badge-dark', ['title' => get_string('captions_available', 'zoomyt')]);
            }
        }

        $statuscell = implode(' ', $statusbadges);

        // Upload status.
        if ($video->status !== 'uploaded') {
            $statusclass = 'badge-secondary';
            if ($video->status === 'failed') {
                $statusclass = 'badge-danger';
            } else if (in_array($video->status, ['downloading', 'uploading'])) {
                $statusclass = 'badge-warning';
            }
            $statuscell .= ' ' . html_writer::span($video->status_label, 'badge ' . $statusclass);

            if ($video->status === 'failed' && $video->error_message) {
                $statuscell .= html_writer::tag('small', ' ' . $video->error_message, ['class' => 'text-danger d-block']);
            }
        }

        // Student visibility.
        $visibletext = $video->visible ? get_string('video_visible', 'zoomyt') : get_string('video_hidden', 'zoomyt');
        $visibleclass = $video->visible ? 'text-success' : 'text-muted';
        $visiblecell = html_writer::span($visibletext, $visibleclass);

        // Actions.
        $actions = [];
        $toggleurl = new moodle_url('/mod/zoomyt/manage_recordings.php', [
            'id' => $id,
            'action' => 'togglevisibility',
            'videoid' => $video->id,
            'sesskey' => sesskey(),
        ]);
        $toggleicon = $video->visible ? 'fa-eye-slash' : 'fa-eye';
        $toggletitle = get_string('toggle_video_visibility', 'zoomyt');
        $actions[] = html_writer::link($toggleurl, '<i class="fa ' . $toggleicon . '"></i>', [
            'title' => $toggletitle,
            'class' => 'btn btn-sm btn-outline-secondary',
        ]);

        if ($video->has_youtube) {
            $actions[] = html_writer::link($video->youtube_url, '<i class="fa fa-external-link"></i>', [
                'target' => '_blank',
                'title' => get_string('view_on_youtube', 'zoomyt'),
                'class' => 'btn btn-sm btn-outline-primary',
            ]);

            $deleteyturl = new moodle_url('/mod/zoomyt/manage_recordings.php', [
                'id' => $id,
                'action' => 'deleteyoutube',
                'videoid' => $video->id,
                'sesskey' => sesskey(),
            ]);
            $actions[] = html_writer::link($deleteyturl, '<i class="fa fa-trash"></i> ' . get_string('delete_from_youtube', 'zoomyt'), [
                'title' => get_string('delete_from_youtube', 'zoomyt'),
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => "return confirm('" . get_string('delete_from_youtube_confirm', 'zoomyt') . "');",
            ]);
        }

        if ($video->status === 'failed') {
            $retryurl = new moodle_url('/mod/zoomyt/manage_recordings.php', [
                'id' => $id,
                'action' => 'retryupload',
                'videoid' => $video->id,
                'sesskey' => sesskey(),
            ]);
            $actions[] = html_writer::link($retryurl, '<i class="fa fa-refresh"></i> ' . get_string('retry_upload', 'zoomyt'), [
                'title' => get_string('retry_upload', 'zoomyt'),
                'class' => 'btn btn-sm btn-outline-warning',
            ]);
        }

        // Transcript column - show download links for available transcripts.
        $transcriptcell = '';
        if ($video->has_transcripts && !empty($video->transcripts)) {
            // Show download links for each transcript file.
            $transcriptlinks = [];
            foreach ($video->transcripts as $transcript) {
                // Add download attribute to trigger save dialog.
                $transcriptlinks[] = html_writer::link(
                    $transcript['url'],
                    '<i class="fa fa-download"></i> ' . $transcript['lang_upper'],
                    [
                        'class' => 'badge badge-info',
                        'download' => $transcript['filename'],
                        'title' => get_string('download_transcript', 'zoomyt'),
                    ]
                );
            }
            $transcriptcell = implode(' ', $transcriptlinks);
        } else if ($video->has_youtube) {
            // Transcripts not yet downloaded - will be fetched on next sync.
            $transcriptcell = html_writer::span(
                get_string('pending_sync', 'zoomyt'),
                'text-muted small',
                ['title' => get_string('transcripts_sync_hint', 'zoomyt')]
            );
        } else {
            $transcriptcell = '-';
        }

        $row->cells = [
            new html_table_cell($titlecell),
            new html_table_cell($descriptioncell),
            $video->session_date,
            new html_table_cell($video->duration),
            new html_table_cell($statuscell),
            new html_table_cell($transcriptcell),
            new html_table_cell($visiblecell),
            implode(' ', $actions),
        ];

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Add inline edit modal.
    echo '
    <div class="modal fade" id="editVideoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . get_string('edit_video', 'zoomyt') . '</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-video-id">
                    <div class="form-group">
                        <label for="edit-video-title">' . get_string('title', 'zoomyt') . '</label>
                        <input type="text" class="form-control" id="edit-video-title">
                    </div>
                    <div class="form-group">
                        <label for="edit-video-description">' . get_string('description') . '</label>
                        <textarea class="form-control" id="edit-video-description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">' . get_string('cancel') . '</button>
                    <button type="button" class="btn btn-primary" id="save-video-btn">' . get_string('savechanges') . '</button>
                </div>
            </div>
        </div>
    </div>';

    // Add JavaScript for inline editing.
    $PAGE->requires->js_amd_inline('
        require(["jquery"], function($) {
            var sesskey = "' . sesskey() . '";
            var ajaxurl = M.cfg.wwwroot + "/mod/zoomyt/ajax_video.php";

            // Click handler for editable fields.
            $(".editable-title, .editable-description").on("click", function() {
                var videoid = $(this).data("videoid");
                
                // Fetch current data.
                $.post(ajaxurl, {
                    action: "get",
                    videoid: videoid,
                    sesskey: sesskey
                }, function(response) {
                    if (response.success) {
                        $("#edit-video-id").val(response.video.id);
                        $("#edit-video-title").val(response.video.title);
                        $("#edit-video-description").val(response.video.description);
                        $("#editVideoModal").modal("show");
                    } else {
                        alert(response.message);
                    }
                }, "json");
            });

            // Save button handler.
            $("#save-video-btn").on("click", function() {
                var videoid = $("#edit-video-id").val();
                var title = $("#edit-video-title").val();
                var description = $("#edit-video-description").val();

                $.post(ajaxurl, {
                    action: "update",
                    videoid: videoid,
                    title: title,
                    description: description,
                    sesskey: sesskey
                }, function(response) {
                    if (response.success) {
                        // Update the table row.
                        var row = $("#video-row-" + videoid);
                        row.find(".editable-title").text(response.title);
                        var descPreview = response.description ? response.description.substring(0, 100) + (response.description.length > 100 ? "..." : "") : "-";
                        row.find(".editable-description").text(descPreview);
                        $("#editVideoModal").modal("hide");
                    } else {
                        alert(response.message);
                    }
                }, "json");
            });
        });
    ');
}

// Past Zoom Sessions Section.
echo $OUTPUT->heading(get_string('sessions', 'zoomyt'), 3);

if (empty($meetingdata)) {
    echo html_writer::tag('p', get_string('nosessions', 'zoomyt'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('date'),
        get_string('duration', 'zoomyt'),
        get_string('zoom_recording_status', 'zoomyt'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($meetingdata as $meeting) {
        $row = new html_table_row();

        // Recording status.
        if ($meeting['has_recording']) {
            $recstatus = html_writer::span(
                get_string('recording_available', 'zoomyt') . ' (' . implode(', ', array_unique($meeting['recording_types'])) . ')',
                'badge badge-success'
            );
        } else {
            $recstatus = html_writer::span(get_string('recording_not_available', 'zoomyt'), 'badge badge-secondary');
        }

        $row->cells = [
            $meeting['topic'],
            userdate($meeting['start_time'], get_string('strftimedatetime')),
            format_time($meeting['duration'] * 60),
            $recstatus,
        ];

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
