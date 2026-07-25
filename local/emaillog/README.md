# Email log (local_emaillog)

Records the emails a Moodle site sends and lets administrators browse them, so that
"the email never arrived" can actually be investigated. Old records are purged
automatically according to a configurable retention period.

Tested against Moodle 5.0, 5.1 and 5.2 (`$plugin->requires = 2025041400`).

## Installation

Copy the plugin into the `local` directory of your site and visit the notifications page
to run the installation:

```
# Moodle 5.0
<moodleroot>/local/emaillog

# Moodle 5.1 and 5.2, where core lives under public/
<moodleroot>/public/local/emaillog
```

Then set the retention period at
**Site administration > Plugins > Local plugins > Email log**.

## Where to find the log

**Site administration > Reports > Email log**, protected by the `local/emaillog:view`
capability (granted to managers by default). The listing supports filtering by date
range, sender, recipient, subject and status, and each row links to a detail page with
the full content of the email. Stored HTML bodies are rendered through Moodle's
purifier, never echoed directly.

## What is and is not recorded

**This is the important caveat.** Moodle 5.x offers no hook, event or overridable class
on the successful path of `email_to_user()`: `lib/classes/hook/` contains no email hook
in any 5.x branch, `email_to_user()` dispatches nothing, and `get_mailer()` instantiates
`moodle_phpmailer` from a fixed path without going through the DI container. The only
signals core provides are the messaging system's `pre_processor_message_send` callback
and the `\core\event\email_failed` event, and this plugin uses both.

Recorded:

- Everything sent through the Message API with the `email` output processor — forum
  notifications, assignment feedback, badges, calendar events, private messages, and
  anything else that goes through `message_send()`.
- Every **failed** send, including failures of direct `email_to_user()` calls, together
  with the error reported by the mailer.

Not recorded:

- Direct `email_to_user()` calls that **succeed**. In practice this means password
  reset, signup confirmation and support form emails: they appear in the log only if
  they fail.

Other details worth knowing:

- The status of a recorded email is "Unknown" unless a failure was observed, because
  core never reports success. Treat "Unknown" as "no failure detected".
- Group conversation messages are batched by the `message_email` processor and sent
  later by a scheduled task; the log entry is created when the message is queued, not
  when the email actually leaves.
- The recipient address stored is the one the email is really delivered to, including
  the per-user override from messaging preferences when
  `$CFG->messagingallowemailoverride` is enabled.
- Logging can never block an email: every database write is wrapped so that a failure
  is reported through `debugging()` and nothing else.

## Retention

The **Keep logs for** setting offers 30 days, 90 days, 6 months (default), 1 year and
Forever. The `\local_emaillog\task\cleanup` scheduled task runs daily at 03:00 and
deletes anything older than the configured period; "Forever" disables deletion
entirely. Reschedule it from **Site administration > Server > Scheduled tasks**.

Email bodies can contain personal data, so prefer a short retention period. To run the
purge by hand:

```
php admin/cli/scheduled_task.php --execute='\local_emaillog\task\cleanup'
```

## Privacy

The plugin implements the Privacy API. On a delete request, records addressed to the
user are deleted, and records the user only sent are kept for the recipient's own audit
trail with the sender's identity removed.

## License

GNU GPL v3 or later.
