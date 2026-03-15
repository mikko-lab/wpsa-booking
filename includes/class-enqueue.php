<?php
/**
 * WPSA Booking Enqueue Manager
 * Handles loading React app and assets
 */

class WPSA_Booking_Enqueue {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    }
    
    public static function register_assets() {
        // Register (but don't enqueue yet - only when shortcode is used)
        $manifest_path = WPSA_BOOKING_PLUGIN_DIR . 'dist/.vite/manifest.json';
        
        if (file_exists($manifest_path)) {
            // Production build (Vite manifest)
            self::enqueue_production_assets();
        } else {
            // Development mode (Vite dev server)
            self::enqueue_dev_assets();
        }
    }
    
    private static function enqueue_production_assets() {
        $manifest = json_decode(
            file_get_contents(WPSA_BOOKING_PLUGIN_DIR . 'dist/.vite/manifest.json'),
            true
        );
        
        $main_js = $manifest['src/main.tsx']['file'] ?? null;
        $main_css = $manifest['src/main.tsx']['css'][0] ?? null;
        
        if ($main_js) {
            wp_register_script(
                'wpsa-booking-app',
                WPSA_BOOKING_PLUGIN_URL . 'dist/' . $main_js,
                [],
                WPSA_BOOKING_VERSION,
                true
            );
            // CRITICAL: Vite uses ES modules
            wp_script_add_data('wpsa-booking-app', 'type', 'module');
        }
        
        if ($main_css) {
            wp_register_style(
                'wpsa-booking-styles',
                WPSA_BOOKING_PLUGIN_URL . 'dist/' . $main_css,
                [],
                WPSA_BOOKING_VERSION
            );
        }
    }
    
    private static function enqueue_dev_assets() {
        // Vite dev server (Hot Module Replacement)
        wp_register_script(
            'wpsa-booking-vite-client',
            'http://localhost:3000/@vite/client',
            [],
            null,
            true
        );
        wp_script_add_data('wpsa-booking-vite-client', 'type', 'module');
        
        wp_register_script(
            'wpsa-booking-app',
            'http://localhost:3000/src/main.tsx',
            ['wpsa-booking-vite-client'],
            null,
            true
        );
        wp_script_add_data('wpsa-booking-app', 'type', 'module');
    }
    
    public static function enqueue_booking_app() {
        // Enqueue React app
        wp_enqueue_script('wpsa-booking-app');
        
        if (wp_style_is('wpsa-booking-styles', 'registered')) {
            wp_enqueue_style('wpsa-booking-styles');
        }
        
        // Localize script with WordPress data
        wp_localize_script('wpsa-booking-app', 'wpsaBooking', [
            'restUrl' => rest_url('wpsa-zeroclick/v1'),  // ← Päivitetty namespace!
            'nonce' => wp_create_nonce('wp_rest'),
            'services' => get_option('wpsa_booking_services', []),
            'workingHours' => get_option('wpsa_booking_working_hours', []),
            'locale' => get_locale(),
            'dateFormat' => get_option('date_format'),
            'timeFormat' => get_option('time_format'),
        ]);
    }
}
