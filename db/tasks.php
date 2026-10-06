<?php
defined('MOODLE_INTERNAL') || die;

$tasks = [
    [
        'classname' => '\\mod_videotrackermax\\task\\aggregate',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
