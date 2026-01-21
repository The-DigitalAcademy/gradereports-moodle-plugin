<?php

defined('MOODLE_INTERNAL') || die();

global $DB;

$groups = $DB->get_records_menu('groups', [], 'name ASC', 'id, name');

if (empty($groups)) {
    $groups = [0 => 'no groups found'];
}

if ($ADMIN->fulltree) {

    // Add a new settings page for the local plugin.
    $settings = new admin_settingpage('local_gradereports', get_string('pluginname', 'local_gradereports'));

    // Create a new settings category under 'Local plugins' if it doesn't exist
    $ADMIN->add('localplugins', $settings);

    // Setting for the API URL
    $settings->add(new admin_setting_configtext(
        'local_gradereports/api_url', // Setting name
        get_string('api_url', 'local_gradereports'), // Title
        get_string('api_url_desc', 'local_gradereports'), // Description
        'http://localhost:3000/reports', // Default value
        PARAM_URL // Parameter type
    ));

    // Setting for the tag used for Complance Report Courses
    $settings->add(new admin_setting_configtext(
        'local_gradereports/course_tag', // Setting name
        get_string('course_tag', 'local_gradereports'), // Title
        get_string('course_tag_desc', 'local_gradereports'), // Description
        'compliance_report', // Default value
        PARAM_TEXT // Parameter type
    ));

    // Setting for group option selection
    $settings->add(new admin_setting_configmultiselect(
        'local_gradereports/groups',
        get_string('groups', 'local_gradereports'),
        get_string('groups_desc', 'local_gradereports'),
        [],
        $groups
    ));
}