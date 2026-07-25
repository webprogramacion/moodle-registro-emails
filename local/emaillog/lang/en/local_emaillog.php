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
 * English strings for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Email log';
$string['reportname'] = 'Email log';

// Capabilities.
$string['emaillog:view'] = 'View the log of outgoing emails';

// List columns.
$string['colactions'] = 'Actions';
$string['colfrom'] = 'From';
$string['colstatus'] = 'Status';
$string['colsubject'] = 'Subject';
$string['coltime'] = 'Date/time';
$string['colto'] = 'To';

// Statuses.
$string['statusfailed'] = 'Failed';
$string['statussent'] = 'Sent';
$string['statusunknown'] = 'Unknown';
$string['statusunknownhelp'] = 'Unknown means no send failure was detected. Moodle does not report successful sends, so this is the normal state for a delivered email.';

// Filters.
$string['datefrom'] = 'From date';
$string['dateto'] = 'To date';
$string['filterfrom'] = 'Sender (email or name)';
$string['filterheading'] = 'Filters';
$string['filterstatus'] = 'Status';
$string['filtersubject'] = 'Subject contains';
$string['filterto'] = 'Recipient (email or name)';
$string['statusany'] = 'Any status';

// List and detail pages.
$string['attachments'] = 'Attachments';
$string['backtolist'] = 'Back to the email log';
$string['bodyhtml'] = 'HTML content';
$string['bodytext'] = 'Plain text content';
$string['component'] = 'Origin';
$string['detailtitle'] = 'Email detail';
$string['errorinfo'] = 'Mailer error';
$string['noresults'] = 'No emails match the filters applied.';
$string['notrecorded'] = 'Not recorded';
$string['replyto'] = 'Reply-To';
$string['viewdetail'] = 'View detail';

// Coverage notice.
$string['coveragenotice'] = 'This log records the emails Moodle sends through the messaging system (forum notifications, assignment feedback, badges, calendar events, private messages). Moodle 5.x does not offer any way to observe successful direct calls to <code>email_to_user()</code>, so password reset, signup confirmation and support form emails are only recorded when they fail.';

// Settings.
$string['retention'] = 'Keep logs for';
$string['retention1year'] = '1 year';
$string['retention30days'] = '30 days';
$string['retention6months'] = '6 months';
$string['retention90days'] = '90 days';
$string['retentiondesc'] = 'How long to keep email log records. The scheduled cleanup task deletes anything older than this period. Choose "Forever" to disable automatic deletion; note that email bodies may contain personal data, so a shorter period is usually preferable.';
$string['retentionforever'] = 'Forever';

// Scheduled task.
$string['taskcleanup'] = 'Delete old email log records';
$string['taskcleanupdeleted'] = 'local_emaillog: deleted {$a} old record(s).';
$string['taskcleanupforever'] = 'local_emaillog: retention is set to "Forever", nothing to delete.';

// Privacy.
$string['privacy:metadata:local_emaillog'] = 'Details of the emails sent by the site, kept so that administrators can audit outgoing mail.';
$string['privacy:metadata:local_emaillog:attachments'] = 'The names of the files attached to the email.';
$string['privacy:metadata:local_emaillog:bodyhtml'] = 'The HTML content of the email.';
$string['privacy:metadata:local_emaillog:bodytext'] = 'The plain text content of the email.';
$string['privacy:metadata:local_emaillog:emailfrom'] = 'The email address the message was sent from.';
$string['privacy:metadata:local_emaillog:emailto'] = 'The email address the message was sent to.';
$string['privacy:metadata:local_emaillog:replyto'] = 'The Reply-To address of the email.';
$string['privacy:metadata:local_emaillog:status'] = 'Whether the email was reported as failed.';
$string['privacy:metadata:local_emaillog:subject'] = 'The subject of the email.';
$string['privacy:metadata:local_emaillog:timecreated'] = 'The time the email was recorded.';
$string['privacy:metadata:local_emaillog:useridfrom'] = 'The ID of the user the message was sent from.';
$string['privacy:metadata:local_emaillog:useridto'] = 'The ID of the user the message was sent to.';
$string['privacy:path'] = 'Email log';
