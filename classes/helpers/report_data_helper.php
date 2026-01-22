<?php

namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

class report_data_helper {

    public static function get_learner_grade_data(array $courseids, array $groupids, array $activity_tagids) {
        global $DB;

        list($courseinsql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        list($groupinsql,  $groupparams)  = $DB->get_in_or_equal($groupids,  SQL_PARAMS_NAMED, 'groupid');
        list($taginsql,    $tagparams)    = $DB->get_in_or_equal($activity_tagids,    SQL_PARAMS_NAMED, 'tagid');

        $params = array_merge($courseparams, $groupparams, $tagparams);

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
                
                CASE
                    WHEN gi.itemmodule = 'assign' THEN a.duedate
                    WHEN gi.itemmodule = 'quiz' THEN q.timeclose
                END AS duedate,
                
                CASE 
                    WHEN gi.itemmodule = 'assign' THEN a_s.timemodified
                    WHEN gi.itemmodule = 'quiz' THEN qa.timemodified
                END AS submissiondate,

                CASE
                    WHEN gi.itemmodule = 'assign' THEN
                        CASE 
                            WHEN a.duedate < a_s.timemodified THEN 'late'
                            WHEN a.duedate > a_s.timemodified THEN 'on time'
                            ELSE 'unknown'
                        END
                    WHEN gi.itemmodule = 'quiz' THEN
                        CASE
                            WHEN q.timeclose < qa.timemodified THEN 'late'
                            WHEN q.timeclose > qa.timemodified THEN 'on time'
                            ELSE 'unknown'
                        END
                END AS submission_status

            FROM mdl_grade_grades gg

            JOIN mdl_user u
                ON u.id = gg.userid
            
            JOIN mdl_groups_members gm
                ON gm.userid = u.id

            JOIN mdl_groups g
                ON g.id = gm.groupid

            JOIN mdl_grade_items gi
                ON gi.id = gg.itemid
                
            JOIN mdl_course c
                ON c.id = gi.courseid
                
            LEFT JOIN mdl_assign a
                ON a.id = gi.iteminstance
                AND gi.itemmodule = 'assign'
            
            LEFT JOIN mdl_quiz q
                ON q.id = gi.iteminstance
                AND gi.itemmodule = 'quiz'
                
            JOIN mdl_course_modules cm
                ON cm.instance = gi.iteminstance

            JOIN mdl_modules m
                ON m.id = cm.module
                AND m.name IN ('quiz', 'assign')

            JOIN mdl_tag_instance ti
                ON ti.itemid = cm.id
                AND ti.itemtype = 'course_modules'
                
            JOIN mdl_tag t
                ON t.id = ti.tagid
            
            LEFT JOIN mdl_quiz_attempts qa
                ON qa.quiz = q.id
                AND qa.userid = u.id
                AND qa.attempt = 1
                
            LEFT JOIN mdl_assign_submission a_s
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