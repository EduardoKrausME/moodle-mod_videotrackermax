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
 * restore_videotrackermax_stepslib.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class restore_videotrackermax_activity_structure_step.
 */
class restore_videotrackermax_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        return $this->prepare_activity_structure([
            new restore_path_element('videotrackermax', '/activity/videotrackermax'),
        ]);
    }

    /**
     * Method process_videotrackermax.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videotrackermax(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videotrackermax', $data);
        $this->apply_activity_instance($data->id);
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files('local_video_bridge', 'video', null);
    }
}
