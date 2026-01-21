<?php
namespace local_gradereports\task;


defined('MOODLE_INTERNAL') || die();

use local_gradereports\helpers\api_helper;

/**
 * 
 */
class send_reports extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sendreports', 'local_gradereports');
    }

    /**
     * Execute the task.
     */
    public function execute() {

        global $DB, $CFG;

        // Make sure tag API is available
        require_once($CFG->dirroot . '/tag/lib.php');

        // course tag
        $course_tagname = get_config('local_gradereports', 'course_tag');
        $course_tag = \core_tag_tag::get_by_name(0, $course_tagname);
        if (!$course_tag) return;
        $course_tagid = $course_tag->id;

        // activity tag
        $activity_tagname = get_config('local_gradereports', 'activity_tag');
        $activity_tag = \core_tag_tag::get_by_name(0, $activity_tagname);
        if (!$activity_tag) return;
        $activity_tagid = $activity_tag->id;

        // tagged courses
        $tagged_courses_sql = "
            SELECT c.id, c.fullname, c.shortname
            FROM {course} c
            JOIN {tag_instance} ti ON ti.itemid = c.id
            WHERE ti.itemtype = 'course'
            AND ti.tagid = :tagid
            AND c.id <> :siteid
        ";
        $tagged_courses_params = [
            'tagid'  => $course_tagid,
            'siteid' => SITEID,
        ];

        $tagged_courses = $DB->get_records_sql($tagged_courses_sql, $tagged_courses_params);
        $indexed_courses = array_values($tagged_courses);
        $courseids = array_column($indexed_courses, 'id');

        // groups
        $groupstr = get_config('local_gradereports', 'groups');
        $groupids = !empty($groupstr) ? explode(',', $groupstr) : [];

        list($courseinsql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        list($groupinsql,  $groupparams)  = $DB->get_in_or_equal($groupids,  SQL_PARAMS_NAMED, 'groupid');
        list($taginsql,    $tagparams)    = $DB->get_in_or_equal($activity_tagid,    SQL_PARAMS_NAMED, 'tagid');

        $params = array_merge($courseparams, $groupparams, $tagparams);

        $sql = "
            SELECT 
                gg.id AS gradeid,
                c.fullname AS coursename,
                g.name AS groupname,
                u.id AS userid,
                u.firstname,
                u.lastname,
                gi.itemmodule AS activitytype,
                gi.itemname AS activityname,
                gg.finalgrade,
                gi.grademax,
                
                CASE
                    WHEN gi.itemmodule = 'assign' THEN a.duedate
                    WHEN gi.itemmodule = 'quiz' THEN q.timeclose
                END AS duedate,
                
                CASE 
                    WHEN gi.itemmodule = 'assign' THEN a_s.timemodified
                    WHEN gi.itemmodule = 'quiz' THEN qa.timemodified
                END AS submissiondate

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

        api_helper::send_report(array_values($records));
    }
}