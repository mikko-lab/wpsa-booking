<?php
/**
 * Timeslot Locking System
 * 
 * Prevents double-bookings by temporarily locking timeslots
 * when users select them.
 * 
 * @package WPSA_Booking
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Timeslot_Lock {
    
    /**
     * Lock duration in seconds (5 minutes)
     */
    const LOCK_DURATION = 300; // 5 minutes
    
    /**
     * Table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpsa_zeroclick_locks';
    }
    
    /**
     * Lock a timeslot
     * 
     * @param string $date Date (YYYY-MM-DD)
     * @param string $time Time (HH:MM:SS)
     * @param string $session_id Unique session identifier
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function lock_timeslot($date, $time, $session_id) {
        global $wpdb;
        
        // Clean up expired locks first
        $this->cleanup_expired_locks();
        
        // Check if already locked by someone else
        $existing_lock = $this->get_lock($date, $time);
        
        if ($existing_lock) {
            // If locked by same session, extend it
            if ($existing_lock->session_id === $session_id) {
                return $this->extend_lock($date, $time, $session_id);
            }
            
            // Locked by someone else
            return new WP_Error(
                'timeslot_locked',
                'Tämä aika on varattu toiselle käyttäjälle. Valitse toinen aika.',
                ['status' => 423] // 423 Locked
            );
        }
        
        // Check if already booked (confirmed)
        if ($this->is_timeslot_booked($date, $time)) {
            return new WP_Error(
                'timeslot_booked',
                'Tämä aika on jo varattu. Valitse toinen aika.',
                ['status' => 409] // 409 Conflict
            );
        }
        
        // Create lock
        $result = $wpdb->insert(
            $this->table_name,
            [
                'booking_date' => $date,
                'booking_time' => $time,
                'session_id' => $session_id,
                'locked_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::LOCK_DURATION)
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error(
                'lock_failed',
                'Lukituksen luominen epäonnistui.',
                ['db_error' => $wpdb->last_error]
            );
        }
        
        return true;
    }
    
    /**
     * Extend existing lock
     * 
     * @param string $date
     * @param string $time
     * @param string $session_id
     * @return bool
     */
    private function extend_lock($date, $time, $session_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            [
                'locked_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::LOCK_DURATION)
            ],
            [
                'booking_date' => $date,
                'booking_time' => $time,
                'session_id' => $session_id
            ],
            ['%s', '%s'],
            ['%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Unlock a timeslot
     * 
     * @param string $date
     * @param string $time
     * @param string $session_id
     * @return bool
     */
    public function unlock_timeslot($date, $time, $session_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            [
                'booking_date' => $date,
                'booking_time' => $time,
                'session_id' => $session_id
            ],
            ['%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Get lock information
     * 
     * @param string $date
     * @param string $time
     * @return object|null Lock object or null
     */
    public function get_lock($date, $time) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE booking_date = %s 
             AND booking_time = %s
             AND expires_at > NOW()",
            $date,
            $time
        ));
    }
    
    /**
     * Check if timeslot is locked
     * 
     * @param string $date
     * @param string $time
     * @param string|null $exclude_session_id Exclude this session (own lock)
     * @return bool
     */
    public function is_locked($date, $time, $exclude_session_id = null) {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM {$this->table_name}
                  WHERE booking_date = %s 
                  AND booking_time = %s
                  AND expires_at > NOW()";
        
        $params = [$date, $time];
        
        if ($exclude_session_id) {
            $query .= " AND session_id != %s";
            $params[] = $exclude_session_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $params));
        
        return $count > 0;
    }
    
    /**
     * Check if timeslot is already booked (confirmed)
     * 
     * @param string $date
     * @param string $time
     * @return bool
     */
    private function is_timeslot_booked($date, $time) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'wpsa_zeroclick_bookings';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$bookings_table}
             WHERE booking_date = %s 
             AND booking_time = %s
             AND status = 'confirmed'",
            $date,
            $time
        ));
        
        return $count > 0;
    }
    
    /**
     * Get all locked timeslots for a date
     * 
     * @param string $date
     * @param string|null $exclude_session_id
     * @return array Array of times (HH:MM:SS)
     */
    public function get_locked_timeslots($date, $exclude_session_id = null) {
        global $wpdb;
        
        $query = "SELECT booking_time FROM {$this->table_name}
                  WHERE booking_date = %s
                  AND expires_at > NOW()";
        
        $params = [$date];
        
        if ($exclude_session_id) {
            $query .= " AND session_id != %s";
            $params[] = $exclude_session_id;
        }
        
        $results = $wpdb->get_col($wpdb->prepare($query, $params));
        
        return $results ?: [];
    }
    
    /**
     * Clean up expired locks
     * 
     * @return int Number of locks removed
     */
    public function cleanup_expired_locks() {
        global $wpdb;
        
        $result = $wpdb->query(
            "DELETE FROM {$this->table_name}
             WHERE expires_at <= NOW()"
        );
        
        return $result !== false ? $result : 0;
    }
    
    /**
     * Clean up all locks for a session
     * 
     * @param string $session_id
     * @return bool
     */
    public function cleanup_session_locks($session_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['session_id' => $session_id],
            ['%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpsa_zeroclick_locks';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            session_id varchar(100) NOT NULL,
            locked_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_timeslot (booking_date, booking_time),
            KEY expires_at (expires_at),
            KEY session_id (session_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
