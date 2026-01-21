<?php

namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

class api_helper {
    public static function send_report(array $payload) {


        $apiurl = get_config('local_gradereports', 'api_url');

        if (empty($apiurl)) {
            debugging("❌ API url is not configured. Skipping task.", DEBUG_DEVELOPER);
            return false;
        }

        $jsondata = json_encode($payload);

        // Initialize cURL session
        $ch = curl_init($apiurl);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsondata) // Content-Length is good practice
        ]);        
         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
         // 4. Execute the cURL request and get the response
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            debugging('❌ cURL error: ' . curl_error($ch), DEBUG_DEVELOPER);
            return false;
        }

        // 5. Close the cURL session
        curl_close($ch);
    }
}