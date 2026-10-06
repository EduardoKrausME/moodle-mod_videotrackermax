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
 * provider.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackermax\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Class provider.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videotrackermax_user', [
            'userid' => 'privacy:metadata:user:userid',
            'day' => 'privacy:metadata:user:day',
            'percent' => 'privacy:metadata:user:percent',
            'watchtime' => 'privacy:metadata:user:watchtime',
            'sessions' => 'privacy:metadata:user:sessions',
        ], 'privacy:metadata:user');

        $collection->add_database_table('videotrackermax_bucket', [
            'userid' => 'privacy:metadata:bucket:userid',
            'bucket' => 'privacy:metadata:bucket:bucket',
            'watched' => 'privacy:metadata:bucket:watched',
        ], 'privacy:metadata:bucket');

        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {videotrackermax_user} vu
                  JOIN {videotrackermax} v ON v.id = vu.activityid
                  JOIN {course_modules} cm ON cm.instance = v.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'videotrackermax'
                  JOIN {context} ctx ON ctx.instanceid = cm.id
                                   AND ctx.contextlevel = :contextmodule
                 WHERE vu.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videotrackermax', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $summaries = $DB->get_records('videotrackermax_user', [
                'activityid' => $cm->instance,
                'userid' => $userid,
            ], 'day ASC');
            if (!$summaries) {
                continue;
            }

            $export = [];
            foreach ($summaries as $summary) {
                $export[] = (object)[
                    'day' => transform::datetime($summary->day),
                    'duration' => (int)$summary->duration,
                    'percent' => (int)$summary->percent,
                    'watchtime' => (int)$summary->watchtime,
                    'sessions' => (int)$summary->sessions,
                    'completed' => (bool)$summary->completed,
                    'speedavg' => (float)$summary->speedavg,
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'videotrackermax')],
                (object)['dailyanalytics' => $export]
            );
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videotrackermax', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        foreach (['videotrackermax_agg', 'videotrackermax_bucket', 'videotrackermax_user', 'videotrackermax_state'] as $table) {
            $DB->delete_records($table, ['activityid' => $cm->instance]);
        }
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videotrackermax', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            foreach (['videotrackermax_bucket', 'videotrackermax_user'] as $table) {
                $DB->delete_records($table, [
                    'activityid' => $cm->instance,
                    'userid' => $userid,
                ]);
            }
            // Collective rows are derived from per-user materializations. Remove them
            // and the cursor so the next cron rebuilds them from remaining bridge data.
            $DB->delete_records('videotrackermax_agg', ['activityid' => $cm->instance]);
            $DB->delete_records('videotrackermax_state', ['activityid' => $cm->instance]);
        }
    }
}
