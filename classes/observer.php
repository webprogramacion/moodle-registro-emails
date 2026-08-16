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

namespace local_emaillog;

use local_emaillog\local\logger;

/**
 * Event observers for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * @var int How far back to look for the log entry this failure belongs to, in seconds.
     *
     * The messaging callback records the email moments before email_to_user() runs, so a
     * short window is enough; it is generous to allow for slow SMTP timeouts.
     */
    protected const CORRELATION_WINDOW = 300;

    /**
     * Mark an email as failed when the mailer reports a problem.
     *
     * When the email came through the messaging system there is already a pending record to
     * update. When it came from a direct email_to_user() call there is not, so a new record
     * is created: this is the only way those emails ever reach the log.
     *
     * @param \core\event\email_failed $event The event.
     */
    public static function email_failed(\core\event\email_failed $event): void {
        global $DB;

        try {
            $data = $event->get_data();
            $other = $data['other'] ?? [];

            $useridto = (int) ($data['relateduserid'] ?? 0);
            $useridfrom = (int) ($data['userid'] ?? 0);
            $subject = (string) ($other['subject'] ?? '');
            $message = (string) ($other['message'] ?? '');
            $errorinfo = (string) ($other['errorinfo'] ?? '');
            $timecreated = (int) ($data['timecreated'] ?? time());

            $existingid = self::find_pending_record($useridto, $subject, $timecreated);

            if ($existingid !== null) {
                $DB->update_record('local_emaillog', (object) [
                    'id' => $existingid,
                    'status' => logger::STATUS_FAILED,
                    'errorinfo' => $errorinfo,
                ]);

                return;
            }

            logger::insert_record(self::build_record_from_event(
                $useridfrom,
                $useridto,
                $subject,
                $message,
                $errorinfo,
                $timecreated
            ));
        } catch (\Throwable $e) {
            debugging('local_emaillog: could not record a failed email: ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    /**
     * Find the most recent log entry that this failure is likely to belong to.
     *
     * Matching on recipient plus subject is reliable because the messaging callback stores
     * exactly the subject that email_to_user() later reports in the event: core only
     * rewrites the copy held in the mailer, not the argument itself.
     *
     * @param int $useridto Recipient user ID from the event.
     * @param string $subject Subject from the event.
     * @param int $timecreated Time the event was raised.
     * @return int|null ID of the record to update, or null if there is none.
     */
    protected static function find_pending_record(int $useridto, string $subject, int $timecreated): ?int {
        global $DB;

        if ($useridto <= 0) {
            return null;
        }

        $select = 'useridto = :useridto
                       AND status = :status
                       AND timecreated >= :since
                       AND ' . $DB->sql_compare_text('subject', 255) . ' = ' . $DB->sql_compare_text(':subject', 255);

        $params = [
            'useridto' => $useridto,
            'status' => logger::STATUS_UNKNOWN,
            'since' => $timecreated - self::CORRELATION_WINDOW,
            'subject' => $subject,
        ];

        $records = $DB->get_records_select(
            'local_emaillog',
            $select,
            $params,
            'timecreated DESC, id DESC',
            'id',
            0,
            1
        );

        if (empty($records)) {
            return null;
        }

        return (int) reset($records)->id;
    }

    /**
     * Build a log record for a failed email that was never recorded before.
     *
     * @param int $useridfrom Sender user ID from the event.
     * @param int $useridto Recipient user ID from the event.
     * @param string $subject Email subject.
     * @param string $message Plain text body as rendered by email_to_user().
     * @param string $errorinfo Error reported by the mailer.
     * @param int $timecreated Time the failure happened.
     * @return \stdClass
     */
    protected static function build_record_from_event(
        int $useridfrom,
        int $useridto,
        string $subject,
        string $message,
        string $errorinfo,
        int $timecreated
    ): \stdClass {
        $record = new \stdClass();
        $record->useridfrom = $useridfrom > 0 ? $useridfrom : null;
        // A missing sender means one of the core pseudo users, which send as no-reply.
        $record->emailfrom = self::get_user_email($useridfrom) ?? self::get_noreply_address();
        $record->useridto = $useridto > 0 ? $useridto : null;
        $record->emailto = self::get_user_email($useridto) ?? '';
        $record->subject = $subject;
        $record->bodytext = $message;
        $record->bodyhtml = null;
        $record->replyto = null;
        $record->attachments = null;
        $record->status = logger::STATUS_FAILED;
        // The event carries no information about which component asked for the email.
        $record->component = null;
        $record->errorinfo = $errorinfo;
        $record->timecreated = $timecreated;

        return $record;
    }

    /**
     * Look up a user's email address.
     *
     * @param int $userid User ID, possibly 0 or negative for the core pseudo users.
     * @return string|null The address, or null when there is no real user to look up.
     */
    protected static function get_user_email(int $userid): ?string {
        if ($userid <= 0) {
            return null;
        }

        $user = \core_user::get_user($userid, 'id, email');
        if (!$user || empty($user->email)) {
            return null;
        }

        return \core_text::substr($user->email, 0, 255);
    }

    /**
     * Return the site no-reply address, falling back the same way email_to_user() does.
     *
     * @return string
     */
    protected static function get_noreply_address(): string {
        global $CFG;

        $default = 'noreply@' . get_host_from_url($CFG->wwwroot);

        return empty($CFG->noreplyaddress) ? $default : $CFG->noreplyaddress;
    }
}
