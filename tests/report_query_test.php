<?php
namespace mod_videotrackermax;

defined('MOODLE_INTERNAL') || die;

use context_module;
use mod_videotrackermax\report\service;

final class report_query_test extends \advanced_testcase {
    public function test_heatmap_deduplicates_same_user_across_days(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/course/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $module = $DB->get_record('modules', ['name' => 'videotrackermax'], '*', MUST_EXIST);

        $activityid = $DB->insert_record('videotrackermax', (object)[
            'course' => $course->id,
            'name' => 'Analytics',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'videosource' => 'test',
            'sourceconfig' => '{}',
            'videourl' => '',
            'completionpercent' => 0,
            'showstudentprogress' => 1,
            'bucketcount' => 10,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $section = course_get_format($course)->get_section(0);
        $cmrecord = (object)[
            'course' => $course->id,
            'module' => $module->id,
            'instance' => $activityid,
            'section' => $section->id,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'groupmode' => NOGROUPS,
            'groupingid' => 0,
            'completion' => COMPLETION_TRACKING_NONE,
        ];
        $cmid = add_course_module($cmrecord);
        course_add_cm_to_section($course, $cmid, 0);
        $cm = get_coursemodule_from_id('videotrackermax', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cmid);

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($u1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($u2->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($u3->id, $course->id, $studentrole->id);

        $mediahash = \local_video_bridge\analytics::media_hash('test', '{}');
        $day1 = \mod_videotrackermax\aggregation\calculator::day_start(time() - DAYSECS);
        $day2 = \mod_videotrackermax\aggregation\calculator::day_start(time());

        foreach ([
            [$u1->id, $day1, 50],
            [$u1->id, $day2, 80],
            [$u2->id, $day2, 40],
        ] as [$userid, $day, $percent]) {
            $DB->insert_record('videotrackermax_user', (object)[
                'activityid' => $activityid,
                'mediahash' => $mediahash,
                'day' => $day,
                'userid' => $userid,
                'duration' => 100,
                'percent' => $percent,
                'watchtime' => 50,
                'sessions' => 1,
                'completed' => 0,
                'speedavg' => 1,
                'firststarted' => $day + 10,
                'lastended' => $day + 60,
                'timemodified' => time(),
            ]);
            $DB->insert_record('videotrackermax_bucket', (object)[
                'activityid' => $activityid,
                'mediahash' => $mediahash,
                'day' => $day,
                'userid' => $userid,
                'bucket' => 0,
                'watched' => 1,
                'plays' => 1,
                'replays' => 0,
                'pauses' => 0,
                'skips' => 0,
                'dropoffs' => 0,
                'timemodified' => time(),
            ]);
        }

        $activity = $DB->get_record('videotrackermax', ['id' => $activityid], '*', MUST_EXIST);
        $report = new service($activity, $cm, $context);
        $filters = [
            'from' => 0,
            'to' => 0,
            'groupid' => 0,
            'groupingid' => 0,
            'cohortid' => 0,
            'status' => 'all',
            'minpercent' => 0,
            'maxpercent' => 100,
            'minsessions' => 0,
        ];

        $summaries = $report->user_summaries($filters);
        $heatmap = $report->heatmap($filters, $summaries);

        $this->assertCount(3, $summaries);
        // The daily stored percentage must not leak into the period result.
        // Only bucket 0 was watched, so period coverage is 10%, not MAX(50, 80).
        $byuser = [];
        foreach ($summaries as $summary) {
            $byuser[(int)$summary->userid] = $summary;
        }
        $this->assertSame(10, (int)$byuser[$u1->id]->percent);
        $this->assertSame(0, (int)$byuser[$u3->id]->percent);
        $this->assertSame(0, (int)$byuser[$u3->id]->sessions);
        $this->assertSame(2, $heatmap[0]['viewers']);
        $this->assertSame(3, $heatmap[0]['plays']);

        $metrics = $report->metrics($filters);
        $this->assertSame(3, $metrics['population']);
        $this->assertSame(2, $metrics['started']);
    }

    public function test_median_handles_even_and_odd_populations(): void {
        $this->assertSame(50.0, service::median([10, 50, 90]));
        $this->assertSame(40.0, service::median([10, 30, 50, 70]));
        $this->assertSame(0.0, service::median([]));
    }
}
