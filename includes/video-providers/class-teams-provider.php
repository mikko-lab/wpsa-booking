<?php
/**
 * Microsoft Teams Video Provider
 * 
 * Creates Microsoft Teams meetings via Microsoft Graph API.
 * Implements WPSA_Video_Provider interface.
 * 
 * @package WPSA_Booking
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Teams_Provider implements WPSA_Video_Provider {
    
    /**
     * Microsoft Graph API endpoint
     */
    private const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0/me/onlineMeetings';
    
    /**
     * Create a Microsoft Teams meeting
     * 
     * @param array $booking_data Booking information
     * @return array Meeting details with join URL
     * @throws Exception If API call fails
     */
    public function create_meeting($booking_data) {
        error_log('WPSA Teams Provider: Creating meeting for booking #' . $booking_data['id']);
        
        // Get access token
        $access_token = WPSA_Teams_Auth::get_access_token();
        
        if (!$access_token) {
            throw new Exception('Microsoft Teams access token not available');
        }
        
        // Calculate meeting times
        $start_time = $booking_data['booking_date'] . 'T' . $booking_data['booking_time'];
        
        // Get service duration
        $services = get_option('wpsa_booking_services', []);
        $duration = 45; // default
        foreach ($services as $service) {
            if ($service['id'] === $booking_data['service_type']) {
                $duration = $service['duration'];
                break;
            }
        }
        
        $end_time = date('Y-m-d\TH:i:s', strtotime($start_time) + ($duration * 60));
        
        // Build meeting request
        $meeting_request = [
            'startDateTime' => $start_time,
            'endDateTime' => $end_time,
            'subject' => sprintf('WCAG-konsultaatio: %s', $booking_data['customer_name']),
        ];
        
        // Call Microsoft Graph API
        $response = wp_remote_post(self::GRAPH_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($meeting_request),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            error_log('WPSA Teams Provider: API request failed - ' . $response->get_error_message());
            throw new Exception('Failed to create Teams meeting: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Handle 401 Unauthorized (token expired/invalid)
        if ($status_code === 401) {
            error_log('WPSA Teams Provider: 401 Unauthorized, attempting token refresh...');
            
            // Try to refresh token
            $refresh_result = WPSA_Teams_Auth::refresh_access_token();
            
            if (is_wp_error($refresh_result)) {
                throw new Exception('Teams authentication failed and token refresh failed');
            }
            
            // Retry with new token
            return $this->create_meeting($booking_data);
        }
        
        // Handle other errors
        if ($status_code !== 201) {
            $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            error_log('WPSA Teams Provider: API error (HTTP ' . $status_code . ') - ' . $error_msg);
            throw new Exception('Teams API error: ' . $error_msg);
        }
        
        // Extract meeting join URL
        $join_url = $data['joinUrl'] ?? $data['joinWebUrl'] ?? null;
        
        if (empty($join_url)) {
            error_log('WPSA Teams Provider: No join URL in response');
            throw new Exception('No Teams meeting URL in response');
        }
        
        error_log('WPSA Teams Provider: Meeting created successfully - ' . $join_url);
        
        return [
            'meeting_link' => $join_url,
            'platform' => 'microsoft-teams',
            'details' => [
                'meeting_id' => $data['id'] ?? null,
                'join_url' => $join_url,
                'start_time' => $data['startDateTime'] ?? null,
                'end_time' => $data['endDateTime'] ?? null,
            ],
        ];
    }
    
    /**
     * Check if Teams is configured
     * 
     * @return bool True if configured
     */
    public function is_configured() {
        return WPSA_Teams_Auth::is_connected();
    }
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function get_name() {
        return 'Microsoft Teams';
    }
}
