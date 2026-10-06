<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackermax', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackermax:rebuild', $context);
require_sesskey();

if (!data_submitted()) {
    throw new moodle_exception('invalidrequest', 'error');
}

(new \mod_videotrackermax\aggregation\service())->rebuild_activity($activity);

$event = \mod_videotrackermax\event\aggregation_rebuilt::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('videotrackermax', $activity);
$event->trigger();

redirect(
    new moodle_url('/mod/videotrackermax/dashboard.php', ['id' => $cm->id]),
    get_string('aggregationrebuilt', 'videotrackermax'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
