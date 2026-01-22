<?php
namespace local_gradereports\task;


defined('MOODLE_INTERNAL') || die();

use local_gradereports\helpers\api_helper;
use local_gradereports\helpers\tag_helper;
use local_gradereports\helpers\report_data_helper;

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

        global $DB;

        // course tag
        $course_tagname = get_config('local_gradereports', 'course_tag');
        $course_tagid = tag_helper::get_tagid($course_tagname);
        if (!$course_tagid) return;

        // activity tag
        $activity_tagname = get_config('local_gradereports', 'activity_tag');
        $activity_tagid = tag_helper::get_tagid($activity_tagname);
        if (!$activity_tagid) return;

        // tagged courses 
        $courseids = tag_helper::get_tagged_courses_ids($course_tagid);
        if (!count($courseids)) return;

        // groups
        $groupstr = get_config('local_gradereports', 'groups');
        $groupids = !empty($groupstr) ? explode(',', $groupstr) : [];

        $records = report_data_helper::get_learner_grade_data($courseids, $groupids, [$activity_tagid]);
        
        api_helper::send_report(array_values($records));
    }
}