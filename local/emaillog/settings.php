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
 * Administration settings and report link for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_emaillog_settings',
        get_string('pluginname', 'local_emaillog')
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_emaillog/coverage',
        '',
        get_string('coveragenotice', 'local_emaillog')
    ));

    // Retention values are in seconds; 0 disables the cleanup task.
    $settings->add(new admin_setting_configselect(
        'local_emaillog/retention',
        get_string('retention', 'local_emaillog'),
        get_string('retentiondesc', 'local_emaillog'),
        180 * DAYSECS,
        [
            30 * DAYSECS => get_string('retention30days', 'local_emaillog'),
            90 * DAYSECS => get_string('retention90days', 'local_emaillog'),
            180 * DAYSECS => get_string('retention6months', 'local_emaillog'),
            365 * DAYSECS => get_string('retention1year', 'local_emaillog'),
            0 => get_string('retentionforever', 'local_emaillog'),
        ]
    ));
}

// The report itself lives under Site administration > Reports and is available to anyone
// holding the capability, not only to full site administrators.
$ADMIN->add('reports', new admin_externalpage(
    'local_emaillog_report',
    get_string('reportname', 'local_emaillog'),
    new moodle_url('/local/emaillog/index.php'),
    'local/emaillog:view'
));
