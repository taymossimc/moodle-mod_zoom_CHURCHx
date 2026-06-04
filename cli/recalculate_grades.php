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
 * CLI tool to (re)calculate aggregate multi-session participation grades for zoomyt activities.
 *
 * Useful for backfilling grades for sessions that were imported before aggregate grading was
 * enabled (the normal recalculation only runs when new attendance rows are imported).
 *
 * Examples:
 *   php mod/zoomyt/cli/recalculate_grades.php --cmid=17272 --dry-run
 *   php mod/zoomyt/cli/recalculate_grades.php --cmid=17272
 *   php mod/zoomyt/cli/recalculate_grades.php --instance=10
 *   php mod/zoomyt/cli/recalculate_grades.php --course=627
 *
 * @package    mod_zoomyt
 * @copyright  2026 ChurchX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'cmid' => false,
        'instance' => false,
        'course' => false,
        'dry-run' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
        'd' => 'dry-run',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || (empty($options['cmid']) && empty($options['instance']) && empty($options['course']))) {
    $help = "Recalculate aggregate multi-session participation grades for zoomyt activities.

Options:
  --cmid=N       Recalculate the activity with this course module id.
  --instance=N   Recalculate the activity with this zoomyt instance id.
  --course=N     Recalculate every zoomyt activity in this course id.
  -d, --dry-run  Show the grades that would be assigned, without writing anything.
  -h, --help     Print this help.

Example:
  php mod/zoomyt/cli/recalculate_grades.php --cmid=17272 --dry-run
";
    cli_writeln($help);
    exit(0);
}

$dryrun = !empty($options['dry-run']);

// Resolve the set of zoomyt instances to process.
$instances = [];
if (!empty($options['cmid'])) {
    $cm = get_coursemodule_from_id('zoomyt', (int) $options['cmid'], 0, false, MUST_EXIST);
    $instances[] = $DB->get_record('zoomyt', ['id' => $cm->instance], '*', MUST_EXIST);
} else if (!empty($options['instance'])) {
    $instances[] = $DB->get_record('zoomyt', ['id' => (int) $options['instance']], '*', MUST_EXIST);
} else if (!empty($options['course'])) {
    $instances = array_values($DB->get_records('zoomyt', ['course' => (int) $options['course']]));
    if (empty($instances)) {
        cli_error('No zoomyt activities found in course ' . (int) $options['course']);
    }
}

$task = new \mod_zoomyt\task\get_meeting_reports();
$task->debuggingenabled = false;

cli_writeln($dryrun ? '== DRY RUN (no grades will be written) ==' : '== Applying grades ==');

foreach ($instances as $zoom) {
    cli_writeln('');
    cli_writeln(sprintf('Activity "%s" (instance %d, course %d, method %s)',
        $zoom->name, $zoom->id, $zoom->course, $zoom->grading_method ?: '(site default)'));

    $report = $task->recalculate_aggregate_grades($zoom, $dryrun);

    if (empty($report)) {
        cli_writeln('  No grade changes (activity not gradable, no sessions imported, or grades already up to date).');
        continue;
    }

    foreach ($report as $userid => $change) {
        $old = is_null($change['old']) ? '(none)' : format_float((float) $change['old'], 5);
        $new = format_float((float) $change['new'], 5);
        cli_writeln(sprintf('  user %d: %s -> %s', $userid, $old, $new));
    }
    cli_writeln(sprintf('  %d grade(s) %s.', count($report), $dryrun ? 'would change' : 'updated'));
}

cli_writeln('');
cli_writeln('Done.');
exit(0);
