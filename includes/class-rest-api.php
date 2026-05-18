<?php
/**
 * WPSA Booking REST API
 * Handles all API endpoints for the booking system
 */

class WPSA_Booking_REST_API {
    
    private static $namespace = 'wpsa-zeroclick/v1';
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    /**
     * Register all REST API routes
     */
    public static function register_routes() {
        
        // GET /wpsa-zeroclick/v1/availability
        register_rest_route(self::$namespace, '/availability', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_availability'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) && strtotime($param) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'service' => [
                    'required' => false,
                    'default' => 'risk-assessment',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'session_id' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // POST /wpsa-zeroclick/v1/lock-timeslot
        register_rest_route(self::$namespace, '/lock-timeslot', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'lock_timeslot'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) && strtotime($param) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'time' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'session_id' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);
        
        // POST /wpsa-zeroclick/v1/unlock-timeslot
        register_rest_route(self::$namespace, '/unlock-timeslot', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'unlock_timeslot'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) && strtotime($param) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'time' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'session_id' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);
        
        // POST /wpsa-booking/v1/bookings
        register_rest_route(self::$namespace, '/bookings', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_booking'],
            'permission_callback' => '__return_true',
            'args' => [
                'service_type' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'booking_date' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) && strtotime($param) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'booking_time' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'meeting_platform' => [
                    'required' => false,
                    'default' => 'manual',
                    'validate_callback' => function($param) {
                        return in_array($param, ['manual', 'google-meet', 'microsoft-teams'], true);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'customer_name' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'customer_email' => [
                    'required' => true,
                    'validate_callback' => 'is_email',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'customer_phone' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'customer_message' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ],
            ]
        ]);
        
        // GET /wpsa-booking/v1/services
        register_rest_route(self::$namespace, '/services', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_services'],
            'permission_callback' => '__return_true',
        ]);
        
        // DELETE /wpsa-booking/v1/bookings/{id}
        register_rest_route(self::$namespace, '/bookings/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'cancel_booking'],
            'permission_callback' => [__CLASS__, 'verify_booking_owner'],
            'args' => [
                'id' => ['required' => true],
                'token' => ['required' => true]
            ]
        ]);
    }
    
    /**
     * GET /availability - Hae vapaat ajat valitulle päivälle
     */
    public static function get_availability($request) {
        $date = $request->get_param('date');
        $service = $request->get_param('service');
        $session_id = $request->get_param('session_id'); // Oma sessio
        
        // Hae palvelun kesto
        $services = get_option('wpsa_booking_services', []);
        $duration = 45; // default
        
        foreach ($services as $s) {
            if ($s['id'] === $service) {
                $duration = $s['duration'];
                break;
            }
        }
        
        // Hae työajat tälle viikonpäivälle
        $day_of_week = strtolower(date('l', strtotime($date)));
        $working_hours = get_option('wpsa_booking_working_hours', []);
        
        // Debug: Log jos työajat puuttuvat
        if (!isset($working_hours[$day_of_week])) {
            error_log("WPSA Booking: No working hours for $day_of_week ($date)");
            error_log("Available days: " . implode(', ', array_keys($working_hours)));
            
            return new WP_REST_Response([
                'slots' => [],
                'message' => 'Ei työaikaa tälle päivälle',
                'debug' => [
                    'day' => $day_of_week,
                    'available_days' => array_keys($working_hours)
                ]
            ], 200);
        }
        
        $start = $working_hours[$day_of_week]['start'];
        $end = $working_hours[$day_of_week]['end'];
        
        // Generoi aikaslotsit
        $slots = self::generate_time_slots($start, $end, $duration);
        
        // Poista varatut ajat
        $available_slots = self::filter_booked_slots($slots, $date);
        
        // Hae lukitut ajat (pois lukien oma sessio)
        $lock_manager = new WPSA_Timeslot_Lock();
        $locked_times = $lock_manager->get_locked_timeslots($date, $session_id);
        
        // Merkitse lukitut ajat
        $available_slots = array_map(function($slot) use ($locked_times) {
            $slot['locked'] = in_array($slot['time'], $locked_times);
            return $slot;
        }, $available_slots);
        
        // Poista menneet ajat (jos tänään)
        if ($date === date('Y-m-d')) {
            $current_time = date('H:i');
            $available_slots = array_filter($available_slots, function($slot) use ($current_time) {
                return $slot['time'] > $current_time;
            });
        }
        
        return new WP_REST_Response([
            'date' => $date,
            'slots' => array_values($available_slots),
            'count' => count($available_slots),
            'locked_count' => count($locked_times)
        ], 200);
    }
    
    /**
     * POST /bookings - Luo uusi varaus
     */
    public static function create_booking($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        $date = $request->get_param('booking_date');
        $time = $request->get_param('booking_time');
        $session_id = $request->get_param('session_id');
        
        // Tarkista että käyttäjällä on lukitus tälle ajalle
        $lock_manager = new WPSA_Timeslot_Lock();
        $lock = $lock_manager->get_lock($date, $time);

        if ( ! $lock || $lock->session_id !== $session_id ) {
            // Lukitus puuttuu tai on vanhentunut — yritetään luoda se uudelleen
            // (suojaa myös tilanteen jossa session_id ei täsmää aiempaan lukitukseen)
            $relock = $lock_manager->lock_timeslot( $date, $time, $session_id );

            if ( is_wp_error( $relock ) ) {
                // Aika on jonkin muun lukitsema tai jo varattu
                return new WP_Error(
                    'no_lock',
                    'Tämä aika ei ole enää saatavilla. Valitse uusi aika.',
                    ['status' => 403]
                );
            }
        }
        
        // Validoi että aika on yhä vapaa
        $is_available = self::check_slot_availability($date, $time);
        
        if (!$is_available) {
            // Poista lukitus
            $lock_manager->unlock_timeslot($date, $time, $session_id);
            
            return new WP_Error(
                'slot_taken',
                'Valitettavasti tämä aika on juuri varattu. Valitse toinen aika.',
                ['status' => 409]
            );
        }
        
        // Tallenna varaus
        $result = $wpdb->insert($table, [
            'service_type' => $request->get_param('service_type'),
            'booking_date' => $request->get_param('booking_date'),
            'booking_time' => $request->get_param('booking_time'),
            'customer_name' => $request->get_param('customer_name'),
            'customer_email' => $request->get_param('customer_email'),
            'customer_phone' => $request->get_param('customer_phone'),
            'customer_message' => $request->get_param('customer_message'),
            'meeting_platform' => $request->get_param('meeting_platform') ?: 'manual',
            'status' => 'confirmed',
            'created_at' => current_time('mysql'),
        ]);
        
        if (!$result) {
            return new WP_Error(
                'booking_failed',
                'Varauksen tallennus epäonnistui. Yritä uudelleen.',
                ['status' => 500]
            );
        }
        
        $booking_id = $wpdb->insert_id;
        
        // ✨ DEBUG: Log booking creation
        error_log(sprintf(
            'WPSA REST API: Booking #%d created successfully. Triggering wpsa_booking_created hook...',
            $booking_id
        ));
        
        // THE ZERO-CLICK MAGIC - Automaatio käyntiin!
        do_action('wpsa_booking_created', $booking_id, $request->get_params());
        
        error_log(sprintf(
            'WPSA REST API: wpsa_booking_created hook completed for booking #%d',
            $booking_id
        ));
        
        // Hae tallennettu varaus
        $booking = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id),
            ARRAY_A
        );
        
        // Poista lukitus (varaus onnistui)
        $lock_manager->unlock_timeslot($date, $time, $session_id);
        
        return new WP_REST_Response([
            'success' => true,
            'booking' => $booking,
            'message' => 'Varaus vahvistettu!'
        ], 201);
    }
    
    /**
     * GET /services - Hae saatavilla olevat palvelut
     */
    public static function get_services() {
        $services = get_option('wpsa_booking_services', []);
        return new WP_REST_Response($services, 200);
    }
    
    /**
     * DELETE /bookings/{id} - Peru varaus
     */
    public static function cancel_booking($request) {
        global $wpdb;
        
        $booking_id = $request->get_param('id');
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        
        $result = $wpdb->update(
            $table,
            ['status' => 'cancelled'],
            ['id' => $booking_id]
        );
        
        if ($result) {
            do_action('wpsa_booking_cancelled', $booking_id);
            
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Varaus peruttu onnistuneesti'
            ], 200);
        }
        
        return new WP_Error('cancel_failed', 'Peruutus epäonnistui', ['status' => 500]);
    }
    
    /**
     * Helper: Generoi aikaslotsit
     */
    private static function generate_time_slots($start, $end, $duration) {
        $slots = [];
        $current = strtotime($start);
        $end_time = strtotime($end);
        
        while ($current + ($duration * 60) <= $end_time) {
            $time = date('H:i', $current);
            $end_slot = date('H:i', $current + ($duration * 60));
            
            $slots[] = [
                'id' => uniqid(),
                'time' => $time,
                'end' => $end_slot,
                'label' => sprintf('%s - %s', $time, $end_slot)
            ];
            
            $current += ($duration * 60);
        }
        
        return $slots;
    }
    
    /**
     * Helper: Suodata varatut ajat
     */
    private static function filter_booked_slots($slots, $date) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        
        $booked = $wpdb->get_col($wpdb->prepare(
            "SELECT booking_time FROM $table WHERE booking_date = %s AND status != 'cancelled'",
            $date
        ));
        
        return array_filter($slots, function($slot) use ($booked) {
            return !in_array($slot['time'], $booked);
        });
    }
    
    /**
     * Helper: Tarkista onko aika vapaa
     */
    private static function check_slot_availability($date, $time) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE booking_date = %s 
            AND booking_time = %s 
            AND status != 'cancelled'",
            $date,
            $time
        ));
        
        return $count == 0;
    }
    
    /**
     * Permission callback: Verify booking owner via HMAC token
     */
    public static function verify_booking_owner($request) {
        $token = $request->get_param('token');
        $booking_id = absint($request->get_param('id'));

        if (empty($token) || empty($booking_id)) {
            return false;
        }

        $expected_token = self::generate_cancel_token($booking_id);

        return hash_equals($expected_token, $token);
    }

    /**
     * Generate a cancel token for a booking using HMAC-SHA256
     */
    public static function generate_cancel_token($booking_id) {
        $secret = get_option('wpsa_cancel_secret');
        return wp_hash($booking_id . '|cancel|' . $secret, 'secure_auth');
    }
    
    /**
     * Check IP-based rate limit using transients (max 10 locks per 15 min)
     */
    private static function check_lock_rate_limit() {
        $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
        $key   = 'wpsa_lock_rate_' . md5( $ip );
        $count = (int) get_transient( $key );

        if ( $count >= 10 ) {
            return false;
        }

        set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
        return true;
    }

    /**
     * Lock a timeslot
     *
     * POST /wpsa-zeroclick/v1/lock-timeslot
     */
    public static function lock_timeslot($request) {
        if ( ! self::check_lock_rate_limit() ) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Liian monta pyyntöä. Yritä hetken kuluttua uudelleen.',
                'code'    => 'rate_limited',
            ], 429);
        }

        $date = sanitize_text_field($request['date']);
        $time = sanitize_text_field($request['time']);
        $session_id = sanitize_text_field($request['session_id']);

        $lock_manager = new WPSA_Timeslot_Lock();
        $result = $lock_manager->lock_timeslot($date, $time, $session_id);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ], $result->get_error_data()['status'] ?? 400);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Aika lukittu onnistuneesti',
            'expires_in' => WPSA_Timeslot_Lock::LOCK_DURATION
        ], 200);
    }
    
    /**
     * Unlock a timeslot
     * 
     * POST /wpsa-zeroclick/v1/unlock-timeslot
     */
    public static function unlock_timeslot($request) {
        $date = sanitize_text_field($request['date']);
        $time = sanitize_text_field($request['time']);
        $session_id = sanitize_text_field($request['session_id']);
        
        $lock_manager = new WPSA_Timeslot_Lock();
        $result = $lock_manager->unlock_timeslot($date, $time, $session_id);
        
        return new WP_REST_Response([
            'success' => $result,
            'message' => $result ? 'Lukitus poistettu' : 'Lukituksen poisto epäonnistui'
        ], $result ? 200 : 400);
    }
}
