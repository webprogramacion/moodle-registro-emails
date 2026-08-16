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
 * Listing of the recorded outgoing emails.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_emaillog_report');
require_capability('local/emaillog:view', context_system::instance());

$perpage = 50;
$statusany = \local_emaillog\form\filter_form::STATUS_ANY;

$filters = [
    'datefrom' => optional_param('datefrom', 0, PARAM_INT),
    'dateto' => optional_param('dateto', 0, PARAM_INT),
    'emailto' => optional_param('emailto', '', PARAM_TEXT),
    'emailfrom' => optional_param('emailfrom', '', PARAM_TEXT),
    'subject' => optional_param('subject', '', PARAM_TEXT),
    'status' => optional_param('status', $statusany, PARAM_INT),
];

$pageurl = new moodle_url('/local/emaillog/index.php');

$mform = new \local_emaillog\form\filter_form($pageurl);

if ($mform->is_cancelled()) {
    redirect($pageurl);
} else if ($data = $mform->get_data()) {
    // Carry the filters in the URL so that the paging and sorting links keep them applied.
    redirect(new moodle_url(
        '/local/emaillog/index.php',
        \local_emaillog\form\filter_form::to_url_params((array) $data)
    ));
}

$mform->set_data($filters);

// The table's paging and sorting links must preserve whatever filters are in force.
$tableurl = new moodle_url(
    '/local/emaillog/index.php',
    \local_emaillog\form\filter_form::to_url_params($filters)
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportname', 'local_emaillog'));
echo $OUTPUT->notification(get_string('coveragenotice', 'local_emaillog'), 'info');

$mform->display();

if (\local_emaillog\table\emaillog_table::count_records($filters) === 0) {
    echo $OUTPUT->notification(get_string('noresults', 'local_emaillog'), 'warning');
} else {
    $table = new \local_emaillog\table\emaillog_table('local-emaillog-list', $tableurl, $filters);
    $table->out($perpage, false);
}

echo $OUTPUT->footer();
