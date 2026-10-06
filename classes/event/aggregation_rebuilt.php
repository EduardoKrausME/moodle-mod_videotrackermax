<?php
namespace mod_videotrackermax\event;

defined('MOODLE_INTERNAL') || die;

class aggregation_rebuilt extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'videotrackermax';
    }

    public static function get_name(): string {
        return get_string('eventaggregationrebuilt', 'videotrackermax');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' rebuilt analytics aggregation for Video Tracker Max activity '{$this->objectid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/videotrackermax/dashboard.php', ['id' => $this->contextinstanceid]);
    }
}
