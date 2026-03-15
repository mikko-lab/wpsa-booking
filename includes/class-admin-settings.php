<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSA_Booking_Admin_Settings {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            'WPSA ZeroClick',
            'WPSA ZeroClick',  // ← Yksilöllinen nimi!
            'manage_options',
            'wpsa-zeroclick-booking',  // ← Yksilöllinen slug!
            [__CLASS__, 'render_settings_page'],
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'wpsa-zeroclick-booking',  // ← Parent slug
            'Asetukset',
            'Asetukset',
            'manage_options',
            'wpsa-zeroclick-settings',  // ← Yksilöllinen slug!
            [__CLASS__, 'render_settings_page']
        );
    }
    
    public static function register_settings() {
        register_setting('wpsa_booking_settings', 'wpsa_booking_working_hours');
        register_setting('wpsa_booking_settings', 'wpsa_booking_services');
        
        // Google Calendar settings
        register_setting('wpsa_booking_google_settings', 'wpsa_booking_google_client_id');
        register_setting('wpsa_booking_google_settings', 'wpsa_booking_google_client_secret');
        register_setting('wpsa_booking_google_settings', 'wpsa_booking_google_access_token');
        register_setting('wpsa_booking_google_settings', 'wpsa_booking_google_refresh_token');
        register_setting('wpsa_booking_google_settings', 'wpsa_booking_google_token_expires');
    }
    
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle Google OAuth callback
        if (isset($_GET['code']) && isset($_GET['page']) && $_GET['page'] === 'wpsa-zeroclick-settings') {
            self::handle_google_oauth_callback();
        }
        
        // Show OAuth success message
        if (isset($_GET['oauth']) && $_GET['oauth'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Google Calendar yhdistetty onnistuneesti!</strong></p></div>';
        }
        
        // Show Teams OAuth success message
        if (isset($_GET['teams_connected']) && $_GET['teams_connected'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Microsoft Teams yhdistetty onnistuneesti!</strong></p></div>';
        }
        
        // Handle Google Calendar settings
        if (isset($_POST['wpsa_google_save_settings']) && check_admin_referer('wpsa_google_settings')) {
            update_option('wpsa_booking_google_client_id', sanitize_text_field($_POST['google_client_id']));
            update_option('wpsa_booking_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
            echo '<div class="notice notice-success"><p>Google Calendar -asetukset tallennettu!</p></div>';
        }
        
        // Handle Teams settings
        if (isset($_POST['wpsa_teams_save_settings']) && check_admin_referer('wpsa_teams_settings')) {
            update_option('wpsa_booking_teams_client_id', sanitize_text_field($_POST['teams_client_id']));
            update_option('wpsa_booking_teams_client_secret', sanitize_text_field($_POST['teams_client_secret']));
            echo '<div class="notice notice-success"><p>Microsoft Teams -asetukset tallennettu!</p></div>';
        }
        
        // Handle Google disconnect
        if (isset($_POST['wpsa_google_disconnect']) && check_admin_referer('wpsa_google_disconnect')) {
            delete_option('wpsa_booking_google_access_token');
            delete_option('wpsa_booking_google_refresh_token');
            delete_option('wpsa_booking_google_token_expires');
            echo '<div class="notice notice-success"><p>Google Calendar -yhteys katkaistu!</p></div>';
        }
        
        // Handle Teams disconnect
        if (isset($_POST['wpsa_teams_disconnect']) && check_admin_referer('wpsa_teams_disconnect')) {
            WPSA_Teams_Auth::disconnect();
            echo '<div class="notice notice-success"><p>Microsoft Teams -yhteys katkaistu!</p></div>';
        }
        
        // Handle form submission
        if (isset($_POST['wpsa_booking_save_settings']) && check_admin_referer('wpsa_booking_settings')) {
            $working_hours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                if (isset($_POST["enabled_$day"]) && $_POST["enabled_$day"] === '1') {
                    $working_hours[$day] = [
                        'start' => sanitize_text_field($_POST["start_$day"]),
                        'end' => sanitize_text_field($_POST["end_$day"])
                    ];
                }
            }
            
            update_option('wpsa_booking_working_hours', $working_hours);
            echo '<div class="notice notice-success"><p>Asetukset tallennettu!</p></div>';
        }
        
        $working_hours = get_option('wpsa_booking_working_hours', []);
        $days_fi = [
            'monday' => 'Maanantai',
            'tuesday' => 'Tiistai',
            'wednesday' => 'Keskiviikko',
            'thursday' => 'Torstai',
            'friday' => 'Perjantai',
            'saturday' => 'Lauantai',
            'sunday' => 'Sunnuntai'
        ];
        ?>
        
        <div class="wrap">
            <h1>WPSA Varausjärjestelmä - Asetukset</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpsa_booking_settings'); ?>
                
                <h2>Työajat</h2>
                <p>Määritä mitkä päivät ja ajat ovat varattavissa.</p>
                
                <table class="form-table">
                    <?php foreach ($days_fi as $day_key => $day_label): 
                        $is_enabled = isset($working_hours[$day_key]);
                        $start_time = $is_enabled ? $working_hours[$day_key]['start'] : '09:00';
                        $end_time = $is_enabled ? $working_hours[$day_key]['end'] : '17:00';
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($day_label); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="enabled_<?php echo esc_attr($day_key); ?>" 
                                       value="1" 
                                       <?php checked($is_enabled); ?>
                                       onchange="toggleDayInputs('<?php echo esc_attr($day_key); ?>', this.checked)">
                                Käytössä
                            </label>
                            
                            <span id="times_<?php echo esc_attr($day_key); ?>" style="<?php echo !$is_enabled ? 'display:none;' : ''; ?>">
                                <label style="margin-left: 20px;">
                                    Alkaa: 
                                    <input type="time" 
                                           name="start_<?php echo esc_attr($day_key); ?>" 
                                           value="<?php echo esc_attr($start_time); ?>"
                                           style="margin-left: 5px;">
                                </label>
                                
                                <label style="margin-left: 20px;">
                                    Päättyy: 
                                    <input type="time" 
                                           name="end_<?php echo esc_attr($day_key); ?>" 
                                           value="<?php echo esc_attr($end_time); ?>"
                                           style="margin-left: 5px;">
                                </label>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="wpsa_booking_save_settings" 
                           class="button button-primary" 
                           value="Tallenna asetukset">
                </p>
            </form>
            
            <hr>
            
            <h2>Google Calendar -integraatio</h2>
            <p>Yhdistä Google Calendar saadaksesi automaattiset Google Meet -linkit varauksiin.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpsa_google_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="google_client_id">Google Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="google_client_id"
                                   name="google_client_id" 
                                   value="<?php echo esc_attr(get_option('wpsa_booking_google_client_id', '')); ?>" 
                                   class="regular-text"
                                   placeholder="123456789-abc123.apps.googleusercontent.com">
                            <p class="description">
                                Saat tämän Google Cloud Consolesta: 
                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank">APIs & Services → Credentials</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="google_client_secret">Google Client Secret</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="google_client_secret"
                                   name="google_client_secret" 
                                   value="<?php echo esc_attr(get_option('wpsa_booking_google_client_secret', '')); ?>" 
                                   class="regular-text"
                                   placeholder="GOCSPX-aBcDeFgHiJkL">
                            <p class="description">
                                Client Secret löytyy samasta paikasta kuin Client ID.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="wpsa_google_save_settings" 
                           class="button button-primary" 
                           value="Tallenna Google-asetukset">
                </p>
            </form>
            
            <?php 
            $client_id = get_option('wpsa_booking_google_client_id', '');
            $client_secret = get_option('wpsa_booking_google_client_secret', '');
            $access_token = get_option('wpsa_booking_google_access_token', '');
            
            if ($client_id && $client_secret): 
            ?>
                <h3>Yhdistä Google Calendariin</h3>
                
                <?php if ($access_token): ?>
                    <div class="notice notice-success inline">
                        <p><strong>✓ Google Calendar yhdistetty!</strong></p>
                        <p>Access Token: <code><?php echo substr($access_token, 0, 50); ?>...</code></p>
                        <p>Token expires: <?php echo date('Y-m-d H:i:s', get_option('wpsa_booking_google_token_expires', 0)); ?></p>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('wpsa_google_disconnect'); ?>
                        <p>
                            <input type="submit" 
                                   name="wpsa_google_disconnect" 
                                   class="button button-secondary" 
                                   value="Katkaise yhteys Google Calendariin"
                                   onclick="return confirm('Haluatko varmasti katkaista yhteyden?');">
                        </p>
                    </form>
                <?php else: ?>
                    <div class="notice notice-info inline">
                        <p><strong>⚠ Ei vielä yhdistetty</strong></p>
                        <p>Klikkaa alla olevaa nappia yhdistääksesi Google Calendariin.</p>
                    </div>
                    
                    <p>
                        <a href="<?php echo esc_url(self::get_google_auth_url()); ?>" 
                           class="button button-primary button-large">
                            Yhdistä Google Calendariin
                        </a>
                    </p>
                    
                    <p class="description">
                        Sinut ohjataan Googleen kirjautumaan. Anna lupa käyttää Google Calendaria, 
                        jonka jälkeen sinut ohjataan takaisin tähän sivulle.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠ Tallenna ensin Client ID ja Client Secret</strong></p>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <!-- Microsoft Teams Integration (v1.4.0) -->
            <h2>Microsoft Teams -integraatio</h2>
            <p>Yhdistä Microsoft Teams saadaksesi automaattiset Teams-linkit varauksiin.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpsa_teams_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="teams_client_id">Application (client) ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="teams_client_id"
                                   name="teams_client_id" 
                                   value="<?php echo esc_attr(get_option('wpsa_booking_teams_client_id', '')); ?>" 
                                   class="regular-text"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <p class="description">
                                Saat tämän Azure Portalista: 
                                <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank">Azure AD → App registrations</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teams_client_secret">Client Secret (Value)</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="teams_client_secret"
                                   name="teams_client_secret" 
                                   value="<?php echo esc_attr(get_option('wpsa_booking_teams_client_secret', '')); ?>" 
                                   class="regular-text"
                                   placeholder="Xxx~xxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <p class="description">
                                Luo Secret Azuressa: Certificates & secrets → New client secret<br>
                                <strong>Kopioi VALUE, ei Secret ID!</strong> (Value näkyy vain kerran)
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="wpsa_teams_save_settings" 
                           class="button button-primary" 
                           value="Tallenna Teams-asetukset">
                </p>
            </form>
            
            <?php
            $teams_client_id = get_option('wpsa_booking_teams_client_id');
            $teams_client_secret = get_option('wpsa_booking_teams_client_secret');
            $teams_connected = WPSA_Teams_Auth::is_connected();
            
            if ($teams_client_id && $teams_client_secret):
            ?>
                <?php if ($teams_connected): ?>
                    <div class="notice notice-success inline">
                        <p><strong>✓ Microsoft Teams yhdistetty!</strong></p>
                        <p>Teams-linkit luodaan automaattisesti uusiin varauksiin.</p>
                        
                        <?php
                        $expires_at = get_option('wpsa_booking_teams_token_expires', 0);
                        if ($expires_at > 0):
                        ?>
                        <p class="description">
                            Access token vanhenee: <?php echo date('Y-m-d H:i:s', $expires_at); ?>
                            <?php if ($expires_at < time()): ?>
                                <span style="color: #d63638;">⚠ Vanhentunut - päivitetään automaattisesti seuraavalla varauksella</span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('wpsa_teams_disconnect'); ?>
                        <p>
                            <input type="submit" 
                                   name="wpsa_teams_disconnect" 
                                   class="button button-secondary" 
                                   value="Katkaise yhteys Microsoft Teamsiin"
                                   onclick="return confirm('Haluatko varmasti katkaista yhteyden?');">
                        </p>
                    </form>
                <?php else: ?>
                    <div class="notice notice-info inline">
                        <p><strong>⚠ Ei vielä yhdistetty</strong></p>
                        <p>Klikkaa alla olevaa nappia yhdistääksesi Microsoft Teamsiin.</p>
                    </div>
                    
                    <p>
                        <a href="<?php echo esc_url(WPSA_Teams_Auth::get_auth_url()); ?>" 
                           class="button button-primary button-large">
                            Yhdistä Microsoft Teamsiin
                        </a>
                    </p>
                    
                    <p class="description">
                        Sinut ohjataan Microsoftin kirjautumissivulle. Kirjaudu Microsoft-tilillä ja 
                        anna lupa luoda Teams-tapaamisia, jonka jälkeen sinut ohjataan takaisin tähän sivulle.
                    </p>
                    
                    <p class="description">
                        <strong>Huom:</strong> Tarvitset Microsoft 365 -tilin (personal Microsoft account ei riitä).
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠ Tallenna ensin Application ID ja Client Secret</strong></p>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <h2>Debug-tiedot</h2>
            <p class="description">Nämä tiedot näkyvät vain kun WP_DEBUG on päällä.</p>
            <p>Nykyiset työajat tietokannassa:</p>
            <pre><?php print_r($working_hours); ?></pre>
            
            <?php if ($client_id): ?>
            <p>Google Calendar:</p>
            <pre><?php 
                echo "Client ID: " . substr($client_id, 0, 20) . "...\n";
                echo "Client Secret: " . ($client_secret ? 'Set (hidden)' : 'Not set') . "\n";
                echo "Access Token: " . ($access_token ? 'Set (' . strlen($access_token) . ' chars)' : 'Not set') . "\n";
                echo "Refresh Token: " . (get_option('wpsa_booking_google_refresh_token') ? 'Set' : 'Not set') . "\n";
            ?></pre>
            <?php endif; ?>
            
            <?php if ($teams_client_id): ?>
            <p>Microsoft Teams:</p>
            <pre><?php 
                echo "Application ID: " . substr($teams_client_id, 0, 20) . "...\n";
                echo "Client Secret: " . ($teams_client_secret ? 'Set (hidden)' : 'Not set') . "\n";
                $teams_access_encrypted = get_option('wpsa_booking_teams_access_token');
                $teams_refresh_encrypted = get_option('wpsa_booking_teams_refresh_token');
                echo "Access Token: " . ($teams_access_encrypted ? 'Set (encrypted, ' . strlen($teams_access_encrypted) . ' chars)' : 'Not set') . "\n";
                echo "Refresh Token: " . ($teams_refresh_encrypted ? 'Set (encrypted)' : 'Not set') . "\n";
                echo "Encryption test: " . (WPSA_Token_Encryption::test() ? '✓ PASS' : '✗ FAIL') . "\n";
            ?></pre>
            <?php endif; ?>
            <?php endif; // WP_DEBUG ?>
        </div>
        
        <script>
        function toggleDayInputs(day, enabled) {
            document.getElementById('times_' + day).style.display = enabled ? '' : 'none';
        }
        </script>
        
        <?php
    }
    
    /**
     * Generate Google OAuth authorization URL
     */
    private static function get_google_auth_url() {
        $client_id = get_option('wpsa_booking_google_client_id', '');
        $redirect_uri = admin_url('admin.php?page=wpsa-zeroclick-settings');
        
        $params = [
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Handle Google OAuth callback
     */
    private static function handle_google_oauth_callback() {
        if (!isset($_GET['code'])) {
            return;
        }
        
        $client_id = get_option('wpsa_booking_google_client_id', '');
        $client_secret = get_option('wpsa_booking_google_client_secret', '');
        $redirect_uri = admin_url('admin.php?page=wpsa-zeroclick-settings');
        
        // Exchange code for tokens
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $_GET['code'],
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ],
        ]);
        
        if (is_wp_error($response)) {
            wp_die('OAuth error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            update_option('wpsa_booking_google_access_token', $body['access_token']);
            update_option('wpsa_booking_google_refresh_token', $body['refresh_token'] ?? '');
            update_option('wpsa_booking_google_token_expires', time() + ($body['expires_in'] ?? 3600));
            
            // Redirect to remove code from URL
            wp_redirect(admin_url('admin.php?page=wpsa-zeroclick-settings&oauth=success'));
            exit;
        } else {
            wp_die('OAuth failed: ' . print_r($body, true));
        }
    }
}
