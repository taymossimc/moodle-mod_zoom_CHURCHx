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
 * YouTube API service class for Zoom YT.
 *
 * Handles OAuth authentication and video uploads to YouTube.
 *
 * @package    mod_zoomyt
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * YouTube API service class.
 */
class youtube_service {

    /** @var string YouTube OAuth token endpoint */
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /** @var string YouTube API base URL */
    const API_URL = 'https://www.googleapis.com/youtube/v3';

    /** @var string YouTube upload URL */
    const UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos';

    /** @var string YouTube audio tracks resumable upload URL */
    const AUDIOTRACK_UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/audiotracks';

    /** @var string YouTube audio tracks API URL */
    const AUDIOTRACK_API_URL = 'https://www.googleapis.com/youtube/v3/audiotracks';

    /** @var string Client ID */
    protected $clientid;

    /** @var string Client secret */
    protected $clientsecret;

    /** @var string Refresh token */
    protected $refreshtoken;

    /** @var string Access token */
    protected $accesstoken;

    /** @var int Category ID for cache keying */
    protected $categoryid;

    /**
     * Constructor.
     *
     * @param object $credentials Object with yt_client_id, yt_client_secret, yt_refresh_token.
     * @param int|null $categoryid Category ID for caching.
     */
    public function __construct(object $credentials, ?int $categoryid = null) {
        $this->clientid = $credentials->yt_client_id ?? '';
        $this->clientsecret = $credentials->yt_client_secret ?? '';
        $this->refreshtoken = $credentials->yt_refresh_token ?? '';
        $this->categoryid = $categoryid;
    }

    /**
     * Get a YouTube service instance for a course.
     *
     * @param int $courseid The course ID.
     * @return self|null The YouTube service or null if not configured.
     */
    public static function get_instance_for_course(int $courseid): ?self {
        global $CFG;
        require_once($CFG->dirroot . '/mod/zoomyt/classes/category_settings.php');

        // Use get_for_course to properly convert course ID to category settings.
        $catsettings = category_settings::get_for_course($courseid);
        $settings = $catsettings->get_effective_settings();

        if (empty($settings->yt_client_id) || empty($settings->yt_client_secret) || empty($settings->yt_refresh_token)) {
            return null;
        }

        return new self($settings, $catsettings->get_settings_source_category());
    }

    /**
     * Get a YouTube service instance for a specific activity.
     * Checks activity -> category -> site settings in that order.
     *
     * @param int $zoomid The zoom activity instance ID.
     * @return self|null The YouTube service or null if not configured.
     */
    public static function get_instance_for_activity(int $zoomid): ?self {
        global $CFG, $DB;

        // Get the zoom activity record.
        $zoom = $DB->get_record('zoomyt', ['id' => $zoomid]);
        if (!$zoom) {
            return null;
        }

        // Site-wide client credentials (always used).
        $clientid = get_config('zoomyt', 'youtube_client_id');
        $clientsecret = get_config('zoomyt', 'youtube_client_secret');

        // If no site-wide credentials, YouTube is not configured at all.
        if (empty($clientid) || empty($clientsecret)) {
            return null;
        }

        $refreshtoken = null;
        $categoryid = null;

        // 1. Check activity-level settings first.
        if (empty($zoom->yt_use_category) && !empty($zoom->yt_refresh_token)) {
            // Activity has its own YouTube channel configured.
            $refreshtoken = $zoom->yt_refresh_token;
        }

        // 2. If not, check category-level settings (walk up the category tree).
        if (empty($refreshtoken)) {
            $course = $DB->get_record('course', ['id' => $zoom->course], 'category', MUST_EXIST);

            // Get category path and walk up looking for YouTube settings.
            $category = $DB->get_record('course_categories', ['id' => $course->category], 'id, path');
            if ($category) {
                $pathparts = array_filter(explode('/', $category->path));
                $categoryids = array_reverse($pathparts); // Most specific first.

                foreach ($categoryids as $catid) {
                    $catsettings = $DB->get_record('zoomyt_category_settings', ['categoryid' => $catid]);

                    if ($catsettings && !empty($catsettings->yt_refresh_token)) {
                        // Check if this category inherits YouTube settings.
                        if (!empty($catsettings->inherit_youtube) && $catid != end($categoryids)) {
                            // This category inherits YouTube, continue looking up.
                            continue;
                        }

                        // Found YouTube settings at this category level.
                        $refreshtoken = $catsettings->yt_refresh_token;
                        $categoryid = (int)$catid;
                        break;
                    }
                }
            }
        }

        // 3. If still not found, check site-wide settings.
        if (empty($refreshtoken)) {
            $refreshtoken = get_config('zoomyt', 'youtube_default_refresh_token');
        }

        // If no refresh token at any level, YouTube is not configured.
        if (empty($refreshtoken)) {
            return null;
        }

        // Create credentials object.
        $credentials = new \stdClass();
        $credentials->yt_client_id = $clientid;
        $credentials->yt_client_secret = $clientsecret;
        $credentials->yt_refresh_token = $refreshtoken;

        return new self($credentials, $categoryid);
    }

    /**
     * Check if YouTube is configured.
     *
     * @return bool True if configured.
     */
    public function is_configured(): bool {
        return !empty($this->clientid) && !empty($this->clientsecret) && !empty($this->refreshtoken);
    }

    /**
     * Get the OAuth authorization URL for initial setup.
     *
     * @param string $redirecturi The redirect URI after authorization.
     * @param string $state State parameter for CSRF protection.
     * @return string The authorization URL.
     */
    public static function get_auth_url(string $clientid, string $redirecturi, string $state): string {
        $params = [
            'client_id' => $clientid,
            'redirect_uri' => $redirecturi,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly https://www.googleapis.com/auth/youtube.force-ssl',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code The authorization code.
     * @param string $redirecturi The redirect URI.
     * @param string $clientid Client ID.
     * @param string $clientsecret Client secret.
     * @return object Token response with access_token, refresh_token, etc.
     * @throws \moodle_exception On error.
     */
    public static function exchange_code_for_tokens(
        string $code,
        string $redirecturi,
        string $clientid,
        string $clientsecret
    ): object {
        $curl = new \curl();

        $data = [
            'code' => $code,
            'client_id' => $clientid,
            'client_secret' => $clientsecret,
            'redirect_uri' => $redirecturi,
            'grant_type' => 'authorization_code',
        ];

        $response = $curl->post(self::TOKEN_URL, $data);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_oauth_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_oauth_error', 'zoomyt', '', $result->error_description ?? $result->error);
        }

        return $result;
    }

    /**
     * Get a valid access token, refreshing if necessary.
     *
     * @return string The access token.
     * @throws \moodle_exception On error.
     */
    protected function get_access_token(): string {
        if (!empty($this->accesstoken)) {
            return $this->accesstoken;
        }

        // Cache key includes a short hash of the refresh token so that
        // reauthorizing (new refresh token) automatically invalidates stale tokens.
        $cache = \cache::make('mod_zoomyt', 'oauth');
        $rthash = substr(md5($this->refreshtoken), 0, 8);
        $cachekey = 'yt_' . ($this->categoryid ?? 'global') . '_' . $rthash . '_accesstoken';
        $expireskey = 'yt_' . ($this->categoryid ?? 'global') . '_' . $rthash . '_expires';

        $token = $cache->get($cachekey);
        $expires = $cache->get($expireskey);

        if (!empty($token) && !empty($expires) && time() < $expires) {
            $this->accesstoken = $token;
            return $token;
        }

        // Refresh the token.
        $curl = new \curl();

        $data = [
            'client_id' => $this->clientid,
            'client_secret' => $this->clientsecret,
            'refresh_token' => $this->refreshtoken,
            'grant_type' => 'refresh_token',
        ];

        $response = $curl->post(self::TOKEN_URL, $data);

        if ($curl->get_errno()) {
            $this->notify_oauth_failure($curl->error);
            throw new \moodle_exception('youtube_oauth_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            // Purge any stale cached token so a reauthorized refresh token is used next time.
            $cache->delete($cachekey);
            $cache->delete($expireskey);
            $errordetail = $result->error_description ?? $result->error;
            $this->notify_oauth_failure($errordetail);
            throw new \moodle_exception('youtube_oauth_error', 'zoomyt', '', $errordetail);
        }

        $this->accesstoken = $result->access_token;
        $expires = time() + ($result->expires_in ?? 3600) - 60; // 60 seconds buffer.

        $cache->set($cachekey, $this->accesstoken);
        $cache->set($expireskey, $expires);

        return $this->accesstoken;
    }

    /**
     * Email the configured administrator when a YouTube OAuth token refresh fails.
     *
     * A revoked or expired refresh token makes every subsequent upload fail until
     * the channel is reconnected, so the admin needs to know. Alerts are throttled
     * per connection (site or category) so a repeatedly failing scheduled task does
     * not flood the inbox.
     *
     * @param string $errordetail The error detail returned by Google / cURL.
     * @return void
     */
    protected function notify_oauth_failure(string $errordetail): void {
        global $CFG, $DB, $SITE;

        // Respect the on/off switch (unset == enabled by default).
        if (get_config('zoomyt', 'oauth_alert_enabled') === '0') {
            return;
        }

        $email = trim((string)get_config('zoomyt', 'oauth_alert_email'));
        if ($email === '') {
            $email = 'imc@tucc.ca';
        }

        // Throttle: at most one alert per connection per window.
        $throttle = 6 * HOURSECS;
        $key = 'oauth_alert_last_' . ($this->categoryid ?? 'site');
        $last = (int)get_config('zoomyt', $key);
        $now = time();
        if ($last && ($now - $last) < $throttle) {
            return;
        }

        // Identify which connection failed and where to reconnect it.
        if (!empty($this->categoryid)) {
            $categoryname = $DB->get_field('course_categories', 'name', ['id' => $this->categoryid]);
            $level = get_string('oauth_alert_level_category', 'zoomyt', $categoryname ?: $this->categoryid);
            $reconnecturl = (new \moodle_url('/mod/zoomyt/categorylist.php'))->out(false);
        } else {
            $level = get_string('oauth_alert_level_site', 'zoomyt');
            $reconnecturl = (new \moodle_url('/mod/zoomyt/youtube_oauth_site.php'))->out(false);
        }

        $a = (object) [
            'site' => format_string($SITE->fullname),
            'wwwroot' => $CFG->wwwroot,
            'level' => $level,
            'error' => $errordetail,
            'time' => userdate($now),
            'reconnecturl' => $reconnecturl,
        ];

        $subject = get_string('oauth_alert_subject', 'zoomyt', $a);
        $body = get_string('oauth_alert_body', 'zoomyt', $a);

        // Prefer a real Moodle account with that email; otherwise build a minimal
        // recipient object that email_to_user() can use.
        $recipient = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MULTIPLE);
        if (!$recipient) {
            $recipient = clone \core_user::get_support_user();
            $recipient->id = -1;
            $recipient->email = $email;
            $recipient->firstname = get_string('oauth_alert_recipient_name', 'zoomyt');
            $recipient->lastname = '';
            $recipient->maildisplay = 1;
            $recipient->mailformat = 1;
            $recipient->emailstop = 0;
            $recipient->deleted = 0;
            $recipient->suspended = 0;
            $recipient->auth = 'manual';
        }

        $from = \core_user::get_support_user();

        // Record the attempt time first so a send failure can't cause a tight loop.
        set_config($key, $now, 'zoomyt');

        try {
            email_to_user($recipient, $from, $subject, $body);
            mtrace('  [zoomyt] Sent YouTube OAuth failure alert to ' . $email);
        } catch (\Exception $e) {
            mtrace('  [zoomyt] Could not send YouTube OAuth failure alert: ' . $e->getMessage());
        }
    }

    /**
     * Get channel information.
     *
     * @return object Channel info with id, title, etc.
     * @throws \moodle_exception On error.
     */
    public function get_channel_info(): object {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::API_URL . '/channels?part=snippet&mine=true';
        $response = $curl->get($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        if (empty($result->items)) {
            throw new \moodle_exception('youtube_no_channel', 'zoomyt');
        }

        $channel = $result->items[0];
        return (object)[
            'id' => $channel->id,
            'title' => $channel->snippet->title,
            'description' => $channel->snippet->description ?? '',
            'thumbnail' => $channel->snippet->thumbnails->default->url ?? '',
        ];
    }

    /**
     * Upload a video to YouTube.
     *
     * @param string $filepath Path to the video file.
     * @param string $title Video title.
     * @param string $description Video description.
     * @param string $visibility Visibility: public, unlisted, private.
     * @param callable|null $progresscallback Optional callback for progress updates.
     * @param string|null $primarylanguage Optional BCP-47 language for the video's
     *                                      metadata (title/description) language.
     * @param string|null $audiolanguage Optional BCP-47 language label for the original
     *                                    (floor) audio track. Defaults to $primarylanguage.
     *                                    Use a neutral code (e.g. 'mul') for a multilingual floor.
     * @return object Video info with id, url, etc.
     * @throws \moodle_exception On error.
     */
    public function upload_video(
        string $filepath,
        string $title,
        string $description = '',
        string $visibility = 'unlisted',
        ?callable $progresscallback = null,
        ?string $primarylanguage = null,
        ?string $audiolanguage = null
    ): object {
        if (!file_exists($filepath)) {
            throw new \moodle_exception('youtube_file_not_found', 'zoomyt', '', $filepath);
        }

        $token = $this->get_access_token();
        $filesize = filesize($filepath);

        // Step 1: Initialize resumable upload.
        $snippet = [
            'title' => substr($title, 0, 100),
            'description' => substr($description, 0, 5000),
            'categoryId' => '27', // Education category.
        ];
        if (!empty($primarylanguage)) {
            // Language of the title/description metadata.
            $snippet['defaultLanguage'] = $primarylanguage;
        }
        // Language label for the original (floor) audio track. For a multilingual
        // floor this should be a neutral code (e.g. 'mul' = multiple languages) so
        // English and Portuguese can be added later as distinct alternate tracks
        // alongside the original. Falls back to the metadata language.
        $audiolang = !empty($audiolanguage) ? $audiolanguage : $primarylanguage;
        if (!empty($audiolang)) {
            $snippet['defaultAudioLanguage'] = $audiolang;
        }
        $metadata = [
            'snippet' => $snippet,
            'status' => [
                'privacyStatus' => $visibility,
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json; charset=UTF-8');
        $curl->setHeader('X-Upload-Content-Length: ' . $filesize);
        $curl->setHeader('X-Upload-Content-Type: video/mp4');

        $initurl = self::UPLOAD_URL . '?uploadType=resumable&part=snippet,status';
        $response = $curl->post($initurl, json_encode($metadata));
        $info = $curl->get_info();

        if ($info['http_code'] !== 200) {
            $error = json_decode($response);
            throw new \moodle_exception('youtube_upload_init_error', 'zoomyt', '', 
                $error->error->message ?? 'HTTP ' . $info['http_code']);
        }

        // Get the upload URL from response headers.
        $headers = $curl->getResponse();
        $uploadurl = $headers['location'] ?? $headers['Location'] ?? null;

        if (empty($uploadurl)) {
            throw new \moodle_exception('youtube_upload_no_location', 'zoomyt');
        }

        // Step 2: Upload the video file.
        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            throw new \moodle_exception('youtube_file_open_error', 'zoomyt', '', $filepath);
        }

        $chunksize = 10 * 1024 * 1024; // 10MB chunks.
        $uploaded = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, $chunksize);
            $chunklen = strlen($chunk);
            $end = $uploaded + $chunklen - 1;

            $curl = new \curl();
            $curl->setHeader('Authorization: Bearer ' . $token);
            $curl->setHeader('Content-Type: video/mp4');
            $curl->setHeader('Content-Length: ' . $chunklen);
            $curl->setHeader('Content-Range: bytes ' . $uploaded . '-' . $end . '/' . $filesize);

            $curl->setopt(['CURLOPT_POSTFIELDS' => $chunk]);
            $response = $curl->put($uploadurl, $chunk);
            $info = $curl->get_info();

            $uploaded += $chunklen;

            if ($progresscallback) {
                $progresscallback($uploaded, $filesize);
            }

            // 308 = Resume Incomplete (continue uploading).
            // 200/201 = Upload complete.
            if ($info['http_code'] !== 308 && $info['http_code'] !== 200 && $info['http_code'] !== 201) {
                fclose($handle);
                $error = json_decode($response);
                throw new \moodle_exception('youtube_upload_chunk_error', 'zoomyt', '', 
                    $error->error->message ?? 'HTTP ' . $info['http_code']);
            }

            if ($info['http_code'] === 200 || $info['http_code'] === 201) {
                // Upload complete.
                break;
            }
        }

        fclose($handle);

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_upload_error', 'zoomyt', '', $result->error->message);
        }

        // Get video details including thumbnail.
        $videoinfo = $this->get_video_info($result->id);

        return (object)[
            'id' => $result->id,
            'url' => 'https://www.youtube.com/watch?v=' . $result->id,
            'title' => $result->snippet->title ?? $title,
            'description' => $result->snippet->description ?? $description,
            'thumbnail_url' => $videoinfo->thumbnail_url ?? '',
            'duration' => $videoinfo->duration ?? 0,
        ];
    }

    /**
     * List the alternate audio tracks attached to a video.
     *
     * Uses the YouTube Data API v3 audiotracks resource. Returns an empty array
     * if the channel is not enrolled in the multi-language audio feature.
     *
     * @param string $videoid YouTube video ID.
     * @return array Array of ['id' => ..., 'language' => ..., 'name' => ...].
     * @throws \moodle_exception On a non-eligibility API error.
     */
    public function list_audio_tracks(string $videoid): array {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::AUDIOTRACK_API_URL . '?part=id,snippet&videoId=' . urlencode($videoid);
        $response = $curl->get($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            $message = $result->error->message ?? 'Unknown error';
            // Not enrolled / feature not available: treat as "no tracks" rather than fatal.
            if (in_array((int) ($result->error->code ?? 0), [403, 404], true)) {
                return [];
            }
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $message);
        }

        $tracks = [];
        if (!empty($result->items)) {
            foreach ($result->items as $item) {
                $tracks[] = [
                    'id' => $item->id ?? '',
                    'language' => $item->snippet->audioTrack->language ?? ($item->snippet->language ?? ''),
                    'name' => $item->snippet->name ?? '',
                ];
            }
        }

        return $tracks;
    }

    /**
     * Attach an alternate (dubbed) audio track to a video.
     *
     * Performs a resumable media upload of an audio-only file to the YouTube
     * Data API v3 audiotracks resource.
     *
     * @param string $videoid YouTube video ID.
     * @param string $filepath Path to the audio-only file (AAC/M4A).
     * @param string $languagecode BCP-47 language code (e.g. "pt", "es-MX").
     * @param string $name Optional display name for the track.
     * @return object ['id' => audiotrack id, 'language' => code].
     * @throws \moodle_exception On error.
     */
    public function upload_audio_track(
        string $videoid,
        string $filepath,
        string $languagecode,
        string $name = ''
    ): object {
        if (!file_exists($filepath)) {
            throw new \moodle_exception('youtube_file_not_found', 'zoomyt', '', $filepath);
        }

        $token = $this->get_access_token();
        $filesize = filesize($filepath);

        // Encode as objects: an empty PHP array would serialise to a JSON list,
        // which the API rejects ("Proto field is not repeating, cannot start list").
        $metadata = new \stdClass();
        if ($name !== '') {
            $metadata->snippet = (object) ['name' => substr($name, 0, 100)];
        }

        // Step 1: Initialize resumable upload.
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json; charset=UTF-8');
        $curl->setHeader('X-Upload-Content-Length: ' . $filesize);
        $curl->setHeader('X-Upload-Content-Type: audio/mp4');

        $initurl = self::AUDIOTRACK_UPLOAD_URL
            . '?uploadType=resumable&part=snippet'
            . '&videoId=' . urlencode($videoid)
            . '&language=' . urlencode($languagecode);

        $response = $curl->post($initurl, json_encode($metadata));
        $info = $curl->get_info();

        if ((int) ($info['http_code'] ?? 0) !== 200) {
            $error = json_decode($response);
            throw new \moodle_exception('youtube_audiotrack_init_error', 'zoomyt', '',
                $error->error->message ?? 'HTTP ' . ($info['http_code'] ?? '0'));
        }

        $headers = $curl->getResponse();
        $uploadurl = $headers['location'] ?? $headers['Location'] ?? null;
        if (empty($uploadurl)) {
            throw new \moodle_exception('youtube_upload_no_location', 'zoomyt');
        }

        // Step 2: Upload the audio file in chunks.
        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            throw new \moodle_exception('youtube_file_open_error', 'zoomyt', '', $filepath);
        }

        $chunksize = 10 * 1024 * 1024;
        $uploaded = 0;
        $response = '';

        while (!feof($handle)) {
            $chunk = fread($handle, $chunksize);
            $chunklen = strlen($chunk);
            $end = $uploaded + $chunklen - 1;

            $curl = new \curl();
            $curl->setHeader('Authorization: Bearer ' . $token);
            $curl->setHeader('Content-Type: audio/mp4');
            $curl->setHeader('Content-Length: ' . $chunklen);
            $curl->setHeader('Content-Range: bytes ' . $uploaded . '-' . $end . '/' . $filesize);

            $response = $curl->put($uploadurl, $chunk);
            $info = $curl->get_info();
            $uploaded += $chunklen;

            $code = (int) ($info['http_code'] ?? 0);
            if ($code !== 308 && $code !== 200 && $code !== 201) {
                fclose($handle);
                $error = json_decode($response);
                throw new \moodle_exception('youtube_audiotrack_upload_error', 'zoomyt', '',
                    $error->error->message ?? 'HTTP ' . $code);
            }

            if ($code === 200 || $code === 201) {
                break;
            }
        }

        fclose($handle);

        $result = json_decode($response);
        if (isset($result->error)) {
            throw new \moodle_exception('youtube_audiotrack_upload_error', 'zoomyt', '', $result->error->message);
        }

        return (object) [
            'id' => $result->id ?? '',
            'language' => $languagecode,
        ];
    }

    /**
     * Delete an alternate audio track from a video.
     *
     * @param string $audiotrackid The audioTrack resource id.
     * @return bool True on success (404 treated as already gone).
     * @throws \moodle_exception On a non-recoverable API error.
     */
    public function delete_audio_track(string $audiotrackid): bool {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::AUDIOTRACK_API_URL . '?id=' . urlencode($audiotrackid);
        $response = $curl->delete($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $httpcode = (int) ($curl->get_info()['http_code'] ?? 0);
        if (in_array($httpcode, [200, 204, 404], true)) {
            return true;
        }

        $message = 'HTTP ' . $httpcode;
        if (!empty($response)) {
            $decoded = json_decode($response);
            if (isset($decoded->error->message)) {
                $message = $decoded->error->message;
            }
        }
        throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $message);
    }

    /**
     * Get video information from YouTube.
     *
     * @param string $videoid YouTube video ID.
     * @return object Video info.
     * @throws \moodle_exception On error.
     */
    public function get_video_info(string $videoid): object {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::API_URL . '/videos?part=snippet,contentDetails,status&id=' . $videoid;
        $response = $curl->get($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        if (empty($result->items)) {
            throw new \moodle_exception('youtube_video_not_found', 'zoomyt', '', $videoid);
        }

        $video = $result->items[0];

        // Parse ISO 8601 duration.
        $duration = 0;
        if (!empty($video->contentDetails->duration)) {
            $interval = new \DateInterval($video->contentDetails->duration);
            $duration = $interval->h * 3600 + $interval->i * 60 + $interval->s;
        }

        // Get best thumbnail.
        $thumbnails = $video->snippet->thumbnails ?? new \stdClass();
        $thumbnailurl = $thumbnails->maxres->url 
            ?? $thumbnails->high->url 
            ?? $thumbnails->medium->url 
            ?? $thumbnails->default->url 
            ?? '';

        return (object)[
            'id' => $video->id,
            'title' => $video->snippet->title ?? '',
            'description' => $video->snippet->description ?? '',
            'thumbnail_url' => $thumbnailurl,
            'thumbnails' => $thumbnails,
            'duration' => $duration,
            'visibility' => $video->status->privacyStatus ?? 'unlisted',
            'categoryId' => $video->snippet->categoryId ?? '22',
            'published_at' => $video->snippet->publishedAt ?? '',
        ];
    }

    /**
     * Delete a video from YouTube.
     *
     * Requires the youtube.force-ssl scope (already requested by this plugin).
     * A 404 response is treated as success: the desired end state - the video no
     * longer existing on YouTube - is already met.
     *
     * @param string $videoid YouTube video ID.
     * @return bool True on success.
     * @throws \moodle_exception On a non-recoverable API error.
     */
    public function delete_video(string $videoid): bool {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::API_URL . '/videos?id=' . urlencode($videoid);
        $response = $curl->delete($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        // 204 No Content is the documented success response; 404 means it is already gone.
        if ($httpcode === 204 || $httpcode === 200 || $httpcode === 404) {
            return true;
        }

        $message = 'HTTP ' . $httpcode;
        if (!empty($response)) {
            $result = json_decode($response);
            if (isset($result->error->message)) {
                $message = $result->error->message;
            }
        }

        throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $message);
    }

    /**
     * Extract a YouTube video ID from a URL (or a bare ID).
     *
     * Supports watch?v=, youtu.be/, embed/, shorts/, live/ and /v/ forms with
     * arbitrary extra query parameters, as well as a bare 11-character ID.
     *
     * @param string $url The YouTube URL or video ID.
     * @return string|null The 11-character video ID, or null if not recognised.
     */
    public static function extract_video_id(string $url): ?string {
        $url = trim($url);

        // Bare 11-character ID.
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{11})~',
            '~youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
            '~youtube\.com/embed/([A-Za-z0-9_-]{11})~',
            '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~',
            '~youtube\.com/live/([A-Za-z0-9_-]{11})~',
            '~youtube\.com/v/([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Update video visibility.
     *
     * @param string $videoid YouTube video ID.
     * @param string $visibility New visibility: public, unlisted, private.
     * @return bool True on success.
     * @throws \moodle_exception On error.
     */
    public function update_video_visibility(string $videoid, string $visibility): bool {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json');

        $data = [
            'id' => $videoid,
            'status' => [
                'privacyStatus' => $visibility,
            ],
        ];

        $url = self::API_URL . '/videos?part=status';
        $response = $curl->put($url, json_encode($data));

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        return true;
    }

    /**
     * Update video title and description on YouTube.
     *
     * @param string $videoid YouTube video ID.
     * @param string $title New title.
     * @param string $description New description.
     * @return bool True on success.
     * @throws \moodle_exception On error.
     */
    public function update_video_metadata(string $videoid, string $title, string $description): bool {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json');

        // First get the current video to preserve category.
        $currentvideo = $this->get_video_info($videoid);
        $categoryid = $currentvideo->categoryId ?? '22'; // Default to "People & Blogs".

        $data = [
            'id' => $videoid,
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'categoryId' => $categoryid,
            ],
        ];

        $url = self::API_URL . '/videos?part=snippet';
        $response = $curl->put($url, json_encode($data));

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        return true;
    }

    /**
     * Set the language labels on an existing video without disturbing its title,
     * description or category.
     *
     * videos.update replaces the whole snippet part, so we re-send the existing
     * title/description/category alongside the new language fields.
     *
     * @param string $videoid YouTube video ID.
     * @param string $defaultaudiolanguage BCP-47 language for the original audio track
     *                                     (e.g. 'mul' for a multilingual floor).
     * @param string|null $defaultlanguage Optional BCP-47 metadata language to preserve/set.
     * @return bool True on success.
     * @throws \moodle_exception On error.
     */
    public function set_audio_language(string $videoid, string $defaultaudiolanguage,
            ?string $defaultlanguage = null): bool {
        $token = $this->get_access_token();

        // Preserve the current title/description/category (snippet is replaced wholesale).
        $current = $this->get_video_info($videoid);

        $snippet = [
            'title' => $current->title,
            'description' => $current->description,
            'categoryId' => $current->categoryId ?? '27',
        ];
        if (!empty($defaultlanguage)) {
            $snippet['defaultLanguage'] = $defaultlanguage;
        }
        $snippet['defaultAudioLanguage'] = $defaultaudiolanguage;

        $data = ['id' => $videoid, 'snippet' => $snippet];

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json');

        $url = self::API_URL . '/videos?part=snippet';
        $response = $curl->put($url, json_encode($data));

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);
        if (isset($result->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        return true;
    }

    /**
     * Get captions/subtitles list for a video.
     *
     * @param string $videoid YouTube video ID.
     * @return array Array of caption tracks with language codes.
     * @throws \moodle_exception On error.
     */
    public function get_video_captions(string $videoid): array {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        $url = self::API_URL . '/captions?part=snippet&videoId=' . urlencode($videoid);
        $response = $curl->get($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        $result = json_decode($response);

        if (isset($result->error)) {
            // If no captions permission, return empty array instead of error.
            if (strpos($result->error->message ?? '', 'forbidden') !== false) {
                return [];
            }
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $result->error->message ?? 'Unknown error');
        }

        $captions = [];
        if (!empty($result->items)) {
            foreach ($result->items as $item) {
                $captions[] = [
                    'id' => $item->id,
                    'language' => $item->snippet->language ?? 'unknown',
                    'name' => $item->snippet->name ?? '',
                    'trackKind' => $item->snippet->trackKind ?? 'standard',
                    'isAutoSynced' => $item->snippet->isAutoSynced ?? false,
                ];
            }
        }

        return $captions;
    }

    /**
     * Download a caption track in SRT format.
     *
     * @param string $captionid The caption track ID.
     * @return string The caption content in SRT format.
     * @throws \moodle_exception On error.
     */
    public function download_caption(string $captionid): string {
        $token = $this->get_access_token();

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);

        // Request SRT format (tfmt=srt).
        $url = self::API_URL . '/captions/' . urlencode($captionid) . '?tfmt=srt';
        $response = $curl->get($url);

        if ($curl->get_errno()) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $curl->error);
        }

        // Log the response for debugging.
        debugging('Caption download response length: ' . strlen($response), DEBUG_DEVELOPER);

        // Check for error response (JSON).
        $decoded = @json_decode($response);
        if (isset($decoded->error)) {
            throw new \moodle_exception('youtube_api_error', 'zoomyt', '', $decoded->error->message ?? 'Unknown error');
        }

        return $response;
    }

    /**
     * Sync video metadata from YouTube to local database.
     *
     * @param int $videoid Local zoomyt_videos record ID.
     * @return bool True on success.
     */
    public function sync_video_from_youtube(int $videoid): bool {
        global $DB;

        $video = $DB->get_record('zoomyt_videos', ['id' => $videoid]);
        if (!$video || empty($video->youtube_video_id)) {
            return false;
        }

        try {
            $ytinfo = $this->get_video_info($video->youtube_video_id);

            $update = new \stdClass();
            $update->id = $video->id;
            $update->title = $ytinfo->title ?? $video->title;
            $update->description = $ytinfo->description ?? $video->description;
            $update->thumbnail_url = $ytinfo->thumbnail_url ?? $video->thumbnail_url;
            $update->visibility = $ytinfo->visibility ?? $video->visibility;
            $update->duration = $ytinfo->duration ?? $video->duration;
            $update->timemodified = time();

            $DB->update_record('zoomyt_videos', $update);

            return true;
        } catch (\Exception $e) {
            debugging('Failed to sync video from YouTube: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Download and store transcripts for a video.
     *
     * @param int $videoid Local zoomyt_videos record ID.
     * @param int $cmid Course module ID for file storage context.
     * @return bool True on success.
     */
    public function download_and_store_transcripts(int $videoid, int $cmid): bool {
        global $DB;

        $video = $DB->get_record('zoomyt_videos', ['id' => $videoid]);
        if (!$video || empty($video->youtube_video_id)) {
            mtrace('  [Subtitle] ERROR: No video or YouTube ID for video ID ' . $videoid);
            return false;
        }

        mtrace('  [Subtitle] Starting transcript download for: ' . $video->title . ' (YT: ' . $video->youtube_video_id . ')');

        try {
            // First try the official Captions API.
            mtrace('  [Subtitle] Trying Method 1: Official YouTube Captions API...');
            $captions = [];
            try {
                $captions = $this->get_video_captions($video->youtube_video_id);
                mtrace('  [Subtitle] Official API returned ' . count($captions) . ' caption tracks');
            } catch (\Exception $e) {
                mtrace('  [Subtitle] Method 1 failed with exception: ' . $e->getMessage());
            }

            // Get the context for file storage.
            $context = \context_module::instance($cmid);
            $fs = get_file_storage();

            // Delete any existing transcript files for this video.
            $fs->delete_area_files($context->id, 'mod_zoomyt', 'transcripts', $video->id);

            $downloadedlangs = [];

            if (!empty($captions)) {
                // Use official API to download captions.
                foreach ($captions as $caption) {
                    $lang = $caption['language'];
                    try {
                        $srtcontent = $this->download_caption($caption['id']);

                        if (!empty($srtcontent) && strlen($srtcontent) > 10) {
                            $fileinfo = [
                                'contextid' => $context->id,
                                'component' => 'mod_zoomyt',
                                'filearea' => 'transcripts',
                                'itemid' => $video->id,
                                'filepath' => '/',
                                'filename' => 'transcript_' . $lang . '.srt',
                            ];

                            $fs->create_file_from_string($fileinfo, $srtcontent);
                            $downloadedlangs[] = $lang;
                            debugging('Downloaded caption for lang: ' . $lang, DEBUG_DEVELOPER);
                        }
                    } catch (\Exception $e) {
                        debugging('Failed to download caption for lang ' . $lang . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    }
                }
            }

            // If official API failed, try public timedtext API as fallback.
            if (empty($downloadedlangs)) {
                mtrace('  [Subtitle] Method 1 (Official API) failed, trying Method 2 (Public API)...');
                $publiccaptions = $this->get_public_captions($video->youtube_video_id);

                foreach ($publiccaptions as $lang => $content) {
                    if (!empty($content) && strlen($content) > 10) {
                        $fileinfo = [
                            'contextid' => $context->id,
                            'component' => 'mod_zoomyt',
                            'filearea' => 'transcripts',
                            'itemid' => $video->id,
                            'filepath' => '/',
                            'filename' => 'transcript_' . $lang . '.srt',
                        ];

                        $fs->create_file_from_string($fileinfo, $content);
                        $downloadedlangs[] = $lang;
                        mtrace('  [Subtitle] Downloaded via Public API: ' . $lang);
                    }
                }
            }

            // If public API also failed, try yt-dlp as final fallback.
            if (empty($downloadedlangs)) {
                mtrace('  [Subtitle] Method 2 (Public API) failed, trying Method 3 (yt-dlp)...');
                $ytdlpcaptions = $this->get_ytdlp_captions($video->youtube_video_id);

                foreach ($ytdlpcaptions as $lang => $content) {
                    if (!empty($content) && strlen($content) > 10) {
                        $fileinfo = [
                            'contextid' => $context->id,
                            'component' => 'mod_zoomyt',
                            'filearea' => 'transcripts',
                            'itemid' => $video->id,
                            'filepath' => '/',
                            'filename' => 'transcript_' . $lang . '.srt',
                        ];

                        $fs->create_file_from_string($fileinfo, $content);
                        $downloadedlangs[] = $lang;
                        mtrace('  [Subtitle] Downloaded via yt-dlp: ' . $lang);
                    }
                }
            }

            // Update the video record.
            $update = new \stdClass();
            $update->id = $video->id;
            $update->caption_languages = implode(',', $downloadedlangs);
            $update->transcript_downloaded = !empty($downloadedlangs) ? 1 : 0;
            $update->timemodified = time();
            $DB->update_record('zoomyt_videos', $update);

            if (!empty($downloadedlangs)) {
                mtrace('  [Subtitle] SUCCESS! Downloaded transcripts: ' . implode(', ', $downloadedlangs));
            } else {
                mtrace('  [Subtitle] WARNING: No transcripts found after trying all methods');
            }

            return !empty($downloadedlangs);
        } catch (\Exception $e) {
            mtrace('  [Subtitle] ERROR: Exception during transcript download: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get captions from YouTube's public timedtext API (fallback method).
     *
     * @param string $videoid YouTube video ID.
     * @return array Array of [lang => srt_content].
     */
    protected function get_public_captions(string $videoid): array {
        $result = [];

        // Common language codes to try.
        $languages = ['en', 'en-US', 'en-GB', 'fr', 'es', 'de', 'pt', 'it', 'ja', 'ko', 'zh-Hans', 'zh-Hant'];

        $curl = new \curl();
        $curl->setopt(['CURLOPT_FOLLOWLOCATION' => true, 'CURLOPT_TIMEOUT' => 30]);

        foreach ($languages as $lang) {
            // Try auto-generated captions first.
            $url = 'https://www.youtube.com/api/timedtext?v=' . urlencode($videoid) . '&lang=' . urlencode($lang) . '&fmt=srt';
            $response = $curl->get($url);

            if (!$curl->get_errno() && !empty($response) && strlen($response) > 50) {
                // Normalize language code (remove region).
                $normLang = explode('-', $lang)[0];
                if (!isset($result[$normLang])) {
                    $result[$normLang] = $response;
                }
            }

            // Also try with asr (auto-generated).
            $url = 'https://www.youtube.com/api/timedtext?v=' . urlencode($videoid) . '&lang=' . urlencode($lang) . '&kind=asr&fmt=srt';
            $response = $curl->get($url);

            if (!$curl->get_errno() && !empty($response) && strlen($response) > 50) {
                $normLang = explode('-', $lang)[0];
                if (!isset($result[$normLang])) {
                    $result[$normLang] = $response;
                }
            }
        }

        mtrace('  [Subtitle] Public timedtext API found ' . count($result) . ' languages');

        return $result;
    }

    /**
     * Get the path to yt-dlp binary, downloading it if necessary.
     *
     * @return string|null Path to yt-dlp binary, or null if unavailable.
     */
    public static function get_ytdlp_path(): ?string {
        global $CFG;

        // First check if yt-dlp is installed system-wide.
        $systempath = trim(shell_exec('which yt-dlp 2>/dev/null') ?? '');
        if (!empty($systempath) && is_executable($systempath)) {
            mtrace('  [yt-dlp] Found system installation: ' . $systempath);
            return $systempath;
        }

        // Check our local bin directory.
        $bindir = $CFG->dataroot . '/mod_zoomyt/bin';
        $localpath = $bindir . '/yt-dlp';

        if (file_exists($localpath) && is_executable($localpath)) {
            mtrace('  [yt-dlp] Using local installation: ' . $localpath);
            return $localpath;
        }

        // Need to download yt-dlp.
        mtrace('  [yt-dlp] Not found, attempting to download...');

        // Create bin directory if needed.
        if (!is_dir($bindir)) {
            if (!mkdir($bindir, 0755, true)) {
                mtrace('  [yt-dlp] ERROR: Could not create directory: ' . $bindir);
                return null;
            }
        }

        // Download yt-dlp from GitHub releases.
        $downloadurl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp';

        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_TIMEOUT' => 120,
        ]);

        $content = $curl->get($downloadurl);

        if ($curl->get_errno() || empty($content) || strlen($content) < 1000000) {
            mtrace('  [yt-dlp] ERROR: Download failed. Size: ' . strlen($content ?? ''));
            return null;
        }

        // Save the binary.
        if (file_put_contents($localpath, $content) === false) {
            mtrace('  [yt-dlp] ERROR: Could not save binary to: ' . $localpath);
            return null;
        }

        // Make it executable.
        if (!chmod($localpath, 0755)) {
            mtrace('  [yt-dlp] ERROR: Could not make binary executable');
            unlink($localpath);
            return null;
        }

        // Verify it works.
        $version = trim(shell_exec($localpath . ' --version 2>/dev/null') ?? '');
        if (empty($version)) {
            mtrace('  [yt-dlp] ERROR: Binary downloaded but not executable');
            unlink($localpath);
            return null;
        }

        mtrace('  [yt-dlp] Successfully downloaded version: ' . $version);
        return $localpath;
    }

    /**
     * Download subtitles using yt-dlp.
     *
     * @param string $videoid YouTube video ID.
     * @return array Array of [lang => srt_content].
     */
    protected function get_ytdlp_captions(string $videoid): array {
        global $CFG;

        $result = [];

        // Get yt-dlp path.
        $ytdlp = self::get_ytdlp_path();
        if (!$ytdlp) {
            mtrace('  [yt-dlp] Not available, skipping');
            return $result;
        }

        // Create temp directory for subtitle files.
        $tempdir = $CFG->dataroot . '/temp/zoomyt_subs_' . $videoid . '_' . time();
        if (!mkdir($tempdir, 0755, true)) {
            mtrace('  [yt-dlp] ERROR: Could not create temp directory');
            return $result;
        }

        try {
            $url = 'https://www.youtube.com/watch?v=' . $videoid;

            // Download auto-generated subtitles.
            $cmd = escapeshellcmd($ytdlp) . ' ' .
                   '--skip-download ' .
                   '--write-auto-sub ' .
                   '--sub-lang en,fr,es,de,pt,it ' .
                   '--sub-format srt ' .
                   '--convert-subs srt ' .
                   '-o ' . escapeshellarg($tempdir . '/%(id)s.%(ext)s') . ' ' .
                   escapeshellarg($url) . ' 2>&1';

            mtrace('  [yt-dlp] Running command for auto-subs...');
            $output = shell_exec($cmd);
            mtrace('  [yt-dlp] Output: ' . substr($output ?? '', 0, 500));

            // Also try manual subtitles.
            $cmd2 = escapeshellcmd($ytdlp) . ' ' .
                    '--skip-download ' .
                    '--write-sub ' .
                    '--sub-lang en,fr,es,de,pt,it ' .
                    '--sub-format srt ' .
                    '--convert-subs srt ' .
                    '-o ' . escapeshellarg($tempdir . '/%(id)s.%(ext)s') . ' ' .
                    escapeshellarg($url) . ' 2>&1';

            mtrace('  [yt-dlp] Running command for manual subs...');
            $output2 = shell_exec($cmd2);
            mtrace('  [yt-dlp] Output: ' . substr($output2 ?? '', 0, 500));

            // Read any downloaded subtitle files.
            $files = glob($tempdir . '/*.srt');
            mtrace('  [yt-dlp] Found ' . count($files) . ' subtitle files');

            foreach ($files as $file) {
                $filename = basename($file);
                // Extract language from filename (e.g., "VIDEO_ID.en.srt").
                if (preg_match('/\.([a-z]{2}(?:-[A-Za-z]+)?)\.srt$/', $filename, $matches)) {
                    $lang = strtolower(explode('-', $matches[1])[0]); // Normalize to 2-letter code.
                    $content = file_get_contents($file);
                    if (!empty($content) && strlen($content) > 50) {
                        $result[$lang] = $content;
                        mtrace('  [yt-dlp] Loaded subtitle: ' . $lang . ' (' . strlen($content) . ' bytes)');
                    }
                }
            }

        } finally {
            // Clean up temp directory.
            $files = glob($tempdir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($tempdir);
        }

        mtrace('  [yt-dlp] Total languages found: ' . count($result));
        return $result;
    }

    /**
     * Get transcript file URLs for a video.
     *
     * @param int $videoid Local zoomyt_videos record ID.
     * @param int $cmid Course module ID.
     * @return array Array of ['lang' => 'en', 'url' => '...'] entries.
     */
    public static function get_transcript_urls(int $videoid, int $cmid): array {
        $context = \context_module::instance($cmid);
        $fs = get_file_storage();

        $files = $fs->get_area_files($context->id, 'mod_zoomyt', 'transcripts', $videoid, 'filename', false);

        $urls = [];
        foreach ($files as $file) {
            $filename = $file->get_filename();
            // Extract language from filename (transcript_en.srt -> en).
            if (preg_match('/transcript_(\w+)\.srt/', $filename, $matches)) {
                $lang = $matches[1];
                $url = \moodle_url::make_pluginfile_url(
                    $context->id,
                    'mod_zoomyt',
                    'transcripts',
                    $videoid,
                    '/',
                    $filename,
                    true // Force download.
                );
                $urls[] = [
                    'lang' => $lang,
                    'lang_upper' => strtoupper($lang),
                    'url' => $url->out(),
                    'filename' => $filename,
                ];
            }
        }

        return $urls;
    }

    /**
     * Test the YouTube connection.
     *
     * @return array ['success' => bool, 'message' => string, 'channel' => object|null]
     */
    public function test_connection(): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => get_string('youtube_not_configured', 'zoomyt'),
                'channel' => null,
            ];
        }

        try {
            $channel = $this->get_channel_info();
            return [
                'success' => true,
                'message' => get_string('youtube_connection_ok', 'zoomyt', $channel->title),
                'channel' => $channel,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('youtube_connection_failed', 'zoomyt') . ' ' . $e->getMessage(),
                'channel' => null,
            ];
        }
    }
}
