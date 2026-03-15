<?php
/**
 * WPSA Booking Post Types
 * Registers custom post types for admin management
 */

class WPSA_Booking_Post_Types {
    
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_types']);
    }
    
    /**
     * Register custom post types
     */
    public static function register_post_types() {
        // Varaukset (for admin display) - liitetty WPSA ZeroClick -menuun
        register_post_type('wpsa_zeroclick_booking', [  // ← Yksilöllisempi nimi!
            'labels' => [
                'name' => 'Varaukset',
                'singular_name' => 'Varaus',
                'add_new' => 'Lisää varaus',
                'add_new_item' => 'Lisää uusi varaus',
                'edit_item' => 'Muokkaa varausta',
                'view_item' => 'Näytä varaus',
                'all_items' => 'Kaikki varaukset',
                'search_items' => 'Hae varauksia',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'wpsa-zeroclick-booking',  // ← Liitetty parent menuun!
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_options',
            ],
        ]);
    }
}
