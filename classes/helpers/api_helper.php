<?php

/**
 * Helper class for handling API transmissions for Grade Reports.
 *
 * This class manages the communication between Moodle and the external monitoring 
 * API, ensuring data is formatted and transmitted securely via cURL.
 *
 * @package     local_gradereports
 * @subpackage  helpers
 */

namespace local_gradereports\helpers;

defined('MOODLE_INTERNAL') || die();

/**
 * api_helper class
 * * Provides static methods to transmit structured student performance data
 * to configured external endpoints.
 */
class api_helper {
    
    /**
     * Sends a JSON-encoded payload to the external monitoring API.
     *
     * This method retrieves the target URL from the plugin's configuration,
     * encodes the provided array, and performs a POST request.
     *
     * @param array $payload The structured performance metrics to be transmitted.
     * @return bool Returns true if the transmission was successful, false otherwise.
     */
    public static function send_report(array $payload) {


        $apiurl = get_config('local_gradereports', 'api_url');
        $api_key = get_config('local_gradereports', 'api_key');

        if (empty($apiurl)) {
            mtrace("❌ API url is not configured. Skipping task.", DEBUG_DEVELOPER);
            return false;
        }

        $jsondata = json_encode($payload);

        // set HTTP HEADER
        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsondata), // Content-Length is good practice
        ];

        if ($api_key) array_push($headers, 'apiKey: '. $api_key);

        $ch = curl_init($apiurl);
        
        // Configure cURL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);        
         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_exec($ch);
        
        if (curl_errno($ch)) {
            mtrace('❌ cURL error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return true;
    }
}