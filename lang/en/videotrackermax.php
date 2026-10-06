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
 * videotrackermax.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aggregationrebuilt'] = 'Analytics aggregation was rebuilt.';
$string['allcohorts'] = 'All cohorts';
$string['allgroupings'] = 'All groupings';
$string['allparticipants'] = 'All participants';
$string['analyticssettings'] = 'Analytics';
$string['applyfilters'] = 'Apply filters';
$string['authoritativeprogress'] = 'Authoritative Video Bridge progress';
$string['averagesessions'] = 'Average sessions';
$string['averagespeed'] = 'Average speed';
$string['averagewatched'] = 'Average watched';
$string['averagewatchtime'] = 'Average watch time';
$string['backtodashboard'] = 'Back to learners';
$string['bucketcount'] = 'Heatmap granularity';
$string['bucketcount_help'] = 'Number of materialized timeline buckets used by Video Tracker Max dashboards. This does not create one database row per player timeupdate.';
$string['compare'] = 'Compare';
$string['comparefirst'] = 'First population';
$string['comparesecond'] = 'Second population';
$string['comparetype'] = 'Compare by';
$string['comparisonperiodhelp'] = 'Select valid start and end dates for both periods to compare them.';
$string['comparisonunavailable'] = 'There are not enough available populations for this comparison.';
$string['completedplural'] = 'Learners completed';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completionpercent'] = 'Required watched percentage';
$string['completionstatus'] = 'Completion';
$string['correlationwarning'] = 'These analytics describe behaviour, not cause. A replay peak can indicate difficulty or importance, but it may also be ordinary study behaviour.';
$string['dailybreakdown'] = 'Daily breakdown';
$string['dashboard'] = 'Analytics';
$string['dateperiods'] = 'Date periods';
$string['eventaggregationrebuilt'] = 'Analytics aggregation rebuilt';
$string['eventsatpoint'] = '{$a} events in this bucket';
$string['exportcsv'] = 'Export CSV';
$string['filterfrom'] = 'From';
$string['filterto'] = 'To';
$string['fullname'] = 'Full name';
$string['heatmap:dropoffs'] = 'Drop-off';
$string['heatmap:pauses'] = 'Pauses';
$string['heatmap:replays'] = 'Replay';
$string['heatmap:skips'] = 'Skips';
$string['heatmap:viewers'] = 'Viewing';
$string['heatmapmetric'] = 'Heatmap type';
$string['individualheatmap'] = 'Individual viewing map';
$string['individualnotavailable'] = 'This learner is not available in the current report scope.';
$string['individualreport'] = 'Individual learner report';
$string['maxdropoff'] = 'Largest drop-off';
$string['maxpause'] = 'Most paused';
$string['maxpercent'] = 'Maximum watched %';
$string['maxreplay'] = 'Most replayed';
$string['maxskip'] = 'Most skipped';
$string['medianwatched'] = 'Median watched';
$string['metric'] = 'Metric';
$string['metric:dropoffs'] = 'drop-offs';
$string['metric:pauses'] = 'pauses';
$string['metric:replays'] = 'replays';
$string['metric:skips'] = 'skips';
$string['metric:viewers'] = 'viewers';
$string['minaggregateusers'] = 'Minimum users for collective analytics';
$string['minaggregateusers_desc'] = 'Collective statistics and comparisons are hidden when the selected population contains fewer than this number of learners.';
$string['minpercent'] = 'Minimum watched %';
$string['minsessions'] = 'Minimum sessions';
$string['modulename'] = 'Video Tracker Max';
$string['modulenameplural'] = 'Video Tracker Max activities';
$string['nodata'] = 'No consolidated analytics match the selected filters.';
$string['nosources'] = 'No Video Bridge source with reliable tracking is available.';
$string['notwatched'] = 'Not watched';
$string['periodafrom'] = 'Period A from';
$string['periodato'] = 'Period A to';
$string['periodbfrom'] = 'Period B from';
$string['periodbto'] = 'Period B to';
$string['pluginname'] = 'Video Tracker Max';
$string['population'] = 'Population';
$string['privacy:metadata'] = 'Video Tracker Max materializes compact learner/day analytics derived from Video Bridge telemetry.';
$string['privacy:metadata:bucket'] = 'Materialized per-learner timeline analytics.';
$string['privacy:metadata:bucket:bucket'] = 'The normalized timeline bucket number.';
$string['privacy:metadata:bucket:userid'] = 'The learner represented by the bucket.';
$string['privacy:metadata:bucket:watched'] = 'Whether the learner watched the bucket.';
$string['privacy:metadata:user'] = 'Materialized learner/day video analytics.';
$string['privacy:metadata:user:day'] = 'The reporting day.';
$string['privacy:metadata:user:percent'] = 'The percentage of timeline buckets reached.';
$string['privacy:metadata:user:sessions'] = 'Number of playback sessions.';
$string['privacy:metadata:user:userid'] = 'The learner represented by the summary.';
$string['privacy:metadata:user:watchtime'] = 'Estimated real playback time.';
$string['privacy:path'] = 'Video Tracker Max analytics';
$string['reachedend'] = 'Reached the end';
$string['rebuildaggregation'] = 'Rebuild aggregation';
$string['retention'] = 'Retention';
$string['retentioncomparison'] = 'Retention comparison';
$string['retentioncurve'] = 'Retention curve';
$string['retentionhelp'] = 'Retention is the percentage of learners in the current filtered population who reached each part of the video.';
$string['sessions'] = 'Sessions';
$string['showstudentprogress'] = 'Show own progress to learners';
$string['source'] = 'Video source';
$string['started'] = 'Learners started';
$string['suppressed'] = 'Collective analytics are hidden because this selection contains fewer than {$a} users.';
$string['suppressedcomparison'] = 'This comparison is hidden because at least one side contains fewer than {$a} users.';
$string['suppressedexport'] = 'This export is unavailable because the selected population is below the configured privacy threshold.';
$string['tab:comparison'] = 'Comparison';
$string['tab:heatmap'] = 'Heatmap';
$string['tab:overview'] = 'Overview';
$string['tab:retention'] = 'Retention';
$string['tab:students'] = 'Learners';
$string['taskaggregate'] = 'Aggregate Video Tracker Max analytics';
$string['value'] = 'Value';
$string['videosource'] = 'Video source';
$string['videotrackermax:addinstance'] = 'Add a Video Tracker Max activity';
$string['videotrackermax:export'] = 'Export analytics';
$string['videotrackermax:rebuild'] = 'Rebuild analytics aggregation';
$string['videotrackermax:view'] = 'View Video Tracker Max';
$string['videotrackermax:viewanalytics'] = 'View collective analytics';
$string['videotrackermax:viewindividual'] = 'View individual learner analytics';
$string['videotrackermaxname'] = 'Activity name';
$string['viewingheatmap'] = 'Viewing heatmap';
$string['watched'] = 'Watched';
$string['watchedpercent'] = 'Watched %';
$string['watchtime'] = 'Watch time';
$string['yourprogress'] = 'Your progress';
