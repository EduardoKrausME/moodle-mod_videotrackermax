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
 * lib.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use local_video_bridge\analytics;
use local_video_bridge\progress\manager as progress_manager;
use local_video_bridge\source\manager as source_manager;

function videotrackermax_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

function videotrackermax_add_instance(stdClass $data, ?mod_videotrackermax_mod_form $mform = null): int {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;

    $manager = new source_manager();
    $manager->normalise_record($data);
    $id = $DB->insert_record('videotrackermax', $data);
    $data->id = $id;

    if (!empty($data->coursemodule)) {
        $context = context_module::instance($data->coursemodule);
        $manager->save_files($data, $context);
    }

    return $id;
}

function videotrackermax_update_instance(stdClass $data, ?mod_videotrackermax_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $previous = $DB->get_record(
        'videotrackermax',
        ['id' => $data->id],
        'id,course,videosource,sourceconfig',
        MUST_EXIST
    );
    $previoussource = (string)$previous->videosource;
    $previoushash = analytics::media_hash(
        (string)$previous->videosource,
        (string)$previous->sourceconfig
    );

    $manager = new source_manager();
    $manager->normalise_record($data);
    $newhash = analytics::media_hash(
        (string)$data->videosource,
        (string)$data->sourceconfig
    );
    $result = $DB->update_record('videotrackermax', $data);

    $cm = null;
    if (!empty($data->coursemodule)) {
        $cm = get_coursemodule_from_id('videotrackermax', (int)$data->coursemodule, 0, false, IGNORE_MISSING);
    }
    if (!$cm) {
        $cm = get_coursemodule_from_instance(
            'videotrackermax',
            (int)$data->id,
            (int)$previous->course,
            false,
            IGNORE_MISSING
        );
    }

    if ($cm) {
        $context = context_module::instance($cm->id);
        $manager->save_files($data, $context, $previoussource);

        if (!hash_equals($previoushash, $newhash)) {
            // A different media identity must start with clean authoritative
            // progress and clean materialized analytics. Bridge-owned data is
            // deleted through its public API, never by touching bridge tables.
            progress_manager::delete_consumer(
                $context->id,
                'mod_videotrackermax',
                (int)$data->id
            );
            foreach ([
                'videotrackermax_agg',
                'videotrackermax_bucket',
                'videotrackermax_user',
                'videotrackermax_state',
            ] as $table) {
                $DB->delete_records($table, ['activityid' => $data->id]);
            }
        }
    }

    return $result;
}

function videotrackermax_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videotrackermax', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videotrackermax', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        (new source_manager())->delete_files($context);
        progress_manager::delete_consumer(
            $context->id,
            'mod_videotrackermax',
            $id
        );
    }

    $transaction = $DB->start_delegated_transaction();
    foreach ([
        'videotrackermax_agg',
        'videotrackermax_bucket',
        'videotrackermax_user',
        'videotrackermax_state',
    ] as $table) {
        $DB->delete_records($table, ['activityid' => $id]);
    }
    $DB->delete_records('videotrackermax', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

function videotrackermax_get_coursemodule_info($coursemodule): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record(
        'videotrackermax',
        ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completionpercent'
    );
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('videotrackermax', $activity, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionpercent'] = (int)$activity->completionpercent;
    }
    return $info;
}

function videotrackermax_get_completion_active_rule_descriptions($cm): array {
    if ($cm->completion != COMPLETION_TRACKING_AUTOMATIC ||
            empty($cm->customdata['customcompletionrules']['completionpercent'])) {
        return [];
    }
    return [
        get_string('completiondetail:percent', 'videotrackermax',
            (int)$cm->customdata['customcompletionrules']['completionpercent']),
    ];
}

function videotrackermax_get_completion_state($course, $cm, $userid, $type): bool {
    global $DB;

    $activity = $DB->get_record('videotrackermax', ['id' => $cm->instance], '*', MUST_EXIST);
    if (empty($activity->completionpercent)) {
        return (bool)$type;
    }

    $context = context_module::instance($cm->id);
    $mediahash = analytics::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
    $progress = progress_manager::get_progress(
        $context->id,
        'mod_videotrackermax',
        (int)$activity->id,
        $mediahash,
        (int)$userid
    );

    return $progress && (int)$progress->percent >= (int)$activity->completionpercent;
}
