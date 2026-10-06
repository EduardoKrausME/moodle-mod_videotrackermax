<?php
namespace mod_videotrackermax\aggregation;

use context_module;
use local_video_bridge\analytics;
use stdClass;

class service {
    public function process_all(): void {
        global $DB;

        $activities = $DB->get_records('videotrackermax', null, 'id ASC');
        foreach ($activities as $activity) {
            try {
                $this->process_activity($activity);
            } catch (\Throwable $exception) {
                mtrace('Video Tracker Max activity ' . $activity->id . ': ' . $exception->getMessage());
            }
        }
    }

    public function rebuild_activity(stdClass $activity): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        foreach (['videotrackermax_agg', 'videotrackermax_bucket', 'videotrackermax_user', 'videotrackermax_state'] as $table) {
            $DB->delete_records($table, ['activityid' => $activity->id]);
        }
        $transaction->allow_commit();

        $this->process_activity($activity, true);
    }

    public function process_activity(stdClass $activity, bool $force = false): void {
        global $DB;

        $cm = get_coursemodule_from_instance('videotrackermax', $activity->id, $activity->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $mediahash = analytics::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
        $state = $DB->get_record('videotrackermax_state', [
            'activityid' => $activity->id,
            'mediahash' => $mediahash,
        ]);

        $cursor = $force ? 0 : (int)($state->lastprocessed ?? 0);
        $modified = analytics::get_session_metrics(
            $context->id,
            'mod_videotrackermax',
            (int)$activity->id,
            $mediahash,
            null,
            ['modifiedfrom' => max(0, $cursor - 2)]
        );

        if (!$modified) {
            if (!$state) {
                $this->save_state((int)$activity->id, $mediahash, $cursor);
            }
            return;
        }

        $affected = [];
        $maxmodified = $cursor;
        foreach ($modified as $session) {
            $day = calculator::day_start((int)$session->startedat);
            $affected[$day][(int)$session->userid] = true;
            $maxmodified = max($maxmodified, (int)$session->timemodified);
        }

        foreach ($affected as $day => $users) {
            foreach (array_keys($users) as $userid) {
                $this->rebuild_user_day($activity, $context, $mediahash, (int)$day, (int)$userid);
            }
            $this->rebuild_day_groups($activity, $mediahash, (int)$day);
        }

        $this->save_state((int)$activity->id, $mediahash, $maxmodified);
    }

    private function rebuild_user_day(
        stdClass $activity,
        context_module $context,
        string $mediahash,
        int $day,
        int $userid
    ): void {
        global $DB;

        $filters = [
            'from' => $day,
            'to' => $day + DAYSECS - 1,
            'userids' => [$userid],
        ];
        $sessions = analytics::get_session_metrics(
            $context->id,
            'mod_videotrackermax',
            (int)$activity->id,
            $mediahash,
            $userid,
            $filters
        );

        $params = [
            'activityid' => (int)$activity->id,
            'mediahash' => $mediahash,
            'day' => $day,
            'userid' => $userid,
        ];

        if (!$sessions) {
            $DB->delete_records('videotrackermax_user', $params);
            $DB->delete_records('videotrackermax_bucket', $params);
            return;
        }

        $coverage = analytics::get_activity_coverage(
            $context->id,
            'mod_videotrackermax',
            (int)$activity->id,
            $mediahash,
            (int)$activity->bucketcount,
            $filters
        );
        $summary = calculator::summarise($sessions, $coverage, (int)$activity->bucketcount);
        $now = time();

        $record = $DB->get_record('videotrackermax_user', $params);
        $values = (object)($params + [
            'duration' => $summary['duration'],
            'percent' => $summary['percent'],
            'watchtime' => $summary['watchtime'],
            'sessions' => $summary['sessions'],
            'completed' => $summary['completed'],
            'speedavg' => $summary['speedavg'],
            'firststarted' => $summary['firststarted'],
            'lastended' => $summary['lastended'],
            'timemodified' => $now,
        ]);
        if ($record) {
            $values->id = $record->id;
            $DB->update_record('videotrackermax_user', $values);
        } else {
            $DB->insert_record('videotrackermax_user', $values);
        }

        $DB->delete_records('videotrackermax_bucket', $params);
        foreach ($summary['buckets'] as $bucket) {
            $DB->insert_record('videotrackermax_bucket', (object)($params + $bucket + [
                'timemodified' => $now,
            ]));
        }
    }

    private function rebuild_day_groups(stdClass $activity, string $mediahash, int $day): void {
        global $DB;

        $DB->delete_records('videotrackermax_agg', [
            'activityid' => $activity->id,
            'mediahash' => $mediahash,
            'day' => $day,
        ]);

        $groups = [0 => null];
        foreach (groups_get_all_groups((int)$activity->course) as $group) {
            $groups[(int)$group->id] = $group;
        }

        foreach ($groups as $groupid => $group) {
            $userids = null;
            if ($groupid !== 0) {
                $members = groups_get_members($groupid, 'u.id');
                $userids = array_map('intval', array_keys($members));
                if (!$userids) {
                    continue;
                }
            }

            $where = [
                'b.activityid = :activityid',
                'b.mediahash = :mediahash',
                'b.day = :day',
            ];
            $params = [
                'activityid' => (int)$activity->id,
                'mediahash' => $mediahash,
                'day' => $day,
            ];
            if ($userids !== null) {
                [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'groupuser');
                $where[] = 'b.userid ' . $insql;
                $params += $inparams;
            }

            $sql = "SELECT b.bucket,
                           SUM(b.watched) AS viewers,
                           SUM(b.plays) AS plays,
                           SUM(b.replays) AS replays,
                           SUM(b.pauses) AS pauses,
                           SUM(b.skips) AS skips,
                           SUM(b.dropoffs) AS dropoffs
                      FROM {videotrackermax_bucket} b
                     WHERE " . implode(' AND ', $where) . "
                  GROUP BY b.bucket
                  ORDER BY b.bucket";
            $buckets = $DB->get_records_sql($sql, $params);

            $userwhere = [
                'activityid = :uactivityid',
                'mediahash = :umediahash',
                'day = :uday',
            ];
            $userparams = [
                'uactivityid' => (int)$activity->id,
                'umediahash' => $mediahash,
                'uday' => $day,
            ];
            if ($userids !== null) {
                [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'summaryuser');
                $userwhere[] = 'userid ' . $insql;
                $userparams += $inparams;
            }
            $summary = $DB->get_record_sql(
                "SELECT COALESCE(SUM(watchtime), 0) AS watchtime,
                        COALESCE(SUM(sessions), 0) AS sessions,
                        COALESCE(AVG(speedavg), 1) AS speedavg
                   FROM {videotrackermax_user}
                  WHERE " . implode(' AND ', $userwhere),
                $userparams
            );

            $now = time();
            foreach ($buckets as $bucket) {
                $DB->insert_record('videotrackermax_agg', (object)[
                    'activityid' => (int)$activity->id,
                    'mediahash' => $mediahash,
                    'day' => $day,
                    'groupid' => $groupid,
                    'bucket' => (int)$bucket->bucket,
                    'viewers' => (int)$bucket->viewers,
                    'plays' => (int)$bucket->plays,
                    'replays' => (int)$bucket->replays,
                    'pauses' => (int)$bucket->pauses,
                    'skips' => (int)$bucket->skips,
                    'dropoffs' => (int)$bucket->dropoffs,
                    'watchtime' => (int)($summary->watchtime ?? 0),
                    'sessions' => (int)($summary->sessions ?? 0),
                    'speedavg' => (float)($summary->speedavg ?? 1),
                    'timemodified' => $now,
                ]);
            }
        }
    }

    private function save_state(int $activityid, string $mediahash, int $cursor): void {
        global $DB;

        $params = ['activityid' => $activityid, 'mediahash' => $mediahash];
        $record = $DB->get_record('videotrackermax_state', $params);
        $values = (object)($params + [
            'lastprocessed' => $cursor,
            'timemodified' => time(),
        ]);
        if ($record) {
            $values->id = $record->id;
            $DB->update_record('videotrackermax_state', $values);
        } else {
            $DB->insert_record('videotrackermax_state', $values);
        }
    }
}
