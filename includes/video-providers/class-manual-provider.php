<?php
/**
 * Manual Provider
 * 
 * Fallback provider that doesn't create any meeting link.
 * Used when:
 * - Customer selects "Apple Calendar tai muu"
 * - Primary provider fails (automatic fallback)
 * - No API integrations are configured
 * 
 * Customer receives only the .ics calendar file.
 * 
 * @package WPSA_Booking
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Manual_Provider implements WPSA_Video_Provider {
    
    /**
     * Create a "manual" meeting (no link)
     * 
     * Returns null for meeting_link, indicating that the customer
     * will handle meeting setup manually using the .ics file.
     * 
     * @param array $booking_data Booking information
     * @return array Meeting details (with null link)
     */
    public function create_meeting($booking_data) {
        // No API call needed - just return structure with no link
        return [
            'meeting_link' => null,
            'meeting_id' => null,
            'passcode' => null,
            'details' => [
                'provider' => 'Manual',
                'note' => 'Customer will receive .ics file only. No automatic video link.',
                'booking_id' => $booking_data['id'] ?? null,
            ]
        ];
    }
    
    /**
     * Manual provider is always available
     * 
     * This is the fallback option, so it's always considered configured.
     * 
     * @return bool Always true
     */
    public function is_configured() {
        return true; // Always available as fallback
    }
    
    /**
     * Get provider name
     * 
     * @return string Provider display name
     */
    public function get_name() {
        return 'Manuaalinen (.ics-tiedosto)';
    }
}
