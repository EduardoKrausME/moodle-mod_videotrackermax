<?php
namespace mod_videotrackermax;

use local_video_bridge\event\analytics_updated;

defined('MOODLE_INTERNAL') || die;

/**
 * Re-evaluates Moodle completion after authoritative Video Bridge progress changes.
 */
final class observer {
    public static function analytics_updated(analytics_updated $event): void {
        global $DB;

        $other = $event->other;
        if (($other['component'] ?? '') !== 'mod_videotrackermax') {
            return;
        }

        $activityid = (int)($other['itemid'] ?? 0);
        $userid = (int)($event->relateduserid ?? 0);
        if ($activityid <= 0 || $userid <= 0) {
            return;
        }

        $cm = get_coursemodule_from_instance(
            'videotrackermax',
            $activityid,
            0,
            false,
            IGNORE_MISSING
        );
        if (!$cm || (int)$cm->id !== (int)$event->contextinstanceid) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $completion = new \completion_info($course);
        if (!$completion->is_enabled($cm)) {
            return;
        }

        $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
    }
}
