<?php
/**
 * Google Meet Provider
 * 
 * Creates Google Meet links via Google Calendar API.
 * Requires Google Calendar API credentials and OAuth tokens.
 * 
 * @package WPSA_Booking
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Google_Meet_Provider implements WPSA_Video_Provider {
    
    /**
     * Create a Google Meet meeting
     * 
     * Uses Google Calendar API to create a calendar event with
     * automatic Google Meet conferencing.
     * Includes automatic token refresh on 401 errors.
     * 
     * @param array $booking_data Booking information
     * @return array Meeting details
     * @throws Exception If API call fails
     */
    public function create_meeting($booking_data) {
        error_log('WPSA Google Meet: Creating meeting for booking #' . $booking_data['id']);
        
        try {
            // Try to create event
            $event = WPSA_Booking_Calendar_Sync::create_google_calendar_event(
                $booking_data['id'],
                $booking_data
            );
            
            error_log('WPSA Google Meet: Event created successfully');
            
        } catch (Exception $e) {
            // If 401 (token expired), refresh and retry once
            if (strpos($e->getMessage(), '401') !== false) {
                error_log('WPSA Google Meet: Token expired, refreshing...');
                
                if (WPSA_Booking_Calendar_Sync::refresh_access_token()) {
                    // Retry after refresh
                    $event = WPSA_Booking_Calendar_Sync::create_google_calendar_event(
                        $booking_data['id'],
                        $booking_data
                    );
                } else {
                    throw new Exception('Failed to refresh Google access token');
                }
            } else {
                // Not a 401 error - re-throw
                throw $e;
            }
        }
        
        if (!$event) {
            throw new Exception('Failed to create Google Calendar event');
        }
        
        if (!isset($event['hangoutLink'])) {
            throw new Exception('Google Calendar event created but no Meet link returned');
        }
        
        return [
            'meeting_link' => $event['hangoutLink'],
            'meeting_id' => $event['id'] ?? null,
            'passcode' => null, // Google Meet handles auth internally
            'details' => [
                'provider' => 'Google Meet',
                'calendar_event_id' => $event['id'] ?? null,
                'created_at' => $event['created'] ?? null,
                'html_link' => $event['htmlLink'] ?? null,
            ]
        ];
    }
    
    /**
     * Check if Google Meet is configured
     * 
     * Verifies that Google Calendar API access token exists.
     * 
     * @return bool True if configured
     */
    public function is_configured() {
        $access_token = get_option('wpsa_booking_google_access_token', '');
        return !empty($access_token);
    }
    
    /**
     * Get provider name
     * 
     * @return string Provider display name
     */
    public function get_name() {
        return 'Google Meet';
    }
}
