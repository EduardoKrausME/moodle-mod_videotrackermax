<?php
defined('MOODLE_INTERNAL') || die;

/**
 * Test data generator for Video Tracker Max.
 */
class mod_videotrackermax_generator extends testing_module_generator {
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object)(array)$record;
        $record->videosource ??= 'url';
        $record->videourl ??= 'https://example.com/video.mp4';
        $record->showstudentprogress ??= 1;
        $record->bucketcount ??= 100;
        $record->completionpercent ??= 0;
        return parent::create_instance($record, $options);
    }
}
