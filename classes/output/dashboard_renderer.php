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
 * Analytics dashboard presentation helpers.
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax\output;

use html_writer;
use moodle_url;
use renderer_base;
use stdClass;
use mod_videotrackermax\report\service;
use mod_videotrackermax\report\time_formatter;

/**
 * Encapsulates dashboard forms, cards and charts.
 */
final class dashboard_renderer {
    /**
     * Render metric card for the dashboard.
     *
     * @param string $label Metric title.
     * @param string $value Metric value.
     * @param string $hint Optional supplementary text.
     * @return string Card markup.
     */
    public static function metric_card(string $label, string $value, string $hint = ''): string {
        $content = html_writer::div($label, 'small text-muted text-uppercase');
        $content .= html_writer::div($value, 'metric-value mt-1');
        if ($hint !== '') {
            $content .= html_writer::div($hint, 'small text-muted mt-1');
        }
        return html_writer::div(html_writer::div($content, 'card-body'), 'card metric-card h-100');
    }

    /**
     * Render filter form for the dashboard.
     *
     * @param stdClass $cm Course module.
     * @param string $section Active dashboard section.
     * @param array $filters Current filters.
     * @param service $report Report query service.
     * @return string Filter form markup.
     */
    public static function filter_form(
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

    /**
     * Render retention chart for the dashboard.
     *
     * @param array $series Retention points.
     * @param string $title Chart title.
     * @return \core\chart_line Retention chart.
     */
    public static function retention_chart(array $series, string $title): \core\chart_line {
        $labels = [];
        $values = [];
        foreach ($series as $index => $point) {
            $labels[] = $index % max(1, (int)ceil(count($series) / 12)) === 0
                ? time_formatter::format_seconds((int)$point['time'])
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

    /**
     * Render heatmap html for the dashboard.
     *
     * @param renderer_base $output Moodle output renderer.
     * @param array $rows Heatmap buckets.
     * @param string $metric Selected metric.
     * @param int $duration Video duration in seconds.
     * @param int $population Filtered learner population.
     * @return string Heatmap markup.
     */
    public static function heatmap_html(
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
            $title = time_formatter::format_seconds($start) . ' — ' .
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
            'endlabel' => time_formatter::format_seconds($duration),
        ]);
    }
}
