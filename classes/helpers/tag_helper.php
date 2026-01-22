<?php

/**
 * Helper class for managing Moodle tags and associated course filtering.
 *
 * This class identifies courses marked with specific tags to determine which
 * performance metrics should be transmitted to the external API.
 *
 * @package     local_gradereports
 * @subpackage  helpers
 * @copyright   2026 Your Name/Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

/**
 * tag_helper class
 * * Provides utility methods to interact with the Moodle Tag API and 
 * perform cross-table queries for tagged course identification.
 */
class tag_helper {

    /**
     * Retrieves the internal Moodle ID for a given tag name.
     *
     * @param string $tagname The plain-text name of the tag (e.g., 'monitor_performance').
     * @return int|null Returns the tag ID if found, otherwise null.
     */
    public static function get_tagid(string $tagname) {
        global $CFG;

        // Ensure the core Moodle tag library is loaded.
        require_once($CFG->dirroot . '/tag/lib.php');

        $tag = \core_tag_tag::get_by_name(0, $tagname);

        if (!$tag) return null;

        return $tag->id;
    }

    /**
     * Fetches a list of course IDs that are associated with a specific tag.
     *
     * @param int $tagid The internal ID of the tag to filter by.
     * @return array An array of course IDs (integers). Returns an empty array if none found.
     */
    public static function get_tagged_courses_ids(int $tagid) {
        global $DB;

        $sql = "
            SELECT c.id
            FROM {course} c
            JOIN {tag_instance} ti ON ti.itemid = c.id
            WHERE ti.itemtype = 'course'
            AND ti.tagid = :tagid
            AND c.id <> :siteid
        ";
        $params = [
            'tagid'  => $tagid,
            'siteid' => SITEID, // Moodle constant for the front-page course
        ];

         $tagged_courses = $DB->get_records_sql($sql, $params);

         if (empty($tagged_courses)) {
            return [];
         }

         // Re-index and flatten the array to return a simple list of IDs.
         return array_column(array_values($tagged_courses), 'id');

    }
}