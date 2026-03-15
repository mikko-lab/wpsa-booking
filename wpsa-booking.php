<?php
/**
 * Plugin Name: WPSA ZeroClick Sync
 * Plugin URI: https://wpsaavutettavuus.fi
 * Description: Täysin saavutettava (WCAG 2.2 AA) varausjärjestelmä Apple-tyylisellä käyttöliittymällä. Automaattinen Google Calendar + Meet -integraatio. React + WordPress REST API.
 * Version: 1.6.2
 * Author: Mikko / WP Saavutettavuus
 * Author URI: https://wpsaavutettavuus.fi
 * License: GPL v2 or later
 * Text Domain: wpsa-booking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Estä suora pääsy
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPSA_BOOKING_VERSION', '1.6.2');
define('WPSA_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSA_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSA_BOOKING_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class WPSA_Booking {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Core classes
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-calendar-sync.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-email-handler.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-enqueue.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-ics-generator.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-timeslot-lock.php';
        
        // Teams Integration (v1.4.0)
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-token-encryption.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/class-teams-auth.php';
        
        // Video Providers (v1.3.0+)
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/video-providers/interface-video-provider.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/video-providers/class-video-provider-factory.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/video-providers/class-google-meet-provider.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/video-providers/class-teams-provider.php';
        require_once WPSA_BOOKING_PLUGIN_DIR . 'includes/video-providers/class-manual-provider.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('plugins_loaded', [$this, 'init']);
        add_action('init', [$this, 'load_textdomain']);
        add_shortcode('wpsa_booking', [$this, 'render_booking_widget']);
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
        $this->set_default_options();
        
        // Schedule cleanup of expired locks (hourly)
        if (!wp_next_scheduled('wpsa_cleanup_expired_locks')) {
            wp_schedule_event(time(), 'hourly', 'wpsa_cleanup_expired_locks');
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        
        // Clear cleanup schedule
        wp_clear_scheduled_hook('wpsa_cleanup_expired_locks');
    }
    
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $bookings_table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            service_type varchar(100) NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL DEFAULT 45,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) DEFAULT NULL,
            customer_message text DEFAULT NULL,
            meeting_platform varchar(50) NOT NULL DEFAULT 'manual',
            status varchar(50) NOT NULL DEFAULT 'pending',
            google_event_id varchar(255) DEFAULT NULL,
            meeting_link varchar(500) DEFAULT NULL,
            meeting_details text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_bookings);
        
        // Create locks table
        WPSA_Timeslot_Lock::create_table();
        
        // Update existing table if needed (add missing columns)
        $this->update_table_schema();
    }
    
    /**
     * Update table schema for existing installations
     * Adds missing columns without dropping the table
     */
    private function update_table_schema() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        
        // Check if meeting_platform column exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM `{$table}` LIKE %s",
                'meeting_platform'
            )
        );
        
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE `{$table}` 
                ADD COLUMN `meeting_platform` varchar(50) NOT NULL DEFAULT 'manual' 
                AFTER `customer_message`"
            );
        }
        
        // Check if meeting_details column exists
        $details_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM `{$table}` LIKE %s",
                'meeting_details'
            )
        );
        
        if (empty($details_exists)) {
            $wpdb->query(
                "ALTER TABLE `{$table}` 
                ADD COLUMN `meeting_details` text DEFAULT NULL 
                AFTER `meeting_link`"
            );
        }
    }
    
    private function set_default_options() {
        $defaults = [
            'wpsa_booking_services' => [
                [
                    'id' => 'quick-consultation',
                    'name' => 'Ilmainen saavutettavuuskeskustelu',
                    'price' => 0,
                    'duration' => 30,
                    'description' => '30 minuutin maksuton videokeskustelu saavutettavuudesta'
                ]
            ],
            'wpsa_booking_working_hours' => [
                'monday'    => ['start' => '09:00', 'end' => '17:00'],
                'tuesday'   => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday'  => ['start' => '09:00', 'end' => '17:00'],
                'friday'    => ['start' => '09:00', 'end' => '16:00'],
            ],
        ];
        
        foreach ($defaults as $key => $value) {
            // Päivitä aina working hours varmistaaksesi että ne ovat oikein
            if ($key === 'wpsa_booking_working_hours') {
                update_option($key, $value);
            } elseif (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
    
    public function init() {
        WPSA_Booking_Post_Types::init();
        WPSA_Booking_REST_API::init();
        WPSA_Booking_Calendar_Sync::init();
        WPSA_Booking_Email_Handler::init();
        WPSA_Booking_Enqueue::init();
        WPSA_Booking_Admin_Settings::init();
        
        // Cleanup expired locks (cron hook)
        add_action('wpsa_cleanup_expired_locks', [$this, 'cleanup_expired_locks']);
    }
    
    public function cleanup_expired_locks() {
        $lock_manager = new WPSA_Timeslot_Lock();
        $removed = $lock_manager->cleanup_expired_locks();
        error_log("WPSA Booking: Cleaned up {$removed} expired locks");
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('wpsa-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function render_booking_widget($atts) {
        $atts = shortcode_atts(['service' => ''], $atts, 'wpsa_booking');
        
        WPSA_Booking_Enqueue::enqueue_booking_app();
        
        $output = sprintf(
            '<div id="wpsa-booking-root" data-service="%s" role="main"></div>',
            esc_attr($atts['service'])
        );
        
        $output .= '<noscript><div role="alert"><p>Varausjärjestelmä vaatii JavaScriptin. Ota yhteyttä: mikko@wpsaavutettavuus.fi</p></div></noscript>';
        
        return $output;
    }
}

function wpsa_booking() {
    return WPSA_Booking::get_instance();
}

wpsa_booking();
