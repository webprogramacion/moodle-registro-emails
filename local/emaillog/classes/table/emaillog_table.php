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

namespace local_emaillog\table;

use local_emaillog\local\logger;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Paginated listing of recorded emails.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emaillog_table extends \table_sql {
    /**
     * Build the table.
     *
     * @param string $uniqueid Unique id for this table.
     * @param \moodle_url $baseurl URL the paging and sorting links point at.
     * @param array $filters Filters as returned by index.php.
     */
    public function __construct(string $uniqueid, \moodle_url $baseurl, array $filters) {
        parent::__construct($uniqueid);

        $this->define_baseurl($baseurl);

        $this->define_columns(['timecreated', 'emailfrom', 'emailto', 'subject', 'status', 'actions']);
        $this->define_headers([
            get_string('coltime', 'local_emaillog'),
            get_string('colfrom', 'local_emaillog'),
            get_string('colto', 'local_emaillog'),
            get_string('colsubject', 'local_emaillog'),
            get_string('colstatus', 'local_emaillog'),
            get_string('colactions', 'local_emaillog'),
        ]);

        $this->sortable(true, 'timecreated', SORT_DESC);
        // Subject is a TEXT column, which not every database can sort on.
        $this->no_sorting('subject');
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable generalbox');

        [$where, $params] = self::build_where($filters);
        $this->set_sql(self::get_fields(), self::get_from(), $where, $params);
    }

    /**
     * Return the SELECT list, aliasing every column so that sorting is never ambiguous.
     *
     * @return string
     */
    protected static function get_fields(): string {
        global $DB;

        $fromname = $DB->sql_fullname('uf.firstname', 'uf.lastname');
        $toname = $DB->sql_fullname('ut.firstname', 'ut.lastname');

        return "el.id AS id,
                el.timecreated AS timecreated,
                el.useridfrom AS useridfrom,
                el.emailfrom AS emailfrom,
                el.useridto AS useridto,
                el.emailto AS emailto,
                el.subject AS subject,
                el.status AS status,
                {$fromname} AS fromname,
                {$toname} AS toname";
    }

    /**
     * Return the FROM clause. Users are joined so that filters can match on names too.
     *
     * @return string
     */
    protected static function get_from(): string {
        return '{local_emaillog} el
                  LEFT JOIN {user} uf ON uf.id = el.useridfrom
                  LEFT JOIN {user} ut ON ut.id = el.useridto';
    }

    /**
     * Turn the filters into a WHERE clause.
     *
     * @param array $filters Filter values, any of which may be empty.
     * @return array [string $where, array $params]
     */
    public static function build_where(array $filters): array {
        global $DB;

        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filters['datefrom'])) {
            $conditions[] = 'el.timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            // The date selector returns midnight, so include the whole of the chosen day.
            $conditions[] = 'el.timecreated < :dateto';
            $params['dateto'] = $filters['dateto'] + DAYSECS;
        }

        if (!empty($filters['emailto'])) {
            $toname = $DB->sql_fullname('ut.firstname', 'ut.lastname');
            $conditions[] = '(' . $DB->sql_like('el.emailto', ':emailto', false) . '
                    OR ' . $DB->sql_like($toname, ':tonametext', false) . ')';
            $params['emailto'] = '%' . $DB->sql_like_escape($filters['emailto']) . '%';
            $params['tonametext'] = $params['emailto'];
        }

        if (!empty($filters['emailfrom'])) {
            $fromname = $DB->sql_fullname('uf.firstname', 'uf.lastname');
            $conditions[] = '(' . $DB->sql_like('el.emailfrom', ':emailfrom', false) . '
                    OR ' . $DB->sql_like($fromname, ':fromnametext', false) . ')';
            $params['emailfrom'] = '%' . $DB->sql_like_escape($filters['emailfrom']) . '%';
            $params['fromnametext'] = $params['emailfrom'];
        }

        if (!empty($filters['subject'])) {
            // The default of sql_compare_text() is 32 characters, far too short for a subject.
            $conditions[] = $DB->sql_like($DB->sql_compare_text('el.subject', 255), ':subject', false);
            $params['subject'] = '%' . $DB->sql_like_escape($filters['subject']) . '%';
        }

        if (isset($filters['status']) && $filters['status'] >= 0) {
            $conditions[] = 'el.status = :status';
            $params['status'] = (int) $filters['status'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * Count the records matching the filters, so the page can show a "no results" message.
     *
     * @param array $filters Filter values.
     * @return int
     */
    public static function count_records(array $filters): int {
        global $DB;

        [$where, $params] = self::build_where($filters);

        return (int) $DB->count_records_sql(
            'SELECT COUNT(el.id) FROM ' . self::get_from() . ' WHERE ' . $where,
            $params
        );
    }

    /**
     * Format the date column.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_timecreated($row): string {
        return userdate($row->timecreated);
    }

    /**
     * Format the sender column.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_emailfrom($row): string {
        return self::format_participant($row->emailfrom, $row->fromname);
    }

    /**
     * Format the recipient column.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_emailto($row): string {
        return self::format_participant($row->emailto, $row->toname);
    }

    /**
     * Format the subject column.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_subject($row): string {
        $subject = (string) $row->subject;
        if (trim($subject) === '') {
            return '';
        }

        // Shorten before escaping, so that an HTML entity is never cut in half.
        return s(shorten_text($subject, 120));
    }

    /**
     * Format the status column.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_status($row): string {
        return logger::get_status_name((int) $row->status);
    }

    /**
     * Build the link to the detail page.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_actions($row): string {
        $url = new \moodle_url('/local/emaillog/view.php', ['id' => $row->id]);

        return \html_writer::link($url, get_string('viewdetail', 'local_emaillog'));
    }

    /**
     * Render an address together with the user's name when there is one.
     *
     * @param string|null $email Email address.
     * @param string|null $name Full name coming from the joined user record.
     * @return string
     */
    protected static function format_participant(?string $email, ?string $name): string {
        $email = (string) $email;
        $name = trim((string) $name);

        if ($name === '') {
            return s($email);
        }

        return s($name) . \html_writer::empty_tag('br') .
            \html_writer::tag('small', s($email));
    }
}
