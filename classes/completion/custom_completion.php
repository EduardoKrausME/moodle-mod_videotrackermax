<?php
namespace mod_videotrackermax\completion;

use core_completion\activity_custom_completion;
use local_video_bridge\analytics;
use local_video_bridge\progress\manager as progress_manager;

class custom_completion extends activity_custom_completion {
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $activity = $DB->get_record('videotrackermax', ['id' => $this->cm->instance], '*', MUST_EXIST);
        if ($rule !== 'completionpercent' || empty($activity->completionpercent)) {
            return COMPLETION_INCOMPLETE;
        }

        $context = \context_module::instance($this->cm->id);
        $mediahash = analytics::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
        $progress = progress_manager::get_progress(
            $context->id,
            'mod_videotrackermax',
            (int)$activity->id,
            $mediahash,
            (int)$this->userid
        );

        return $progress && (int)$progress->percent >= (int)$activity->completionpercent
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    public static function get_defined_custom_rules(): array {
        return ['completionpercent'];
    }

    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videotrackermax', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionpercent' => get_string(
                'completiondetail:percent',
                'videotrackermax',
                (int)$activity->completionpercent
            ),
        ];
    }

    public function get_sort_order(): array {
        return ['completionview', 'completionpercent'];
    }
}
