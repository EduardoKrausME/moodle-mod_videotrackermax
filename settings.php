<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings->add(new admin_setting_configtext(
        'videotrackermax/minaggregateusers',
        get_string('minaggregateusers', 'videotrackermax'),
        get_string('minaggregateusers_desc', 'videotrackermax'),
        5,
        PARAM_INT
    ));
}
