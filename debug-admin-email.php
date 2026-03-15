<?php
/**
 * WPSA Admin Email Debug
 * 
 * Lisää: wp-content/plugins/wpsa-booking/debug-admin-email.php
 * Aja: https://wpsaavutettavuus.fi/wp-content/plugins/wpsa-booking/debug-admin-email.php
 * 
 * POISTA KUN VALMIS!
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>WPSA Admin Email Debug</h1>";
echo "<pre>";

echo "=== WORDPRESS ADMIN EMAIL ===\n";
$admin_email = get_option('admin_email');
echo "admin_email: " . ($admin_email ?: '(EMPTY!)') . "\n";
echo "is_email valid: " . (is_email($admin_email) ? 'YES ✓' : 'NO ✗') . "\n\n";

echo "=== SITE INFO ===\n";
echo "site_url: " . get_site_url() . "\n";
echo "admin_url: " . admin_url() . "\n";
echo "blogname: " . get_option('blogname') . "\n\n";

echo "=== TEST EMAIL TO ADMIN ===\n";
$test_subject = 'Test Email from WPSA Debug';
$test_message = 'If you receive this, admin email works!';
$test_headers = ['From: WP Saavutettavuus <mikko@wpsaavutettavuus.fi>'];

$sent = wp_mail($admin_email, $test_subject, $test_message, $test_headers);

echo "wp_mail() to admin_email: " . ($sent ? "TRUE ✓" : "FALSE ✗") . "\n";
echo "Check inbox: $admin_email\n\n";

echo "=== LATEST BOOKING ===\n";
global $wpdb;
$table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
$latest = $wpdb->get_row("SELECT * FROM $table ORDER BY id DESC LIMIT 1", ARRAY_A);

if ($latest) {
    echo "Booking ID: {$latest['id']}\n";
    echo "Customer: {$latest['customer_name']}\n";
    echo "Customer Email: {$latest['customer_email']}\n";
    echo "Date: {$latest['booking_date']} {$latest['booking_time']}\n";
    echo "Status: {$latest['status']}\n\n";
    
    echo "Trying to send admin notification for this booking...\n";
    
    require_once(__DIR__ . '/includes/class-email-handler.php');
    
    // Simulate admin email
    $admin_test = wp_mail(
        $admin_email,
        'TEST: Uusi varaus: ' . $latest['customer_name'],
        'This is a test admin notification.',
        $test_headers
    );
    
    echo "Admin notification: " . ($admin_test ? "SENT ✓" : "FAILED ✗") . "\n";
} else {
    echo "No bookings found.\n";
}

echo "\n=== DONE ===\n";
echo "⚠️  REMOVE THIS FILE AFTER TESTING!\n";
echo "</pre>";
