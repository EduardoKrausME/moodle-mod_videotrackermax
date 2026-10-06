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
 * filters.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax\report;

/**
 * Class filters.
 */
class filters {
    /**
     * Method from_request.
     *
     * @return array Return value.
     */
    public static function from_request(): array {
        $fromraw = optional_param('from', '', PARAM_RAW_TRIMMED);
        $toraw = optional_param('to', '', PARAM_RAW_TRIMMED);

        return [
            'from' => self::parse_date_value($fromraw, false),
            'to' => self::parse_date_value($toraw, true),
            'fromdate' => self::valid_date($fromraw) ? $fromraw : '',
            'todate' => self::valid_date($toraw) ? $toraw : '',
            'groupid' => max(0, optional_param('groupid', 0, PARAM_INT)),
            'groupingid' => max(0, optional_param('groupingid', 0, PARAM_INT)),
            'cohortid' => max(0, optional_param('cohortid', 0, PARAM_INT)),
            'status' => self::status(optional_param('status', 'all', PARAM_ALPHA)),
            'minpercent' => max(0, min(100, optional_param('minpercent', 0, PARAM_INT))),
            'maxpercent' => max(0, min(100, optional_param('maxpercent', 100, PARAM_INT))),
            'minsessions' => max(0, optional_param('minsessions', 0, PARAM_INT)),
        ];
    }

    /**
     * Method to_url_params.
     *
     * @param array $filters Parameter filters.
     * @return array Return value.
     */
    public static function to_url_params(array $filters): array {
        return [
            'from' => (string)($filters['fromdate'] ?? ''),
            'to' => (string)($filters['todate'] ?? ''),
            'groupid' => (int)($filters['groupid'] ?? 0),
            'groupingid' => (int)($filters['groupingid'] ?? 0),
            'cohortid' => (int)($filters['cohortid'] ?? 0),
            'status' => self::status((string)($filters['status'] ?? 'all')),
            'minpercent' => max(0, min(100, (int)($filters['minpercent'] ?? 0))),
            'maxpercent' => max(0, min(100, (int)($filters['maxpercent'] ?? 100))),
            'minsessions' => max(0, (int)($filters['minsessions'] ?? 0)),
        ];
    }

    /**
     * Method status.
     *
     * @param string $status Parameter status.
     * @return string Return value.
     */
    private static function status(string $status): string {
        return in_array($status, ['all', 'completed', 'incomplete'], true) ? $status : 'all';
    }

    /**
     * Method parse_date_value.
     *
     * @param string $value Parameter value.
     * @param bool $endofday Parameter endofday.
     * @return int Return value.
     */
    public static function parse_date_value(string $value, bool $endofday = false): int {
        if (!self::valid_date($value)) {
            return 0;
        }
        $timezone = \core_date::get_server_timezone_object();
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        if (!$date) {
            return 0;
        }
        if ($endofday) {
            $date = $date->setTime(23, 59, 59);
        }
        return $date->getTimestamp();
    }

    /**
     * Method valid_date.
     *
     * @param string $value Parameter value.
     * @return bool Return value.
     */
    private static function valid_date(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $timezone = \core_date::get_server_timezone_object();
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        return $date && $date->format('Y-m-d') === $value;
    }
}
