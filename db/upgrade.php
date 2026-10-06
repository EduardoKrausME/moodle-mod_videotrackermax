<?php
defined('MOODLE_INTERNAL') || die;

/**
 * Upgrade steps for Video Tracker Max.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_videotrackermax_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026100601) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('videotrackermax_state');
        $field = new xmldb_field(
            'lastsessionid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'lastprocessed'
        );

        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026100601, 'videotrackermax');
    }

    return true;
}
