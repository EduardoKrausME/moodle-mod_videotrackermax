<?php
defined('MOODLE_INTERNAL') || die;

class restore_videotrackermax_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure(): array {
        return $this->prepare_activity_structure([
            new restore_path_element('videotrackermax', '/activity/videotrackermax'),
        ]);
    }

    protected function process_videotrackermax(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videotrackermax', $data);
        $this->apply_activity_instance($data->id);
    }

    protected function after_execute(): void {
        $this->add_related_files('local_video_bridge', 'video', null);
    }
}
