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
 * calculator.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax\aggregation;

/**
 * Class calculator.
 */
class calculator {
    /**
     * Method day_start.
     *
     * @param int $timestamp Parameter timestamp.
     * @return int Return value.
     */
    public static function day_start(int $timestamp): int {
        $timezone = \core_date::get_server_timezone_object();
        $date = (new \DateTimeImmutable('@' . max(0, $timestamp)))->setTimezone($timezone);
        return $date->setTime(0, 0, 0)->getTimestamp();
    }

    /**
     * Method summarise.
     *
     * @param array $sessions Parameter sessions.
     * @param array $coverage Parameter coverage.
     * @param int $bucketcount Parameter bucketcount.
     * @return array Return value.
     */
    public static function summarise(array $sessions, array $coverage, int $bucketcount): array {
        $bucketcount = max(10, min(1000, $bucketcount));
        $duration = (int)($coverage['duration'] ?? 0);
        $watchtime = 0;
        $speedweight = 0.0;
        $firststarted = 0;
        $lastended = 0;
        $completed = false;

        foreach ($sessions as $session) {
            $sessionwatch = max(0, (int)$session->watchtime);
            $watchtime += $sessionwatch;
            $speedweight += max(0.1, (float)$session->speedavg) * max(1, $sessionwatch);
            $firststarted = $firststarted === 0
                ? (int)$session->startedat
                : min($firststarted, (int)$session->startedat);
            $lastended = max($lastended, (int)$session->endedat, (int)$session->startedat);
            $duration = max($duration, (int)$session->duration);
            if ((int)$session->duration > 0 &&
                    (int)$session->maxposition >= max(0, (int)$session->duration - 2)) {
                $completed = true;
            }
        }

        $buckets = [];
        $watched = 0;
        foreach (($coverage['buckets'] ?? []) as $bucket) {
            $iswatched = !empty($bucket['viewers']);
            if ($iswatched) {
                $watched++;
            }
            if ($iswatched || !empty($bucket['plays']) || !empty($bucket['replays']) ||
                    !empty($bucket['pauses']) || !empty($bucket['skips']) || !empty($bucket['dropoffs'])) {
                $buckets[] = [
                    'bucket' => (int)$bucket['bucket'],
                    'watched' => $iswatched ? 1 : 0,
                    'plays' => (int)$bucket['plays'],
                    'replays' => (int)$bucket['replays'],
                    'pauses' => (int)$bucket['pauses'],
                    'skips' => (int)$bucket['skips'],
                    'dropoffs' => (int)$bucket['dropoffs'],
                ];
            }
        }

        $percent = $bucketcount > 0 ? (int)round(($watched / $bucketcount) * 100) : 0;
        $speedavg = $sessions
            ? $speedweight / array_sum(array_map(
                static fn($session): int => max(1, (int)$session->watchtime),
                $sessions
            ))
            : 1.0;

        return [
            'duration' => $duration,
            'percent' => max(0, min(100, $percent)),
            'watchtime' => $watchtime,
            'sessions' => count($sessions),
            'completed' => $completed ? 1 : 0,
            'speedavg' => $speedavg,
            'firststarted' => $firststarted,
            'lastended' => $lastended,
            'buckets' => $buckets,
        ];
    }
}
