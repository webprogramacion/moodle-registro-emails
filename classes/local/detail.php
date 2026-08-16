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

namespace local_emaillog\local;

/**
 * Formatting helpers for the email detail page.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detail {
    /**
     * Build the rows of the detail table for a record.
     *
     * @param \stdClass $record An email log record.
     * @return array List of [label, html] pairs.
     */
    public static function get_rows(\stdClass $record): array {
        $rows = [
            [get_string('coltime', 'local_emaillog'), userdate($record->timecreated)],
            [
                get_string('colfrom', 'local_emaillog'),
                self::participant($record->useridfrom, $record->emailfrom),
            ],
            [
                get_string('colto', 'local_emaillog'),
                self::participant($record->useridto, $record->emailto),
            ],
            [get_string('colsubject', 'local_emaillog'), self::value($record->subject)],
            [get_string('replyto', 'local_emaillog'), self::value($record->replyto)],
            [get_string('attachments', 'local_emaillog'), self::value($record->attachments)],
            [get_string('component', 'local_emaillog'), self::value($record->component)],
            [get_string('colstatus', 'local_emaillog'), self::status((int) $record->status)],
        ];

        if (trim((string) $record->errorinfo) !== '') {
            $rows[] = [get_string('errorinfo', 'local_emaillog'), s($record->errorinfo)];
        }

        return $rows;
    }

    /**
     * Render a stored value, or a note when nothing was recorded.
     *
     * @param string|null $value Raw stored value.
     * @return string
     */
    public static function value(?string $value): string {
        if ($value === null || trim($value) === '') {
            return \html_writer::tag('em', get_string('notrecorded', 'local_emaillog'));
        }

        return s($value);
    }

    /**
     * Render the status, explaining what "unknown" actually means.
     *
     * @param int $status One of the logger STATUS_* constants.
     * @return string
     */
    public static function status(int $status): string {
        $label = s(logger::get_status_name($status));

        if ($status !== logger::STATUS_UNKNOWN) {
            return $label;
        }

        return $label . ' ' . \html_writer::tag(
            'small',
            s(get_string('statusunknownhelp', 'local_emaillog')),
            ['class' => 'text-muted']
        );
    }

    /**
     * Describe a sender or recipient, linking the name when the user still exists.
     *
     * The link points at the profile edit form for anyone who may edit users, so that a
     * wrong address can be corrected from here; everybody else keeps the read-only profile
     * link this page has always offered. Where to link is decided by userlink, so that this
     * page and the listing cannot drift apart.
     *
     * @param int|null $userid Stored user ID.
     * @param string|null $email Stored address.
     * @return string
     */
    public static function participant(?int $userid, ?string $email): string {
        $address = self::value($email);

        if (empty($userid)) {
            return $address;
        }

        $user = \core_user::get_user((int) $userid, 'id, firstname, lastname, deleted');
        if (!$user) {
            return $address;
        }

        $name = userlink::render(
            (int) $user->id,
            fullname($user),
            !empty($user->deleted),
            new \moodle_url('/user/profile.php', ['id' => $user->id])
        );

        return $name . ' ' . \html_writer::tag('small', '&lt;' . $address . '&gt;', ['class' => 'text-muted']);
    }
}
