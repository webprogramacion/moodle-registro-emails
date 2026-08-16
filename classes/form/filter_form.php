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

namespace local_emaillog\form;

use local_emaillog\local\logger;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Filters for the email log listing.
 *
 * The form posts to itself and the page then redirects to the listing with the chosen
 * values in the URL, so that paging and sorting links keep the filters applied.
 *
 * @package    local_emaillog
 * @copyright  2026 Damaso Velazquez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends \moodleform {
    /** @var int Value used in the status select to mean "do not filter by status". */
    public const STATUS_ANY = -1;

    /** @var string[] The filters this form handles. */
    protected const FILTER_NAMES = ['datefrom', 'dateto', 'emailto', 'emailfrom', 'subject', 'status'];

    /**
     * Reduce a set of filter values to the ones worth putting in a URL.
     *
     * Status needs its own test: zero is a real status ("unknown"), so it must survive the
     * emptiness check that discards unset dates and blank text fields.
     *
     * @param array $values Filter values, keyed by filter name.
     * @return array Values to add to the URL.
     */
    public static function to_url_params(array $values): array {
        $params = [];

        foreach (self::FILTER_NAMES as $name) {
            $value = $values[$name] ?? null;

            if ($name === 'status') {
                if ($value !== null && (int) $value !== self::STATUS_ANY) {
                    $params[$name] = (int) $value;
                }
                continue;
            }

            if ($value === null || $value === '' || $value === 0) {
                continue;
            }

            $params[$name] = $value;
        }

        return $params;
    }

    /**
     * Define the form elements.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'filterheading', get_string('filterheading', 'local_emaillog'));
        $mform->setExpanded('filterheading', true);

        $mform->addElement(
            'date_selector',
            'datefrom',
            get_string('datefrom', 'local_emaillog'),
            ['optional' => true]
        );

        $mform->addElement(
            'date_selector',
            'dateto',
            get_string('dateto', 'local_emaillog'),
            ['optional' => true]
        );

        $mform->addElement('text', 'emailto', get_string('filterto', 'local_emaillog'));
        $mform->setType('emailto', PARAM_TEXT);

        $mform->addElement('text', 'emailfrom', get_string('filterfrom', 'local_emaillog'));
        $mform->setType('emailfrom', PARAM_TEXT);

        $mform->addElement('text', 'subject', get_string('filtersubject', 'local_emaillog'));
        $mform->setType('subject', PARAM_TEXT);

        $statuses = [self::STATUS_ANY => get_string('statusany', 'local_emaillog')]
            + logger::get_filter_status_options();
        $mform->addElement('select', 'status', get_string('filterstatus', 'local_emaillog'), $statuses);
        $mform->setType('status', PARAM_INT);

        $this->add_action_buttons(false, get_string('filter'));
    }
}
