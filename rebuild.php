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
 * rebuild.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
