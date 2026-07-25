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
 * Builds and stores email log records.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logger {
    /** @var int No send failure was observed. Moodle does not report successful sends. */
    public const STATUS_UNKNOWN = 0;

    /** @var int The email was confirmed as sent. Reserved: core gives no success signal. */
    public const STATUS_SENT = 1;

    /** @var int The mailer reported a failure. */
    public const STATUS_FAILED = 2;

    /**
     * Return the list of statuses with their human readable names.
     *
     * @return array Status constant => localised name.
     */
    public static function get_status_options(): array {
        return [
            self::STATUS_UNKNOWN => get_string('statusunknown', 'local_emaillog'),
            self::STATUS_SENT => get_string('statussent', 'local_emaillog'),
            self::STATUS_FAILED => get_string('statusfailed', 'local_emaillog'),
        ];
    }

    /**
     * Return the statuses worth offering as a filter.
     *
     * STATUS_SENT is left out: Moodle 5.x gives no success signal, so nothing ever carries
     * it and offering it would only ever produce an empty result.
     *
     * @return array Status constant => localised name.
     */
    public static function get_filter_status_options(): array {
        $options = self::get_status_options();
        unset($options[self::STATUS_SENT]);

        return $options;
    }

    /**
     * Return the human readable name of a status.
     *
     * @param int $status One of the STATUS_* constants.
     * @return string
     */
    public static function get_status_name(int $status): string {
        $options = self::get_status_options();

        return $options[$status] ?? $options[self::STATUS_UNKNOWN];
    }

    /**
     * Record an email that the messaging system is about to send.
     *
     * Never throws: logging must not be able to stop an email from going out.
     *
     * @param \stdClass $eventdata Message data as passed to the output processor.
     */
    public static function log_message($eventdata): void {
        global $DB;

        try {
            $record = self::build_record($eventdata);
            if ($record === null) {
                return;
            }
            $DB->insert_record('local_emaillog', $record);
        } catch (\Throwable $e) {
            debugging('local_emaillog: could not record an outgoing email: ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    /**
     * Store a record for an email whose failure was reported without a preceding log entry.
     *
     * Used for direct email_to_user() calls, which the messaging callback never sees.
     *
     * @param \stdClass $record A record ready for insertion.
     * @return void
     */
    public static function insert_record(\stdClass $record): void {
        global $DB;

        try {
            $DB->insert_record('local_emaillog', $record);
        } catch (\Throwable $e) {
            debugging('local_emaillog: could not record a failed email: ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    /**
     * Build a database record from the data the messaging system passes to the email processor.
     *
     * @param \stdClass $eventdata Message data.
     * @return \stdClass|null The record, or null when there is no usable recipient.
     */
    protected static function build_record($eventdata): ?\stdClass {
        $userto = self::resolve_user($eventdata->userto ?? null);
        if ($userto === null || empty($userto->email)) {
            // Without a recipient address there is nothing meaningful to audit.
            return null;
        }

        [$useridfrom, $emailfrom] = self::resolve_sender($eventdata->userfrom ?? null);

        $record = new \stdClass();
        $record->useridfrom = $useridfrom;
        $record->emailfrom = self::truncate($emailfrom, 255);
        $record->useridto = !empty($userto->id) && $userto->id > 0 ? (int) $userto->id : null;
        $record->emailto = self::truncate(self::resolve_recipient_address($userto), 255);
        $record->subject = (string) ($eventdata->subject ?? '');
        $record->bodytext = (string) ($eventdata->fullmessage ?? '');
        $record->bodyhtml = !empty($eventdata->fullmessagehtml) ? (string) $eventdata->fullmessagehtml : null;
        $record->replyto = !empty($eventdata->replyto) ? self::truncate((string) $eventdata->replyto, 255) : null;
        $record->attachments = self::resolve_attachments($eventdata);
        $record->status = self::STATUS_UNKNOWN;
        $record->component = self::resolve_component($eventdata);
        $record->errorinfo = null;
        $record->timecreated = time();

        return $record;
    }

    /**
     * Turn whatever the messaging system gave us into a user object.
     *
     * userfrom and userto may be either a user object or a plain user ID.
     *
     * @param \stdClass|int|null $user A user object, a user ID, or nothing.
     * @return \stdClass|null
     */
    protected static function resolve_user($user): ?\stdClass {
        if (is_object($user)) {
            return $user;
        }

        if (is_numeric($user) && (int) $user > 0) {
            $record = \core_user::get_user((int) $user, 'id, email, firstname, lastname');

            return $record ?: null;
        }

        return null;
    }

    /**
     * Work out the sender user ID and email address to store.
     *
     * The core no-reply and support pseudo users have negative IDs, which are stored as
     * NULL because they do not point at a real user record.
     *
     * @param \stdClass|int|null $userfrom Sender as passed to the message processor.
     * @return array [int|null $useridfrom, string $emailfrom]
     */
    protected static function resolve_sender($userfrom): array {
        $sender = self::resolve_user($userfrom);

        $userid = null;
        $email = '';

        if ($sender !== null) {
            if (!empty($sender->id) && $sender->id > 0) {
                $userid = (int) $sender->id;
            }
            $email = (string) ($sender->email ?? '');
        }

        if ($email === '') {
            $email = self::get_noreply_address();
        }

        return [$userid, $email];
    }

    /**
     * Work out the address the email will actually be delivered to.
     *
     * Mirrors message_output_email::send_message(), which prefers the address set in the
     * user's messaging preferences when the site allows overriding it.
     *
     * @param \stdClass $userto Recipient.
     * @return string
     */
    protected static function resolve_recipient_address(\stdClass $userto): string {
        global $CFG;

        if (!empty($CFG->messagingallowemailoverride)) {
            $override = get_user_preferences('message_processor_email_email', null, $userto);
            $override = clean_param($override, PARAM_EMAIL);
            if (!empty($override)) {
                return $override;
            }
        }

        return (string) ($userto->email ?? '');
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

    /**
     * Return the name of the attached file, if the site allows attachments and there is one.
     *
     * @param \stdClass $eventdata Message data.
     * @return string|null
     */
    protected static function resolve_attachments($eventdata): ?string {
        global $CFG;

        if (empty($CFG->allowattachments) || empty($eventdata->attachment)) {
            return null;
        }

        $name = (string) ($eventdata->attachname ?? '');
        if ($name === '' && $eventdata->attachment instanceof \stored_file) {
            $name = $eventdata->attachment->get_filename();
        }

        if ($name === '') {
            return null;
        }

        return clean_filename($name);
    }

    /**
     * Describe where the email came from, as "component/providername".
     *
     * @param \stdClass $eventdata Message data.
     * @return string|null
     */
    protected static function resolve_component($eventdata): ?string {
        $component = (string) ($eventdata->component ?? '');
        $name = (string) ($eventdata->name ?? '');

        if ($component === '') {
            return null;
        }

        $origin = $name !== '' ? $component . '/' . $name : $component;

        return self::truncate($origin, 100);
    }

    /**
     * Cut a string down to the length of its database column.
     *
     * @param string $value Value to shorten.
     * @param int $length Maximum length.
     * @return string
     */
    protected static function truncate(string $value, int $length): string {
        return \core_text::substr($value, 0, $length);
    }
}
