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
 * Playback duration formatting.
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax\report;

/**
 * Formats watch times consistently in report pages.
 */
final class time_formatter {
    /**
     * Render a duration with hours only when they are present.
     *
     * @param float $seconds Duration in seconds.
     * @return string Human-readable duration.
     */
    public static function format_seconds(float $seconds): string {
        $seconds = max(0, (int)round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;
        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining)
            : sprintf('%02d:%02d', $minutes, $remaining);
    }
}
