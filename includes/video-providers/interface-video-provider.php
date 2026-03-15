<?php
/**
 * Video Provider Interface
 * 
 * Defines the standard interface for all video meeting platforms.
 * Each provider (Google Meet, Teams, Zoom, etc.) must implement this interface.
 * 
 * @package WPSA_Booking
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

interface WPSA_Video_Provider {
    
    /**
     * Create a video meeting
     * 
     * Creates a new video meeting and returns the meeting details.
     * 
     * @param array $booking_data {
     *     Booking information
     *     
     *     @type int    $id              Booking ID
     *     @type string $customer_name   Customer name
     *     @type string $customer_email  Customer email
     *     @type string $customer_phone  Customer phone (optional)
     *     @type string $customer_message Customer message (optional)
     *     @type string $booking_date    Booking date (YYYY-MM-DD)
     *     @type string $booking_time    Booking time (HH:MM)
     *     @type string $service_type    Service type ID
     * }
     * 
     * @return array {
     *     Meeting information
     *     
     *     @type string|null $meeting_link Video meeting URL
     *     @type string|null $meeting_id   Meeting ID (provider-specific)
     *     @type string|null $passcode     Meeting passcode (if applicable)
     *     @type array       $details      Additional provider-specific details
     * }
     * 
     * @throws Exception If meeting creation fails
     */
    public function create_meeting($booking_data);
    
    /**
     * Check if provider is configured and ready to use
     * 
     * Verifies that all required settings (API credentials, tokens, etc.)
     * are in place for this provider to function.
     * 
     * @return bool True if provider is configured, false otherwise
     */
    public function is_configured();
    
    /**
     * Get the display name of this provider
     * 
     * Returns a human-readable name for this video provider.
     * Used in admin UI and user-facing messages.
     * 
     * @return string Provider display name (e.g., "Google Meet", "Microsoft Teams")
     */
    public function get_name();
}
