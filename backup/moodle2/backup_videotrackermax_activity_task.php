<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackermax/backup/moodle2/backup_videotrackermax_stepslib.php');

class backup_videotrackermax_activity_task extends backup_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_videotrackermax_activity_structure_step(
            'videotrackermax_structure',
            'videotrackermax.xml'
        ));
    }

    public static function encode_content_links($content): string {
        return $content;
    }
}
