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

        // $tagname = get_config('local_gradereports', 'course_tag');;
        // // Make sure tag API is available
        // require_once($CFG->dirroot . '/tag/lib.php');

        // //  Get tags attached to this assignment activity.
        // $tag = \core_tag_tag::get_by_name(0, $tagname);

        // if (!$tag) {
        //     return;
        // }
        // $tagid = $tag->id;

        // // get tagged courses
        // $sql = "
        //     SELECT c.id, c.fullname, c.shortname
        //     FROM {course} c
        //     JOIN {tag_instance} ti ON ti.itemid = c.id
        //     WHERE ti.itemtype = 'course'
        //     AND ti.tagid = :tagid
        //     AND c.id <> :siteid
        // ";
        // $params = [
        //     'tagid'  => $tagid,
        //     'siteid' => SITEID,
        // ];

        // $courses_data = $DB->get_records_sql($sql, $params);
        // $indexed_courses = array_values($courses_data);
        
        // $groupstr = get_config('local_gradereports', 'groups');
        // $groups = !empty($groupstr) ? explode(',', $groupstr) : [];
        
        // $quizzes_data = $DB->get_records('quiz', array('course' => $indexed_courses[0]->id), '', 'id, course, name');
        // $indexed_quizzes = array_values($quizzes_data);

        // $assignments_data = $DB->get_records('assign', array('course' => $indexed_courses[0]->id), '', 'id, course, name');
        // $indexed_assignments = array_values($assignments_data);


        // echo "\ncourses: " . json_encode($indexed_courses);
        // echo "\ngroups: " . json_encode($groups);
        // echo "\nquizzes: " . json_encode($indexed_quizzes);
        // echo "\nassignments: " . json_encode($indexed_assignments);

        // $tagged_activities_sql = "
        //     SELECT
        //         cm.id AS cmid,
        //         cm.module,
        //         m.name AS modulename,
        //         cm.instance,
        //         q.id AS quizid,
        //         q.name AS quizname,
        //         a.id AS assignid,
        //         a.name AS assignname,
        //         c.fullname AS coursename
        //     FROM {course_modules} cm
        //     JOIN {modules} m ON m.id = cm.module
        //     JOIN {course} c ON c.id = cm.course
        //     JOIN {tag_instance} ti ON ti.itemid = cm.id
        //     LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
        //     LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
        //     WHERE cm.course = :courseid
        //     AND ti.itemtype = 'course_modules'
        //     AND ti.tagid = :tagid
        //     AND m.name IN ('quiz', 'assign')
        //     AND cm.deletioninprogress = 0
        // ";
        // $tagged_activities_params = [
        //     'courseid' => $indexed_courses[0]->id,
        //     'tagid'    => 7,
        // ];

        // $activities = $DB->get_records_sql($tagged_activities_sql, $tagged_activities_params);
        // $indexed_activities = array_values($activities);



        // // get group members
        // $group_members = $DB->get_records('group_members', array('groupid' => 1));
        // $quiz_grades = $DB->get_records('quiz_grades', array('userid' => array_values($group_members)[1]->userid));

        // api_helper::send_report(array('courses' => $indexed_courses, 'groups' => $groups, 'quizzes' => $indexed_quizzes, 'assignments'=> $indexed_assignments, 'activities' => $indexed_activities));


        // -------------------------------------------------------------

        $courseids = [2];
        $groupids = [1];
        $tagids = [7];

        list($courseinsql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        list($groupinsql,  $groupparams)  = $DB->get_in_or_equal($groupids,  SQL_PARAMS_NAMED, 'groupid');
        list($taginsql,    $tagparams)    = $DB->get_in_or_equal($tagids,    SQL_PARAMS_NAMED, 'tagid');

        $params = array_merge($courseparams, $groupparams, $tagparams);

        $sql = "
            SELECT
                c.id               AS courseid,
                c.fullname         AS coursename,

                g.id               AS groupid,
                g.name             AS groupname,

                u.id               AS userid,
                u.firstname,
                u.lastname,
                u.email,

                m.name             AS moduletype,
                cm.id              AS cmid,
                gi.iteminstance    AS activityid,

                gi.itemname        AS activityname,
                gg.finalgrade,
                gi.grademax,

                -- Due date
                CASE 
                    WHEN m.name = 'assign'  THEN a.duedate
                    WHEN m.name ='quiz'     THEN q.timeclose
                END AS duedate,

                -- Submission date
                CASE 
                    WHEN m.name = 'assign'  THEN s.timemodified
                    WHEN m.name = 'quiz'    THEN qa.timemodified
                END AS submissiondate

            FROM {course} c

            JOIN {groups} g
                ON g.courseid = c.id

            JOIN {groups_members} gm
                ON gm.groupid = g.id

            JOIN {user} u
                ON u.id = gm.userid

            JOIN {course_modules} cm
                ON cm.course = c.id
            AND cm.deletioninprogress = 0

            JOIN {modules} m
                ON m.id = cm.module
            AND m.name IN ('quiz', 'assign')

            JOIN {tag_instance} ti
                ON ti.itemtype = 'course_modules'
            AND ti.itemid = cm.id
            AND ti.tagid $taginsql

            JOIN {grade_items} gi
                ON gi.courseid = c.id
            AND gi.itemmodule = m.name
            AND gi.iteminstance = cm.instance

            LEFT JOIN {grade_grades} gg
                ON gg.itemid = gi.id
            AND gg.userid = u.id

            -- Assignment dates
            LEFT JOIN {assign} a
                ON a.id = cm.instance
                AND m.name = 'assign'
            
            LEFT JOIN {assign_submission} s
                ON s.assignment = a.id
                AND s.userid = u.id
                AND s.latest = 1
            
            -- Quiz dates
            LEFT JOIN {quiz} q
                ON q.id = cm.instance
                AND m.name = 'quiz'

            LEFT JOIN {quiz_attempts} qa
                ON qa.quiz = q.id
                AND qa.userid = u.id
                AND qa.state = 'finished'

            WHERE c.id $courseinsql
            AND g.id $groupinsql
        ";

        $records = $DB->get_records_sql($sql, $params);

        api_helper::send_report(array_values($records));
    }
}