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
 * mod_form.php
 *
 * @package   mod_videotrackermax
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_video_bridge\source\manager as source_manager;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_videotrackermax_mod_form.
 */
class mod_videotrackermax_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $manager = new source_manager();
        $sources = $manager->get_options(['tracking']);
        if (!$sources) {
            throw new moodle_exception('nosources', 'videotrackermax');
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videotrackermaxname', 'videotrackermax'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'sourceheader', get_string('source', 'videotrackermax'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videotrackermax'), $sources);
        $mform->setType('videosource', PARAM_PLUGIN);
        $mform->setDefault('videosource', $manager->get_default_source());
        $manager->add_form_elements($mform, 'videosource');

        $mform->addElement('header', 'analyticsheader', get_string('analyticssettings', 'videotrackermax'));
        $mform->addElement('select', 'bucketcount', get_string('bucketcount', 'videotrackermax'), [
            100 => '100',
            200 => '200',
            300 => '300',
            500 => '500',
            1000 => '1000',
        ]);
        $mform->setDefault('bucketcount', 200);
        $mform->addHelpButton('bucketcount', 'bucketcount', 'videotrackermax');

        $mform->addElement('selectyesno', 'showstudentprogress', get_string('showstudentprogress', 'videotrackermax'));
        $mform->setDefault('showstudentprogress', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $mform->addElement('select', 'completionpercent', get_string('completionpercent', 'videotrackermax'), [
            0 => get_string('none'),
            25 => '25%',
            50 => '50%',
            60 => '60%',
            70 => '70%',
            75 => '75%',
            80 => '80%',
            90 => '90%',
            95 => '95%',
            100 => '100%',
        ]);
        $mform->setDefault('completionpercent', 0);
        return ['completionpercent'];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionpercent']);
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        return $errors + (new source_manager())->validation($data, $files);
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if (!empty($this->_cm->id)) {
            (new source_manager())->prepare_form_data(
                $defaultvalues,
                context_module::instance($this->_cm->id)
            );
        }
    }
}
