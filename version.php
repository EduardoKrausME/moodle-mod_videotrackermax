<?php
defined('MOODLE_INTERNAL') || die;

$plugin->component = 'mod_videotrackermax';
$plugin->version = 2026100600;
$plugin->release = '1.0.0';
$plugin->requires = 2024100700;
$plugin->maturity = MATURITY_ALPHA;
$plugin->dependencies = [
    'local_video_bridge' => 2026100601,
];
