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
 * Audio processing helper (ffmpeg) for synthesizing per-language tracks.
 *
 * Zoom's language interpretation cloud recording produces, for each language, an
 * isolated audio file containing only the interpreter's voice. To build a usable
 * "<language> version" of a session we mix the floor (room) audio under the
 * interpreter voice using sidechain ducking: the floor plays normally and is
 * automatically attenuated whenever the interpreter speaks.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt;

defined('MOODLE_INTERNAL') || die();

/**
 * Wraps ffmpeg/ffprobe for building per-language audio tracks.
 */
class audio_processor {

    /** @var string Path to the ffmpeg binary. */
    protected $ffmpeg;

    /** @var string Path to the ffprobe binary. */
    protected $ffprobe;

    /** @var array Ducking parameters (threshold, ratio, attack, release, makeup). */
    protected $ducking;

    /**
     * Constructor. Reads binary paths and ducking parameters from plugin config,
     * falling back to sensible defaults.
     */
    public function __construct() {
        $this->ffmpeg = self::get_ffmpeg_path();
        $this->ffprobe = self::get_ffprobe_path();

        $this->ducking = [
            // sidechaincompress threshold: linear amplitude 0-1. Lower = ducks more easily.
            'threshold' => (float) (get_config('zoomyt', 'duck_threshold') ?: 0.02),
            // Compression ratio applied to the floor while the interpreter speaks. Higher
            // = the original audio is pushed down much harder (more pronounced ducking).
            'ratio'     => (float) (get_config('zoomyt', 'duck_ratio') ?: 20),
            'attack'    => (float) (get_config('zoomyt', 'duck_attack') ?: 5),
            'release'   => (float) (get_config('zoomyt', 'duck_release') ?: 350),
            // Sidechain (interpreter) detection gain. Boosting this makes even moderate
            // interpreter speech trigger a deep duck, so the original is barely audible
            // underneath the translation. 1 = no boost.
            'level_sc'  => (float) (get_config('zoomyt', 'duck_level_sc') ?: 4),
        ];
    }

    /**
     * Resolve the ffmpeg binary path.
     *
     * @return string
     */
    public static function get_ffmpeg_path(): string {
        $configured = trim((string) get_config('zoomyt', 'ffmpeg_path'));
        if ($configured !== '') {
            return $configured;
        }
        $which = trim((string) @shell_exec('command -v ffmpeg 2>/dev/null'));
        return $which !== '' ? $which : 'ffmpeg';
    }

    /**
     * Resolve the ffprobe binary path.
     *
     * @return string
     */
    public static function get_ffprobe_path(): string {
        $configured = trim((string) get_config('zoomyt', 'ffprobe_path'));
        if ($configured !== '') {
            return $configured;
        }
        $which = trim((string) @shell_exec('command -v ffprobe 2>/dev/null'));
        return $which !== '' ? $which : 'ffprobe';
    }

    /**
     * Check whether ffmpeg and ffprobe are usable.
     *
     * @return bool
     */
    public function is_available(): bool {
        $rc = null;
        $out = [];
        @exec(escapeshellarg($this->ffmpeg) . ' -version 2>/dev/null', $out, $rc);
        if ($rc !== 0) {
            return false;
        }
        $out = [];
        $rc = null;
        @exec(escapeshellarg($this->ffprobe) . ' -version 2>/dev/null', $out, $rc);
        return $rc === 0;
    }

    /**
     * Get the duration of a media file in seconds.
     *
     * @param string $file Path to media file.
     * @return float|null Duration in seconds, or null on failure.
     */
    public function get_duration(string $file): ?float {
        if (!file_exists($file)) {
            return null;
        }
        $cmd = escapeshellarg($this->ffprobe)
            . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($file) . ' 2>/dev/null';
        $out = trim((string) @shell_exec($cmd));
        if ($out === '' || !is_numeric($out)) {
            return null;
        }
        return (float) $out;
    }

    /**
     * Synthesize a single per-language audio track by ducking the floor audio
     * under an isolated interpreter voice track.
     *
     * @param string $floorfile Path to the floor/room audio (or video) file.
     * @param string $interpreterfile Path to the isolated interpreter voice file.
     * @param string $outfile Destination path (.m4a, AAC).
     * @param float $offsetseconds Seconds to delay the interpreter relative to the
     *                             floor (positive = interpreter starts later).
     *                             Negative values trim the interpreter's start.
     * @return bool True on success.
     */
    public function synthesize_ducked_track(
        string $floorfile,
        string $interpreterfile,
        string $outfile,
        float $offsetseconds = 0.0
    ): bool {
        if (!file_exists($floorfile) || !file_exists($interpreterfile)) {
            return false;
        }

        // Align the interpreter track to the floor timeline. Normalize both inputs
        // to a fixed sample rate and channel layout so sidechaincompress (which
        // requires a defined channel layout) and amix behave consistently.
        $interpprep = '[1:a]aresample=48000,aformat=channel_layouts=stereo';
        if ($offsetseconds > 0.0) {
            $delayms = (int) round($offsetseconds * 1000);
            // adelay all channels by the same amount.
            $interpprep .= ',adelay=' . $delayms . '|' . $delayms;
        } else if ($offsetseconds < 0.0) {
            $trim = sprintf('%.3f', abs($offsetseconds));
            $interpprep .= ',atrim=start=' . $trim . ',asetpts=PTS-STARTPTS';
        }
        // Pad so the interpreter input covers the whole timeline (avoids dropout),
        // then split it: one copy keys the sidechain compressor, the other is mixed
        // on top. A filter output label can only feed one consumer, hence asplit.
        $interpprep .= ',apad,asplit=2[interpa][interpb]';

        $threshold = sprintf('%.4f', $this->ducking['threshold']);
        $ratio = sprintf('%.2f', $this->ducking['ratio']);
        $attack = sprintf('%.1f', $this->ducking['attack']);
        $release = sprintf('%.1f', $this->ducking['release']);
        $levelsc = sprintf('%.2f', max(1.0, $this->ducking['level_sc']));

        $filter =
            '[0:a]aresample=48000,aformat=channel_layouts=stereo[floor];'
            . $interpprep . ';'
            . '[floor][interpa]sidechaincompress='
                . 'threshold=' . $threshold
                . ':ratio=' . $ratio
                . ':attack=' . $attack
                . ':release=' . $release
                . ':level_sc=' . $levelsc . '[ducked];'
            // amix normalize=0 keeps full levels (interpreter on top of ducked floor).
            . '[ducked][interpb]amix=inputs=2:duration=first:dropout_transition=0:normalize=0[mixed];'
            . '[mixed]loudnorm=I=-16:TP=-1.5:LRA=11[out]';

        $cmd = escapeshellarg($this->ffmpeg)
            . ' -y -nostdin'
            . ' -i ' . escapeshellarg($floorfile)
            . ' -i ' . escapeshellarg($interpreterfile)
            . ' -filter_complex ' . escapeshellarg($filter)
            . ' -map ' . escapeshellarg('[out]')
            . ' -c:a aac -b:a 192k -ar 48000 -ac 2'
            . ' -movflags +faststart'
            . ' ' . escapeshellarg($outfile)
            . ' 2>&1';

        $out = [];
        $rc = null;
        @exec($cmd, $out, $rc);

        if ($rc !== 0 || !file_exists($outfile) || filesize($outfile) < 1000) {
            $tail = implode("\n", array_slice($out, -15));
            debugging('zoomyt audio_processor ffmpeg failed (rc=' . $rc . '): ' . $tail, DEBUG_DEVELOPER);
            if (function_exists('mtrace')) {
                mtrace('  [audio] ffmpeg failed (rc=' . $rc . '): ' . substr($tail, 0, 500));
            }
            if (file_exists($outfile)) {
                @unlink($outfile);
            }
            return false;
        }

        return true;
    }
}
