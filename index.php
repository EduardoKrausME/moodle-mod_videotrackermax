<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/videotrackermax/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'videotrackermax'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('videotrackermax', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videotrackermax'));

if (!$instances) {
    echo $OUTPUT->notification(get_string('thereareno', 'moodle', get_string('modulenameplural', 'videotrackermax')), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('description')];
    foreach ($instances as $instance) {
        $table->data[] = [
            html_writer::link(new moodle_url('/mod/videotrackermax/view.php', ['id' => $instance->coursemodule]),
                format_string($instance->name)),
            format_text($instance->intro, $instance->introformat),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
