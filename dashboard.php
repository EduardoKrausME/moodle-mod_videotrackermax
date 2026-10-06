<?php
require('../../config.php');

use mod_videotrackermax\report\filters;
use mod_videotrackermax\report\service;

$id = required_param('id', PARAM_INT);
$section = optional_param('section', 'overview', PARAM_ALPHA);
$allowedsections = ['overview', 'retention', 'heatmap', 'comparison', 'students'];
if (!in_array($section, $allowedsections, true)) {
    $section = 'overview';
}

$cm = get_coursemodule_from_id('videotrackermax', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackermax:viewanalytics', $context);
if ($section === 'students') {
    require_capability('mod/videotrackermax:viewindividual', $context);
}

$PAGE->set_url('/mod/videotrackermax/dashboard.php', ['id' => $cm->id, 'section' => $section]);
$PAGE->set_title(get_string('dashboard', 'videotrackermax') . ': ' . format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

$filters = filters::from_request();
$report = new service($activity, $cm, $context);
$metrics = $report->metrics($filters);
$filterparams = filters::to_url_params($filters);

function mod_videotrackermax_format_seconds(float $seconds): string {
    $seconds = max(0, (int)round($seconds));
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remaining = $seconds % 60;
    return $hours > 0
        ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining)
        : sprintf('%02d:%02d', $minutes, $remaining);
}

function mod_videotrackermax_metric_card(string $label, string $value, string $hint = ''): string {
    $content = html_writer::div($label, 'small text-muted text-uppercase');
    $content .= html_writer::div($value, 'metric-value mt-1');
    if ($hint !== '') {
        $content .= html_writer::div($hint, 'small text-muted mt-1');
    }
    return html_writer::div(html_writer::div($content, 'card-body'), 'card metric-card h-100');
}

function mod_videotrackermax_filter_form(
    stdClass $cm,
    string $section,
    array $filters,
    service $report
): string {
    $fields = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    $fields .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'section', 'value' => $section]);

    $controls = [];
    $controls[] = html_writer::tag('label',
        get_string('filterfrom', 'videotrackermax') .
        html_writer::empty_tag('input', [
            'type' => 'date',
            'name' => 'from',
            'value' => $filters['fromdate'],
            'class' => 'form-control mt-1',
        ]),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('filterto', 'videotrackermax') .
        html_writer::empty_tag('input', [
            'type' => 'date',
            'name' => 'to',
            'value' => $filters['todate'],
            'class' => 'form-control mt-1',
        ]),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('group', 'group') .
        html_writer::select($report->group_options(), 'groupid', $filters['groupid'], false, ['class' => 'form-select mt-1']),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('grouping', 'group') .
        html_writer::select($report->grouping_options(), 'groupingid', $filters['groupingid'], false, ['class' => 'form-select mt-1']),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('cohort', 'cohort') .
        html_writer::select($report->cohort_options(), 'cohortid', $filters['cohortid'], false, ['class' => 'form-select mt-1']),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('completionstatus', 'videotrackermax') .
        html_writer::select([
            'all' => get_string('all'),
            'completed' => get_string('completed', 'completion'),
            'incomplete' => get_string('notcompleted', 'completion'),
        ], 'status', $filters['status'], false, ['class' => 'form-select mt-1']),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('minpercent', 'videotrackermax') .
        html_writer::empty_tag('input', [
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'name' => 'minpercent',
            'value' => $filters['minpercent'],
            'class' => 'form-control mt-1',
        ]),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('maxpercent', 'videotrackermax') .
        html_writer::empty_tag('input', [
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'name' => 'maxpercent',
            'value' => $filters['maxpercent'],
            'class' => 'form-control mt-1',
        ]),
        ['class' => 'form-label']
    );
    $controls[] = html_writer::tag('label',
        get_string('minsessions', 'videotrackermax') .
        html_writer::empty_tag('input', [
            'type' => 'number',
            'min' => 0,
            'name' => 'minsessions',
            'value' => $filters['minsessions'],
            'class' => 'form-control mt-1',
        ]),
        ['class' => 'form-label']
    );

    $grid = '';
    foreach ($controls as $control) {
        $grid .= html_writer::div($control, 'col-12 col-md-6 col-xl');
    }
    $fields .= html_writer::div($grid, 'row g-2 align-items-end');
    $fields .= html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('applyfilters', 'videotrackermax'),
            'class' => 'btn btn-primary',
        ]),
        'mt-3'
    );

    return html_writer::tag('form', $fields, [
        'method' => 'get',
        'action' => (new moodle_url('/mod/videotrackermax/dashboard.php'))->out(false),
        'class' => 'videotrackermax-filterbar',
    ]);
}

function mod_videotrackermax_retention_chart(array $series, string $title): \core\chart_line {
    $labels = [];
    $values = [];
    foreach ($series as $index => $point) {
        $labels[] = $index % max(1, (int)ceil(count($series) / 12)) === 0
            ? mod_videotrackermax_format_seconds((int)$point['time'])
            : '';
        $values[] = round((float)$point['percent'], 2);
    }
    $chart = new \core\chart_line();
    $chart->set_title($title);
    $chart->set_labels($labels);
    $chart->add_series(new \core\chart_series(get_string('retention', 'videotrackermax'), $values));
    $chart->set_legend_options(['position' => 'bottom']);
    return $chart;
}

function mod_videotrackermax_heatmap_html(
    renderer_base $output,
    array $rows,
    string $metric,
    int $duration,
    int $population
): string {
    $allowed = ['viewers', 'replays', 'skips', 'pauses', 'dropoffs'];
    if (!in_array($metric, $allowed, true)) {
        $metric = 'viewers';
    }
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int)$row[$metric]);
    }

    $buckets = [];
    $count = max(1, count($rows));
    foreach ($rows as $row) {
        $value = (int)$row[$metric];
        $intensity = $max > 0 ? 0.08 + (0.92 * ($value / $max)) : 0;
        $start = (int)floor(((int)$row['bucket'] / $count) * $duration);
        $percent = $metric === 'viewers' && $population > 0
            ? round(($value / $population) * 100, 1)
            : null;
        $title = mod_videotrackermax_format_seconds($start) . ' — ' .
            get_string('metric:' . $metric, 'videotrackermax') . ': ' . $value;
        if ($percent !== null) {
            $title .= ' (' . $percent . '%)';
        }
        $buckets[] = [
            'bucket' => (int)$row['bucket'],
            'intensity' => number_format($intensity, 3, '.', ''),
            'title' => $title,
        ];
    }

    return $output->render_from_template('mod_videotrackermax/heatmap', [
        'label' => get_string('heatmap:' . $metric, 'videotrackermax'),
        'buckets' => $buckets,
        'endlabel' => mod_videotrackermax_format_seconds($duration),
    ]);
}

$tabs = [];
foreach ($allowedsections as $tabsection) {
    if ($tabsection === 'students' && !has_capability('mod/videotrackermax:viewindividual', $context)) {
        continue;
    }
    $tabs[] = new tabobject(
        $tabsection,
        new moodle_url('/mod/videotrackermax/dashboard.php',
            ['id' => $cm->id, 'section' => $tabsection] + $filterparams),
        get_string('tab:' . $tabsection, 'videotrackermax')
    );
}

echo $OUTPUT->header();
echo html_writer::start_div('videotrackermax-dashboard');
echo html_writer::div(
    html_writer::tag('h2', get_string('dashboard', 'videotrackermax')) .
    html_writer::div(format_string($activity->name), 'text-muted'),
    'mb-3'
);
echo $OUTPUT->tabtree($tabs, $section);
echo mod_videotrackermax_filter_form($cm, $section, $filters, $report);

$actions = [];
if (has_capability('mod/videotrackermax:export', $context)) {
    $actions[] = html_writer::link(
        new moodle_url('/mod/videotrackermax/export.php', ['id' => $cm->id] + $filterparams),
        get_string('exportcsv', 'videotrackermax'),
        ['class' => 'btn btn-outline-secondary']
    );
}
if (has_capability('mod/videotrackermax:rebuild', $context)) {
    $actions[] = html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-outline-danger',
            'value' => get_string('rebuildaggregation', 'videotrackermax'),
        ]),
        ['method' => 'post', 'action' => (new moodle_url('/mod/videotrackermax/rebuild.php'))->out(false),
            'class' => 'd-inline']
    );
}
if ($actions) {
    echo html_writer::div(implode(' ', $actions), 'mb-3 d-flex gap-2 flex-wrap');
}

if ($section !== 'students' && $metrics['suppressed']) {
    echo html_writer::div(
        get_string('suppressed', 'videotrackermax', $report->minimum_population()),
        'alert alert-warning videotrackermax-suppressed'
    );
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

if ($section !== 'students' && $metrics['population'] === 0) {
    echo $OUTPUT->notification(get_string('nodata', 'videotrackermax'), 'info');
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

if ($section === 'overview') {
    $cards = [
        [get_string('started', 'videotrackermax'), (string)$metrics['started']],
        [get_string('completedplural', 'videotrackermax'), (string)$metrics['completed']],
        [get_string('averagewatched', 'videotrackermax'), number_format($metrics['averagepercent'], 1) . '%'],
        [get_string('medianwatched', 'videotrackermax'), number_format($metrics['medianpercent'], 1) . '%'],
        [get_string('averagewatchtime', 'videotrackermax'), mod_videotrackermax_format_seconds($metrics['averagewatchtime'])],
        [get_string('averagesessions', 'videotrackermax'), number_format($metrics['averagesessions'], 2)],
        [get_string('averagespeed', 'videotrackermax'), number_format($metrics['averagespeed'], 2) . 'x'],
        [get_string('reachedend', 'videotrackermax'), number_format($metrics['endpercent'], 1) . '%'],
        [get_string('maxdropoff', 'videotrackermax'), mod_videotrackermax_format_seconds($metrics['maxdropoff']),
            get_string('eventsatpoint', 'videotrackermax', $metrics['maxdropoffcount'])],
        [get_string('maxreplay', 'videotrackermax'), mod_videotrackermax_format_seconds($metrics['maxreplay']),
            get_string('eventsatpoint', 'videotrackermax', $metrics['maxreplaycount'])],
        [get_string('maxskip', 'videotrackermax'), mod_videotrackermax_format_seconds($metrics['maxskip']),
            get_string('eventsatpoint', 'videotrackermax', $metrics['maxskipcount'])],
        [get_string('maxpause', 'videotrackermax'), mod_videotrackermax_format_seconds($metrics['maxpause']),
            get_string('eventsatpoint', 'videotrackermax', $metrics['maxpausecount'])],
    ];
    $grid = '';
    foreach ($cards as $card) {
        $grid .= html_writer::div(mod_videotrackermax_metric_card($card[0], $card[1], $card[2] ?? ''),
            'col-12 col-sm-6 col-lg-4 col-xl-3');
    }
    echo html_writer::div($grid, 'row g-3');
    echo html_writer::tag('h3', get_string('viewingheatmap', 'videotrackermax'), ['class' => 'h4 mt-4']);
    echo mod_videotrackermax_heatmap_html(
        $OUTPUT,
        $metrics['heatmap'],
        'viewers',
        (int)$metrics['duration'],
        (int)$metrics['population']
    );
} else if ($section === 'retention') {
    $retention = $report->retention($filters);
    echo html_writer::div(
        get_string('retentionhelp', 'videotrackermax'),
        'alert alert-light border'
    );
    echo $OUTPUT->render(mod_videotrackermax_retention_chart(
        $retention,
        get_string('retentioncurve', 'videotrackermax')
    ));
} else if ($section === 'heatmap') {
    $metric = optional_param('metric', 'viewers', PARAM_ALPHA);
    $metricoptions = [
        'viewers' => get_string('heatmap:viewers', 'videotrackermax'),
        'replays' => get_string('heatmap:replays', 'videotrackermax'),
        'skips' => get_string('heatmap:skips', 'videotrackermax'),
        'pauses' => get_string('heatmap:pauses', 'videotrackermax'),
        'dropoffs' => get_string('heatmap:dropoffs', 'videotrackermax'),
    ];
    if (!isset($metricoptions[$metric])) {
        $metric = 'viewers';
    }
    $selector = html_writer::select($metricoptions, 'metric', $metric, false, ['class' => 'form-select']);
    $form = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'section', 'value' => 'heatmap']);
    foreach ($filterparams as $name => $value) {
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
    $form .= html_writer::div(
        html_writer::tag('label', get_string('heatmapmetric', 'videotrackermax') . $selector,
            ['class' => 'form-label']) .
        html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary ms-2',
            'value' => get_string('show')]),
        'd-flex align-items-end mb-3'
    );
    echo html_writer::tag('form', $form, ['method' => 'get']);
    echo html_writer::tag('h3', $metricoptions[$metric], ['class' => 'h4']);
    echo mod_videotrackermax_heatmap_html(
        $OUTPUT,
        $metrics['heatmap'],
        $metric,
        (int)$metrics['duration'],
        (int)$metrics['population']
    );
    echo html_writer::div(get_string('correlationwarning', 'videotrackermax'), 'alert alert-info mt-4');
} else if ($section === 'comparison') {
    $type = optional_param('comparetype', 'group', PARAM_ALPHA);
    if (!in_array($type, ['group', 'grouping', 'cohort', 'period'], true)) {
        $type = 'group';
    }

    $compareform = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'section', 'value' => 'comparison']);
    foreach ($filterparams as $name => $value) {
        $compareform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }

    $typefield = html_writer::tag('label',
        get_string('comparetype', 'videotrackermax') .
        html_writer::select([
            'group' => get_string('groups'),
            'grouping' => get_string('groupings', 'group'),
            'cohort' => get_string('cohorts', 'cohort'),
            'period' => get_string('dateperiods', 'videotrackermax'),
        ], 'comparetype', $type, false, ['class' => 'form-select mt-1']),
        ['class' => 'form-label col-12 col-md']
    );

    $comparison = null;
    $fields = $typefield;

    if ($type === 'period') {
        $periodafrom = optional_param('periodafrom', '', PARAM_RAW_TRIMMED);
        $periodato = optional_param('periodato', '', PARAM_RAW_TRIMMED);
        $periodbfrom = optional_param('periodbfrom', '', PARAM_RAW_TRIMMED);
        $periodbto = optional_param('periodbto', '', PARAM_RAW_TRIMMED);

        foreach ([
            ['periodafrom', get_string('periodafrom', 'videotrackermax'), $periodafrom],
            ['periodato', get_string('periodato', 'videotrackermax'), $periodato],
            ['periodbfrom', get_string('periodbfrom', 'videotrackermax'), $periodbfrom],
            ['periodbto', get_string('periodbto', 'videotrackermax'), $periodbto],
        ] as [$name, $label, $value]) {
            $fields .= html_writer::tag('label',
                $label . html_writer::empty_tag('input', [
                    'type' => 'date',
                    'name' => $name,
                    'value' => $value,
                    'class' => 'form-control mt-1',
                ]),
                ['class' => 'form-label col-12 col-md']
            );
        }

        $afrom = filters::parse_date_value($periodafrom);
        $ato = filters::parse_date_value($periodato, true);
        $bfrom = filters::parse_date_value($periodbfrom);
        $bto = filters::parse_date_value($periodbto, true);

        if ($afrom && $ato && $bfrom && $bto && $afrom <= $ato && $bfrom <= $bto) {
            $comparison = $report->period_comparison(
                $filters,
                [
                    'from' => $afrom,
                    'to' => $ato,
                    'label' => $periodafrom . ' — ' . $periodato,
                ],
                [
                    'from' => $bfrom,
                    'to' => $bto,
                    'label' => $periodbfrom . ' — ' . $periodbto,
                ]
            );
        }
    } else {
        $options = $report->dimension_options($type);
        $ids = array_keys($options);
        $a = optional_param('comparea', $ids[0] ?? 0, PARAM_INT);
        $b = optional_param('compareb', $ids[1] ?? ($ids[0] ?? 0), PARAM_INT);

        $fields .= html_writer::tag('label',
            get_string('comparefirst', 'videotrackermax') .
            html_writer::select($options, 'comparea', $a, false, ['class' => 'form-select mt-1']),
            ['class' => 'form-label col-12 col-md']
        );
        $fields .= html_writer::tag('label',
            get_string('comparesecond', 'videotrackermax') .
            html_writer::select($options, 'compareb', $b, false, ['class' => 'form-select mt-1']),
            ['class' => 'form-label col-12 col-md']
        );

        if ($options && $a && $b && isset($options[$a], $options[$b])) {
            $comparison = $report->comparison($filters, $type, $a, $b);
        }
    }

    $fields .= html_writer::div(html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-secondary',
        'value' => get_string('compare', 'videotrackermax'),
    ]), 'col-auto');

    $compareform .= html_writer::div($fields, 'row g-2 align-items-end mb-3');
    echo html_writer::tag('form', $compareform, ['method' => 'get']);

    if ($comparison === null) {
        echo $OUTPUT->notification(
            $type === 'period'
                ? get_string('comparisonperiodhelp', 'videotrackermax')
                : get_string('comparisonunavailable', 'videotrackermax'),
            'info'
        );
    } else {
        $suppressed = $comparison['a']['metrics']['suppressed'] || $comparison['b']['metrics']['suppressed'];
        if ($suppressed) {
            echo html_writer::div(
                get_string('suppressedcomparison', 'videotrackermax', $report->minimum_population()),
                'alert alert-warning videotrackermax-suppressed'
            );
        } else {
            $table = new html_table();
            $table->head = [
                get_string('metric', 'videotrackermax'),
                s($comparison['a']['label']),
                s($comparison['b']['label']),
            ];
            $rows = [
                [get_string('population', 'videotrackermax'), 'population', 'number'],
                [get_string('completedplural', 'videotrackermax'), 'completed', 'number'],
                [get_string('averagewatched', 'videotrackermax'), 'averagepercent', 'percent'],
                [get_string('averagewatchtime', 'videotrackermax'), 'averagewatchtime', 'time'],
                [get_string('averagesessions', 'videotrackermax'), 'averagesessions', 'decimal'],
                [get_string('maxdropoff', 'videotrackermax'), 'maxdropoff', 'time'],
                [get_string('reachedend', 'videotrackermax'), 'endpercent', 'percent'],
            ];
            foreach ($rows as [$label, $field, $format]) {
                $values = [];
                foreach (['a', 'b'] as $key) {
                    $value = $comparison[$key]['metrics'][$field];
                    if ($format === 'percent') {
                        $values[] = number_format((float)$value, 1) . '%';
                    } else if ($format === 'time') {
                        $values[] = mod_videotrackermax_format_seconds((float)$value);
                    } else if ($format === 'decimal') {
                        $values[] = number_format((float)$value, 2);
                    } else {
                        $values[] = (string)(int)$value;
                    }
                }
                $table->data[] = [$label, $values[0], $values[1]];
            }
            echo html_writer::table($table);

            $labels = [];
            $seriesa = [];
            $seriesb = [];
            $maxpoints = max(count($comparison['a']['retention']), count($comparison['b']['retention']));
            for ($i = 0; $i < $maxpoints; $i++) {
                $pa = $comparison['a']['retention'][$i] ?? ['time' => 0, 'percent' => 0];
                $pb = $comparison['b']['retention'][$i] ?? ['time' => 0, 'percent' => 0];
                $labels[] = $i % max(1, (int)ceil($maxpoints / 12)) === 0
                    ? mod_videotrackermax_format_seconds(max((int)$pa['time'], (int)$pb['time']))
                    : '';
                $seriesa[] = round((float)$pa['percent'], 2);
                $seriesb[] = round((float)$pb['percent'], 2);
            }
            $chart = new \core\chart_line();
            $chart->set_title(get_string('retentioncomparison', 'videotrackermax'));
            $chart->set_labels($labels);
            $chart->add_series(new \core\chart_series($comparison['a']['label'], $seriesa));
            $chart->add_series(new \core\chart_series($comparison['b']['label'], $seriesb));
            $chart->set_legend_options(['position' => 'bottom']);
            echo $OUTPUT->render($chart);
        }
    }
} else if ($section === 'students') {
    $summaries = $report->user_summaries($filters);
    if (!$summaries) {
        echo $OUTPUT->notification(get_string('nodata', 'videotrackermax'), 'info');
    } else {
        $userids = array_map(static fn($row): int => (int)$row->userid, $summaries);
        $users = $DB->get_records_list('user', 'id', $userids, '', 'id,firstname,lastname,email,picture,imagealt');
        $table = new html_table();
        $table->head = [
            get_string('fullname', 'videotrackermax'),
            get_string('email'),
            get_string('watchedpercent', 'videotrackermax'),
            get_string('watchtime', 'videotrackermax'),
            get_string('sessions', 'videotrackermax'),
            get_string('averagespeed', 'videotrackermax'),
            get_string('completionstatus', 'videotrackermax'),
        ];
        foreach ($summaries as $summary) {
            $user = $users[$summary->userid] ?? null;
            if (!$user) {
                continue;
            }
            $table->data[] = [
                html_writer::link(
                    new moodle_url('/mod/videotrackermax/student.php', [
                        'id' => $cm->id,
                        'userid' => $user->id,
                    ] + $filterparams),
                    fullname($user)
                ),
                s($user->email),
                (int)$summary->percent . '%',
                mod_videotrackermax_format_seconds((int)$summary->watchtime),
                (int)$summary->sessions,
                number_format((float)$summary->speedavg, 2) . 'x',
                !empty($summary->completed)
                    ? get_string('completed', 'completion')
                    : get_string('notcompleted', 'completion'),
            ];
        }
        echo html_writer::table($table);
    }
}

echo html_writer::end_div();
echo $OUTPUT->footer();
