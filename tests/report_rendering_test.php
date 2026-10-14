<?php
// This file is part of Moodle - http://moodle.org/
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
 * Report presentation unit tests.
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax;

use mod_videotrackermax\report\time_formatter;
use mod_videotrackermax\output\dashboard_renderer;

/**
 * Regression checks for the extracted dashboard presentation methods.
 * @covers \mod_videotrackermax\report\time_formatter
 * @covers \mod_videotrackermax\output\dashboard_renderer
 */
final class report_rendering_test extends \advanced_testcase {
    /**
     * The dashboard and learner report must use the same clock formatting.
     */
    public function test_duration_formatting(): void {
        $this->assertSame('00:00', time_formatter::format_seconds(0));
        $this->assertSame('00:59', time_formatter::format_seconds(59));
        $this->assertSame('01:00', time_formatter::format_seconds(60));
        $this->assertSame('01:00:01', time_formatter::format_seconds(3601));
        $this->assertSame('00:00', time_formatter::format_seconds(-5));
        $this->assertSame('01:01', time_formatter::format_seconds(60.6));
    }

    /**
     * Preserve markup and optional content expected by the dashboard CSS.
     */
    public function test_metric_card_markup(): void {
        $html = dashboard_renderer::metric_card('Watch time', '01:20', 'Last 30 days');
        $this->assertStringContainsString('metric-card', $html);
        $this->assertStringContainsString('metric-value', $html);
        $this->assertStringContainsString('Watch time', $html);
        $this->assertStringContainsString('01:20', $html);
        $this->assertStringContainsString('Last 30 days', $html);
    }
}
