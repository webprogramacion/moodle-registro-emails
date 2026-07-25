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

namespace local_emaillog\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_emaillog\local\logger;

/**
 * Privacy provider for local_emaillog.
 *
 * Email log records are stored in the system context: they describe site level activity
 * rather than anything inside a course.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Describe the personal data this plugin stores.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_emaillog', [
            'useridfrom' => 'privacy:metadata:local_emaillog:useridfrom',
            'emailfrom' => 'privacy:metadata:local_emaillog:emailfrom',
            'useridto' => 'privacy:metadata:local_emaillog:useridto',
            'emailto' => 'privacy:metadata:local_emaillog:emailto',
            'subject' => 'privacy:metadata:local_emaillog:subject',
            'bodytext' => 'privacy:metadata:local_emaillog:bodytext',
            'bodyhtml' => 'privacy:metadata:local_emaillog:bodyhtml',
            'replyto' => 'privacy:metadata:local_emaillog:replyto',
            'attachments' => 'privacy:metadata:local_emaillog:attachments',
            'status' => 'privacy:metadata:local_emaillog:status',
            'timecreated' => 'privacy:metadata:local_emaillog:timecreated',
        ], 'privacy:metadata:local_emaillog');

        return $collection;
    }

    /**
     * Return the contexts holding data for a user.
     *
     * @param int $userid The user to search for.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        $exists = $DB->record_exists_select(
            'local_emaillog',
            'useridfrom = :useridfrom OR useridto = :useridto',
            ['useridfrom' => $userid, 'useridto' => $userid]
        );

        if ($exists) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Return the users holding data in a context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userlist->add_from_sql(
            'useridfrom',
            'SELECT useridfrom FROM {local_emaillog} WHERE useridfrom IS NOT NULL',
            []
        );

        $userlist->add_from_sql(
            'useridto',
            'SELECT useridto FROM {local_emaillog} WHERE useridto IS NOT NULL',
            []
        );
    }

    /**
     * Export the emails a user sent or received.
     *
     * @param approved_contextlist $contextlist The approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $records = $DB->get_recordset_select(
                'local_emaillog',
                'useridfrom = :useridfrom OR useridto = :useridto',
                ['useridfrom' => $user->id, 'useridto' => $user->id],
                'timecreated ASC'
            );

            $emails = [];
            foreach ($records as $record) {
                $emails[] = (object) [
                    'timecreated' => transform::datetime($record->timecreated),
                    'emailfrom' => $record->emailfrom,
                    'emailto' => $record->emailto,
                    'subject' => $record->subject,
                    'bodytext' => $record->bodytext,
                    'bodyhtml' => $record->bodyhtml,
                    'replyto' => $record->replyto,
                    'attachments' => $record->attachments,
                    'status' => logger::get_status_name((int) $record->status),
                ];
            }
            $records->close();

            if (empty($emails)) {
                continue;
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'local_emaillog')],
                (object) ['emails' => $emails]
            );
        }
    }

    /**
     * Delete every record in a context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_emaillog');
    }

    /**
     * Delete the data of one user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            self::delete_for_users([$contextlist->get_user()->id]);
        }
    }

    /**
     * Delete the data of a set of users in a context.
     *
     * @param approved_userlist $userlist The approved context and users to delete for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        self::delete_for_users($userlist->get_userids());
    }

    /**
     * Remove a set of users from the log.
     *
     * A record describes an email between two people, so it is handled from both sides:
     * records addressed to the user are deleted outright, because the recipient address and
     * the body are that person's data, while records the user only sent are kept for the
     * recipient's own audit trail with the sender's identity stripped out.
     *
     * @param array $userids The users to remove.
     */
    protected static function delete_for_users(array $userids): void {
        global $DB;

        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');

        $DB->delete_records_select('local_emaillog', "useridto {$insql}", $params);

        $DB->set_field_select('local_emaillog', 'emailfrom', '', "useridfrom {$insql}", $params);
        $DB->set_field_select('local_emaillog', 'useridfrom', null, "useridfrom {$insql}", $params);
    }
}
