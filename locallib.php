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
 * Internal library of functions for module zoom
 *
 * All the zoom specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_zoomyt
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/zoomyt/lib.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/webservice_exception.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/api_limit_exception.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/bad_request_exception.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/not_found_exception.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/retry_failed_exception.php');
require_once($CFG->dirroot . '/mod/zoomyt/classes/webservice.php');

// Constants.
// Audio options.
define('ZOOM_AUDIO_TELEPHONY', 'telephony');
define('ZOOM_AUDIO_VOIP', 'voip');
define('ZOOM_AUDIO_BOTH', 'both');
// Meeting types.
define('ZOOM_INSTANT_MEETING', 1);
define('ZOOM_SCHEDULED_MEETING', 2);
define('ZOOM_RECURRING_MEETING', 3);
define('ZOOM_SCHEDULED_WEBINAR', 5);
define('ZOOM_RECURRING_WEBINAR', 6);
define('ZOOM_RECURRING_FIXED_MEETING', 8);
define('ZOOM_RECURRING_FIXED_WEBINAR', 9);
// Meeting status.
define('zoomyt_meeting_EXPIRED', 0);
define('zoomyt_meeting_EXISTS', 1);

// Number of meetings per page from zoom's get user report.
define('ZOOM_DEFAULT_RECORDS_PER_CALL', 30);
define('ZOOM_MAX_RECORDS_PER_CALL', 300);
// User types. Numerical values from Zoom API.
define('ZOOM_USER_TYPE_BASIC', 1);
define('ZOOM_USER_TYPE_PRO', 2);
define('ZOOM_USER_TYPE_CORP', 3);
define('zoomyt_meeting_NOT_FOUND_ERROR_CODE', 3001);
define('ZOOM_USER_NOT_FOUND_ERROR_CODE', 1001);
define('ZOOM_INVALID_USER_ERROR_CODE', 1120);
// Webinar options.
define('ZOOM_WEBINAR_DISABLE', 0);
define('ZOOM_WEBINAR_SHOWONLYIFLICENSE', 1);
define('ZOOM_WEBINAR_ALWAYSSHOW', 2);
// Encryption type options.
define('ZOOM_ENCRYPTION_DISABLE', 0);
define('ZOOM_ENCRYPTION_SHOWONLYIFPOSSIBLE', 1);
define('ZOOM_ENCRYPTION_ALWAYSSHOW', 2);
// Encryption types. String values for Zoom API.
define('ZOOM_ENCRYPTION_TYPE_ENHANCED', 'enhanced_encryption');
define('ZOOM_ENCRYPTION_TYPE_E2EE', 'e2ee');
// Alternative hosts options.
define('ZOOM_ALTERNATIVEHOSTS_DISABLE', 0);
define('ZOOM_ALTERNATIVEHOSTS_INPUTFIELD', 1);
define('ZOOM_ALTERNATIVEHOSTS_PICKER', 2);
// Scheduling privilege options.
define('ZOOM_SCHEDULINGPRIVILEGE_DISABLE', 0);
define('ZOOM_SCHEDULINGPRIVILEGE_ENABLE', 1);
// All meetings options.
define('ZOOM_ALLMEETINGS_DISABLE', 0);
define('ZOOM_ALLMEETINGS_ENABLE', 1);
// Download iCal options.
define('ZOOM_DOWNLOADICAL_DISABLE', 0);
define('ZOOM_DOWNLOADICAL_ENABLE', 1);
// Capacity warning options.
define('ZOOM_CAPACITYWARNING_DISABLE', 0);
define('ZOOM_CAPACITYWARNING_ENABLE', 1);
// Recurrence type options.
define('ZOOM_RECURRINGTYPE_NOTIME', 0);
define('ZOOM_RECURRINGTYPE_DAILY', 1);
define('ZOOM_RECURRINGTYPE_WEEKLY', 2);
define('ZOOM_RECURRINGTYPE_MONTHLY', 3);
// Recurring monthly repeat options.
define('ZOOM_MONTHLY_REPEAT_OPTION_DAY', 1);
define('ZOOM_MONTHLY_REPEAT_OPTION_WEEK', 2);
// Recurring end date options.
define('ZOOM_END_DATE_OPTION_BY', 1);
define('ZOOM_END_DATE_OPTION_AFTER', 2);
// API endpoint options.
define('ZOOM_API_ENDPOINT_EU', 'eu');
define('ZOOM_API_ENDPOINT_GLOBAL', 'global');
define('ZOOM_API_URL_EU', 'https://eu01api-www4local.zoom.us/v2/');
define('ZOOM_API_URL_GLOBAL', 'https://api.zoom.us/v2/');
// Auto-recording options.
define('ZOOM_AUTORECORDING_NONE', 'none');
define('ZOOM_AUTORECORDING_USERDEFAULT', 'userdefault');
define('ZOOM_AUTORECORDING_LOCAL', 'local');
define('ZOOM_AUTORECORDING_CLOUD', 'cloud');
// Registration options.
define('ZOOM_REGISTRATION_AUTOMATIC', 0);
define('ZOOM_REGISTRATION_MANUAL', 1);
define('ZOOM_REGISTRATION_OFF', 2);

/**
 * Write a row to the zoomyt_provision_log table for diagnosing teacher provisioning.
 *
 * @param string $action Short label for the step being attempted.
 * @param string $result 'ok', 'skip', or 'error'.
 * @param string|null $message Human-readable detail or error text.
 * @param string|null $email Email address being provisioned.
 * @param int|null $userid Moodle user ID.
 * @param int|null $courseid Course ID.
 * @param int|null $meetingid Zoom meeting ID.
 */
function zoomyt_provision_log($action, $result, $message = null, $email = null, $userid = null, $courseid = null, $meetingid = null) {
    global $DB;
    try {
        $record = new stdClass();
        $record->timecreated = time();
        $record->action = substr($action, 0, 100);
        $record->result = substr($result, 0, 20);
        $record->message = $message;
        $record->email = $email;
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->meetingid = $meetingid;
        $DB->insert_record('zoomyt_provision_log', $record, false);
    } catch (\Exception $e) {
        // Never let logging break the main flow.
    }
}

/**
 * Terminate the current script with a fatal error.
 *
 * Adapted from core_renderer's fatal_error() method. Needed because throwing errors with HTML links in them will convert links
 * to text using htmlentities. See MDL-66161 - Reflected XSS possible from some fatal error messages.
 *
 * So need custom error handler for fatal Zoom errors that have links to help people.
 *
 * @param string $errorcode The name of the string from error.php to print
 * @param string $module name of module
 * @param string $continuelink The url where the user will be prompted to continue.
 *                             If no url is provided the user will be directed to
 *                             the site index page.
 * @param mixed $a Extra words and phrases that might be required in the error string
 */
function zoomyt_fatal_error($errorcode, $module = '', $continuelink = '', $a = null) {
    global $CFG, $COURSE, $OUTPUT, $PAGE;

    $output = '';
    $obbuffer = '';

    // Assumes that function is run before output is generated.
    if ($OUTPUT->has_started()) {
        // If not then have to default to standard error.
        throw new moodle_exception($errorcode, $module, $continuelink, $a);
    }

    $PAGE->set_heading($COURSE->fullname);
    $output .= $OUTPUT->header();

    // Output message without messing with HTML content of error.
    $message = '<p class="errormessage">' . get_string($errorcode, $module, $a) . '</p>';

    $output .= $OUTPUT->box($message, 'errorbox alert alert-danger', null, ['data-rel' => 'fatalerror']);

    if ($CFG->debugdeveloper) {
        if (!empty($debuginfo)) {
            $debuginfo = s($debuginfo); // Removes all nasty JS.
            $debuginfo = str_replace("\n", '<br />', $debuginfo); // Keep newlines.
            $output .= $OUTPUT->notification('<strong>Debug info:</strong> ' . $debuginfo, 'notifytiny');
        }

        if (!empty($backtrace)) {
            $output .= $OUTPUT->notification('<strong>Stack trace:</strong> ' . format_backtrace($backtrace), 'notifytiny');
        }

        if ($obbuffer !== '') {
            $output .= $OUTPUT->notification('<strong>Output buffer:</strong> ' . s($obbuffer), 'notifytiny');
        }
    }

    if (!empty($continuelink)) {
        $output .= $OUTPUT->continue_button($continuelink);
    }

    $output .= $OUTPUT->footer();

    // Padding to encourage IE to display our error page, rather than its own.
    $output .= str_repeat(' ', 512);

    echo $output;

    exit(1); // General error code.
}

/**
 * Get course/cm/zoom objects from url parameters, and check for login/permissions.
 *
 * @return array Array of ($course, $cm, $zoom)
 */
function zoomyt_get_instance_setup() {
    global $DB;

    $id = optional_param('id', 0, PARAM_INT); // Course_module ID.
    $n = optional_param('n', 0, PARAM_INT);  // Zoom instance ID.

    if ($id) {
        $cm = get_coursemodule_from_id('zoomyt', $id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $zoom = $DB->get_record('zoomyt', ['id' => $cm->instance], '*', MUST_EXIST);
    } else if ($n) {
        $zoom = $DB->get_record('zoomyt', ['id' => $n], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $zoom->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('zoomyt', $zoom->id, $course->id, false, MUST_EXIST);
    } else {
        throw new moodle_exception('zoomerr_id_missing', 'mod_zoomyt');
    }

    require_login($course, true, $cm);

    $context = context_module::instance($cm->id);
    require_capability('mod/zoomyt:view', $context);

    return [$course, $cm, $zoom];
}

/**
 * Retrieves information for a meeting.
 *
 * @param int $zoomid
 * @return array information about the meeting
 */
function zoomyt_get_sessions_for_display($zoomid) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/moodlelib.php');

    $sessions = [];
    $format = get_string('strftimedatetimeshort', 'langconfig');

    // Sort sessions in start_time ascending order.
    $instances = $DB->get_records('zoomyt_meeting_details', ['zoomid' => $zoomid], 'start_time');

    foreach ($instances as $instance) {
        // The meeting uuid, not the participant's uuid.
        $uuid = $instance->uuid;
        $participantlist = zoomyt_get_participants_report($instance->id);
        $sessions[$uuid]['participants'] = $participantlist;

        $uniquevalues = [];
        $uniqueparticipantcount = 0;
        foreach ($participantlist as $participant) {
            $unique = true;
            if ($participant->uuid != null) {
                if (array_key_exists($participant->uuid, $uniquevalues)) {
                    $unique = false;
                } else {
                    $uniquevalues[$participant->uuid] = true;
                }
            }

            if ($participant->userid != null) {
                if (!$unique || !array_key_exists($participant->userid, $uniquevalues)) {
                    $uniquevalues[$participant->userid] = true;
                } else {
                    $unique = false;
                }
            }

            if ($participant->user_email != null) {
                if (!$unique || !array_key_exists($participant->user_email, $uniquevalues)) {
                    $uniquevalues[$participant->user_email] = true;
                } else {
                    $unique = false;
                }
            }

            $uniqueparticipantcount += $unique ? 1 : 0;
        }

        $sessions[$uuid]['count'] = $uniqueparticipantcount;
        $sessions[$uuid]['topic'] = $instance->topic;

        // Calculate actual session duration from participant join/leave times.
        // This gives the real elapsed time from first join to last leave,
        // which is more accurate than the Zoom API duration (which may only
        // reflect the original host's session time).
        $earliestjoin = null;
        $latestleave = null;
        foreach ($participantlist as $participant) {
            if (!empty($participant->join_time)) {
                if ($earliestjoin === null || $participant->join_time < $earliestjoin) {
                    $earliestjoin = $participant->join_time;
                }
            }
            if (!empty($participant->leave_time)) {
                if ($latestleave === null || $participant->leave_time > $latestleave) {
                    $latestleave = $participant->leave_time;
                }
            }
        }

        if ($earliestjoin !== null && $latestleave !== null && $latestleave > $earliestjoin) {
            // Use participant-derived duration (in seconds).
            $sessions[$uuid]['duration'] = $latestleave - $earliestjoin;
            $sessions[$uuid]['starttime'] = userdate($earliestjoin, $format);
            $sessions[$uuid]['endtime'] = userdate($latestleave, $format);
        } else {
            // Fallback to Zoom API values.
            $sessions[$uuid]['duration'] = $instance->duration;
            $sessions[$uuid]['starttime'] = userdate($instance->start_time, $format);
            $sessions[$uuid]['endtime'] = userdate($instance->start_time + $instance->duration, $format);
        }
    }

    return $sessions;
}

/**
 * Get the next occurrence of a meeting.
 *
 * @param stdClass $zoom
 * @return int The timestamp of the next occurrence of a recurring meeting or
 *             0 if this is a recurring meeting without fixed time or
 *             the timestamp of the meeting start date if this isn't a recurring meeting.
 */
function zoomyt_get_next_occurrence($zoom) {
    global $DB;

    // Prepare an ad-hoc request cache as this function could be called multiple times throughout a request
    // and we want to avoid to make duplicate DB calls.
    $cacheoptions = [
        'simplekeys' => true,
        'simpledata' => true,
    ];
    $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'zoomyt', 'nextoccurrence', [], $cacheoptions);

    // If the next occurrence wasn't already cached, fill the cache.
    $cachednextoccurrence = $cache->get($zoom->id);
    if ($cachednextoccurrence === false) {
        // If this isn't a recurring meeting.
        if (!$zoom->recurring) {
            // Use the meeting start time.
            $cachednextoccurrence = $zoom->start_time;

            // Or if this is a recurring meeting without fixed time.
        } else if ($zoom->recurrence_type == ZOOM_RECURRINGTYPE_NOTIME) {
            // Use 0 as there isn't anything better to return.
            $cachednextoccurrence = 0;

            // Otherwise we have a recurring meeting with a recurrence schedule.
        } else {
            // Get the calendar event of the next occurrence.
            $selectclause = "modulename = :modulename AND instance = :instance AND (timestart + timeduration) >= :now";
            $selectparams = ['modulename' => 'zoomyt', 'instance' => $zoom->id, 'now' => time()];
            $nextoccurrence = $DB->get_records_select('event', $selectclause, $selectparams, 'timestart ASC', 'timestart', 0, 1);

            // If we haven't got a single event.
            if (empty($nextoccurrence)) {
                // Use 0 as there isn't anything better to return.
                $cachednextoccurrence = 0;
            } else {
                // Use the timestamp of the event.
                $nextoccurenceobject = reset($nextoccurrence);
                $cachednextoccurrence = $nextoccurenceobject->timestart;
            }
        }

        // Store the next occurrence into the cache.
        $cache->set($zoom->id, $cachednextoccurrence);
    }

    // Return the next occurrence.
    return $cachednextoccurrence;
}

/**
 * Determine if a zoom meeting is in progress, is available, and/or is finished.
 *
 * @param stdClass $zoom
 * @return array Array of booleans: [in progress, available, finished].
 */
/**
 * Get the effective join before start time for a zoom activity.
 *
 * Checks instance setting first, then category setting, then global setting.
 *
 * @param object $zoom The zoom activity record.
 * @return int Minutes before start that participants can join.
 */
function zoomyt_get_effective_joinbeforestart($zoom) {
    global $CFG, $DB;

    // Check instance-level setting first.
    if (isset($zoom->joinbeforestart) && $zoom->joinbeforestart !== null && $zoom->joinbeforestart !== '') {
        return (int)$zoom->joinbeforestart;
    }

    // Check category-level setting.
    require_once($CFG->dirroot . '/mod/zoomyt/classes/category_settings.php');
    $course = $DB->get_record('course', ['id' => $zoom->course], 'category');
    if ($course) {
        $catsettings = \mod_zoomyt\category_settings::get_for_course($zoom->course);
        $rawsettings = $catsettings->get_raw_settings();
        if ($rawsettings && isset($rawsettings->joinbeforestart) && $rawsettings->joinbeforestart !== null) {
            return (int)$rawsettings->joinbeforestart;
        }
    }

    // Fall back to global setting.
    $config = get_config('zoomyt');
    return (int)($config->firstabletojoin ?? 15);
}

function zoomyt_get_state($zoom, $ishost = false, $isteacher = false) {
    // Get plugin config.
    $config = get_config('zoomyt');

    // Get the current time as calculation basis.
    $now = time();

    // If this is a recurring meeting with a recurrence schedule.
    if ($zoom->recurring && $zoom->recurrence_type != ZOOM_RECURRINGTYPE_NOTIME) {
        // Get the next occurrence start time.
        $starttime = zoomyt_get_next_occurrence($zoom);
    } else {
        // Get the meeting start time.
        $starttime = $zoom->start_time;
    }

    // Check if "join before host" / "join anytime" is enabled.
    $joinbeforehost = !empty($zoom->option_jbh);

    // Determine early access time based on user role.
    if ($ishost || $isteacher) {
        // Hosts and teachers get early access (configurable, default 15 minutes).
        $earlyaccessmins = (int)($config->hostearlyaccess ?? 15);
    } else {
        // Participants use the effective join before start setting.
        $participantaccess = zoomyt_get_effective_joinbeforestart($zoom);

        // If "join before host" is enabled OR early access is set to "anytime" (-1),
        // students get the same early access as teachers.
        if ($joinbeforehost || $participantaccess == -1) {
            $earlyaccessmins = (int)($config->hostearlyaccess ?? 15);
        } else {
            $earlyaccessmins = $participantaccess;
        }
    }

    // Calculate the time when the meeting becomes available.
    $firstavailable = $starttime - ($earlyaccessmins * 60);

    // Calculate the time when the meeting ends to be available,
    // based on the start time and the meeting duration.
    $lastavailable = $starttime + $zoom->duration;

    // Determine if the meeting is in progress (within the availability window).
    $inprogress = ($firstavailable <= $now && $now <= $lastavailable);

    // Determine if its a recurring meeting with no fixed time.
    $isrecurringnotime = $zoom->recurring && $zoom->recurrence_type == ZOOM_RECURRINGTYPE_NOTIME;

    // Determine if the meeting is available.
    // - Recurring meetings with no fixed time are always available.
    // - Otherwise, the meeting must be in progress.
    $available = $isrecurringnotime || $inprogress;

    // Determine if the meeting is finished.
    $finished = !$isrecurringnotime && $now > $lastavailable;

    // Return the requested information.
    return [$inprogress, $available, $finished];
}

/**
 * Get the Zoom id of the currently logged-in user.
 *
 * @param bool $required If true, will error if the user doesn't have a Zoom account.
 * @return string
 */
function zoomyt_get_user_id($required = true) {
    global $USER;

    // v1.8.5 - COMPLETELY BYPASS CACHE - Always look up fresh by email.
    $identifier = zoomyt_get_api_identifier($USER);
    
    // TEMPORARY VISIBLE DEBUG - This will show on the page if the new code is running.
    debugging('ZOOMYT v1.8.5: Looking up Zoom user by email: ' . $identifier, DEBUG_DEVELOPER);
    
    $zoomuserid = false;

    try {
        $zoomuser = zoomyt_webservice()->get_user($identifier);
        if ($zoomuser !== false && isset($zoomuser->id) && ($zoomuser->id !== false)) {
            $zoomuserid = $zoomuser->id;
            debugging('ZOOMYT v1.8.5: Found Zoom user ID: ' . $zoomuserid, DEBUG_DEVELOPER);
        }
        // If user does not have a Zoom account, throw an error.
        if ($required && $zoomuser === false) {
            throw new moodle_exception('zoomerr_usernotfound', 'mod_zoomyt', '', get_config('zoomyt', 'zoomurl'));
        }
    } catch (\Exception $error) {
        debugging('ZOOMYT v1.8.5 Exception: ' . $error->getMessage(), DEBUG_DEVELOPER);
        if ($required) {
            throw $error;
        }
    }

    return $zoomuserid;
}

/**
 * Get the Zoom meeting security settings, including meeting password requirements of the user's master account.
 *
 * @param string|int $identifier The user's email or the user's ID per Zoom API.
 * @return stdClass
 */
function zoomyt_get_meeting_security_settings($identifier) {
    $cache = cache::make('mod_zoomyt', 'zoomytmeetingsecurity');
    $zoommeetingsecurity = $cache->get($identifier);
    if (empty($zoommeetingsecurity)) {
        $zoommeetingsecurity = zoomyt_webservice()->get_account_meeting_security_settings($identifier);
        $cache->set($identifier, $zoommeetingsecurity);
    }

    return $zoommeetingsecurity;
}

/**
 * Check if the error indicates that a meeting is gone.
 *
 * @param moodle_exception $error
 * @return bool
 */
function zoomyt_is_meeting_gone_error($error) {
    // If the meeting's owner/user cannot be found, we consider the meeting to be gone.
    return ($error->zoomerrorcode === ZOOM_MEETING_NOT_FOUND_ERROR_CODE) || zoomyt_is_user_not_found_error($error);
}

/**
 * Check if the error indicates that a user is not found or does not belong to the current account.
 *
 * @param moodle_exception $error
 * @return bool
 */
function zoomyt_is_user_not_found_error($error) {
    return ($error->zoomerrorcode === ZOOM_USER_NOT_FOUND_ERROR_CODE) || ($error->zoomerrorcode === ZOOM_INVALID_USER_ERROR_CODE);
}

/**
 * Return the string parameter for zoomerr_meetingnotfound.
 *
 * @param string $cmid
 * @return stdClass
 */
function zoomyt_meetingnotfound_param($cmid) {
    // Provide links to recreate and delete.
    $recreate = new moodle_url('/mod/zoomyt/recreate.php', ['id' => $cmid, 'sesskey' => sesskey()]);
    $delete = new moodle_url('/course/mod.php', ['delete' => $cmid, 'sesskey' => sesskey()]);

    // Convert links to strings and pass as error parameter.
    $param = new stdClass();
    $param->recreate = $recreate->out();
    $param->delete = $delete->out();

    return $param;
}

/**
 * Get the data of each user for the participants report.
 * @param string $detailsid The meeting ID that you want to get the participants report for.
 * @return array The user data as an array of records (array of arrays).
 */
function zoomyt_get_participants_report($detailsid) {
    global $DB;
    $sql = 'SELECT zmp.id,
                   zmp.name,
                   zmp.userid,
                   zmp.user_email,
                   zmp.join_time,
                   zmp.leave_time,
                   zmp.duration,
                   zmp.uuid
              FROM {zoomyt_meeting_participants} zmp
             WHERE zmp.detailsid = :detailsid
    ';
    $params = [
        'detailsid' => $detailsid,
    ];
    $participants = $DB->get_records_sql($sql, $params);
    return $participants;
}

/**
 * Creates a default passcode from the user's Zoom meeting security settings.
 *
 * @param stdClass $meetingpasswordrequirement
 * @return string passcode
 */
function zoomyt_create_default_passcode($meetingpasswordrequirement) {
    $length = max($meetingpasswordrequirement->length, 6);
    $random = rand(0, pow(10, $length) - 1);
    $passcode = str_pad(strval($random), $length, '0', STR_PAD_LEFT);

    // Get a random set of indexes to replace with non-numberic values.
    $indexes = range(0, $length - 1);
    shuffle($indexes);

    if ($meetingpasswordrequirement->have_letter || $meetingpasswordrequirement->have_upper_and_lower_characters) {
        // Random letter from A-Z.
        $passcode[$indexes[0]] = chr(rand(65, 90));
        // Random letter from a-z.
        $passcode[$indexes[1]] = chr(rand(97, 122));
    }

    if ($meetingpasswordrequirement->have_special_character) {
        $specialchar = '@_*-';
        $passcode[$indexes[2]] = substr(str_shuffle($specialchar), 0, 1);
    }

    return $passcode;
}

/**
 * Creates a description string from the user's Zoom meeting security settings.
 *
 * @param stdClass $meetingpasswordrequirement
 * @return string description of password requirements
 */
function zoomyt_create_passcode_description($meetingpasswordrequirement) {
    $description = '';
    if ($meetingpasswordrequirement->only_allow_numeric) {
        $description .= get_string('password_only_numeric', 'mod_zoomyt') . ' ';
    } else {
        if ($meetingpasswordrequirement->have_letter && !$meetingpasswordrequirement->have_upper_and_lower_characters) {
            $description .= get_string('password_letter', 'mod_zoomyt') . ' ';
        } else if ($meetingpasswordrequirement->have_upper_and_lower_characters) {
            $description .= get_string('password_lower_upper', 'mod_zoomyt') . ' ';
        }

        if ($meetingpasswordrequirement->have_number) {
            $description .= get_string('password_number', 'mod_zoomyt') . ' ';
        }

        if ($meetingpasswordrequirement->have_special_character) {
            $description .= get_string('password_special', 'mod_zoomyt') . ' ';
        } else {
            $description .= get_string('password_allowed_char', 'mod_zoomyt') . ' ';
        }
    }

    if ($meetingpasswordrequirement->length) {
        $description .= get_string('password_length', 'mod_zoomyt', $meetingpasswordrequirement->length) . ' ';
    }

    if ($meetingpasswordrequirement->consecutive_characters_length > 0) {
        $description .= get_string(
            'password_consecutive',
            'mod_zoomyt',
            $meetingpasswordrequirement->consecutive_characters_length - 1
        ) . ' ';
    }

    $description .= get_string('password_max_length', 'mod_zoomyt');
    return $description;
}

/**
 * Creates an array of users who can be selected as alternative host in a given context.
 *
 * @param context $context The context to be used.
 *
 * @return array Array of users (mail => fullname).
 */
function zoomyt_get_selectable_alternative_hosts_list(context $context) {
    // Get selectable alternative host users based on the capability.
    $users = get_enrolled_users($context, 'mod/zoomyt:eligiblealternativehost', 0, 'u.*', 'lastname');

    // Create array of users.
    $selectablealternativehosts = [];

    // Iterate over selectable alternative host users.
    foreach ($users as $u) {
        // Note: Basically, if this is the user's own data row, the data row should be skipped.
        // But this would then not cover the case when a user is scheduling the meeting _for_ another user
        // and wants to be an alternative host himself.
        // As this would have to be handled at runtime in the browser, we just offer all users with the
        // capability as selectable and leave this aspect as possible improvement for the future.
        // At least, Zoom does not care if the user who is the host adds himself as alternative host as well.

        // Verify that the user really has a Zoom account.
        // Furthermore, verify that the user's status is active. Adding a pending or inactive user as alternative host will result
        // in a Zoom API error otherwise.
        $zoomuser = zoomyt_get_user($u->email);
        if ($zoomuser !== false && $zoomuser->status === 'active') {
            // Add user to array of users.
            $selectablealternativehosts[strtolower($u->email)] = fullname($u);
        }
    }

    return $selectablealternativehosts;
}

/**
 * Creates a string of roles who can be selected as alternative host in a given context.
 *
 * @param context $context The context to be used.
 *
 * @return string The string of roles.
 */
function zoomyt_get_selectable_alternative_hosts_rolestring(context $context) {
    // Get selectable alternative host users based on the capability.
    $roles = get_role_names_with_caps_in_context($context, ['mod/zoomyt:eligiblealternativehost']);

    // Compose string.
    $rolestring = implode(', ', $roles);

    return $rolestring;
}

/**
 * Get existing Moodle users from a given set of alternative hosts.
 *
 * @param array $alternativehosts The array of alternative hosts email addresses.
 *
 * @return array The array of existing Moodle user objects.
 */
function zoomyt_get_users_from_alternativehosts(array $alternativehosts) {
    global $DB;

    // Get the existing Moodle user objects from the DB.
    [$insql, $inparams] = $DB->get_in_or_equal($alternativehosts);
    $sql = 'SELECT *
            FROM {user}
            WHERE email ' . $insql . '
            ORDER BY lastname ASC';
    $alternativehostusers = $DB->get_records_sql($sql, $inparams);

    return $alternativehostusers;
}

/**
 * Get non-Moodle users from a given set of alternative hosts.
 *
 * @param array $alternativehosts The array of alternative hosts email addresses.
 *
 * @return array The array of non-Moodle user mail addresses.
 */
function zoomyt_get_nonusers_from_alternativehosts(array $alternativehosts) {
    global $DB;

    // Get the non-Moodle user mail addresses by checking which one does not exist in the DB.
    $alternativehostnonusers = [];
    [$insql, $inparams] = $DB->get_in_or_equal($alternativehosts);
    $sql = 'SELECT email
            FROM {user}
            WHERE email ' . $insql . '
            ORDER BY email ASC';
    $alternativehostusersmails = $DB->get_records_sql($sql, $inparams);
    foreach ($alternativehosts as $ah) {
        if (!array_key_exists($ah, $alternativehostusersmails)) {
            $alternativehostnonusers[] = $ah;
        }
    }

    return $alternativehostnonusers;
}

/**
 * Get the unavailability note based on the Zoom plugin configuration.
 *
 * @param object $zoom The Zoom meeting object.
 * @param bool|null $finished The function needs to know if the meeting is already finished.
 *                       You can provide this information, if already available, to the function.
 *                       Otherwise it will determine it with a small overhead.
 *
 * @return string The unavailability note.
 */
function zoomyt_get_unavailability_note($zoom, $finished = null, $ishost = false, $isteacher = false) {
    // Get config.
    $config = get_config('zoomyt');

    // Get the plain unavailable string.
    $strunavailable = get_string('unavailable', 'mod_zoomyt');

    // If this is a recurring meeting without fixed time, just use the plain unavailable string.
    if ($zoom->recurring && $zoom->recurrence_type == ZOOM_RECURRINGTYPE_NOTIME) {
        $unavailabilitynote = $strunavailable;

        // Otherwise we add some more information to the unavailable string.
    } else {
        // If we don't have the finished information yet, get it with a small overhead.
        if ($finished === null) {
            [$inprogress, $available, $finished] = zoomyt_get_state($zoom, $ishost, $isteacher);
        }

        // If this meeting is still pending.
        if ($finished !== true) {
            // For hosts/teachers, show when they can start.
            if ($ishost || $isteacher) {
                $earlyaccessmins = (int)($config->hostearlyaccess ?? 15);
                $unavailabilitynote = $strunavailable . '<br />' .
                    get_string('unavailableteacherearly', 'mod_zoomyt', ['mins' => $earlyaccessmins]);
            } else {
                // For participants, show when they can join.
                $joinbeforemins = zoomyt_get_effective_joinbeforestart($zoom);
                if (!empty($config->displayleadtime) && $joinbeforemins > 0) {
                    $unavailabilitynote = $strunavailable . '<br />' .
                        get_string('unavailablefirstjoin', 'mod_zoomyt', ['mins' => $joinbeforemins]);
                } else {
                    $unavailabilitynote = $strunavailable . '<br />' . get_string('unavailablenotstartedyet', 'mod_zoomyt');
                }
            }

            // Otherwise, the meeting has finished.
        } else {
            $unavailabilitynote = $strunavailable . '<br />' . get_string('unavailablefinished', 'mod_zoomyt');
        }
    }

    return $unavailabilitynote;
}

/**
 * Gets the meeting capacity of a given Zoom user.
 * Please note: This function does not check if the Zoom user really exists, this has to be checked before calling this function.
 *
 * @param string $zoomhostid The Zoom ID of the host.
 * @param bool $iswebinar The meeting is a webinar.
 *
 * @return int|bool The meeting capacity of the Zoom user or false if the user does not have any meeting capacity at all.
 */
function zoomyt_get_meeting_capacity(string $zoomhostid, bool $iswebinar = false) {
    // Get the 'feature' section of the user's Zoom settings.
    $userfeatures = zoomyt_get_user_settings($zoomhostid)->feature;

    $meetingcapacity = false;

    // If this is a webinar.
    if ($iswebinar === true) {
        // Get the appropriate capacity value.
        if (!empty($userfeatures->webinar_capacity)) {
            $meetingcapacity = $userfeatures->webinar_capacity;
        } else if (!empty($userfeatures->zoom_events_capacity)) {
            $meetingcapacity = $userfeatures->zoom_events_capacity;
        }
    } else {
        // If this is a meeting, get the 'meeting_capacity' value.
        if (!empty($userfeatures->meeting_capacity)) {
            $meetingcapacity = $userfeatures->meeting_capacity;

            // Check if the user has a 'large_meeting' license that has a higher capacity value.
            if (!empty($userfeatures->large_meeting_capacity) && $userfeatures->large_meeting_capacity > $meetingcapacity) {
                $meetingcapacity = $userfeatures->large_meeting_capacity;
            }
        }
    }

    return $meetingcapacity;
}

/**
 * Gets the number of eligible meeting participants in a given context.
 * Please note: This function only covers users who are enrolled into the given context.
 * It does _not_ include users who have the necessary capability on a higher context without being enrolled.
 *
 * @param context $context The context which we want to check.
 *
 * @return int The number of eligible meeting participants.
 */
function zoomyt_get_eligible_meeting_participants(context $context) {
    global $DB;

    // Compose SQL query.
    $sqlsnippets = get_enrolled_with_capabilities_join($context, '', 'mod/zoomyt:view', 0, true);
    $sql = 'SELECT count(DISTINCT u.id)
            FROM {user} u ' . $sqlsnippets->joins . ' WHERE ' . $sqlsnippets->wheres;

    // Run query and count records.
    $eligibleparticipantcount = $DB->count_records_sql($sql, $sqlsnippets->params);

    return $eligibleparticipantcount;
}

/**
 * Get array of alternative hosts from a string.
 *
 * @param string $alternativehoststring Comma (or semicolon) separated list of alternative hosts.
 * @return string[] $alternativehostarray Array of alternative hosts.
 */
function zoomyt_get_alternative_host_array_from_string($alternativehoststring) {
    if (empty($alternativehoststring)) {
        return [];
    }

    // The Zoom API has historically returned either semicolons or commas, so we need to support both.
    $alternativehoststring = str_replace(';', ',', $alternativehoststring);
    $alternativehostarray = array_filter(explode(',', $alternativehoststring));

    // Lowercase email addresses so that we can do case-insensitive comparisons.
    foreach ($alternativehostarray as $key => $value) {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            $alternativehostarray[$key] = strtolower($value);
        }
    }
    return $alternativehostarray;
}

/**
 * Get all custom user profile fields of type text
 *
 * @return array list of user profile fields
 */
function zoomyt_get_user_profile_fields() {
    global $DB;

    $userfields = [];
    $records = $DB->get_records('user_info_field', ['datatype' => 'text']);
    foreach ($records as $record) {
        $userfields[$record->shortname] = $record->name;
    }

    return $userfields;
}

/**
 * Get all valid options for API Identifier field
 *
 * @return array list of all valid options
 */
function zoomyt_get_api_identifier_fields() {
    $options = [
        'email' => get_string('email'),
        'username' => get_string('username'),
        'idnumber' => get_string('idnumber'),
    ];

    $userfields = zoomyt_get_user_profile_fields();
    if (!empty($userfields)) {
        $options += $userfields;
    }

    return $options;
}

/**
 * Get the zoom api identifier
 *
 * @param object $user The user object
 *
 * @return string the value of the identifier
 */
function zoomyt_get_api_identifier($user) {
    // Get the value from the config first.
    $field = get_config('zoomyt', 'apiidentifier');

    // DEBUG: Log the configured field.
    debugging('zoomyt_get_api_identifier: apiidentifier config = ' . var_export($field, true), DEBUG_DEVELOPER);

    $identifier = '';

    // Only try to use the configured field if it's not empty.
    if (!empty($field)) {
        if (isset($user->$field)) {
            // If one of the standard user fields.
            $identifier = $user->$field;
        } else if (isset($user->profile) && isset($user->profile[$field])) {
            // If one of the custom user fields.
            $identifier = $user->profile[$field];
        }
    }

    if (empty($identifier)) {
        // Fallback to email if the field is not set or empty.
        $identifier = $user->email;
    }

    // DEBUG: Log the final identifier.
    debugging('zoomyt_get_api_identifier: returning identifier = ' . $identifier, DEBUG_DEVELOPER);

    return $identifier;
}

/**
 * Creates an iCalendar_event for a Zoom meeting.
 *
 * @param stdClass $event The meeting object.
 * @param string $description The event description.
 *
 * @return iCalendar_event
 */
function zoomyt_helper_icalendar_event($event, $description) {
    global $CFG;

    // Match Moodle's uid format for iCal events.
    $hostaddress = str_replace('http://', '', $CFG->wwwroot);
    $hostaddress = str_replace('https://', '', $hostaddress);
    $uid = $event->id . '@' . $hostaddress;

    $icalevent = new iCalendar_event();
    $icalevent->add_property('uid', $uid); // A unique identifier.
    $icalevent->add_property('summary', $event->name); // Title.
    $icalevent->add_property('dtstamp', Bennu::timestamp_to_datetime()); // Time of creation.
    $icalevent->add_property('last-modified', Bennu::timestamp_to_datetime($event->timemodified));
    $icalevent->add_property('dtstart', Bennu::timestamp_to_datetime($event->timestart)); // Start time.
    $icalevent->add_property('dtend', Bennu::timestamp_to_datetime($event->timestart + $event->timeduration)); // End time.
    $icalevent->add_property('description', $description);
    return $icalevent;
}

/**
 * Get the configured Zoom API URL.
 *
 * @return string The API URL.
 */
function zoomyt_get_api_url() {
    // Get the API endpoint setting.
    $apiendpoint = get_config('zoomyt', 'apiendpoint');

    // Pick the corresponding API URL.
    switch ($apiendpoint) {
        case ZOOM_API_ENDPOINT_EU:
            $apiurl = ZOOM_API_URL_EU;
            break;

        case ZOOM_API_ENDPOINT_GLOBAL:
        default:
            $apiurl = ZOOM_API_URL_GLOBAL;
            break;
    }

    // Return API URL.
    return $apiurl;
}

/**
 * Loads the zoom meeting and passes back a meeting URL
 * after processing events, view completion, grades, and license updates.
 *
 * @param int $id course module id
 * @param object $context moodle context object
 * @param bool $usestarturl
 * @return array $returns contains url object 'nexturl' or string 'error'
 */
function zoomyt_load_meeting($id, $context, $usestarturl = true) {
    global $CFG, $DB, $USER;
    require_once($CFG->libdir . '/gradelib.php');

    $cm = get_coursemodule_from_id('zoomyt', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $zoom = $DB->get_record('zoomyt', ['id' => $cm->instance], '*', MUST_EXIST);

    require_login($course, true, $cm);

    require_capability('mod/zoomyt:view', $context);

    $returns = ['nexturl' => null, 'error' => null];

    // Determine user role for early access.
    $userisrealhost = (zoomyt_get_user_id(false) === $zoom->host_id);
    $alternativehosts = zoomyt_get_alternative_host_array_from_string($zoom->alternative_hosts);
    $userapiidentifier = zoomyt_get_api_identifier($USER);
    if (filter_var($userapiidentifier, FILTER_VALIDATE_EMAIL) !== false) {
        $userapiidentifier = strtolower($userapiidentifier);
    }
    $userishost = ($userisrealhost || in_array($userapiidentifier, $alternativehosts, true));
    $isteacher = has_capability('mod/zoomyt:eligiblealternativehost', $context);

    zoomyt_provision_log('launch_check', 'ok',
        "isteacher={$isteacher}, userishost={$userishost}, userisrealhost={$userisrealhost}, apiident={$userapiidentifier}, althosts=" . ($zoom->alternative_hosts ?? '(empty)'),
        $USER->email, $USER->id, $zoom->course, $zoom->meeting_id);

    // Provision teacher at launch time: ensure they have a Pro license and are an
    // alternative host BEFORE redirecting to Zoom. This prevents the race condition
    // where a teacher launches a meeting but Zoom still sees them as Basic/unlicensed.
    if ($isteacher) {
        $config = get_config('zoomyt');
        if (empty($config->autoaddinstructorsashosts)) {
            zoomyt_provision_log('launch_provision', 'skip', 'autoaddinstructorsashosts is disabled',
                $USER->email, $USER->id, $zoom->course, $zoom->meeting_id);
        } else {
            $teacheremail = strtolower($USER->email);
            zoomyt_provision_log('ensure_zoom_user_start', 'ok', "Calling zoomyt_ensure_zoom_user for {$teacheremail}",
                $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);

            // Step 1: Ensure teacher has a Zoom account with a Pro license.
            $haszoomaccount = zoomyt_ensure_zoom_user($teacheremail, $zoom->course);

            zoomyt_provision_log('ensure_zoom_user_result', $haszoomaccount ? 'ok' : 'error',
                "zoomyt_ensure_zoom_user returned " . ($haszoomaccount ? 'true' : 'false'),
                $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);

            // Step 2: Add as alternative host if not already one.
            if ($haszoomaccount && !$userishost) {
                $existinghosts = $zoom->alternative_hosts ?? '';
                $hostemail = null;
                if (!empty($zoom->host_id)) {
                    try {
                        $hostuser = zoomyt_get_user($zoom->host_id);
                        $hostemail = $hostuser->email ?? null;
                    } catch (moodle_exception $e) {
                        zoomyt_provision_log('get_host_email', 'error', $e->getMessage(),
                            $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);
                    }
                }

                zoomyt_provision_log('merge_alt_hosts', 'ok',
                    "existing='{$existinghosts}', hostemail='{$hostemail}', teacheremail='{$teacheremail}'",
                    $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);

                $newhosts = zoomyt_merge_alternative_hosts($existinghosts, [$teacheremail], $hostemail, $zoom->course);

                zoomyt_provision_log('merge_alt_hosts_result', 'ok',
                    "newhosts='{$newhosts}', changed=" . ($newhosts !== $existinghosts ? 'yes' : 'no'),
                    $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);

                if ($newhosts !== $existinghosts) {
                    if (zoomyt_update_meeting_alternative_hosts($zoom, $newhosts)) {
                        $DB->set_field('zoomyt', 'alternative_hosts', $newhosts, ['id' => $zoom->id]);
                        $zoom->alternative_hosts = $newhosts;

                        $alternativehosts = zoomyt_get_alternative_host_array_from_string($newhosts);
                        $userishost = ($userisrealhost || in_array($userapiidentifier, $alternativehosts, true));

                        zoomyt_provision_log('update_alt_hosts', 'ok',
                            "Updated on Zoom. userishost now={$userishost}",
                            $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);
                    } else {
                        zoomyt_provision_log('update_alt_hosts', 'error',
                            'zoomyt_update_meeting_alternative_hosts returned false',
                            $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);
                    }
                }
            } else if (!$haszoomaccount) {
                zoomyt_provision_log('launch_provision', 'error',
                    'Skipping alt host — could not ensure Zoom account',
                    $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);
            } else {
                zoomyt_provision_log('launch_provision', 'skip',
                    'Teacher is already an alternative host',
                    $teacheremail, $USER->id, $zoom->course, $zoom->meeting_id);
            }
        }
    } else {
        zoomyt_provision_log('launch_provision', 'skip', 'User does not have eligiblealternativehost capability',
            $USER->email, $USER->id, $zoom->course, $zoom->meeting_id);
    }

    // Get meeting state with user role context.
    [$inprogress, $available, $finished] = zoomyt_get_state($zoom, $userishost, $isteacher);

    $userisregistered = false;
    $userisregistering = false;
    if ($zoom->registration != ZOOM_REGISTRATION_OFF) {
        // Check if user already registered.
        $registrantjoinurl = zoomyt_get_registrant_join_url($USER->email, $zoom->meeting_id, $zoom->webinar);
        $userisregistered = !empty($registrantjoinurl);

        // Allow unregistered users to register.
        if (!$userisregistered) {
            $userisregistering = true;
        }
    }

    // If the meeting is not yet available, deny access.
    if (!$available && !$userisregistering) {
        // Get unavailability note with user role context.
        $returns['error'] = zoomyt_get_unavailability_note($zoom, $finished, $userishost, $isteacher);
        return $returns;
    }

    // Check if we should use the start meeting url.
    // Teachers get the start_url (full host control) even if they're not the "real host"
    // (e.g., when a fallback host account was used to create the meeting).
    if (($userisrealhost || $isteacher) && $usestarturl) {
        // If the meeting uses the fallback host, rename it to match the teacher.
        zoomyt_rename_host_for_teacher($zoom->host_id, $USER);
        $starturl = zoomyt_get_start_url($zoom->meeting_id, $zoom->webinar, $zoom->join_url);
        $returns['nexturl'] = new moodle_url($starturl);
    } else {
        $url = $zoom->join_url;
        if ($userisregistered) {
            $url = $registrantjoinurl;
        }

        $unamesetting = get_config('zoomyt', 'unamedisplay');
        switch ($unamesetting) {
            case 'fullname':
            default:
                $unamedisplay = fullname($USER);
                break;

            case 'firstname':
                $unamedisplay = $USER->firstname;
                break;

            case 'idfullname':
                $unamedisplay = '(' . $USER->id . ') ' . fullname($USER);
                break;

            case 'id':
                $unamedisplay = '(' . $USER->id . ')';
                break;
        }

        // Try to send the user email (not guaranteed).
        $returns['nexturl'] = new moodle_url($url, ['uname' => $unamedisplay, 'uemail' => $USER->email]);
    }

    // If the user is pre-registering, skip grading/completion.
    if (!$available && $userisregistering) {
        return $returns;
    }

    // Record user's clicking join.
    \mod_zoomyt\event\join_meeting_button_clicked::create([
        'context' => $context,
        'objectid' => $zoom->id,
        'other' => [
            'cmid' => $id,
            'meetingid' => (int) $zoom->meeting_id,
            'userishost' => $userishost,
        ],
    ])->trigger();

    // Track completion viewed.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // Check the grading method settings.
    if (!empty($zoom->grading_method)) {
        $gradingmethod = $zoom->grading_method;
    } else if ($defaultgrading = get_config('gradingmethod', 'zoomyt')) {
        $gradingmethod = $defaultgrading;
    } else {
        $gradingmethod = 'entry';
    }

    if ($gradingmethod === 'entry') {
        // Check whether user has a grade. If not, then assign full credit to them.
        $gradelist = grade_get_grades($course->id, 'mod', 'zoomyt', $cm->instance, $USER->id);

        // Assign full credits for user who has no grade yet, if this meeting is gradable (i.e. the grade type is not "None").
        if (!empty($gradelist->items) && empty($gradelist->items[0]->grades[$USER->id]->grade)) {
            $grademax = $gradelist->items[0]->grademax;
            $grades = [
                'rawgrade' => $grademax,
                'userid' => $USER->id,
                'usermodified' => $USER->id,
                'dategraded' => '',
                'feedbackformat' => '',
                'feedback' => '',
            ];

            zoomyt_grade_item_update($zoom, $grades);
        }
    } // Otherwise, the get_meetings_report task calculates the grades according to duration.

    // Upgrade host upon joining meeting, if host is not Licensed.
    if ($userishost) {
        $config = get_config('zoomyt');
        if (!empty($config->recycleonjoin)) {
            // Provide license to the meeting's original host.
            zoomyt_webservice()->provide_license($zoom->host_id);

            // Also provide license to the current user if they're an alternative host.
            if (!$userisrealhost) {
                $currentuserzoomid = zoomyt_get_user_id(false);
                if ($currentuserzoomid) {
                    zoomyt_webservice()->provide_license($currentuserzoomid);
                }
            }
        }
    }

    return $returns;
}

/**
 * Fetches a fresh URL that can be used to start the Zoom meeting.
 *
 * @param string $meetingid Zoom meeting ID.
 * @param bool $iswebinar If the session is a webinar.
 * @param string $fallbackurl URL to use if the webservice call fails.
 * @return string Best available URL for starting the meeting.
 */
function zoomyt_get_start_url($meetingid, $iswebinar, $fallbackurl) {
    try {
        $response = zoomyt_webservice()->get_meeting_webinar_info($meetingid, $iswebinar);
        return $response->start_url ?? $response->join_url;
    } catch (moodle_exception $e) {
        // If an exception was thrown, gracefully use the fallback URL.
        return $fallbackurl;
    }
}

/**
 * Get the configured Zoom tracking fields.
 *
 * @return array tracking fields, keys as lower case
 */
function zoomyt_list_tracking_fields() {
    $trackingfields = [];

    // Get the tracking fields configured on the account.
    $response = zoomyt_webservice()->list_tracking_fields();
    if (isset($response->tracking_fields)) {
        foreach ($response->tracking_fields as $trackingfield) {
            $field = str_replace(' ', '_', strtolower($trackingfield->field));
            $trackingfields[$field] = (array) $trackingfield;
        }
    }

    return $trackingfields;
}

/**
 * Trim and lower case tracking fields.
 *
 * @return array tracking fields trimmed, keys as lower case
 */
function zoomyt_clean_tracking_fields() {
    $config = get_config('zoomyt');
    $defaulttrackingfields = explode(',', $config->defaulttrackingfields);
    $trackingfields = [];

    foreach ($defaulttrackingfields as $key => $defaulttrackingfield) {
        $trimmed = trim($defaulttrackingfield);
        if (!empty($trimmed)) {
            $key = str_replace(' ', '_', strtolower($trimmed));
            $trackingfields[$key] = $trimmed;
        }
    }

    return $trackingfields;
}

/**
 * Synchronize tracking field data for a meeting.
 *
 * @param int $zoomid Zoom meeting ID
 * @param array $trackingfields Tracking fields configured in Zoom.
 */
function zoomyt_sync_meeting_tracking_fields($zoomid, $trackingfields) {
    global $DB;

    $tfvalues = [];
    foreach ($trackingfields as $trackingfield) {
        $field = str_replace(' ', '_', strtolower($trackingfield->field));
        $tfvalues[$field] = $trackingfield->value;
    }

    $tfrows = $DB->get_records('zoomyt_tracking_fields', ['meeting_id' => $zoomid]);
    $tfobjects = [];
    foreach ($tfrows as $tfrow) {
        $tfobjects[$tfrow->tracking_field] = $tfrow;
    }

    $defaulttrackingfields = zoomyt_clean_tracking_fields();
    foreach ($defaulttrackingfields as $key => $defaulttrackingfield) {
        $value = $tfvalues[$key] ?? '';
        if (isset($tfobjects[$key])) {
            $tfobject = $tfobjects[$key];
            if ($value === '') {
                $DB->delete_records('zoomyt_tracking_fields', ['meeting_id' => $zoomid, 'tracking_field' => $key]);
            } else if ($tfobject->value !== $value) {
                $tfobject->value = $value;
                $DB->update_record('zoomyt_tracking_fields', $tfobject);
            }
        } else if ($value !== '') {
            $tfobject = new stdClass();
            $tfobject->meeting_id = $zoomid;
            $tfobject->tracking_field = $key;
            $tfobject->value = $value;
            $DB->insert_record('zoomyt_tracking_fields', $tfobject);
        }
    }
}

/**
 * Get all meeting records
 *
 * @return array All zoom meetings stored in the database.
 */
function zoomyt_get_all_meeting_records() {
    global $DB;

    $meetings = [];
    // Only get meetings that exist on zoom.
    $records = $DB->get_records('zoomyt', ['exists_on_zoom' => ZOOM_MEETING_EXISTS]);
    foreach ($records as $record) {
        $meetings[] = $record;
    }

    return $meetings;
}

/**
 * Get all recordings for a particular meeting.
 *
 * @param int $zoomid Optional. The id of the zoom meeting.
 *
 * @return array All the recordings for the zoom meeting.
 */
function zoomyt_get_meeting_recordings($zoomid = null) {
    global $DB;

    $params = [];
    if ($zoomid !== null) {
        $params['zoomid'] = $zoomid;
    }

    $records = $DB->get_records('zoomyt_meeting_recordings', $params);
    $recordings = [];
    foreach ($records as $recording) {
        $recordings[$recording->zoomrecordingid] = $recording;
    }

    return $recordings;
}

/**
 * Get all meeting recordings grouped together.
 *
 * @param int $zoomid Optional. The id of the zoom meeting.
 *
 * @return array All recordings for the zoom meeting grouped together.
 */
function zoomyt_get_meeting_recordings_grouped($zoomid = null) {
    global $DB;

    $params = [];
    if ($zoomid !== null) {
        $params['zoomid'] = $zoomid;
    }

    $records = $DB->get_records('zoomyt_meeting_recordings', $params, 'recordingstart ASC');
    $recordings = [];
    foreach ($records as $recording) {
        $recordings[$recording->meetinguuid][$recording->zoomrecordingid] = $recording;
    }

    return $recordings;
}

/**
 * Singleton for Zoom webservice class.
 *
 * @return \mod_zoomyt\webservice
 */
function zoomyt_webservice() {
    static $service;

    if (empty($service)) {
        $service = new \mod_zoomyt\webservice();
    }

    return $service;
}

/**
 * Helper to get a Zoom user, efficiently.
 *
 * @param string|int $identifier The user's email or the user's ID per Zoom API.
 * @return stdClass|false If user is found, returns a Zoom user object. Otherwise, returns false.
 */
function zoomyt_get_user($identifier) {
    static $users = [];

    if (!isset($users[$identifier])) {
        $users[$identifier] = zoomyt_webservice()->get_user($identifier);
    }

    return $users[$identifier];
}

/**
 * Helper to get Zoom user settings, efficiently.
 *
 * @param string|int $identifier The user's email or the user's ID per Zoom API.
 * @return stdClass|false If user is found, returns a Zoom user object. Otherwise, returns false.
 */
function zoomyt_get_user_settings($identifier) {
    static $settings = [];

    if (!isset($settings[$identifier])) {
        $settings[$identifier] = zoomyt_webservice()->get_user_settings($identifier);
    }

    return $settings[$identifier];
}

/**
 * Get the zoom meeting registrants.
 *
 * @param string $meetingid Zoom meeting ID.
 * @param bool $iswebinar If the session is a webinar.
 * @return stdClass Returns a Zoom object containing the registrants (if found).
 */
function zoomyt_get_meeting_registrants($meetingid, $iswebinar) {
    $response = zoomyt_webservice()->get_meeting_registrants($meetingid, $iswebinar);
    return $response;
}

/**
 * Checks if a user has registered for a meeting/webinar based on their email address.
 *
 * @param string $useremail The email address of a user used to determine if they registered or not.
 * @param string $meetingid Zoom meeting ID.
 * @param bool $iswebinar If the session is a webinar.
 * @return bool Returns whether or not the user has registered for the zoom meeting/webinar based on their email address.
 */
function zoomyt_is_user_registered_for_meeting($useremail, $meetingid, $iswebinar) {
    $registrantjoinurl = zoomyt_get_registrant_join_url($useremail, $meetingid, $iswebinar);
    return !empty($registrantjoinurl);
}

/**
 * Get the join url for a user for the specified meeting/webinar.
 *
 * @param string $useremail The email address of a user used to determine if they registered or not.
 * @param string $meetingid Zoom meeting ID.
 * @param bool $iswebinar If the session is a webinar.
 * @return string|false Returns the join url for the user (based on email address) for the specified meeting (if found).
 */
function zoomyt_get_registrant_join_url($useremail, $meetingid, $iswebinar) {
    $response = zoomyt_get_meeting_registrants($meetingid, $iswebinar);
    if (isset($response->registrants)) {
        foreach ($response->registrants as $registrant) {
            if (strcasecmp($useremail, $registrant->email) == 0) {
                return $registrant->join_url;
            }
        }
    }

    return false;
}

/**
 * Get the display name for a Zoom user.
 * This is wrapped in a function to avoid unnecessary API calls.
 *
 * @param string $zoomuserid Zoom user ID.
 * @return ?string
 */
function zoomyt_get_user_display_name($zoomuserid) {
    try {
        $hostuser = zoomyt_get_user($zoomuserid);

        // Compose Moodle user object for host.
        $hostmoodleuser = new stdClass();
        $hostmoodleuser->firstname = $hostuser->first_name;
        $hostmoodleuser->lastname = $hostuser->last_name;
        $hostmoodleuser->alternatename = '';
        $hostmoodleuser->firstnamephonetic = '';
        $hostmoodleuser->lastnamephonetic = '';
        $hostmoodleuser->middlename = '';

        return fullname($hostmoodleuser);
    } catch (moodle_exception $error) {
        return null;
    }
}

/**
 * Get the email addresses of all course instructors (users with editing teacher role).
 *
 * This function returns email addresses of users who have the capability to add
 * Zoom activities in the course context - typically editing teachers and managers.
 *
 * @param int $courseid The course ID.
 * @param int|null $excludeuserid Optional user ID to exclude (e.g., the meeting host).
 * @return array Array of email addresses.
 */
function zoomyt_get_course_instructor_emails($courseid, $excludeuserid = null) {
    $context = context_course::instance($courseid);

    // Get users eligible to be alternative hosts (teachers + editing teachers).
    $users = get_enrolled_users($context, 'mod/zoomyt:eligiblealternativehost', 0, 'u.id, u.email', 'u.lastname');

    $emails = [];
    foreach ($users as $user) {
        // Skip the excluded user if specified.
        if ($excludeuserid !== null && $user->id == $excludeuserid) {
            continue;
        }
        // Validate email format.
        if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($user->email);
        }
    }

    return array_unique($emails);
}

/**
 * Check if a user exists on the Zoom account.
 *
 * @param string $email The user's email address.
 * @return bool True if the user exists on Zoom.
 */
function zoomyt_user_exists_on_zoom($email) {
    try {
        $user = zoomyt_webservice()->get_user($email);
        return !empty($user);
    } catch (\mod_zoomyt\not_found_exception $e) {
        return false;
    } catch (moodle_exception $e) {
        // For other errors (network, auth, etc), assume user doesn't exist to be safe.
        debugging("ZOOMYT: Error checking Zoom user {$email}: " . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Ensure a Moodle user exists on the Zoom account, creating them and assigning a license if needed.
 *
 * If the admin setting 'autocreatezoomusers' is enabled, users who don't have a Zoom
 * account will be automatically added to the Zoom account. They are initially created
 * as Basic users, and then the license recycling logic (provide_license) is used to
 * upgrade them to Pro by freeing up a license from the least recently active paid user
 * if necessary.
 *
 * @param string $email The user's email address.
 * @param int $courseid The course ID (to look up the Moodle user).
 * @return bool True if the user exists (or was created) on Zoom.
 */
function zoomyt_ensure_zoom_user($email, $courseid) {
    global $DB;

    zoomyt_provision_log('ensure_user_begin', 'ok', "Looking up {$email} on Zoom", $email, null, $courseid);

    try {
        $service = zoomyt_webservice();
    } catch (moodle_exception $e) {
        zoomyt_provision_log('ensure_user_webservice', 'error',
            'Could not init webservice: ' . $e->getMessage(), $email, null, $courseid);
        return false;
    }

    // First check if user already exists on Zoom.
    $existinguser = null;
    try {
        $existinguser = $service->get_user($email);
    } catch (\mod_zoomyt\not_found_exception $e) {
        $existinguser = null;
        zoomyt_provision_log('zoom_user_lookup', 'ok', 'User not found on Zoom (expected, will create)', $email, null, $courseid);
    } catch (moodle_exception $e) {
        zoomyt_provision_log('zoom_user_lookup', 'error',
            'API error looking up user: ' . $e->getMessage(), $email, null, $courseid);
        return false;
    }

    if (!empty($existinguser)) {
        $zoomuserid = $existinguser->id;
        $usertype = $existinguser->type ?? 'unknown';
        zoomyt_provision_log('zoom_user_lookup', 'ok',
            "User exists on Zoom. zoomid={$zoomuserid}, type={$usertype}", $email, null, $courseid);
        try {
            $service->provide_license($zoomuserid);
            zoomyt_provision_log('provide_license', 'ok', 'License ensured for existing user', $email, null, $courseid);
        } catch (\Exception $e) {
            zoomyt_provision_log('provide_license', 'error', $e->getMessage(), $email, null, $courseid);
        }
        return true;
    }

    // Check if auto-creating users is enabled.
    $config = get_config('zoomyt');
    if (empty($config->autocreatezoomusers)) {
        zoomyt_provision_log('autocreate_check', 'skip',
            'autocreatezoomusers is disabled — cannot create Zoom account', $email, null, $courseid);
        return false;
    }

    // Look up the Moodle user record to get their name.
    $moodleuser = $DB->get_record('user', ['email' => $email], 'id, email, firstname, lastname');
    if (!$moodleuser) {
        zoomyt_provision_log('moodle_user_lookup', 'error',
            'No Moodle user found with this email', $email, null, $courseid);
        return false;
    }

    zoomyt_provision_log('autocreate_user', 'ok',
        "Creating Zoom user: {$moodleuser->firstname} {$moodleuser->lastname}", $email, $moodleuser->id, $courseid);

    // Try autoCreate first (instant, no email confirmation needed).
    // Falls back to create (sends invitation email) if autoCreate fails
    // due to managed domain restrictions (common on Pro plans).
    $created = false;
    $createmethod = 'autoCreate';

    try {
        $service->autocreate_user($moodleuser, 'autoCreate', ZOOM_USER_TYPE_BASIC);
        $created = true;
        zoomyt_provision_log('autocreate_user_result', 'ok',
            'Zoom user created via autoCreate', $email, $moodleuser->id, $courseid);
    } catch (moodle_exception $e) {
        $msg = $e->getMessage();

        if (strpos($msg, 'already in the account') !== false) {
            zoomyt_provision_log('autocreate_user_result', 'ok',
                'User already in this Zoom account — treating as success', $email, $moodleuser->id, $courseid);
            return true;
        }

        // "Already been used" means the email is registered on a DIFFERENT Zoom account.
        // We cannot add them to this account or use them as an alternative host.
        if (strpos($msg, 'already been used') !== false) {
            zoomyt_provision_log('autocreate_user_result', 'skip',
                'Email is registered on another Zoom account — cannot add to this account',
                $email, $moodleuser->id, $courseid);
            return false;
        }

        // Domain mismatch (error 1116) or other restriction — fall back to create (invitation).
        $isdomain = (strpos($msg, 'Domain') !== false || strpos($msg, '1116') !== false);
        zoomyt_provision_log('autocreate_fallback', $isdomain ? 'ok' : 'error',
            "autoCreate failed: {$msg}. " . ($isdomain ? 'Falling back to create (invitation).' : 'Attempting create fallback.'),
            $email, $moodleuser->id, $courseid);

        try {
            $service->autocreate_user($moodleuser, 'create', ZOOM_USER_TYPE_BASIC);
            $created = true;
            $createmethod = 'create';
            zoomyt_provision_log('create_user_result', 'ok',
                'Zoom invitation sent via create action — teacher must accept email to activate',
                $email, $moodleuser->id, $courseid);
        } catch (moodle_exception $e2) {
            $msg2 = $e2->getMessage();
            if (strpos($msg2, 'already in the account') !== false) {
                zoomyt_provision_log('create_user_result', 'ok',
                    'User already in this Zoom account — treating as success', $email, $moodleuser->id, $courseid);
                return true;
            }
            zoomyt_provision_log('create_user_result', 'error',
                'Both autoCreate and create failed: ' . $msg2, $email, $moodleuser->id, $courseid);
            return false;
        }
    }

    if (!$created) {
        return false;
    }

    // Fetch the newly created user and check their status.
    try {
        $newuser = $service->get_user($email);
        if (empty($newuser)) {
            zoomyt_provision_log('get_new_user', 'error',
                'Could not fetch newly created user from Zoom', $email, $moodleuser->id, $courseid);
            return false;
        }

        $userstatus = $newuser->status ?? 'unknown';
        zoomyt_provision_log('new_user_status', 'ok',
            "New user zoomid={$newuser->id} (via {$createmethod}), status={$userstatus}, type={$newuser->type}",
            $email, $moodleuser->id, $courseid);

        // Pending users (created via invitation) haven't accepted yet.
        // They can't be used as alternative hosts until they activate their account.
        if ($userstatus === 'pending') {
            zoomyt_provision_log('pending_user', 'skip',
                'User is pending (invitation sent). Cannot be alt host until they accept. '
                . 'The scheduled sync task will add them once active.',
                $email, $moodleuser->id, $courseid);
            return false;
        }

        // Assign a Pro license for active users.
        zoomyt_provision_log('provide_license', 'ok',
            "Upgrading user to Pro", $email, $moodleuser->id, $courseid);
        $service->provide_license($newuser->id);
        zoomyt_provision_log('provide_license_result', 'ok', 'Pro license assigned', $email, $moodleuser->id, $courseid);
    } catch (\Exception $e) {
        zoomyt_provision_log('provide_license', 'error',
            "License assignment after {$createmethod}: " . $e->getMessage(), $email, $moodleuser->id, $courseid);
    }

    return true;
}

/**
 * Look up the Zoom user ID for the fallback host account.
 *
 * @return string|false The Zoom user ID, or false if not configured/found.
 */
function zoomyt_get_fallback_host_id() {
    static $fallbackid = null;
    if ($fallbackid !== null) {
        return $fallbackid ?: false;
    }

    $fallbackemail = get_config('zoomyt', 'fallback_host_email');
    if (empty($fallbackemail)) {
        $fallbackid = '';
        return false;
    }

    try {
        $service = zoomyt_webservice();
        $user = $service->get_user($fallbackemail);
        if (!empty($user) && !empty($user->id)) {
            $fallbackid = $user->id;
            return $fallbackid;
        }
    } catch (\Exception $e) {
        zoomyt_provision_log('fallback_host_lookup', 'error',
            'Could not look up fallback host: ' . $e->getMessage(), $fallbackemail);
    }

    $fallbackid = '';
    return false;
}

/**
 * Resolve which Zoom user ID should host a meeting, with cascading fallback.
 *
 * Tries the teacher's own Zoom identity first. If they're on another Zoom account,
 * still pending, or can't be created, falls back to a configurable generic host account.
 *
 * @param string $useremail The Moodle user's email.
 * @return string The Zoom user ID to use as meeting host.
 * @throws moodle_exception If neither the user nor fallback can be resolved.
 */
function zoomyt_resolve_host_for_meeting($useremail) {
    try {
        $service = zoomyt_webservice();
    } catch (moodle_exception $e) {
        throw new moodle_exception('errorwebservice', 'mod_zoomyt', '', null, $e->getMessage());
    }

    // Step 1: Check if the user already exists on this Zoom account.
    try {
        $existinguser = $service->get_user($useremail);
        if (!empty($existinguser) && !empty($existinguser->id)) {
            $status = $existinguser->status ?? 'unknown';
            if ($status === 'active') {
                zoomyt_provision_log('resolve_host', 'ok',
                    "Teacher exists on Zoom and is active, using as host. zoomid={$existinguser->id}",
                    $useremail);
                return $existinguser->id;
            }
            // Pending user — can't host, use fallback.
            zoomyt_provision_log('resolve_host', 'skip',
                "Teacher exists on Zoom but is {$status} — using fallback host", $useremail);
            return zoomyt_require_fallback_host($useremail);
        }
    } catch (\mod_zoomyt\not_found_exception $e) {
        // Not found — continue to creation attempt.
    } catch (moodle_exception $e) {
        zoomyt_provision_log('resolve_host', 'error',
            'API error looking up teacher: ' . $e->getMessage(), $useremail);
        return zoomyt_require_fallback_host($useremail);
    }

    // Step 2: User not on this account. Try to create them.
    $config = get_config('zoomyt');
    if (!empty($config->autocreatezoomusers)) {
        global $DB;
        $moodleuser = $DB->get_record('user', ['email' => $useremail], 'id, email, firstname, lastname');
        if ($moodleuser) {
            try {
                $service->autocreate_user($moodleuser, 'autoCreate', ZOOM_USER_TYPE_BASIC);
                zoomyt_provision_log('resolve_host_create', 'ok', 'User created via autoCreate', $useremail);
            } catch (moodle_exception $e) {
                $msg = $e->getMessage();
                if (strpos($msg, 'already in the account') !== false) {
                    // Race condition — user was just created. Look them up again.
                    try {
                        $user = $service->get_user($useremail);
                        if (!empty($user) && ($user->status ?? '') === 'active') {
                            return $user->id;
                        }
                    } catch (\Exception $e2) {
                        // Fall through to fallback.
                    }
                    return zoomyt_require_fallback_host($useremail);
                }
                if (strpos($msg, 'already been used') !== false) {
                    zoomyt_provision_log('resolve_host_create', 'skip',
                        'Email is on another Zoom account — using fallback', $useremail);
                    return zoomyt_require_fallback_host($useremail);
                }

                // Domain mismatch — try create (invitation) fallback.
                try {
                    $service->autocreate_user($moodleuser, 'create', ZOOM_USER_TYPE_BASIC);
                    zoomyt_provision_log('resolve_host_create', 'ok',
                        'User created via invitation — checking status', $useremail);
                } catch (moodle_exception $e2) {
                    $msg2 = $e2->getMessage();
                    if (strpos($msg2, 'already in the account') !== false || strpos($msg2, 'already been used') !== false) {
                        return zoomyt_require_fallback_host($useremail);
                    }
                    zoomyt_provision_log('resolve_host_create', 'error',
                        'Both autoCreate and create failed: ' . $msg2, $useremail);
                    return zoomyt_require_fallback_host($useremail);
                }
            }

            // Check the newly created user's status.
            try {
                $newuser = $service->get_user($useremail);
                if (!empty($newuser) && ($newuser->status ?? '') === 'active') {
                    $service->provide_license($newuser->id);
                    return $newuser->id;
                }
                // Pending — use fallback.
                zoomyt_provision_log('resolve_host_create', 'skip',
                    'Newly created user is pending — using fallback', $useremail);
                return zoomyt_require_fallback_host($useremail);
            } catch (\Exception $e) {
                return zoomyt_require_fallback_host($useremail);
            }
        }
    }

    // Could not create user — use fallback.
    return zoomyt_require_fallback_host($useremail);
}

/**
 * Get the fallback host Zoom user ID, or throw if not configured.
 *
 * @param string $useremail The teacher's email (for logging).
 * @return string The fallback host's Zoom user ID.
 * @throws moodle_exception If fallback host is not configured or not found.
 */
function zoomyt_require_fallback_host($useremail) {
    $fallbackemail = get_config('zoomyt', 'fallback_host_email');
    if (empty($fallbackemail)) {
        throw new moodle_exception('fallback_host_not_configured', 'mod_zoomyt');
    }

    $fallbackid = zoomyt_get_fallback_host_id();
    if (!$fallbackid) {
        throw new moodle_exception('fallback_host_not_found', 'mod_zoomyt', '', $fallbackemail);
    }

    zoomyt_provision_log('fallback_host', 'ok',
        "Using fallback host {$fallbackemail} (zoomid={$fallbackid})", $useremail);
    return $fallbackid;
}

/**
 * Rename the fallback Zoom host account to match a teacher's name.
 *
 * Only renames if the meeting's host_id matches the fallback host account.
 * This makes the teacher appear under their own name when they join via start_url.
 *
 * @param string $hostid The meeting's current host Zoom user ID.
 * @param stdClass $moodleuser The Moodle user object (needs firstname, lastname).
 * @return void
 */
function zoomyt_rename_host_for_teacher($hostid, $moodleuser) {
    $fallbackid = zoomyt_get_fallback_host_id();
    if (!$fallbackid || $hostid !== $fallbackid) {
        return;
    }

    try {
        $service = zoomyt_webservice();
        $displayname = trim($moodleuser->firstname . ' ' . $moodleuser->lastname);
        $service->update_user_name($fallbackid, $moodleuser->firstname, $moodleuser->lastname, $displayname);
        zoomyt_provision_log('rename_host', 'ok',
            "Renamed fallback host to {$displayname}", $moodleuser->email, $moodleuser->id);
    } catch (\Exception $e) {
        zoomyt_provision_log('rename_host', 'error',
            'Failed to rename fallback host: ' . $e->getMessage(), $moodleuser->email, $moodleuser->id);
    }
}

/**
 * Merge instructor emails into the existing alternative hosts list.
 *
 * This function takes an existing alternative hosts string and merges in
 * instructor emails, avoiding duplicates. It validates each instructor against
 * the Zoom account and optionally creates them as Basic users if they don't exist.
 *
 * @param string $existinghosts Comma-separated string of existing alternative host emails.
 * @param array $instructoremails Array of instructor email addresses to add.
 * @param string|null $hostemail Optional host email to exclude from the list.
 * @param int|null $courseid Optional course ID for creating Zoom users.
 * @return string Updated comma-separated string of alternative host emails.
 */
function zoomyt_merge_alternative_hosts($existinghosts, array $instructoremails, $hostemail = null, $courseid = null) {
    // Parse existing hosts.
    $existingemails = zoomyt_get_alternative_host_array_from_string($existinghosts);

    // Normalize to lowercase.
    $existingemails = array_map('strtolower', $existingemails);
    $instructoremails = array_map('strtolower', $instructoremails);

    // Remove the host email if specified (host can't be alternative host of their own meeting).
    $hostemail = $hostemail !== null ? strtolower($hostemail) : null;

    // Validate instructor emails against Zoom - only add those who exist (or can be created).
    $validinstructors = [];
    foreach ($instructoremails as $email) {
        // Skip the host email.
        if ($hostemail !== null && $email === $hostemail) {
            continue;
        }
        // Skip if already in the existing list.
        if (in_array($email, $existingemails)) {
            continue;
        }
        // Ensure the user exists on Zoom (create as Basic if needed).
        if ($courseid !== null) {
            if (zoomyt_ensure_zoom_user($email, $courseid)) {
                $validinstructors[] = $email;
                debugging("ZOOMYT: Validated Zoom user for alternative host: {$email}", DEBUG_DEVELOPER);
            } else {
                debugging("ZOOMYT: Skipping {$email} - not a valid Zoom user and could not be created.", DEBUG_DEVELOPER);
            }
        } else {
            // No course ID provided - can't create users, just try to validate.
            if (zoomyt_user_exists_on_zoom($email)) {
                $validinstructors[] = $email;
            } else {
                debugging("ZOOMYT: Skipping {$email} - not a valid Zoom user.", DEBUG_DEVELOPER);
            }
        }
    }

    // Merge the lists.
    $allhosts = array_unique(array_merge($existingemails, $validinstructors));

    // Remove the host email.
    if ($hostemail !== null) {
        $allhosts = array_filter($allhosts, function($email) use ($hostemail) {
            return $email !== $hostemail;
        });
    }

    // Remove empty entries.
    $allhosts = array_filter($allhosts);

    return implode(',', $allhosts);
}

/**
 * Update the alternative hosts for a Zoom meeting on the Zoom server.
 *
 * This function updates only the alternative_hosts setting of an existing meeting.
 *
 * @param object $zoom The zoom instance object.
 * @param string $alternativehosts Comma-separated string of alternative host emails.
 * @return bool True if successful, false otherwise.
 */
function zoomyt_update_meeting_alternative_hosts($zoom, $alternativehosts) {
    try {
        $service = zoomyt_webservice();

        // Targeted PATCH that only updates alternative hosts.
        $service->update_meeting_hosts($zoom->meeting_id, $zoom->webinar ?? false, $alternativehosts);

        debugging("ZOOMYT: Updated alternative hosts for meeting {$zoom->meeting_id}: {$alternativehosts}", DEBUG_DEVELOPER);
        return true;
    } catch (moodle_exception $e) {
        debugging("ZOOMYT: Failed to update alternative hosts for meeting {$zoom->meeting_id}: " . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Extract a problematic email from a Zoom alt-host error message.
 *
 * Zoom returns errors like:
 *   'Unable to assign "foo@bar.com" as an alternative host because ...'
 *
 * @param string $message The error message from the Zoom API.
 * @return string|null The extracted email, or null if not found.
 */
function zoomyt_extract_bad_alt_host_email($message) {
    if (preg_match('/Unable to assign "([^"]+)" as an alternative host/', $message, $matches)) {
        return strtolower($matches[1]);
    }
    return null;
}

/**
 * Remove a specific email from a comma-separated alternative hosts string.
 *
 * @param string $althosts Comma-separated list of alternative host emails.
 * @param string $email The email to remove.
 * @return string Updated comma-separated list.
 */
function zoomyt_remove_alt_host($althosts, $email) {
    $hosts = array_map('trim', explode(',', $althosts));
    $hosts = array_filter($hosts, function ($h) use ($email) {
        return strtolower($h) !== strtolower($email);
    });
    return implode(',', $hosts);
}

/**
 * Create a meeting on Zoom, retrying without problematic alternative hosts.
 *
 * If Zoom rejects an alternative host (pending, unlicensed, external account),
 * that host is stripped and the request is retried. This prevents alt host issues
 * from blocking meeting creation. The scheduled sync task will re-add valid hosts later.
 *
 * @param stdClass $zoom The meeting object (alternative_hosts may be modified).
 * @return stdClass The Zoom API response.
 * @throws moodle_exception If the meeting creation fails for non-alt-host reasons.
 */
function zoomyt_create_meeting_with_alt_host_retry($zoom) {
    $service = zoomyt_webservice();
    $maxretries = 5;

    for ($attempt = 0; $attempt <= $maxretries; $attempt++) {
        try {
            return $service->create_meeting($zoom, $zoom->coursemodule);
        } catch (moodle_exception $e) {
            $bademail = zoomyt_extract_bad_alt_host_email($e->getMessage());
            if ($bademail === null || empty($zoom->alternative_hosts)) {
                throw $e;
            }

            zoomyt_provision_log('alt_host_retry', 'skip',
                "Removing invalid alt host {$bademail}: " . $e->getMessage(),
                $bademail, null, $zoom->course ?? null);

            $zoom->alternative_hosts = zoomyt_remove_alt_host($zoom->alternative_hosts, $bademail);
        }
    }

    return $service->create_meeting($zoom, $zoom->coursemodule);
}

/**
 * Update a meeting on Zoom, retrying without problematic alternative hosts.
 *
 * @param stdClass $zoom The meeting object (alternative_hosts may be modified).
 * @return void
 * @throws moodle_exception If the update fails for non-alt-host reasons.
 */
function zoomyt_update_meeting_with_alt_host_retry($zoom) {
    global $DB;
    $service = zoomyt_webservice();
    $maxretries = 5;

    for ($attempt = 0; $attempt <= $maxretries; $attempt++) {
        try {
            $service->update_meeting($zoom, $zoom->coursemodule);
            return;
        } catch (moodle_exception $e) {
            $bademail = zoomyt_extract_bad_alt_host_email($e->getMessage());
            if ($bademail === null || empty($zoom->alternative_hosts)) {
                throw $e;
            }

            zoomyt_provision_log('alt_host_retry', 'skip',
                "Removing invalid alt host {$bademail}: " . $e->getMessage(),
                $bademail, null, $zoom->course ?? null);

            $zoom->alternative_hosts = zoomyt_remove_alt_host($zoom->alternative_hosts, $bademail);
            $DB->set_field('zoomyt', 'alternative_hosts', $zoom->alternative_hosts, ['id' => $zoom->id]);
        }
    }

    $service->update_meeting($zoom, $zoom->coursemodule);
}
