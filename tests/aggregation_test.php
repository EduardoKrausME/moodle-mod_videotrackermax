<?php
namespace mod_videotrackermax;

defined('MOODLE_INTERNAL') || die;

use mod_videotrackermax\aggregation\calculator;

final class aggregation_test extends \advanced_testcase {
    public function test_summarise_compact_sessions_and_buckets(): void {
        $sessions = [
            (object)[
                'duration' => 100,
                'watchtime' => 40,
                'speedavg' => 1.0,
                'startedat' => 1000,
                'endedat' => 1040,
                'maxposition' => 40,
                'reachedend' => 0,
            ],
            (object)[
                'duration' => 100,
                'watchtime' => 50,
                'speedavg' => 1.5,
                'startedat' => 2000,
                'endedat' => 2050,
                'maxposition' => 100,
                'reachedend' => 1,
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

    public function test_max_position_without_ended_signal_is_not_reached_end(): void {
        $sessions = [
            (object)[
                'duration' => 100,
                'watchtime' => 10,
                'speedavg' => 1.0,
                'startedat' => 1000,
                'endedat' => 1010,
                'maxposition' => 100,
                'reachedend' => 0,
            ],
        ];
        $coverage = [
            'duration' => 100,
            'buckets' => [
                [
                    'bucket' => 0,
                    'viewers' => 1,
                    'plays' => 1,
                    'replays' => 0,
                    'pauses' => 0,
                    'skips' => 1,
                    'dropoffs' => 1,
                ],
            ],
        ];

        $summary = calculator::summarise($sessions, $coverage, 1);

        $this->assertSame(100, $summary['percent']);
        $this->assertSame(0, $summary['completed']);
    }

}
