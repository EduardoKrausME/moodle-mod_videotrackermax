# Video Tracker Max

Video Tracker Max is a Moodle activity focused on collective video analytics: heatmaps, retention, replay, skips, pauses, drop-off and comparison between groups, groupings, cohorts and date ranges.

Playback and tracking are deliberately delegated to [local_video_bridge](https://github.com/EduardoKrausME/moodle-local_video_bridge). The activity contains no provider-specific player code and does not read Video Bridge internal tables when a public API exists.

## What the dashboards mean

**Viewing heatmap** shows how many learners actually watched each part of the video. A strong area means more unique learners reached that bucket; it does not mean they understood the content.

**Retention** shows the percentage of the selected learner population who reached each position. Learners who have not started remain in the denominator with 0%, so the curve describes class consumption rather than only the people who already pressed play. A drop indicates that fewer learners reached later positions.

**Replay** highlights positions where learners moved backward and watched material again. A replay peak may indicate difficulty, importance, review before an assessment, or ordinary study behaviour. Correlation is not causation.

**Drop-off** shows where a closed playback session stopped without an explicit natural video-end event. Reaching the normal end is completion evidence, not abandonment. A peak can indicate loss of attention, a session interruption or a point where learners commonly stop and return later.

**Skip** shows forward jumps. Skips can indicate familiar content, navigation behaviour, searching for a specific explanation or content learners considered less useful.

**Pause** shows concentrations of pauses. Pauses may indicate note-taking, reflection, interruptions or difficult content.

## Architecture

The player is built with `local_video_bridge\source\manager` and requests `local_video_bridge\analytics::LEVEL_DETAILED`. Video Bridge persists compact sessions and watched ranges; it does not create one database row for every `timeupdate`.

Video Tracker Max consumes only public Video Bridge APIs such as `analytics::media_hash()`, `get_session_metrics()`, `get_watched_ranges()` and `get_activity_coverage()`.

The scheduled aggregation task incrementally materializes:

- one learner/day summary;
- per-learner/day heatmap buckets;
- per-day/per-group aggregate buckets;
- a stable `(timemodified, id)` cursor per activity/media hash.

Normal cron runs consume bounded batches of compact sessions, while an administrative rebuild keeps paging through the public bridge API until the activity is consolidated. Opening a dashboard therefore queries compact materialized data instead of raw playback events.

## Privacy

Collective dashboards respect Moodle groups and the administrative minimum population threshold. The threshold is enforced against both the selected participant population and the observed sample, which avoids exposing a tiny active sample inside a large class. Individual reports require a separate capability. Learners see only their own progress when that option is enabled.

CSV exports contain consolidated report metrics, not internal bridge telemetry.

## Completion

Custom completion uses the authoritative normalized percentage stored by Video Bridge. Video Tracker Max does not maintain a competing progress percentage, and a Video Bridge analytics update triggers Moodle completion re-evaluation immediately.


## Comparisons and individual drill-down

Collective comparison supports Moodle groups, groupings, cohorts when the viewer has the required cohort capability, and two independent date periods. Retention curves are overlaid using the same filters so the populations remain comparable.

The Learners tab is intentionally secondary to the collective dashboards. Users with the dedicated individual-report capability can drill into one learner to inspect consolidated watched percentage, watch time, sessions, playback speed, end reached, the authoritative Video Bridge progress, an individual viewing map and daily summaries.

The configured minimum-population threshold applies to collective analytics and comparisons, not to explicitly authorized individual reports.
