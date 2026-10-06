<?php
require('../../config.php');

use mod_videotrackermax\report\filters;
use mod_videotrackermax\report\service;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackermax', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackermax:export', $context);

$filters = filters::from_request();
$report = new service($activity, $cm, $context);
$metrics = $report->metrics($filters);

if ($metrics['population'] > 0 && $metrics['population'] < $report->minimum_population()) {
    throw new moodle_exception('suppressedexport', 'videotrackermax');
}

$filename = clean_filename('video-tracker-max-' . $activity->id . '-' . userdate(time(), '%Y%m%d-%H%M') . '.csv');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['metric', 'value']);
foreach ([
    'population' => $metrics['population'],
    'started' => $metrics['started'],
    'completed' => $metrics['completed'],
    'average_percent' => round($metrics['averagepercent'], 2),
    'median_percent' => round($metrics['medianpercent'], 2),
    'average_watch_time_seconds' => round($metrics['averagewatchtime'], 2),
    'average_sessions' => round($metrics['averagesessions'], 2),
    'average_speed' => round($metrics['averagespeed'], 4),
    'reached_end_percent' => round($metrics['endpercent'], 2),
    'max_dropoff_second' => $metrics['maxdropoff'],
    'most_replayed_second' => $metrics['maxreplay'],
    'most_skipped_second' => $metrics['maxskip'],
    'most_paused_second' => $metrics['maxpause'],
] as $metric => $value) {
    fputcsv($out, [$metric, $value]);
}

fputcsv($out, []);
fputcsv($out, ['bucket', 'start_second', 'viewers', 'viewer_percent', 'plays', 'replays', 'pauses', 'skips', 'dropoffs']);
$bucketcount = max(1, count($metrics['heatmap']));
foreach ($metrics['heatmap'] as $bucket) {
    $start = (int)floor(((int)$bucket['bucket'] / $bucketcount) * (int)$metrics['duration']);
    $viewerpercent = $metrics['population'] > 0
        ? round(((int)$bucket['viewers'] / (int)$metrics['population']) * 100, 2)
        : 0;
    fputcsv($out, [
        (int)$bucket['bucket'],
        $start,
        (int)$bucket['viewers'],
        $viewerpercent,
        (int)$bucket['plays'],
        (int)$bucket['replays'],
        (int)$bucket['pauses'],
        (int)$bucket['skips'],
        (int)$bucket['dropoffs'],
    ]);
}
fclose($out);
exit;
