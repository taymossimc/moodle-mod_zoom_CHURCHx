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
 * Web page to run the Zoom meeting reports task directly (no CLI dependency).
 *
 * Previously this used exec() to call the CLI script, which failed when the
 * PHP CLI was missing the intl extension. Now it runs the task in-process.
 *
 * @package    mod_zoomyt
 * @copyright  2020 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

$courseid = required_param('courseid', PARAM_INT);
$startdate = optional_param('start', date('Y-m-d', strtotime('-30 days')), PARAM_ALPHANUMEXT);
$enddate = optional_param('end', date('Y-m-d'), PARAM_ALPHANUMEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course);

$context = context_course::instance($course->id);
require_capability('mod/zoomyt:view', $context);
require_capability('mod/zoomyt:refreshsessions', $context);

// Set up the moodle page.
$PAGE->set_url('/mod/zoomyt/console/get_meeting_report.php', [
    'courseid' => $courseid,
    'start' => $startdate,
    'end' => $enddate,
]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('getmeetingreports', 'mod_zoomyt'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('getmeetingreports', 'mod_zoomyt'));

echo '<pre>';

// Capture mtrace output.
ob_start();

// Find host UUIDs for this course.
$hostuuids = $DB->get_fieldset_select('zoomyt', 'DISTINCT host_id', 'course = :courseid', ['courseid' => $courseid]);

if (empty($hostuuids)) {
    mtrace('No Zoom activities found for this course.');
} else {
    mtrace(sprintf('Found %d host(s) for course %d', count($hostuuids), $courseid));
    mtrace(sprintf('Date range: %s to %s', $startdate, $enddate));
    mtrace('');

    try {
        // Run the meeting reports task directly in-process.
        $meetingtask = new \mod_zoomyt\task\get_meeting_reports();
        $meetingtask->execute($startdate, $enddate, $hostuuids);
        mtrace('');
        mtrace('DONE!');
    } catch (Exception $e) {
        mtrace('');
        mtrace('ERROR: ' . $e->getMessage());
        mtrace('');
        mtrace('Stack trace:');
        mtrace($e->getTraceAsString());
    }
}

$output = ob_get_clean();
echo htmlspecialchars($output);

echo '</pre>';

// Add a back link.
$backurl = new moodle_url('/mod/zoomyt/index.php', ['id' => $courseid]);
echo html_writer::link($backurl, get_string('back'), ['class' => 'btn btn-primary mt-3']);

echo $OUTPUT->footer();
