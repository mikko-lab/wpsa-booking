<?php
/**
 * WPSA Booking Calendar Sync
 * Handles Google Calendar integration and meeting link creation
 * 
 * THE ZERO-CLICK MAGIC - Automaatio!
 */

class WPSA_Booking_Calendar_Sync {
    
    public static function init() {
        // Hook into booking creation
        add_action('wpsa_booking_created', [__CLASS__, 'sync_to_calendar'], 10, 2);
        add_action('wpsa_booking_cancelled', [__CLASS__, 'remove_from_calendar'], 10, 1);
    }
    
    /**
     * Synkronoi varaus kalenteriin ja luo videoneuvottelulinkki
     * 
     * v1.3.0: Refaktoroitu käyttämään Video Provider -arkkitehtuuria
     * 
     * @since 1.3.0
     */
    public static function sync_to_calendar($booking_id, $booking_data) {
        error_log('═══════════════════════════════════════════════════════');
        error_log(sprintf(
            'WPSA Sync: ✨ HOOK TRIGGERED! Starting sync for booking #%d',
            $booking_id
        ));
        error_log(sprintf(
            'WPSA Sync: Platform from data: %s',
            $booking_data['meeting_platform'] ?? 'NOT SET'
        ));
        error_log('═══════════════════════════════════════════════════════');
        
        // Lisää meeting_platform dataan jos puuttuu (backward compatibility)
        if (!isset($booking_data['meeting_platform'])) {
            $booking_data['meeting_platform'] = 'manual';
            error_log('WPSA Sync: ⚠️  meeting_platform missing, using fallback: manual');
        }
        
        // Lisää ID dataan
        $booking_data['id'] = $booking_id;
        
        try {
            // ✨ Video Provider Pattern (v1.3.0)
            $platform = $booking_data['meeting_platform'];
            $provider = WPSA_Video_Provider_Factory::create($platform);
            
            // Luo video meeting
            $meeting = $provider->create_meeting($booking_data);
            
            // Tallenna meeting link tietokantaan
            global $wpdb;
            $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
            
            $wpdb->update(
                $table,
                [
                    'meeting_link' => $meeting['meeting_link'],
                    'meeting_platform' => $platform,
                    'meeting_details' => json_encode($meeting['details'])
                ],
                ['id' => $booking_id]
            );
            
            // Lähetä vahvistussähköposti
            WPSA_Booking_Email_Handler::send_confirmation($booking_id, $meeting);
            
        } catch (Exception $e) {
            // Log error
            error_log(sprintf(
                'WPSA Video Provider (%s) failed for booking #%d: %s',
                $platform ?? 'unknown',
                $booking_id,
                $e->getMessage()
            ));
            
            // FALLBACK: Manual provider (always works)
            try {
                $manual = new WPSA_Manual_Provider();
                $meeting = $manual->create_meeting($booking_data);
                
                // Send email with .ics only
                WPSA_Booking_Email_Handler::send_confirmation($booking_id, $meeting);
                
                // Store error for admin notification
                update_option('wpsa_last_provider_error', [
                    'timestamp' => time(),
                    'provider' => $platform ?? 'unknown',
                    'error' => $e->getMessage(),
                    'booking_id' => $booking_id,
                ]);
                
            } catch (Exception $fallback_error) {
                // Even fallback failed - critical error
                error_log(sprintf(
                    'WPSA CRITICAL: Even fallback provider failed for booking #%d: %s',
                    $booking_id,
                    $fallback_error->getMessage()
                ));
            }
        }
    }
    
    /**
     * Luo Google Calendar tapahtuma
     * 
     * @since 1.0.0
     * @since 1.3.0 Changed to public for Video Provider access
     */
    public static function create_google_calendar_event($booking_id, $data) {
        $access_token = get_option('wpsa_booking_google_access_token');
        
        if (!$access_token) {
            throw new Exception('Google Calendar access token missing');
        }
        
        // Rakenna tapahtuma
        $start_datetime = $data['booking_date'] . 'T' . $data['booking_time'] . ':00';
        
        // Laske lopetusaika
        $services = get_option('wpsa_booking_services', []);
        $duration = 45;
        foreach ($services as $service) {
            if ($service['id'] === $data['service_type']) {
                $duration = $service['duration'];
                break;
            }
        }
        
        $end_datetime = date(
            'Y-m-d\TH:i:s',
            strtotime($start_datetime) + ($duration * 60)
        );
        
        // Palvelun nimi
        $service_name = '';
        foreach ($services as $service) {
            if ($service['id'] === $data['service_type']) {
                $service_name = $service['name'];
                break;
            }
        }
        
        $event = [
            'summary' => sprintf('WCAG-konsultaatio: %s', $data['customer_name']),
            'description' => sprintf(
                "WCAG-KONSULTAATIO - WP SAAVUTETTAVUUS\n\n" .
                "═══════════════════════════════════════\n\n" .
                "TAPAAMISEN TIEDOT:\n" .
                "• Palvelu: %s\n" .
                "• Kesto: %d minuuttia\n\n" .
                "ASIAKKAAN TIEDOT:\n" .
                "• Nimi: %s\n" .
                "• Sähköposti: %s\n" .
                "• Puhelin: %s\n\n" .
                "%s" .
                "═══════════════════════════════════════\n\n" .
                "HUOM: Google Meet -linkki lisätään automaattisesti ylös.\n" .
                "Asiakas saa erillisen vahvistussähköpostin linkillä.",
                $service_name,
                $duration,
                $data['customer_name'],
                $data['customer_email'],
                $data['customer_phone'] ?? '-',
                !empty($data['customer_message']) ? "ASIAKKAAN VIESTI:\n" . $data['customer_message'] . "\n\n" : ""
            ),
            'start' => [
                'dateTime' => $start_datetime,
                'timeZone' => wp_timezone_string(),
            ],
            'end' => [
                'dateTime' => $end_datetime,
                'timeZone' => wp_timezone_string(),
            ],
            // NOTE: Don't add attendees - we send our own email with meeting link
            // If we add attendees, Google will send separate calendar invitations
            // 'attendees' => [...], // REMOVED to prevent duplicate emails
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 30],
                ],
            ],
        ];
        
        // Lähetä pyyntö Google Calendariin
        $response = wp_remote_post(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body' => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 15,
            ]
        );
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // 401 = Token vanhentunut, heitä selkeä virhe retry-logiikkaa varten
        if ($status_code === 401) {
            throw new Exception('401 Unauthorized - Token expired');
        }
        
        // Hyväksy sekä 200 että 201 (Google voi palauttaa molempia)
        if ($status_code !== 200 && $status_code !== 201) {
            $error_msg = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
            throw new Exception(sprintf('Google API error (%d): %s', $status_code, $error_msg));
        }
        
        return $body;
    }
    
    /**
     * Poista tapahtuma kalenterista
     */
    public static function remove_from_calendar($booking_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        $booking = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id),
            ARRAY_A
        );
        
        if (!$booking || !$booking['google_event_id']) {
            return;
        }
        
        $access_token = get_option('wpsa_booking_google_access_token');
        
        if (!$access_token) {
            return;
        }
        
        // Poista Google Calendar tapahtuma
        wp_remote_request(
            sprintf(
                'https://www.googleapis.com/calendar/v3/calendars/primary/events/%s',
                $booking['google_event_id']
            ),
            [
                'method' => 'DELETE',
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                ],
            ]
        );
    }
    
    /**
     * Refresh Google OAuth token
     */
    public static function refresh_access_token() {
        $refresh_token = get_option('wpsa_booking_google_refresh_token');
        $client_id = get_option('wpsa_booking_google_client_id');
        $client_secret = get_option('wpsa_booking_google_client_secret');
        
        if (!$refresh_token || !$client_id || !$client_secret) {
            return false;
        }
        
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            update_option('wpsa_booking_google_access_token', $body['access_token']);
            return true;
        }
        
        return false;
    }
}
