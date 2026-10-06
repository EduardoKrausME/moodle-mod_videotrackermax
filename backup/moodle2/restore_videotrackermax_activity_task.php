<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackermax/backup/moodle2/restore_videotrackermax_stepslib.php');

class restore_videotrackermax_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_videotrackermax_activity_structure_step(
            'videotrackermax_structure',
            'videotrackermax.xml'
        ));
    }

    public static function define_decode_contents(): array {
        return [
            new restore_decode_content('videotrackermax', ['intro'], 'videotrackermax'),
        ];
    }

    public static function define_decode_rules(): array {
        return [];
    }

    public static function define_restore_log_rules(): array {
        return [];
    }

    public static function define_restore_log_rules_for_course(): array {
        return [];
    }
}
