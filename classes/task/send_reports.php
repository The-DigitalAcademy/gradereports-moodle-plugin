<?php

/**
 * Scheduled task for transmitting student performance reports.
 *
 * This task automates the data collection and transmission process. It identifies
 * tagged courses and activities, retrieves learner data, and pushes it to the 
 * configured external API endpoint.
 *
 * @package     local_gradereports
 * @subpackage  task
 */

namespace local_gradereports\task;


defined('MOODLE_INTERNAL') || die();

use local_gradereports\helpers\api_helper;
use local_gradereports\helpers\tag_helper;
use local_gradereports\helpers\report_data_helper;

/**
 * send_reports class
 * * Extends the Moodle scheduled_task core class to allow background processing
 * of grade reports based on the interval defined in the site administration.
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
     * * This is the entry point for the Moodle Cron system.
     */
    public function execute() {
        global $DB;

        // Resolve Course Tag: Only process courses marked with this specific tag.
        $course_tagname = get_config('local_gradereports', 'course_tag');
        $course_tagid = tag_helper::get_tagid($course_tagname);
        if (!$course_tagname || !$course_tagid) return;

        // Resolve Activity Tag: Only include grades for activities marked with this tag.
        $activity_tagname = get_config('local_gradereports', 'activity_tag');
        $activity_tagid = tag_helper::get_tagid($activity_tagname);
        if (!$activity_tagname || !$activity_tagid) return;

        // Identify Course Scope: Get IDs of all courses currently using the course tag.
        $courseids = tag_helper::get_tagged_courses_ids($course_tagid);
        if (empty($courseids)) return;

        // Group Filtering: Retrieve specific group IDs from plugin settings if configured.
        $groupstr = get_config('local_gradereports', 'groups');
        $groupids = !empty($groupstr) ? explode(',', $groupstr) : [];

        $records = report_data_helper::get_learner_grade_data($courseids, $groupids, [$activity_tagid]);

        if (!empty($records)) {
            api_helper::send_report(array_values($records));
        }
    }
}