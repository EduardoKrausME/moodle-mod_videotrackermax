<?php
defined('MOODLE_INTERNAL') || die;

class backup_videotrackermax_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure(): backup_nested_element {
        $activity = new backup_nested_element('videotrackermax', ['id'], [
            'name',
            'intro',
            'introformat',
            'videosource',
            'sourceconfig',
            'videourl',
            'completionpercent',
            'showstudentprogress',
            'bucketcount',
            'timecreated',
            'timemodified',
        ]);

        $activity->set_source_table('videotrackermax', ['id' => backup::VAR_ACTIVITYID]);
        $activity->annotate_files('local_video_bridge', 'video', null);

        // Analytics aggregates are intentionally not backed up. They are
        // materialized data and are rebuilt from Video Bridge after restore.
        return $this->prepare_activity_structure($activity);
    }
}
