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
 * aggregation_test.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax;

defined('MOODLE_INTERNAL') || die;

use mod_videotrackermax\aggregation\calculator;

/**
 * Class aggregation_test.
 */
final class aggregation_test extends \advanced_testcase {
    /**
     * Method test_summarise_compact_sessions_and_buckets.
     *
     * @return void Return value.
     */
    public function test_summarise_compact_sessions_and_buckets(): void {
        $sessions = [
            (object)[
                'duration' => 100,
                'watchtime' => 40,
                'speedavg' => 1.0,
                'startedat' => 1000,
                'endedat' => 1040,
                'maxposition' => 40,
            ],
            (object)[
                'duration' => 100,
                'watchtime' => 50,
                'speedavg' => 1.5,
                'startedat' => 2000,
                'endedat' => 2050,
                'maxposition' => 100,
            ],
        ];
        $coverage = [
            'duration' => 100,
            'buckets' => [
                ['bucket' => 0, 'viewers' => 1, 'plays' => 2, 'replays' => 0, 'pauses' => 1, 'skips' => 0, 'dropoffs' => 0],
                ['bucket' => 1, 'viewers' => 1, 'plays' => 0, 'replays' => 1, 'pauses' => 0, 'skips' => 1, 'dropoffs' => 1],
                ['bucket' => 2, 'viewers' => 0, 'plays' => 0, 'replays' => 0, 'pauses' => 0, 'skips' => 0, 'dropoffs' => 0],
                ['bucket' => 3, 'viewers' => 1, 'plays' => 0, 'replays' => 0, 'pauses' => 0, 'skips' => 0, 'dropoffs' => 0],
            ],
        ];

        $summary = calculator::summarise($sessions, $coverage, 4);

        $this->assertSame(100, $summary['duration']);
        $this->assertSame(90, $summary['watchtime']);
        $this->assertSame(2, $summary['sessions']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(75, $summary['percent']);
        $this->assertSame(3, count($summary['buckets']));
        $this->assertGreaterThan(1.2, $summary['speedavg']);
        $this->assertLessThan(1.3, $summary['speedavg']);
    }
}
