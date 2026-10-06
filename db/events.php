<?php
defined('MOODLE_INTERNAL') || die;

$observers = [
    [
        'eventname' => '\\local_video_bridge\\event\\analytics_updated',
        'callback' => '\\mod_videotrackermax\\observer::analytics_updated',
    ],
];
