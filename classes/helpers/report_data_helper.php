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
                CONCAT(c.id, '_', g.id, '_', u.id, '_', cm.id) AS uniqueid,
                c.fullname AS coursename, 
                g.name AS groupname, 
                u.id AS userid, 
                u.firstname, 
                u.lastname,  
                CASE
                    WHEN m.name = 'assign' THEN 'assignment'
                    ELSE m.name
                END AS activitytype,
                -- Normalize activity name across different activity types.
                CASE
                    WHEN m.name = 'assign' THEN a.name
                    WHEN m.name = 'quiz' THEN q.name
                END AS activityname,
                -- Normalize deadlines across different activity types.
                CASE
                    WHEN m.name = 'assign' THEN ao.duedate
                    WHEN m.name = 'quiz' THEN qo.timeclose
                END AS duedate,
                
                CASE 
                    WHEN m.name = 'assign' THEN ROUND(gg_a.finalgrade / gi_assign.grademax * 100, 2)
                    WHEN m.name = 'quiz' THEN ROUND(gg_q.finalgrade / gi_quiz.grademax * 100, 2)
                END AS grade,
                
                -- Normalize submission times.
                CASE 
                    WHEN m.name = 'assign' THEN a_s.timemodified
                    WHEN m.name = 'quiz' THEN qa.timemodified
                END AS submissiondate,
                -- Logic to determine if a student submitted on time or late.
                CASE
                    WHEN m.name = 'assign' THEN
                        CASE
                            WHEN a_s.timemodified IS NULL AND FROM_UNIXTIME(ao.duedate) < NOW() THEN 'missed'
                            WHEN ao.duedate IS NULL AND a_s.timemodified > 0 THEN 'ontime'
                            WHEN ao.duedate > 0 AND ao.duedate < a_s.timemodified THEN 'late'
                            WHEN ao.duedate > a_s.timemodified THEN 'ontime'
                            ELSE 'pending'
                        END
                    WHEN m.name = 'quiz' THEN
                        CASE
                            WHEN qa.timemodified IS NULL AND FROM_UNIXTIME(qo.timeclose) < NOW() THEN 'missed'
                            WHEN qo.timeclose IS NULL AND qa.timemodified > 0 THEN 'ontime'
                            WHEN qo.timeclose > 0 AND qo.timeclose < qa.timemodified THEN 'late'
                            WHEN qo.timeclose > qa.timemodified THEN 'ontime'
                            ELSE 'pending'
                        END
                END AS submissionstatus
                
            FROM {groups} g 

            JOIN {groups_members} gm 
                ON gm.groupid = g.id 

            JOIN {user} u 
                ON u.id = gm.userid 

            LEFT JOIN {course} c 
                ON c.id = g.courseid 

            JOIN {course_modules} cm 
                ON cm.course = c.id 
                
            JOIN {tag_instance} ti
                ON ti.itemid = cm.id
                
            JOIN {tag} t
                ON t.id = ti.tagid

            JOIN {modules} m 
                ON m.id = cm.module
                
            LEFT JOIN {assign} a
                ON a.id = cm.instance

            LEFT JOIN {assign_overrides} ao
                ON ao.assignid = a.id
                AND ao.groupid = g.id
                
            LEFT JOIN {quiz} q
                ON q.id = cm.instance

            LEFT JOIN {quiz_overrides} qo
                ON qo.quiz = q.id
                AND qo.groupid = g.id

            LEFT JOIN {assign_submission} a_s
                ON a_s.assignment = a.id
                AND a_s.userid = u.id

            LEFT JOIN {grade_items} gi_assign
                ON gi_assign.iteminstance = a_s.assignment
                AND gi_assign.itemmodule = 'assign'

            LEFT JOIN {grade_grades} gg_a
                ON gg_a.itemid = gi_assign.id
                AND gg_a.userid = a_s.userid

            LEFT JOIN {quiz_attempts} qa
                ON qa.quiz = q.id
                AND qa.userid = u.id
                
            LEFT JOIN {grade_items} gi_quiz
                ON gi_quiz.iteminstance = qa.quiz
                AND gi_quiz.itemmodule = 'quiz'

            LEFT JOIN {grade_grades} gg_q
                ON gg_q.itemid = gi_quiz.id
                AND gg_q.userid = qa.userid



            WHERE c.id $courseinsql
            AND g.id $groupinsql
            AND m.name IN ('quiz', 'assign')
            AND t.id $taginsql

            ORDER BY firstname, lastname
        ";

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

    


}