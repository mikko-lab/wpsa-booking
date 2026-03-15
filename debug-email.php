<?php
/**
 * WPSA Email Debug Tool
 * 
 * Lisää tämä tiedosto wp-content/plugins/wpsa-booking/ kansioon
 * Aja: https://wpsaavutettavuus.fi/wp-content/plugins/wpsa-booking/debug-email.php
 * 
 * POISTA TIEDOSTO KUN VALMIS!
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>WPSA Email Debug</h1>";
echo "<pre>";

// 1. Test basic WordPress email
echo "=== TEST 1: Basic WordPress wp_mail() ===\n";
$test1 = wp_mail(
    'mikko@wpsaavutettavuus.fi',
    'Test Email from WPSA Debug',
    'If you receive this, WordPress email works!',
    ['From: WP Saavutettavuus <mikko@wpsaavutettavuus.fi>']
);
echo "wp_mail() returned: " . ($test1 ? "TRUE ✓" : "FALSE ✗") . "\n\n";

// 2. Check if PHPMailer errors
add_action('wp_mail_failed', function($error) {
    echo "PHPMailer Error: " . $error->get_error_message() . "\n";
});

// 3. Test with booking data
echo "=== TEST 2: Simulate booking confirmation ===\n";
global $wpdb;
$table = $wpdb->prefix . 'wpsa_zeroclick_bookings';

// Get latest booking
$latest = $wpdb->get_row("SELECT * FROM $table ORDER BY id DESC LIMIT 1", ARRAY_A);

if ($latest) {
    echo "Found booking ID: {$latest['id']}\n";
    echo "Customer: {$latest['customer_name']}\n";
    echo "Email: {$latest['customer_email']}\n";
    echo "Platform: " . ($latest['meeting_platform'] ?? 'NOT SET') . "\n\n";
    
    // Try to send email manually
    echo "Attempting to send confirmation email...\n";
    
    try {
        // Call email handler directly
        require_once(__DIR__ . '/includes/class-email-handler.php');
        
        WPSA_Booking_Email_Handler::send_confirmation(
            $latest['id'],
            ['meeting_link' => $latest['meeting_link']]
        );
        
        echo "✓ Email handler called successfully\n";
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "No bookings found in database\n";
}

// 4. Check server email settings
echo "\n=== TEST 3: Server Configuration ===\n";
echo "PHP mail() available: " . (function_exists('mail') ? "YES ✓" : "NO ✗") . "\n";
echo "SMTP host: " . ini_get('SMTP') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";

// 5. Check WordPress settings
echo "\n=== TEST 4: WordPress Settings ===\n";
echo "Admin email: " . get_option('admin_email') . "\n";
echo "Site URL: " . get_site_url() . "\n";
echo "From email (default): wordpress@" . parse_url(get_site_url(), PHP_URL_HOST) . "\n";

echo "\n=== DONE ===\n";
echo "Check your inbox: mikko@wpsaavutettavuus.fi\n";
echo "\n⚠️  REMOVE THIS FILE AFTER TESTING!\n";
echo "</pre>";
