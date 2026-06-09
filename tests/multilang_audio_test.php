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
 * Unit tests for multi-language interpretation audio helpers.
 *
 * @package    mod_zoomyt
 * @copyright  2026 TUCC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zoomyt;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/zoomyt/locallib.php');

/**
 * Tests for the language-mapping helpers used by the YouTube audio-track pipeline.
 *
 * @covers ::zoomyt_moodle_lang_to_bcp47
 * @covers ::zoomyt_interp_code_to_bcp47
 * @covers ::zoomyt_get_activity_interpretation_languages
 * @covers ::zoomyt_interp_language_name_to_bcp47
 * @covers ::zoomyt_interp_filename_to_bcp47
 */
final class multilang_audio_test extends advanced_testcase {

    /**
     * Moodle language code -> BCP-47 conversion.
     */
    public function test_moodle_lang_to_bcp47(): void {
        $this->assertEquals('en', zoomyt_moodle_lang_to_bcp47('en'));
        $this->assertEquals('pt-BR', zoomyt_moodle_lang_to_bcp47('pt_br'));
        $this->assertEquals('es-MX', zoomyt_moodle_lang_to_bcp47('es_mx'));
        // Parent suffix (e.g. en_us_wp) keeps only language + region.
        $this->assertEquals('en-US', zoomyt_moodle_lang_to_bcp47('en_us_wp'));
        // Empty falls back to English.
        $this->assertEquals('en', zoomyt_moodle_lang_to_bcp47(''));
        // Non-region second segment is ignored.
        $this->assertEquals('de', zoomyt_moodle_lang_to_bcp47('de_kids'));
    }

    /**
     * Zoom interpretation code -> BCP-47 conversion.
     */
    public function test_interp_code_to_bcp47(): void {
        $this->assertEquals('en', zoomyt_interp_code_to_bcp47('US'));
        $this->assertEquals('pt', zoomyt_interp_code_to_bcp47('PT'));
        $this->assertEquals('es', zoomyt_interp_code_to_bcp47('ES'));
        $this->assertEquals('ja', zoomyt_interp_code_to_bcp47('jp')); // Case-insensitive.
        $this->assertNull(zoomyt_interp_code_to_bcp47('ZZ'));
    }

    /**
     * Deriving configured interpretation languages from a zoomyt activity.
     */
    public function test_get_activity_interpretation_languages(): void {
        // Disabled interpretation -> empty.
        $zoom = (object) ['interpretation_enable' => 0, 'interpretation_data' => null];
        $this->assertSame([], zoomyt_get_activity_interpretation_languages($zoom));

        // Single interpreter US,PT -> en + pt.
        $zoom = (object) [
            'interpretation_enable' => 1,
            'interpretation_data' => json_encode([
                ['email' => 'a@example.com', 'languages' => 'US,PT'],
            ]),
        ];
        $langs = zoomyt_get_activity_interpretation_languages($zoom);
        sort($langs);
        $this->assertEquals(['en', 'pt'], $langs);

        // Multiple interpreters, de-duplicated.
        $zoom = (object) [
            'interpretation_enable' => 1,
            'interpretation_data' => json_encode([
                ['email' => 'a@example.com', 'languages' => 'US,PT'],
                ['email' => 'b@example.com', 'languages' => 'US,ES'],
            ]),
        ];
        $langs = zoomyt_get_activity_interpretation_languages($zoom);
        sort($langs);
        $this->assertEquals(['en', 'es', 'pt'], $langs);

        // Malformed JSON -> empty.
        $zoom = (object) ['interpretation_enable' => 1, 'interpretation_data' => 'not-json'];
        $this->assertSame([], zoomyt_get_activity_interpretation_languages($zoom));
    }

    /**
     * Language display name (as used in Zoom file names) -> BCP-47 conversion.
     */
    public function test_interp_language_name_to_bcp47(): void {
        $this->assertEquals('en', zoomyt_interp_language_name_to_bcp47('English'));
        $this->assertEquals('pt', zoomyt_interp_language_name_to_bcp47('Português'));
        $this->assertEquals('pt', zoomyt_interp_language_name_to_bcp47('Portuguese'));
        $this->assertEquals('ja', zoomyt_interp_language_name_to_bcp47('日本語'));
        $this->assertEquals('es', zoomyt_interp_language_name_to_bcp47('Español'));
        $this->assertEquals('fr', zoomyt_interp_language_name_to_bcp47(' Français '));
        $this->assertNull(zoomyt_interp_language_name_to_bcp47('Klingon'));
    }

    /**
     * Deriving the language from Zoom interpretation recording file names.
     */
    public function test_interp_filename_to_bcp47(): void {
        $this->assertEquals('en', zoomyt_interp_filename_to_bcp47('Audio only - Interpretation (English)'));
        $this->assertEquals('pt', zoomyt_interp_filename_to_bcp47('Audio only - Interpretation (Português)'));
        $this->assertEquals('ja', zoomyt_interp_filename_to_bcp47('Audio only - Interpretation (日本語)'));
        // The last parenthesised group wins.
        $this->assertEquals('es', zoomyt_interp_filename_to_bcp47('My meeting (test) - Interpretation (Español)'));
        // Unknown language or no parentheses -> null.
        $this->assertNull(zoomyt_interp_filename_to_bcp47('Audio only - Interpretation (Klingon)'));
        $this->assertNull(zoomyt_interp_filename_to_bcp47('Audio only'));
        $this->assertNull(zoomyt_interp_filename_to_bcp47(''));
    }
}
