<?php
namespace mod_videotrackermax\event;

defined('MOODLE_INTERNAL') || die;

class course_module_viewed extends \core\event\course_module_viewed {
    protected function init(): void {
        $this->data['objecttable'] = 'videotrackermax';
        parent::init();
    }

    public static function get_objectid_mapping() {
        return ['db' => 'videotrackermax', 'restore' => 'videotrackermax'];
    }
}
