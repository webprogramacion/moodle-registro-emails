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
 * Library functions for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Record an outgoing email just before the messaging system hands it to a processor.
 *
 * Core calls this from \core\message\manager::call_processors() once per output
 * processor, via get_plugins_with_function('pre_processor_message_send'). This is the
 * only extension point Moodle 5.x offers on the outgoing email path: email_to_user()
 * itself dispatches no hook, and get_mailer() is not swappable.
 *
 * The callback must never interfere with sending, so every failure is swallowed and
 * only reported through debugging().
 *
 * @param string $processorname Name of the message output processor about to run.
 * @param stdClass $eventdata Message data as returned by message::get_eventobject_for_processor().
 */
function local_emaillog_pre_processor_message_send($processorname, $eventdata) {
    if ($processorname !== 'email') {
        // Only the email processor results in an actual email being sent.
        return;
    }

    \local_emaillog\local\logger::log_message($eventdata);
}
