<?php

/**
 * Helper class for aggregating complex learner performance data.
 *
 * This class compiles data from several Moodle subsystems (Grades, Groups, 
 * Activities, and Tags) into a unified dataset.
 *
 * @package     local_gradereports
 * @subpackage  helpers
 * @copyright   2026 Your Name/Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

/**
 * report_data_helper class
 * * Provides methods to extract granular student performance metrics including
 * grades, submission timeliness, and activity metadata.
 */
class report_data_helper {

    /**
     * Compiles detailed grade and submission data for learners.
     *
     * This method executes a high-performance SQL query that joins grade items
     * with their respective module instances (assign/quiz) to calculate 
     * percentages and determine submission status (on-time vs late).
     *
     * @param array $courseids       List of course IDs to include.
     * @param array $groupids        List of group IDs to filter students by.
     * @param array $activity_tagids List of tag IDs applied to specific activities.
     * @return array|stdClass[]      A list of objects containing student performance metrics.
     */
    public static function get_learner_grade_data(array $courseids, array $groupids, array $activity_tagids) {
        global $DB;

        list($courseinsql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        list($groupinsql,  $groupparams)  = $DB->get_in_or_equal($groupids,  SQL_PARAMS_NAMED, 'groupid');
        list($taginsql,    $tagparams)    = $DB->get_in_or_equal($activity_tagids,    SQL_PARAMS_NAMED, 'tagid');

        $params = array_merge($courseparams, $groupparams, $tagparams);

        // Main Query: Aggregates Gradebook data with Activity-specific deadlines.
        $sql = "
            SELECT 
                gg.id AS gradeid,
                c.fullname AS coursename,
                g.name AS groupname,
                u.id AS userid,
                u.firstname,
                u.lastname,
                CASE
                    WHEN gi.itemmodule = 'assign' THEN 'assignment'
                    ELSE gi.itemmodule
                END AS activitytype,

                gi.itemname AS activityname,
                ROUND(finalgrade / grademax * 100, 2) AS grade_percent,

                -- Normalize deadlines across different activity types.
                CASE
                    WHEN gi.itemmodule = 'assign' THEN a.duedate
                    WHEN gi.itemmodule = 'quiz' THEN q.timeclose
                END AS duedate,
                
                -- Normalize submission times.
                CASE 
                    WHEN gi.itemmodule = 'assign' THEN a_s.timemodified
                    WHEN gi.itemmodule = 'quiz' THEN qa.timemodified
                END AS submissiondate,

                -- Logic to determine if a student submitted on time or late.
                CASE
                    WHEN gi.itemmodule = 'assign' THEN
                        CASE 
                            WHEN a.duedate > 0 AND a.duedate < a_s.timemodified THEN 'late'
                            WHEN a.duedate = 0 OR a.duedate > a_s.timemodified THEN 'on time'
                            ELSE 'unknown'
                        END
                    WHEN gi.itemmodule = 'quiz' THEN
                        CASE
                            WHEN q.timeclose > 0 AND q.timeclose < qa.timemodified THEN 'late'
                            WHEN q.timeclose = 0 OR q.timeclose > qa.timemodified THEN 'on time'
                            ELSE 'unknown'
                        END
                END AS submission_status

            FROM {grade_grades} gg

            JOIN {user} u
                ON u.id = gg.userid
            
            JOIN {groups_members} gm
                ON gm.userid = u.id

            JOIN {groups} g
                ON g.id = gm.groupid

            JOIN {grade_items} gi
                ON gi.id = gg.itemid
                
            JOIN {course} c
                ON c.id = gi.courseid
                
            -- Join specific activity tables to get deadlines (duedate / timeclose)
            LEFT JOIN {assign} a
                ON a.id = gi.iteminstance
                AND gi.itemmodule = 'assign'
            
            LEFT JOIN {quiz} q
                ON q.id = gi.iteminstance
                AND gi.itemmodule = 'quiz'
                
            JOIN {course_modules} cm
                ON cm.instance = gi.iteminstance

            JOIN {modules} m
                ON m.id = cm.module
                AND m.name IN ('quiz', 'assign')

            -- Filter activities by tags applied at the Course Module level.
            JOIN {tag_instance} ti
                ON ti.itemid = cm.id
                AND ti.itemtype = 'course_modules'
                
            JOIN {tag} t
                ON t.id = ti.tagid

            -- Attempt to find the user's submission/attempt to calculate timeliness.
            LEFT JOIN {quiz_attempts} qa
                ON qa.quiz = q.id
                AND qa.userid = u.id
                AND qa.attempt = 1
                
            LEFT JOIN {assign_submission} a_s
                ON a_s.assignment = a.id
                AND a_s.userid = u.id
                AND a_s.status = 'submitted'

            WHERE gg.finalgrade IS NOT NULL
            AND g.id $groupinsql
            AND gi.itemmodule IN ('quiz', 'assign')
            AND gi.courseid $courseinsql
            AND ti.tagid $taginsql
        ";

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

    


}