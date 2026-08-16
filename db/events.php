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
 * Event observers for local_emaillog.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [

    // Core triggers this from email_to_user() whenever the mailer reports a failure. It is
    // the only signal core gives about the outcome of an email, and the only signal at all
    // for emails sent with direct email_to_user() calls.
    [
        'eventname' => '\core\event\email_failed',
        'callback' => '\local_emaillog\observer::email_failed',
        'internal' => false,
    ],
];
