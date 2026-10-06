# Video Tracker Max

Video Tracker Max is a Moodle activity focused on collective video analytics: heatmaps, retention, replay, skips, pauses, drop-off and comparison between groups, groupings, cohorts and date ranges.

Playback and tracking are deliberately delegated to [local_video_bridge](https://github.com/EduardoKrausME/moodle-local_video_bridge). The activity contains no provider-specific player code and does not read Video Bridge internal tables when a public API exists.

## What the dashboards mean

**Viewing heatmap** shows how many learners actually watched each part of the video. A strong area means more unique learners reached that bucket; it does not mean they understood the content.

**Retention** shows the percentage of learners who reached each position relative to the population included by the active filters. A drop indicates that fewer learners reached later positions.

**Replay** highlights positions where learners moved backward and watched material again. A replay peak may indicate difficulty, importance, review before an assessment, or ordinary study behaviour. Correlation is not causation.

**Drop-off** shows where playback sessions ended. A peak can indicate loss of attention, but it can also be caused by a natural stopping point, session interruption or learners returning later.

**Skip** shows forward jumps. Skips can indicate familiar content, navigation behaviour, searching for a specific explanation or content learners considered less useful.

**Pause** shows concentrations of pauses. Pauses may indicate note-taking, reflection, interruptions or difficult content.

## Architecture

The player is built with `local_video_bridge\source\manager` and requests `local_video_bridge\analytics::LEVEL_DETAILED`. Video Bridge persists compact sessions and watched ranges; it does not create one database row for every `timeupdate`.

Video Tracker Max consumes only public Video Bridge APIs such as `analytics::media_hash()`, `get_session_metrics()`, `get_watched_ranges()` and `get_activity_coverage()`.

The scheduled aggregation task incrementally materializes:

- one learner/day summary;
- per-learner/day heatmap buckets;
- per-day/per-group aggregate buckets;
- a cursor per activity/media hash.

Opening a dashboard therefore queries compact materialized data instead of raw playback events.

## Privacy

Collective dashboards respect Moodle groups and the administrative minimum population threshold. Individual reports require a separate capability. Learners see only their own progress when that option is enabled.

CSV exports contain consolidated report metrics, not internal bridge telemetry.

## Completion

Custom completion uses the authoritative normalized percentage stored by Video Bridge. Video Tracker Max does not maintain a competing progress percentage.
