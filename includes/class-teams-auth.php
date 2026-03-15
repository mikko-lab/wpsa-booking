<?php
/**
 * WPSA Teams Authentication
 * 
 * Handles OAuth 2.0 authentication with Microsoft Azure AD
 * for accessing Microsoft Graph API and creating Teams meetings.
 * 
 * @package WPSA_Booking
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Teams_Auth {
    
    /**
     * Microsoft OAuth endpoints
     */
    private const AUTH_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    private const TOKEN_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    
    /**
     * Required scopes
     */
    private const SCOPES = [
        'OnlineMeetings.ReadWrite',
        'User.Read',
        'offline_access'
    ];
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('admin_init', [__CLASS__, 'handle_oauth_callback']);
    }
    
    /**
     * Get authorization URL
     * 
     * @return string Authorization URL
     */
    public static function get_auth_url() {
        $client_id = get_option('wpsa_booking_teams_client_id');
        
        if (empty($client_id)) {
            return '';
        }
        
        // Generate and store state nonce for CSRF protection
        $state = wp_create_nonce('wpsa_teams_oauth');
        set_transient('wpsa_teams_oauth_state', $state, 600); // 10 min
        
        // Redirect URI
        $redirect_uri = admin_url('admin.php?page=wpsa-zeroclick-settings&provider=teams');
        
        // Build authorization URL
        $params = [
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => $redirect_uri,
            'scope' => implode(' ', self::SCOPES),
            'state' => $state,
            'response_mode' => 'query',
        ];
        
        return self::AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback
     */
    public static function handle_oauth_callback() {
        // Check if this is a Teams OAuth callback
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpsa-zeroclick-settings') {
            return;
        }
        
        if (!isset($_GET['provider']) || $_GET['provider'] !== 'teams') {
            return;
        }
        
        if (!isset($_GET['code'])) {
            return;
        }
        
        // Verify state (CSRF protection)
        $state = isset($_GET['state']) ? $_GET['state'] : '';
        $saved_state = get_transient('wpsa_teams_oauth_state');
        
        if (empty($state) || $state !== $saved_state) {
            wp_die('Invalid state parameter. Please try again.');
        }
        
        // Delete used state
        delete_transient('wpsa_teams_oauth_state');
        
        // Exchange code for tokens
        $code = sanitize_text_field($_GET['code']);
        $result = self::exchange_code_for_tokens($code);
        
        if (is_wp_error($result)) {
            wp_die('OAuth error: ' . $result->get_error_message());
        }
        
        // Redirect to settings page with success message
        $redirect_url = add_query_arg([
            'page' => 'wpsa-zeroclick-settings',
            'teams_connected' => '1'
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Exchange authorization code for access token
     * 
     * @param string $code Authorization code
     * @return array|WP_Error Token data or error
     */
    private static function exchange_code_for_tokens($code) {
        $client_id = get_option('wpsa_booking_teams_client_id');
        $client_secret = get_option('wpsa_booking_teams_client_secret');
        
        if (empty($client_id) || empty($client_secret)) {
            return new WP_Error('missing_credentials', 'Teams Client ID or Secret not configured');
        }
        
        $redirect_uri = admin_url('admin.php?page=wpsa-zeroclick-settings&provider=teams');
        
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            error_log('WPSA Teams: Token exchange failed - ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            error_log('WPSA Teams: Token exchange error - ' . $data['error_description']);
            return new WP_Error('token_error', $data['error_description']);
        }
        
        if (!isset($data['access_token'])) {
            return new WP_Error('no_token', 'No access token in response');
        }
        
        // Store tokens (encrypted)
        self::store_tokens($data);
        
        return $data;
    }
    
    /**
     * Store tokens (encrypted)
     * 
     * @param array $token_data Token data from OAuth response
     */
    private static function store_tokens($token_data) {
        // Encrypt tokens before storing
        $access_token_encrypted = WPSA_Token_Encryption::encrypt($token_data['access_token']);
        $refresh_token_encrypted = WPSA_Token_Encryption::encrypt($token_data['refresh_token']);
        
        update_option('wpsa_booking_teams_access_token', $access_token_encrypted);
        update_option('wpsa_booking_teams_refresh_token', $refresh_token_encrypted);
        
        // Store expiry time (current time + expires_in - 5 min buffer)
        $expires_at = time() + ($token_data['expires_in'] - 300);
        update_option('wpsa_booking_teams_token_expires', $expires_at);
        
        error_log('WPSA Teams: Tokens stored successfully (expires: ' . date('Y-m-d H:i:s', $expires_at) . ')');
    }
    
    /**
     * Get current access token (auto-refresh if needed)
     * 
     * @return string|false Access token or false
     */
    public static function get_access_token() {
        // Check if token expired
        $expires_at = get_option('wpsa_booking_teams_token_expires', 0);
        
        if (time() >= $expires_at) {
            // Token expired, try to refresh
            error_log('WPSA Teams: Access token expired, refreshing...');
            $result = self::refresh_access_token();
            
            if (is_wp_error($result)) {
                error_log('WPSA Teams: Token refresh failed - ' . $result->get_error_message());
                return false;
            }
        }
        
        // Get encrypted token
        $encrypted = get_option('wpsa_booking_teams_access_token');
        
        if (empty($encrypted)) {
            return false;
        }
        
        // Decrypt and return
        return WPSA_Token_Encryption::decrypt($encrypted);
    }
    
    /**
     * Refresh access token using refresh token
     * 
     * @return array|WP_Error New token data or error
     */
    public static function refresh_access_token() {
        $client_id = get_option('wpsa_booking_teams_client_id');
        $client_secret = get_option('wpsa_booking_teams_client_secret');
        $refresh_token_encrypted = get_option('wpsa_booking_teams_refresh_token');
        
        if (empty($client_id) || empty($client_secret) || empty($refresh_token_encrypted)) {
            return new WP_Error('missing_data', 'Missing Teams credentials or refresh token');
        }
        
        // Decrypt refresh token
        $refresh_token = WPSA_Token_Encryption::decrypt($refresh_token_encrypted);
        
        if ($refresh_token === false) {
            return new WP_Error('decrypt_failed', 'Failed to decrypt refresh token');
        }
        
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('refresh_error', $data['error_description']);
        }
        
        if (!isset($data['access_token'])) {
            return new WP_Error('no_token', 'No access token in refresh response');
        }
        
        // Store new tokens
        self::store_tokens($data);
        
        error_log('WPSA Teams: Access token refreshed successfully');
        
        return $data;
    }
    
    /**
     * Check if Teams is connected
     * 
     * @return bool True if connected
     */
    public static function is_connected() {
        $access_token = get_option('wpsa_booking_teams_access_token');
        $refresh_token = get_option('wpsa_booking_teams_refresh_token');
        
        return !empty($access_token) && !empty($refresh_token);
    }
    
    /**
     * Disconnect Teams (delete all tokens)
     */
    public static function disconnect() {
        delete_option('wpsa_booking_teams_access_token');
        delete_option('wpsa_booking_teams_refresh_token');
        delete_option('wpsa_booking_teams_token_expires');
        
        error_log('WPSA Teams: Disconnected - all tokens deleted');
    }
}

// Initialize
WPSA_Teams_Auth::init();
