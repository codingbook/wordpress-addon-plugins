<?php
    /**
     * Plugin Name: FluentSupport Reporting Extended
     * Description: Extended reporting for FluentSupport with API integration
     * Version: 1.0.0
     * Author: Your Name
     * Text Domain: fluent-support-reporting-extended
     */

    // Prevent direct access
    if (!defined('ABSPATH')) {
        exit;
    }

    // Define plugin constants
    define('FSRE_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('FSRE_PLUGIN_URL', plugin_dir_url(__FILE__));

    class FluentSupportReportingExtended {

        public function __construct() {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_ajax_fsre_save_credentials', array($this, 'save_credentials'));
            add_action('wp_ajax_fsre_get_reports', array($this, 'get_reports'));
            add_action('wp_ajax_fsre_get_agents', array($this, 'get_agents'));
            add_action('wp_ajax_fsre_test_api', array($this, 'test_api'));
        }

        public function add_admin_menu() {
            add_menu_page(
                'FluentSupport Reporting',
                'FS Reporting',
                'manage_options',
                'fluent-support-reporting',
                array($this, 'admin_page'),
                'dashicons-chart-bar',
                30
            );
        }

        public function enqueue_scripts($hook) {
            if (strpos($hook, 'fluent-support-reporting') !== false) {
                wp_enqueue_script('jquery');
                wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true);
                wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9');
                wp_enqueue_script('fsre-admin', FSRE_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'flatpickr'), '1.0.0', true);
                wp_enqueue_style('fsre-admin', FSRE_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');

                wp_localize_script('fsre-admin', 'fsre_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('fsre_nonce')
                ));
            }
        }

        public function admin_page() {
            $credentials = get_option('fsre_credentials', array());
        ?>
        <div class="wrap">
            <h1>FluentSupport Reporting Extended</h1>

            <div id="fsre-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#credentials" class="nav-tab nav-tab-active">Credentials</a>
                    <a href="#reports" class="nav-tab">Reports</a>
                </nav>

                <div id="credentials" class="tab-content">
                    <h2>API Credentials</h2>
                    <form id="fsre-credentials-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Username</th>
                                <td>
                                    <input type="text" name="user_name" id="user_name"
                                           value="<?php echo esc_attr($credentials['user_name'] ?? ''); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Password</th>
                                <td>
                                    <input type="password" name="user_pass" id="user_pass"
                                           value="<?php echo esc_attr($credentials['user_pass'] ?? ''); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button-primary" value="Save Credentials">
                            <button type="button" id="test_api" class="button-secondary">Test API Connection</button>
                        </p>
                    </form>
                </div>

                <div id="reports" class="tab-content" style="display:none;">
                    <h2>Ticket Response Reports</h2>

                    <div class="fsre-filters">
                        <label for="date_range">Date Range:</label>
                        <input type="text" id="date_range" placeholder="Select date range" />

                        <label for="agent_select">Agent:</label>
                        <select id="agent_select">
                            <option value="">All Agents</option>
                        </select>

                        <button id="get_reports" class="button-primary">Get Reports</button>
                    </div>

                    <div id="loading" style="display:none;">
                        <p>Loading reports...</p>
                    </div>

                    <div id="reports_container"></div>
                </div>
            </div>
        </div>
        <?php
            }

                public function save_credentials() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $user_name = sanitize_text_field($_POST['user_name']);
                    $user_pass = sanitize_text_field($_POST['user_pass']);

                    $credentials = array(
                        'user_name' => $user_name,
                        'user_pass' => $user_pass
                    );

                    update_option('fsre_credentials', $credentials);

                    wp_send_json_success('Credentials saved successfully!');
                }

                public function get_reports() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $credentials = get_option('fsre_credentials', array());

                    if (empty($credentials['user_name']) || empty($credentials['user_pass'])) {
                        wp_send_json_error('Please configure API credentials first.');
                    }

                    $date_range = sanitize_text_field($_POST['date_range']);
                    $agent_id   = sanitize_text_field($_POST['agent_id']);

                    // Parse date range
                    $dates      = explode(' to ', $date_range);
                    $start_date = isset($dates[0]) ? $dates[0] : date('Y-m-d');
                    $end_date   = isset($dates[1]) ? $dates[1] : date('Y-m-d');

                    // Build API URL
                    $api_url = 'https://support.wpmanageninja.com/wp-json/fluent-support/v2/reports/ticket-response-stats';

                    // Build query parameters
                    $query_parts   = array();
                    $query_parts[] = 'date_range[]=' . urlencode($start_date);
                    $query_parts[] = 'date_range[]=' . urlencode($end_date);
                    $query_parts[] = 'person_type=agent';

                    if (!empty($agent_id)) {
                        $query_parts[] = 'agent_id=' . urlencode($agent_id);
                    }

                    $api_url .= '?' . implode('&', $query_parts);

                    // Make API request
                    $response = wp_remote_get($api_url, array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode($credentials['user_name'] . ':' . $credentials['user_pass']),
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ),
                        'timeout' => 30
                    ));

                    if (is_wp_error($response)) {
                        wp_send_json_error('API request failed: ' . $response->get_error_message());
                    }

                    $response_code = wp_remote_retrieve_response_code($response);
                    $body          = wp_remote_retrieve_body($response);

                    // Debug logging
                    error_log('API URL: ' . $api_url);
                    error_log('Response Code: ' . $response_code);
                    error_log('Response Body: ' . $body);

                    // Check for HTTP errors
                    if ($response_code !== 200) {
                        wp_send_json_error('API returned HTTP ' . $response_code . ': ' . $body);
                    }

                    $data = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        wp_send_json_error('Invalid JSON response from API. Response: ' . substr($body, 0, 500));
                    }

                    wp_send_json_success($data);
                }

                public function get_agents() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $credentials = get_option('fsre_credentials', array());

                    if (empty($credentials['user_name']) || empty($credentials['user_pass'])) {
                        wp_send_json_error('Please configure API credentials first.');
                    }

                    // This would typically be another API endpoint to get agents
                    // For now, we'll extract agents from the sample data
                    $agents = array(
                        array('id' => '18110', 'name' => 'Rahul Deb Das'),
                        array('id' => '11886', 'name' => 'Amimul Ihsan Mahdi')
                    );

                    wp_send_json_success($agents);
                }

                public function test_api() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $credentials = get_option('fsre_credentials', array());

                    if (empty($credentials['user_name']) || empty($credentials['user_pass'])) {
                        wp_send_json_error('Please configure API credentials first.');
                    }

                    // Simple API test - just try to access the base endpoint
                    $api_url = 'https://support.wpmanageninja.com/wp-json/fluent-support/v2/reports/ticket-response-stats?date_range[]=2025-07-11&date_range[]=2025-07-11&person_type=agent';

                    $response = wp_remote_get($api_url, array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode($credentials['user_name'] . ':' . $credentials['user_pass']),
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ),
                        'timeout' => 30
                    ));

                    if (is_wp_error($response)) {
                        wp_send_json_error('API request failed: ' . $response->get_error_message());
                    }

                    $response_code = wp_remote_retrieve_response_code($response);
                    $body          = wp_remote_retrieve_body($response);
                    $headers       = wp_remote_retrieve_headers($response);

                    wp_send_json_success(array(
                        'url'           => $api_url,
                        'response_code' => $response_code,
                        'headers'       => $headers,
                        'body'          => $body,
                        'is_json'       => (json_decode($body) !== null)
                    ));
                }
            }

            // Initialize the plugin
        new FluentSupportReportingExtended();
