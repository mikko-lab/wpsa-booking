<?php
/**
 * Video Provider Factory
 * 
 * Factory class for creating video provider instances.
 * Uses the Factory pattern to instantiate the correct provider
 * based on the selected platform.
 * 
 * @package WPSA_Booking
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Video_Provider_Factory {
    
    /**
     * Create a video provider instance
     * 
     * @param string $platform Platform identifier ('google-meet', 'microsoft-teams', 'manual')
     * @return WPSA_Video_Provider Provider instance
     * @throws Exception If platform is unknown
     */
    public static function create($platform) {
        switch ($platform) {
            case 'google-meet':
                return new WPSA_Google_Meet_Provider();
                
            case 'microsoft-teams':
                return new WPSA_Teams_Provider();
                
            case 'manual':
                return new WPSA_Manual_Provider();
                
            default:
                throw new Exception("Unknown video platform: $platform");
        }
    }
    
    /**
     * Get all available (configured) providers
     * 
     * Returns only providers that are properly configured and ready to use.
     * Useful for displaying available options in the admin UI.
     * 
     * @return array Associative array of platform_id => provider_instance
     */
    public static function get_available_providers() {
        $all_providers = [
            'google-meet' => new WPSA_Google_Meet_Provider(),
            'microsoft-teams' => new WPSA_Teams_Provider(),
            'manual' => new WPSA_Manual_Provider(),
        ];
        
        // Filter to only configured providers
        return array_filter($all_providers, function($provider) {
            return $provider->is_configured();
        });
    }
    
    /**
     * Get all registered providers (regardless of configuration status)
     * 
     * Returns all providers, including those not yet configured.
     * Useful for admin settings pages to show configuration options.
     * 
     * @return array Associative array of platform_id => provider_instance
     */
    public static function get_all_providers() {
        return [
            'google-meet' => new WPSA_Google_Meet_Provider(),
            'microsoft-teams' => new WPSA_Teams_Provider(),
            'manual' => new WPSA_Manual_Provider(),
        ];
    }
}
