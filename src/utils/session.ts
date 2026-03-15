/**
 * Session Management Utility
 * 
 * Generates and manages unique session IDs for timeslot locking
 * 
 * @package WPSA_Booking
 * @since 1.6.0
 */

/**
 * Generate or retrieve session ID
 * 
 * Stored in sessionStorage (lasts until browser tab closes)
 * 
 * @returns {string} Unique session ID
 */
export const getSessionId = (): string => {
  const STORAGE_KEY = 'wpsa_booking_session_id';
  
  // Try to get existing session ID
  let sessionId = sessionStorage.getItem(STORAGE_KEY);
  
  if (!sessionId) {
    // Generate new session ID
    sessionId = `sess_${Date.now()}_${Math.random().toString(36).substring(2, 15)}`;
    sessionStorage.setItem(STORAGE_KEY, sessionId);
  }
  
  return sessionId;
};

/**
 * Clear session ID (for testing)
 */
export const clearSessionId = (): void => {
  sessionStorage.removeItem('wpsa_booking_session_id');
};
