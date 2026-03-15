<?php
/**
 * ICS Calendar File Generator
 * 
 * Generates iCalendar (.ics) files for booking confirmations.
 * Compatible with Apple Calendar, Google Calendar, Outlook, and all
 * other calendar applications that support the iCalendar format.
 * 
 * @package WPSA_Booking
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_ICS_Generator {
    
    /**
     * Generate .ics file content
     * 
     * Creates a complete iCalendar file with event details and reminders.
     * 
     * @param array $event_data {
     *     Event information
     *     
     *     @type int    $booking_id      Booking ID
     *     @type string $summary         Event title
     *     @type string $description     Event description
     *     @type string $location        Meeting location/URL
     *     @type string $start_datetime  Start time (Y-m-d H:i:s)
     *     @type string $end_datetime    End time (Y-m-d H:i:s)
     *     @type string $organizer_email Organizer email
     *     @type string $organizer_name  Organizer name
     *     @type string $attendee_email  Attendee email
     *     @type string $attendee_name   Attendee name
     * }
     * 
     * @return string ICS file content
     */
    public static function generate($event_data) {
        // Format dates for iCalendar (UTC)
        $start = new DateTime($event_data['start_datetime'], new DateTimeZone(wp_timezone_string()));
        $end = new DateTime($event_data['end_datetime'], new DateTimeZone(wp_timezone_string()));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        
        // Convert to UTC for iCalendar
        $start->setTimezone(new DateTimeZone('UTC'));
        $end->setTimezone(new DateTimeZone('UTC'));
        
        // Format: 20260326T074500Z
        $dtstart = $start->format('Ymd\THis\Z');
        $dtend = $end->format('Ymd\THis\Z');
        $dtstamp = $now->format('Ymd\THis\Z');
        
        // Unique ID for this event
        $uid = 'varaus-' . $event_data['booking_id'] . '@wpsaavutettavuus.fi';
        
        // Escape text for iCalendar format
        $summary = self::escape_ics_text($event_data['summary']);
        $description = self::escape_ics_text($event_data['description']);
        $location = self::escape_ics_text($event_data['location'] ?? '');
        
        // Build ICS content
        $ics = [];
        $ics[] = 'BEGIN:VCALENDAR';
        $ics[] = 'VERSION:2.0';
        $ics[] = 'PRODID:-//WP Saavutettavuus//Varausjarjestelma//FI';
        $ics[] = 'CALSCALE:GREGORIAN';
        $ics[] = 'METHOD:REQUEST';
        $ics[] = '';
        $ics[] = 'BEGIN:VEVENT';
        $ics[] = 'UID:' . $uid;
        $ics[] = 'DTSTAMP:' . $dtstamp;
        $ics[] = 'DTSTART:' . $dtstart;
        $ics[] = 'DTEND:' . $dtend;
        $ics[] = 'SUMMARY:' . $summary;
        
        if (!empty($description)) {
            $ics[] = 'DESCRIPTION:' . $description;
        }
        
        if (!empty($location)) {
            $ics[] = 'LOCATION:' . $location;
        }
        
        // Organizer
        $organizer_email = $event_data['organizer_email'] ?? get_option('admin_email');
        $organizer_name = $event_data['organizer_name'] ?? 'WP Saavutettavuus';
        $ics[] = 'ORGANIZER;CN=' . self::escape_ics_text($organizer_name) . ':mailto:' . $organizer_email;
        
        // Attendee
        if (!empty($event_data['attendee_email'])) {
            $attendee_name = $event_data['attendee_name'] ?? '';
            $ics[] = 'ATTENDEE;CN=' . self::escape_ics_text($attendee_name) . ';RSVP=TRUE:mailto:' . $event_data['attendee_email'];
        }
        
        $ics[] = 'STATUS:CONFIRMED';
        $ics[] = 'SEQUENCE:0';
        
        // Reminders
        // 24 hours before
        $ics[] = 'BEGIN:VALARM';
        $ics[] = 'TRIGGER:-PT24H';
        $ics[] = 'ACTION:DISPLAY';
        $ics[] = 'DESCRIPTION:Muistutus: ' . $summary . ' huomenna';
        $ics[] = 'END:VALARM';
        
        // 30 minutes before
        $ics[] = 'BEGIN:VALARM';
        $ics[] = 'TRIGGER:-PT30M';
        $ics[] = 'ACTION:DISPLAY';
        $ics[] = 'DESCRIPTION:Muistutus: ' . $summary . ' alkaa 30 min kuluttua';
        $ics[] = 'END:VALARM';
        
        $ics[] = 'END:VEVENT';
        $ics[] = 'END:VCALENDAR';
        
        // Join with CRLF (required by RFC 5545)
        return implode("\r\n", $ics);
    }
    
    /**
     * Escape text for iCalendar format
     * 
     * Escapes special characters according to RFC 5545.
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    private static function escape_ics_text($text) {
        // Replace special characters
        $text = str_replace('\\', '\\\\', $text);  // Backslash
        $text = str_replace(',', '\\,', $text);    // Comma
        $text = str_replace(';', '\\;', $text);    // Semicolon
        $text = str_replace("\n", '\\n', $text);   // Newline
        
        // Fold long lines (75 characters max per RFC 5545)
        return self::fold_ics_line($text);
    }
    
    /**
     * Fold long lines for iCalendar format
     * 
     * RFC 5545 requires lines to be max 75 octets.
     * Long lines must be folded with CRLF + space.
     * 
     * @param string $text Text to fold
     * @return string Folded text
     */
    private static function fold_ics_line($text) {
        if (strlen($text) <= 75) {
            return $text;
        }
        
        $lines = [];
        $line = '';
        $words = explode(' ', $text);
        
        foreach ($words as $word) {
            if (strlen($line . ' ' . $word) > 75) {
                $lines[] = $line;
                $line = ' ' . $word; // Continuation line starts with space
            } else {
                $line .= ($line ? ' ' : '') . $word;
            }
        }
        
        if ($line) {
            $lines[] = $line;
        }
        
        return implode("\r\n", $lines);
    }
    
    /**
     * Get filename for .ics attachment
     * 
     * Generates a descriptive filename for the calendar file.
     * 
     * @param int $booking_id Booking ID
     * @param string $booking_code Booking code (e.g., K7MQ)
     * @return string Filename (e.g., "varaus-K7MQ.ics")
     */
    public static function get_filename($booking_id, $booking_code) {
        return 'varaus-' . $booking_code . '.ics';
    }
}
