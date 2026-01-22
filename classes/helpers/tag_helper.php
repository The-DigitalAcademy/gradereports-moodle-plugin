<?php
namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

class tag_helper {

    public static function get_tagid(string $tagname) {
        global $CFG, $DB;

        // Ensure the Moodle tag API functions are available.
        require_once($CFG->dirroot . '/tag/lib.php');

        $tag = \core_tag_tag::get_by_name(0, $tagname);

        $tagid = $tag->id;

        if (!$tagid) return null;

        return $tagid;
    }

    public static function get_tagged_courses_ids(int $tagid) {
        global $DB;

            // tagged courses
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
            'siteid' => SITEID,
        ];

         $tagged_courses = $DB->get_records_sql($sql, $params);

         $indexed = array_values($tagged_courses);
         return array_column($indexed, 'id');

    }
}