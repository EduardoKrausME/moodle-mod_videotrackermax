<?php
require('../../config.php');

use local_video_bridge\analytics;
use local_video_bridge\progress\manager as progress_manager;
use mod_videotrackermax\aggregation\calculator;
use mod_videotrackermax\report\filters;
use mod_videotrackermax\report\service;

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('videotrackermax', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackermax:viewindividual', $context);

$filters = filters::from_request();
$report = new service($activity, $cm, $context);
$summaries = $report->user_summaries($filters);
$summary = null;
foreach ($summaries as $candidate) {
    if ((int)$candidate->userid === $userid) {
        $summary = $candidate;
        break;
    }
}
if (!$summary) {
    throw new moodle_exception('individualnotavailable', 'videotrackermax');
}

$user = core_user::get_user($userid, '*', MUST_EXIST);
$mediahash = analytics::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
$progress = progress_manager::get_progress(
    $context->id,
    'mod_videotrackermax',
    (int)$activity->id,
    $mediahash,
    $userid
);

$where = [
    'activityid = :activityid',
    'mediahash = :mediahash',
    'userid = :userid',
];
$params = [
    'activityid' => (int)$activity->id,
    'mediahash' => $mediahash,
    'userid' => $userid,
];
if (!empty($filters['from'])) {
    $where[] = 'day >= :fromday';
    $params['fromday'] = calculator::day_start((int)$filters['from']);
}
if (!empty($filters['to'])) {
    $where[] = 'day <= :today';
    $params['today'] = calculator::day_start((int)$filters['to']);
}
$daily = $DB->get_records_select(
    'videotrackermax_user',
    implode(' AND ', $where),
    $params,
    'day ASC'
);

$heatmap = $report->heatmap($filters, [$summary]);
$bucketcount = max(1, count($heatmap));
$duration = (int)$summary->duration;
$heatmapdata = [];
foreach ($heatmap as $bucket) {
    $watched = !empty($bucket['viewers']);
    $start = $duration > 0
        ? (int)floor(((int)$bucket['bucket'] / $bucketcount) * $duration)
        : 0;
    $heatmapdata[] = [
        'bucket' => (int)$bucket['bucket'],
        'intensity' => $watched ? '0.9' : '0',
        'title' => gmdate($start >= HOURSECS ? 'H:i:s' : 'i:s', $start) .
            ' — ' . ($watched ? get_string('watched', 'videotrackermax') : get_string('notwatched', 'videotrackermax')),
    ];
}

$PAGE->set_url('/mod/videotrackermax/student.php', [
    'id' => $cm->id,
    'userid' => $userid,
] + filters::to_url_params($filters));
$PAGE->set_title(get_string('individualreport', 'videotrackermax') . ': ' . fullname($user));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

function mod_videotrackermax_student_time(int $seconds): string {
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, HOURSECS);
    $minutes = intdiv($seconds % HOURSECS, MINSECS);
    $remaining = $seconds % MINSECS;
    return $hours > 0
        ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining)
        : sprintf('%02d:%02d', $minutes, $remaining);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('individualreport', 'videotrackermax') . ': ' . fullname($user));

echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/videotrackermax/dashboard.php', [
            'id' => $cm->id,
            'section' => 'students',
        ] + filters::to_url_params($filters)),
        get_string('backtodashboard', 'videotrackermax'),
        ['class' => 'btn btn-outline-secondary']
    ),
    'mb-3'
);

$table = new html_table();
$table->head = [get_string('metric', 'videotrackermax'), get_string('value', 'videotrackermax')];
$table->data = [
    [get_string('watchedpercent', 'videotrackermax'), (int)$summary->percent . '%'],
    [get_string('watchtime', 'videotrackermax'), mod_videotrackermax_student_time((int)$summary->watchtime)],
    [get_string('sessions', 'videotrackermax'), (int)$summary->sessions],
    [get_string('averagespeed', 'videotrackermax'), number_format((float)$summary->speedavg, 2) . 'x'],
    [
        get_string('completionstatus', 'videotrackermax'),
        !empty($summary->completed)
            ? get_string('completed', 'completion')
            : get_string('notcompleted', 'completion'),
    ],
    [
        get_string('reachedend', 'videotrackermax'),
        !empty($summary->reachedend) ? get_string('yes') : get_string('no'),
    ],
    [
        get_string('authoritativeprogress', 'videotrackermax'),
        $progress ? (int)$progress->percent . '%' : '0%',
    ],
];
echo html_writer::table($table);

echo html_writer::tag('h3', get_string('individualheatmap', 'videotrackermax'), ['class' => 'h4 mt-4']);
echo $OUTPUT->render_from_template('mod_videotrackermax/heatmap', [
    'label' => get_string('individualheatmap', 'videotrackermax'),
    'buckets' => $heatmapdata,
    'endlabel' => mod_videotrackermax_student_time($duration),
]);

echo html_writer::tag('h3', get_string('dailybreakdown', 'videotrackermax'), ['class' => 'h4 mt-4']);
$dailytable = new html_table();
$dailytable->head = [
    get_string('date'),
    get_string('watchedpercent', 'videotrackermax'),
    get_string('watchtime', 'videotrackermax'),
    get_string('sessions', 'videotrackermax'),
    get_string('reachedend', 'videotrackermax'),
];
foreach ($daily as $row) {
    $dailytable->data[] = [
        userdate((int)$row->day, get_string('strftimedatefullshort', 'langconfig')),
        (int)$row->percent . '%',
        mod_videotrackermax_student_time((int)$row->watchtime),
        (int)$row->sessions,
        !empty($row->completed) ? get_string('yes') : get_string('no'),
    ];
}
echo html_writer::table($dailytable);
echo $OUTPUT->footer();
