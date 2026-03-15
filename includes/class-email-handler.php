<?php
/**
 * WPSA Booking Email Handler
 * Sends beautiful, accessible confirmation emails
 * 
 * THE ACCESSIBLE DELIGHT - Apple-tyylinen sähköposti
 */

class WPSA_Booking_Email_Handler {
    
    public static function init() {
        // Aseta HTML-sähköpostit
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_email_type']);
    }
    
    /**
     * Generoi varauskoodi ID:stä (sama algoritmi kuin frontendissä)
     */
    private static function generate_booking_code($id) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No confusing chars (0,O,I,1)
        
        // Use ID as seed for consistent code per booking
        $seed = $id * 7919; // Prime number for better distribution
        $code = '';
        
        for ($i = 0; $i < 4; $i++) {
            $index = ($seed * ($i + 1) * 31) % strlen($chars);
            $code .= $chars[$index];
        }
        
        return $code;
    }
    
    /**
     * Lähetä vahvistussähköposti
     * 
     * @since 1.0.0
     * @since 1.3.0 Added .ics calendar attachment
     */
    public static function send_confirmation($booking_id, $event_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        $booking = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id),
            ARRAY_A
        );
        
        if (!$booking) {
            return;
        }
        
        // Hae palvelun tiedot
        $services = get_option('wpsa_booking_services', []);
        $service_name = '';
        $service_duration = 30;
        
        foreach ($services as $service) {
            if ($service['id'] === $booking['service_type']) {
                $service_name = $service['name'];
                $service_duration = $service['duration'] ?? 30;
                break;
            }
        }
        
        // Muotoile päivämäärä ja aika
        $date = date_i18n(
            get_option('date_format'),
            strtotime($booking['booking_date'])
        );
        $time = date_i18n(
            get_option('time_format'),
            strtotime($booking['booking_time'])
        );
        
        // Meeting link (from Video Provider)
        $meeting_link = $event_data['meeting_link'] ?? null;
        
        // Booking code
        $booking_code = self::generate_booking_code($booking_id);
        
        // Peruutuslinkki
        $cancel_token = md5($booking_id . 'wpsa_secret_salt');
        $cancel_url = add_query_arg([
            'action' => 'cancel_booking',
            'id' => $booking_id,
            'token' => $cancel_token,
        ], home_url('/varaukset/peru'));
        
        // Email subject
        $subject = sprintf(
            'Varaus vahvistettu: %s - %s klo %s',
            $service_name,
            $date,
            $time
        );
        
        // Email body (Apple-tyylinen HTML)
        $message = self::get_email_template([
            'customer_name' => $booking['customer_name'],
            'service_name' => $service_name,
            'date' => $date,
            'time' => $time,
            'meeting_link' => $meeting_link,
            'cancel_url' => $cancel_url,
            'booking_code' => $booking_code,
        ]);
        
        // Headers
        $headers = [
            'From: WP Saavutettavuus <mikko@wpsaavutettavuus.fi>',
            'Reply-To: mikko@wpsaavutettavuus.fi',
        ];
        
        // ✨ NEW v1.3.0: Generate .ics calendar file
        $ics_content = self::generate_ics_attachment(
            $booking,
            $service_name,
            $service_duration,
            $meeting_link,
            $booking_code
        );
        
        // Save .ics to temp file for attachment
        $ics_filename = WPSA_ICS_Generator::get_filename($booking_id, $booking_code);
        $temp_dir = sys_get_temp_dir();
        $ics_filepath = $temp_dir . '/' . $ics_filename;
        file_put_contents($ics_filepath, $ics_content);
        
        // ✨ DEBUG: Log email attempt
        error_log(sprintf(
            'WPSA Email: Attempting to send confirmation for booking #%d to %s',
            $booking_id,
            $booking['customer_email']
        ));
        
        // Lähetä asiakkaalle (with .ics attachment)
        $customer_sent = wp_mail(
            $booking['customer_email'],
            $subject,
            $message,
            $headers,
            [$ics_filepath] // Attachment
        );
        
        error_log(sprintf(
            'WPSA Email: Customer email %s (to: %s)',
            $customer_sent ? 'SENT ✓' : 'FAILED ✗',
            $booking['customer_email']
        ));
        
        // Lähetä myös itsellesi (with .ics attachment)
        $admin_email = get_option('admin_email');
        
        error_log(sprintf(
            'WPSA Email: Admin email address from WP options: %s',
            $admin_email ?: '(EMPTY!)'
        ));
        
        if (empty($admin_email) || !is_email($admin_email)) {
            error_log('WPSA Email: CRITICAL - Admin email is empty or invalid!');
            return; // Don't try to send if invalid
        }
        
        $admin_sent = wp_mail(
            $admin_email,
            'Uusi varaus: ' . $booking['customer_name'],
            self::get_admin_email_template($booking, [
                'service_name' => $service_name,
                'date' => $date,
                'time' => $time,
                'booking_code' => $booking_code,
            ]),
            $headers,
            [$ics_filepath] // Attachment
        );
        
        error_log(sprintf(
            'WPSA Email: Admin email %s (to: %s)',
            $admin_sent ? 'SENT ✓' : 'FAILED ✗',
            $admin_email
        ));
        
        // Clean up temp file
        @unlink($ics_filepath);
    }
    
    /**
     * Generate .ics calendar attachment
     * 
     * @since 1.3.0
     */
    private static function generate_ics_attachment($booking, $service_name, $duration, $meeting_link, $booking_code) {
        // Calculate start and end times
        // NOTE: booking_time from DB is already HH:MM:SS format (e.g., "13:30:00")
        $booking_time = $booking['booking_time'];
        
        // Ensure time has seconds (but don't double-add them!)
        if (substr_count($booking_time, ':') === 1) {
            // HH:MM format → add :00
            $booking_time .= ':00';
        }
        // If already HH:MM:SS, use as-is
        
        $start_datetime = $booking['booking_date'] . ' ' . $booking_time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($duration * 60));
        
        // Event summary
        $summary = sprintf('WCAG-konsultaatio: %s', $booking['customer_name']);
        
        // Event description
        $description = sprintf(
            "Palvelu: %s\n\nAsiakas: %s\nSähköposti: %s\nPuhelin: %s\n\nViesti:\n%s\n\nVarauskoodi: %s",
            $service_name,
            $booking['customer_name'],
            $booking['customer_email'],
            $booking['customer_phone'] ?? '-',
            $booking['customer_message'] ?? '-',
            $booking_code
        );
        
        // Location (meeting link or placeholder)
        $location = $meeting_link ?: 'Online-tapaaminen (linkki lähetetään erikseen)';
        
        // Generate .ics content
        return WPSA_ICS_Generator::generate([
            'booking_id' => $booking['id'],
            'summary' => $summary,
            'description' => $description,
            'location' => $location,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'organizer_email' => 'mikko@wpsaavutettavuus.fi',
            'organizer_name' => 'WP Saavutettavuus',
            'attendee_email' => $booking['customer_email'],
            'attendee_name' => $booking['customer_name'],
        ]);
    }
    
    /**
     * Apple-tyylinen sähköpostipohja (WCAG 2.2 AA)
     */
    private static function get_email_template($data) {
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varaus vahvistettu</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1d1d1f;
            background-color: #f5f5f7;
            padding: 20px;
        }
        
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .email-header {
            background: #007aff;
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        
        .email-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        /* Body */
        .email-body {
            padding: 32px 24px;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 16px;
            color: #1d1d1f;
        }
        
        .intro {
            font-size: 16px;
            color: #1d1d1f;
            margin-bottom: 24px;
        }
        
        /* Booking details card */
        .booking-card {
            background: #f5f5f7;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #d2d2d7;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #86868b;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1d1d1f;
            text-align: right;
        }
        
        /* CTA Button - WCAG 2.2 compliant */
        .cta-button {
            display: block;
            width: 100%;
            padding: 16px 24px;
            background: #007aff;
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-size: 17px;
            font-weight: 600;
            margin: 24px 0;
            /* WCAG 2.2: Min 44px height */
            min-height: 44px;
        }
        
        .cta-button:hover {
            background: #0051d5;
        }
        
        /* Secondary button */
        .secondary-button {
            display: block;
            width: 100%;
            padding: 12px 24px;
            background: white;
            color: #007aff !important;
            text-decoration: none;
            border: 2px solid #007aff;
            border-radius: 8px;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            margin: 12px 0;
        }
        
        /* Footer */
        .email-footer {
            background: #f5f5f7;
            padding: 24px;
            text-align: center;
            font-size: 14px;
            color: #86868b;
        }
        
        .email-footer a {
            color: #007aff;
            text-decoration: none;
        }
        
        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    </style>
</head>
<body role="article" aria-label="Varausvahvistus">
    <div class="email-container">
        <!-- Header -->
        <header class="email-header" role="banner">
            <h1>✓ Varaus vahvistettu</h1>
            <p>Tavataan pian!</p>
        </header>
        
        <!-- Body -->
        <main class="email-body" role="main">
            <p class="greeting">Hei <?php echo esc_html($data['customer_name']); ?>!</p>
            
            <p class="intro">
                Kiitos varauksestasi. Odotan innolla keskusteluamme saavutettavuudesta. 
                Alla ovat varauksen tiedot:
            </p>
            
            <!-- Booking Details -->
            <div class="booking-card" role="region" aria-label="Varauksen tiedot">
                <div class="detail-row">
                    <span class="detail-label">Palvelu</span>
                    <span class="detail-value"><?php echo esc_html($data['service_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Päivämäärä</span>
                    <span class="detail-value"><?php echo esc_html($data['date']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kellonaika</span>
                    <span class="detail-value"><?php echo esc_html($data['time']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Varaustunnus</span>
                    <span class="detail-value"><?php echo esc_html($data['booking_code']); ?></span>
                </div>
            </div>
            
            <!-- Meeting Link -->
            <?php if (!empty($data['meeting_link'])): ?>
            <a href="<?php echo esc_url($data['meeting_link']); ?>" 
               class="cta-button"
               role="button"
               aria-label="Liity videotapaamiseen">
                📹 Liity tapaamiseen
            </a>
            <?php endif; ?>
            
            <!-- Add to Calendar -->
            <p style="text-align: center; color: #86868b; font-size: 14px; margin: 16px 0;">
                Lisää kalenteriin:
                <a href="#" style="color: #007aff; text-decoration: none; margin: 0 8px;">Google</a> •
                <a href="#" style="color: #007aff; text-decoration: none; margin: 0 8px;">Apple</a> •
                <a href="#" style="color: #007aff; text-decoration: none; margin: 0 8px;">Outlook</a>
            </p>
            
            <!-- Cancel -->
            <p style="text-align: center; margin-top: 32px;">
                <a href="<?php echo esc_url($data['cancel_url']); ?>" 
                   class="secondary-button"
                   aria-label="Peru varaus">
                    Peru varaus
                </a>
            </p>
            
            <!-- Info -->
            <p style="margin-top: 32px; font-size: 14px; color: #86868b; line-height: 1.6;">
                <strong>Valmistaudu tapaamiseen:</strong><br>
                • Varmista että laitteesi kamera ja mikrofoni toimivat<br>
                • Varaa hiljainen tila keskustelulle<br>
                • Pidä sivustosi URL-osoite valmiina<br>
                • Kirjoita ylös kysymyksiä etukäteen
            </p>
        </main>
        
        <!-- Footer -->
        <footer class="email-footer" role="contentinfo">
            <p>
                <strong>WP Saavutettavuus</strong><br>
                Mikko | WCAG-asiantuntija<br>
                <a href="mailto:mikko@wpsaavutettavuus.fi">mikko@wpsaavutettavuus.fi</a><br>
                <a href="https://wpsaavutettavuus.fi">wpsaavutettavuus.fi</a>
            </p>
            
            <p style="margin-top: 16px; font-size: 12px;">
                Sait tämän viestin, koska varasi ajan palveluumme.<br>
                <a href="<?php echo esc_url($data['cancel_url']); ?>">Peru varaus</a>
            </p>
        </footer>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Admin-sähköpostipohja
     */
    private static function get_admin_email_template($booking, $data) {
        return sprintf(
            "Uusi varaus saapunut!\n\n" .
            "Varauskoodi: %s\n\n" .
            "Asiakas: %s\n" .
            "Sähköposti: %s\n" .
            "Puhelin: %s\n\n" .
            "Palvelu: %s\n" .
            "Aika: %s klo %s\n\n" .
            "Viesti:\n%s\n\n" .
            "Hallitse varausta: %s",
            $data['booking_code'] ?? '#' . $booking['id'],
            $booking['customer_name'],
            $booking['customer_email'],
            $booking['customer_phone'] ?? '-',
            $data['service_name'],
            $data['date'],
            $data['time'],
            $booking['customer_message'] ?? '-',
            admin_url('edit.php?post_type=wpsa_booking')
        );
    }
    
    /**
     * Set HTML email type
     */
    public static function set_html_email_type() {
        return 'text/html';
    }
}
