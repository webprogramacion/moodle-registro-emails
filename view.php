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
 * Detail of a single recorded email.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('local_emaillog_report');
require_capability('local/emaillog:view', context_system::instance());

$context = context_system::instance();
$record = $DB->get_record('local_emaillog', ['id' => $id], '*', MUST_EXIST);

$listurl = new moodle_url('/local/emaillog/index.php');
$PAGE->set_url(new moodle_url('/local/emaillog/view.php', ['id' => $id]));
$PAGE->navbar->add(get_string('detailtitle', 'local_emaillog'));

$detailtable = new html_table();
$detailtable->attributes['class'] = 'generaltable';
$detailtable->head = null;

foreach (\local_emaillog\local\detail::get_rows($record) as $row) {
    $label = new html_table_cell($row[0]);
    $label->header = true;
    $detailtable->data[] = new html_table_row([$label, new html_table_cell($row[1])]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('detailtitle', 'local_emaillog'));
echo html_writer::table($detailtable);

echo $OUTPUT->heading(get_string('bodytext', 'local_emaillog'), 3);
echo html_writer::tag('pre', s((string) $record->bodytext), ['class' => 'border p-3']);

if (trim((string) $record->bodyhtml) !== '') {
    echo $OUTPUT->heading(get_string('bodyhtml', 'local_emaillog'), 3);
    // Always run stored HTML through the purifier: it comes from whatever generated the
    // email and must never be echoed straight into the page.
    echo html_writer::div(
        format_text($record->bodyhtml, FORMAT_HTML, ['context' => $context, 'noclean' => false]),
        'border p-3'
    );
}

echo html_writer::div(html_writer::link($listurl, get_string('backtolist', 'local_emaillog')), 'mt-3');

echo $OUTPUT->footer();
