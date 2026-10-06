<?php
namespace mod_videotrackermax\report;

use context;
use context_module;
use context_system;
use stdClass;

class service {
    private stdClass $activity;
    private stdClass $cm;
    private context_module $context;
    private int $minimum;

    public function __construct(stdClass $activity, stdClass $cm, context_module $context) {
        $this->activity = $activity;
        $this->cm = $cm;
        $this->context = $context;
        $configuredminimum = get_config('videotrackermax', 'minaggregateusers');
        $this->minimum = max(1, $configuredminimum === false ? 5 : (int)$configuredminimum);
    }

    public function minimum_population(): int {
        return $this->minimum;
    }

    public function group_options(): array {
        $options = [0 => get_string('allparticipants', 'videotrackermax')];
        foreach ($this->visible_groups() as $group) {
            $options[(int)$group->id] = format_string($group->name);
        }
        return $options;
    }

    public function grouping_options(): array {
        global $DB;
        $options = [0 => get_string('allgroupings', 'videotrackermax')];
        $groupings = $DB->get_records('groupings', ['courseid' => $this->activity->course], 'name ASC');
        foreach ($groupings as $grouping) {
            $options[(int)$grouping->id] = format_string($grouping->name);
        }
        return $options;
    }

    public function cohort_options(): array {
        global $DB;
        $options = [0 => get_string('allcohorts', 'videotrackermax')];
        $cohorts = $DB->get_records_select('cohort', 'visible = :visible', ['visible' => 1], 'name ASC', 'id,name,contextid');
        foreach ($cohorts as $cohort) {
            $cohortcontext = context::instance_by_id((int)$cohort->contextid, IGNORE_MISSING);
            if ($cohortcontext && has_any_capability(['moodle/cohort:view', 'moodle/cohort:manage'], $cohortcontext)) {
                $options[(int)$cohort->id] = format_string($cohort->name);
            }
        }
        return $options;
    }

    public function user_summaries(array $filters): array {
        global $DB;

        [$where, $params] = $this->user_where($filters);
        $sql = "SELECT u.userid,
                       MAX(u.duration) AS duration,
                       SUM(u.watchtime) AS watchtime,
                       SUM(u.sessions) AS sessions,
                       MAX(u.completed) AS reachedend,
                       AVG(u.speedavg) AS speedavg,
                       MIN(NULLIF(u.firststarted, 0)) AS firststarted,
                       MAX(u.lastended) AS lastended
                  FROM {videotrackermax_user} u
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY u.userid";

        $records = array_values($DB->get_records_sql($sql, $params));
        if (!$records) {
            return [];
        }

        // Daily percentages cannot be combined with MAX() or SUM(): a learner can
        // watch different halves on different days. Rebuild period coverage from
        // distinct materialized learner/bucket rows instead.
        $userids = array_map(static fn($row): int => (int)$row->userid, $records);
        $bucketwhere = [
            'activityid = :bucketactivityid',
            'mediahash = :bucketmediahash',
            'watched = :bucketwatched',
        ];
        $bucketparams = [
            'bucketactivityid' => (int)$this->activity->id,
            'bucketmediahash' => \local_video_bridge\analytics::media_hash(
                (string)$this->activity->videosource,
                (string)$this->activity->sourceconfig
            ),
            'bucketwatched' => 1,
        ];
        $this->apply_dates($bucketwhere, $bucketparams, $filters, '');
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'coverageuser');
        $bucketwhere[] = 'userid ' . $insql;
        $bucketparams += $inparams;

        $coverage = $DB->get_records_sql(
            "SELECT userid, COUNT(DISTINCT bucket) AS watchedbuckets
               FROM {videotrackermax_bucket}
              WHERE " . implode(' AND ', $bucketwhere) . "
           GROUP BY userid",
            $bucketparams
        );

        $bucketcount = max(1, (int)$this->activity->bucketcount);
        $completionpercent = max(0, min(100, (int)$this->activity->completionpercent));
        $mediahash = \local_video_bridge\analytics::media_hash(
            (string)$this->activity->videosource,
            (string)$this->activity->sourceconfig
        );
        $progressrows = \local_video_bridge\progress\manager::get_activity_progress(
            $this->context->id,
            'mod_videotrackermax',
            (int)$this->activity->id,
            $mediahash,
            $userids
        );
        $progressbyuser = [];
        foreach ($progressrows as $progress) {
            $progressbyuser[(int)$progress->userid] = $progress;
        }

        foreach ($records as $row) {
            $watchedbuckets = isset($coverage[$row->userid])
                ? (int)$coverage[$row->userid]->watchedbuckets
                : 0;
            $row->percent = (int)round(($watchedbuckets / $bucketcount) * 100);
            $row->percent = max(0, min(100, $row->percent));
            $row->reachedend = !empty($row->reachedend) ? 1 : 0;
            $row->authoritativepercent = isset($progressbyuser[$row->userid])
                ? (int)$progressbyuser[$row->userid]->percent
                : 0;
            $row->completed = $completionpercent > 0
                ? ($row->authoritativepercent >= $completionpercent ? 1 : 0)
                : $row->reachedend;
        }

        $status = clean_param((string)($filters['status'] ?? 'all'), PARAM_ALPHA);
        $minpercent = max(0, min(100, (int)($filters['minpercent'] ?? 0)));
        $maxpercent = max($minpercent, min(100, (int)($filters['maxpercent'] ?? 100)));
        $minsessions = max(0, (int)($filters['minsessions'] ?? 0));

        return array_values(array_filter($records, static function($row) use ($status, $minpercent, $maxpercent, $minsessions) {
            if ($status === 'completed' && empty($row->completed)) {
                return false;
            }
            if ($status === 'incomplete' && !empty($row->completed)) {
                return false;
            }
            return (int)$row->percent >= $minpercent
                && (int)$row->percent <= $maxpercent
                && (int)$row->sessions >= $minsessions;
        }));
    }

    public function heatmap(array $filters, ?array $summaries = null): array {
        global $DB;

        $summaries ??= $this->user_summaries($filters);
        $userids = array_map(static fn($row): int => (int)$row->userid, $summaries);
        $bucketcount = max(10, min(1000, (int)$this->activity->bucketcount));
        $empty = [];
        for ($i = 0; $i < $bucketcount; $i++) {
            $empty[$i] = [
                'bucket' => $i,
                'viewers' => 0,
                'plays' => 0,
                'replays' => 0,
                'pauses' => 0,
                'skips' => 0,
                'dropoffs' => 0,
            ];
        }
        if (!$userids) {
            return array_values($empty);
        }

        $where = [
            'activityid = :activityid',
            'mediahash = :mediahash',
        ];
        $params = [
            'activityid' => (int)$this->activity->id,
            'mediahash' => \local_video_bridge\analytics::media_hash(
                (string)$this->activity->videosource,
                (string)$this->activity->sourceconfig
            ),
        ];
        $this->apply_dates($where, $params, $filters, '');
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'heatuser');
        $where[] = 'userid ' . $insql;
        $params += $inparams;

        $sql = "SELECT x.bucket,
                       SUM(x.watched) AS viewers,
                       SUM(x.plays) AS plays,
                       SUM(x.replays) AS replays,
                       SUM(x.pauses) AS pauses,
                       SUM(x.skips) AS skips,
                       SUM(x.dropoffs) AS dropoffs
                  FROM (
                        SELECT userid, bucket,
                               MAX(watched) AS watched,
                               SUM(plays) AS plays,
                               SUM(replays) AS replays,
                               SUM(pauses) AS pauses,
                               SUM(skips) AS skips,
                               SUM(dropoffs) AS dropoffs
                          FROM {videotrackermax_bucket}
                         WHERE " . implode(' AND ', $where) . "
                      GROUP BY userid, bucket
                  ) x
              GROUP BY x.bucket
              ORDER BY x.bucket";
        foreach ($DB->get_records_sql($sql, $params) as $row) {
            $bucket = (int)$row->bucket;
            if (isset($empty[$bucket])) {
                foreach (['viewers', 'plays', 'replays', 'pauses', 'skips', 'dropoffs'] as $field) {
                    $empty[$bucket][$field] = (int)$row->{$field};
                }
            }
        }
        return array_values($empty);
    }

    public function metrics(array $filters): array {
        $summaries = $this->user_summaries($filters);
        $heatmap = $this->heatmap($filters, $summaries);
        $count = count($summaries);
        $percents = array_map(static fn($row): int => (int)$row->percent, $summaries);
        $watchtimes = array_map(static fn($row): int => (int)$row->watchtime, $summaries);
        $sessions = array_map(static fn($row): int => (int)$row->sessions, $summaries);
        $completed = count(array_filter($summaries, static fn($row): bool => !empty($row->completed)));
        $reachedend = count(array_filter($summaries, static fn($row): bool => !empty($row->reachedend)));
        $duration = $summaries ? max(array_map(static fn($row): int => (int)$row->duration, $summaries)) : 0;

        $metric = [
            'population' => $count,
            'suppressed' => $count > 0 && $count < $this->minimum,
            'started' => $count,
            'completed' => $completed,
            'averagepercent' => $count ? array_sum($percents) / $count : 0,
            'medianpercent' => self::median($percents),
            'averagewatchtime' => $count ? array_sum($watchtimes) / $count : 0,
            'averagesessions' => $count ? array_sum($sessions) / $count : 0,
            'averagespeed' => $count
                ? array_sum(array_map(static fn($row): float => (float)$row->speedavg, $summaries)) / $count
                : 1.0,
            'endpercent' => $count ? ($reachedend / $count) * 100 : 0,
            'duration' => $duration,
            'heatmap' => $heatmap,
        ];

        foreach ([
            'maxdropoff' => 'dropoffs',
            'maxreplay' => 'replays',
            'maxskip' => 'skips',
            'maxpause' => 'pauses',
        ] as $target => $field) {
            $best = null;
            foreach ($heatmap as $bucket) {
                if ($best === null || $bucket[$field] > $best[$field]) {
                    $best = $bucket;
                }
            }
            $metric[$target] = $best
                ? $this->bucket_position((int)$best['bucket'], $duration)
                : 0;
            $metric[$target . 'count'] = $best ? (int)$best[$field] : 0;
        }

        return $metric;
    }

    public function retention(array $filters, ?array $summaries = null): array {
        $summaries ??= $this->user_summaries($filters);
        $population = count($summaries);
        $heatmap = $this->heatmap($filters, $summaries);
        $duration = $summaries ? max(array_map(static fn($row): int => (int)$row->duration, $summaries)) : 0;
        $series = [];
        foreach ($heatmap as $bucket) {
            $series[] = [
                'bucket' => (int)$bucket['bucket'],
                'time' => $this->bucket_position((int)$bucket['bucket'], $duration),
                'percent' => $population > 0 ? ((int)$bucket['viewers'] / $population) * 100 : 0,
                'viewers' => (int)$bucket['viewers'],
            ];
        }
        return $series;
    }

    public function comparison(array $filters, string $type, int $a, int $b): array {
        $result = [];
        foreach (['a' => $a, 'b' => $b] as $key => $id) {
            $copy = $filters;
            $copy['groupid'] = 0;
            $copy['groupingid'] = 0;
            $copy['cohortid'] = 0;
            if ($type === 'group') {
                $copy['groupid'] = $id;
            } else if ($type === 'grouping') {
                $copy['groupingid'] = $id;
            } else if ($type === 'cohort') {
                $copy['cohortid'] = $id;
            } else {
                throw new \invalid_parameter_exception('Invalid comparison type.');
            }
            $summaries = $this->user_summaries($copy);
            $metrics = $this->metrics($copy);
            $result[$key] = [
                'id' => $id,
                'label' => $this->dimension_label($type, $id),
                'metrics' => $metrics,
                'retention' => $metrics['suppressed'] ? [] : $this->retention($copy, $summaries),
            ];
        }
        return $result;
    }

    public function period_comparison(array $filters, array $a, array $b): array {
        $result = [];
        foreach (['a' => $a, 'b' => $b] as $key => $period) {
            $copy = $filters;
            $copy['from'] = max(0, (int)($period['from'] ?? 0));
            $copy['to'] = max(0, (int)($period['to'] ?? 0));
            $metrics = $this->metrics($copy);
            $result[$key] = [
                'id' => 0,
                'label' => (string)($period['label'] ?? ''),
                'metrics' => $metrics,
                'retention' => $metrics['suppressed'] ? [] : $this->retention($copy),
            ];
        }
        return $result;
    }

    public function dimension_options(string $type): array {
        if ($type === 'group') {
            $options = $this->group_options();
        } else if ($type === 'grouping') {
            $options = $this->grouping_options();
        } else if ($type === 'cohort') {
            $options = $this->cohort_options();
        } else {
            return [];
        }
        unset($options[0]);
        return $options;
    }

    public static function median(array $values): float {
        if (!$values) {
            return 0.0;
        }
        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = intdiv($count, 2);
        return $count % 2
            ? (float)$values[$middle]
            : ((float)$values[$middle - 1] + (float)$values[$middle]) / 2;
    }

    private function user_where(array $filters): array {
        global $DB;

        $where = [
            'u.activityid = :activityid',
            'u.mediahash = :mediahash',
        ];
        $params = [
            'activityid' => (int)$this->activity->id,
            'mediahash' => \local_video_bridge\analytics::media_hash(
                (string)$this->activity->videosource,
                (string)$this->activity->sourceconfig
            ),
        ];
        $this->apply_dates($where, $params, $filters, 'u.');

        $population = $this->population_userids($filters);
        if ($population !== null) {
            if (!$population) {
                $where[] = '1 = 0';
            } else {
                [$insql, $inparams] = $DB->get_in_or_equal($population, SQL_PARAMS_NAMED, 'population');
                $where[] = 'u.userid ' . $insql;
                $params += $inparams;
            }
        }
        return [$where, $params];
    }

    private function population_userids(array $filters): ?array {
        global $DB, $USER;

        $population = null;
        $groupmode = groups_get_activity_groupmode($this->cm);
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $this->context)) {
            $own = groups_get_all_groups((int)$this->activity->course, (int)$USER->id, (int)$this->cm->groupingid);
            $population = $this->users_in_groups(array_map('intval', array_keys($own)));
        }

        $groupid = max(0, (int)($filters['groupid'] ?? 0));
        if ($groupid) {
            $groups = $this->visible_groups();
            if (!isset($groups[$groupid])) {
                throw new \required_capability_exception(
                    $this->context,
                    'moodle/site:accessallgroups',
                    'nopermissions',
                    ''
                );
            }
            $population = $this->intersect($population, $this->users_in_groups([$groupid]));
        }

        $groupingid = max(0, (int)($filters['groupingid'] ?? 0));
        if ($groupingid) {
            $grouping = $DB->get_record('groupings', [
                'id' => $groupingid,
                'courseid' => $this->activity->course,
            ], '*', MUST_EXIST);
            $groups = groups_get_all_groups((int)$this->activity->course, 0, (int)$grouping->id);
            $allowedgroups = array_intersect_key($groups, $this->visible_groups());
            $population = $this->intersect(
                $population,
                $this->users_in_groups(array_map('intval', array_keys($allowedgroups)))
            );
        }

        $cohortid = max(0, (int)($filters['cohortid'] ?? 0));
        if ($cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $cohortid, 'visible' => 1], '*', MUST_EXIST);
            $cohortcontext = context::instance_by_id((int)$cohort->contextid, MUST_EXIST);
            require_any_capability(['moodle/cohort:view', 'moodle/cohort:manage'], $cohortcontext);
            $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = :cohortid', [
                'cohortid' => $cohortid,
            ]);
            $population = $this->intersect($population, array_map('intval', $members));
        }

        return $population;
    }

    private function visible_groups(): array {
        global $USER;

        if (has_capability('moodle/site:accessallgroups', $this->context)) {
            return groups_get_all_groups((int)$this->activity->course, 0, (int)$this->cm->groupingid);
        }
        return groups_get_all_groups((int)$this->activity->course, (int)$USER->id, (int)$this->cm->groupingid);
    }

    private function users_in_groups(array $groupids): array {
        $users = [];
        foreach ($groupids as $groupid) {
            foreach (groups_get_members((int)$groupid, 'u.id') as $user) {
                $users[(int)$user->id] = (int)$user->id;
            }
        }
        return array_values($users);
    }

    private function intersect(?array $current, array $next): array {
        $next = array_values(array_unique(array_map('intval', $next)));
        if ($current === null) {
            return $next;
        }
        return array_values(array_intersect($current, $next));
    }

    private function apply_dates(array &$where, array &$params, array $filters, string $prefix): void {
        if (!empty($filters['from'])) {
            $where[] = $prefix . 'day >= :fromday';
            $params['fromday'] = \mod_videotrackermax\aggregation\calculator::day_start((int)$filters['from']);
        }
        if (!empty($filters['to'])) {
            $where[] = $prefix . 'day <= :today';
            $params['today'] = \mod_videotrackermax\aggregation\calculator::day_start((int)$filters['to']);
        }
    }

    private function bucket_position(int $bucket, int $duration): int {
        $count = max(1, (int)$this->activity->bucketcount);
        return $duration > 0 ? (int)floor(($bucket / $count) * $duration) : 0;
    }

    private function dimension_label(string $type, int $id): string {
        $options = $this->dimension_options($type);
        return $options[$id] ?? (string)$id;
    }
}
