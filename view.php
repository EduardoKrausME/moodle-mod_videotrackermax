<?php
require('../../config.php');

use local_video_bridge\analytics;
use local_video_bridge\progress\manager as progress_manager;
use local_video_bridge\source\manager as source_manager;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('videotrackermax', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackermax:view', $context);

$PAGE->set_url('/mod/videotrackermax/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

$event = \mod_videotrackermax\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videotrackermax', $activity);
$event->trigger();

$manager = new source_manager();
$player = $manager->get_player_config($activity, $context, analytics::LEVEL_DETAILED);
$clientconfig = $player;
unset($clientconfig['sourcetemplate']);
$player['sourcehtml'] = $OUTPUT->render_from_template($player['sourcetemplate'], ['player' => $player]);

$mediahash = analytics::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
$progress = progress_manager::get_progress(
    $context->id,
    'mod_videotrackermax',
    (int)$activity->id,
    $mediahash,
    (int)$USER->id
);

$data = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videotrackermax', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $player,
    'configjson' => json_encode($clientconfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'showstudentprogress' => !empty($activity->showstudentprogress),
    'studentpercent' => $progress ? (int)$progress->percent : 0,
    'studentposition' => $progress ? (int)$progress->currenttime : 0,
    'studentduration' => $progress ? (int)$progress->duration : 0,
    'canviewanalytics' => has_capability('mod/videotrackermax:viewanalytics', $context),
    'dashboardurl' => (new moodle_url('/mod/videotrackermax/dashboard.php', ['id' => $cm->id]))->out(false),
];

$PAGE->requires->js_call_amd('mod_videotrackermax/player', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackermax/view', $data);
echo $OUTPUT->footer();
