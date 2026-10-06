<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * backup_videotrackermax_stepslib.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class backup_videotrackermax_activity_structure_step.
 */
class backup_videotrackermax_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return backup_nested_element Return value.
     */
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
