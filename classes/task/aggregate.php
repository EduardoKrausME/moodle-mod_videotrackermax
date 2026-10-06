<?php
namespace mod_videotrackermax\task;

class aggregate extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('taskaggregate', 'videotrackermax');
    }

    public function execute(): void {
        (new \mod_videotrackermax\aggregation\service())->process_all();
    }
}
