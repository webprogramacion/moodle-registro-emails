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
 * Decides whether a participant's name is a link, and where it points.
 *
 * The point of the log is to diagnose failed deliveries, and the usual cause is a
 * mistyped address on the user's profile, so the name links straight to the form that
 * can fix it. Both the listing and the detail page go through this class so that they
 * cannot end up applying different rules to the same question.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userlink {
    /** @var int|null The user the cached answer belongs to, null when nothing is cached. */
    protected static ?int $canediteduserid = null;

    /** @var bool Cached result of the capability check for self::$canediteduserid. */
    protected static bool $canedit = false;

    /**
     * Whether the current user may edit other users' profiles.
     *
     * Checked in the system context because that is what /user/editadvanced.php requires to
     * edit somebody else. Holding local/emaillog:view says nothing about this: a role can
     * perfectly well read the log without being allowed to touch user accounts.
     *
     * The answer cannot change between the rows of a page, and the listing renders up to
     * fifty rows with two participants each, so it is cached. The cache is keyed by user so
     * that code which swaps $USER mid-request never gets a stale answer.
     *
     * @return bool
     */
    public static function can_edit(): bool {
        global $USER;

        if (self::$canediteduserid !== (int) $USER->id) {
            self::$canediteduserid = (int) $USER->id;
            self::$canedit = has_capability('moodle/user:update', \context_system::instance());
        }

        return self::$canedit;
    }

    /**
     * URL of the form that can correct a user's email address.
     *
     * This is the same form the core user browser links to, and the only one that lets an
     * administrator change somebody else's address.
     *
     * @param int $userid The user to edit.
     * @return \moodle_url
     */
    public static function edit_url(int $userid): \moodle_url {
        return new \moodle_url('/user/editadvanced.php', ['id' => $userid, 'course' => SITEID]);
    }

    /**
     * Render a user's name, linked when there is somewhere useful and permitted to link to.
     *
     * Links open in a new tab: rebuilding a filtered report is far more work than closing a
     * tab, and losing the filters is exactly what makes the log tedious to work with.
     *
     * @param int|null $userid Stored user ID, empty for the noreply and support pseudo users.
     * @param string $fullname The user's name, unescaped.
     * @param bool $deleted Whether the user is flagged as deleted.
     * @param \moodle_url|null $fallbackurl Where to link when the viewer cannot edit users.
     *                                      Null renders plain text instead.
     * @return string
     */
    public static function render(
        ?int $userid,
        string $fullname,
        bool $deleted,
        ?\moodle_url $fallbackurl = null
    ): string {
        $name = s(trim($fullname));

        // Nothing to link: no user behind the record, a deleted account whose profile cannot
        // be edited, or no name to hang the link on.
        if (empty($userid) || $deleted || $name === '') {
            return $name;
        }

        if (self::can_edit()) {
            return \html_writer::link(
                self::edit_url((int) $userid),
                $name,
                self::link_attributes('editprofilelink', trim($fullname))
            );
        }

        if ($fallbackurl === null) {
            return $name;
        }

        return \html_writer::link(
            $fallbackurl,
            $name,
            self::link_attributes('viewprofilelink', trim($fullname))
        );
    }

    /**
     * Attributes shared by every link this class builds.
     *
     * The title carries the new window warning: opening a tab with no notice is disorienting
     * with a screen reader, and a visible "(new window)" on every one of fifty rows would
     * drown the table.
     *
     * @param string $stringid Language string describing the destination.
     * @param string $fullname The user's name, unescaped.
     * @return array Attributes for html_writer::link().
     */
    protected static function link_attributes(string $stringid, string $fullname): array {
        return [
            'target' => '_blank',
            // Keeps the opened tab from reaching back into this one through window.opener.
            'rel' => 'noopener',
            'title' => get_string($stringid, 'local_emaillog', $fullname),
        ];
    }
}
