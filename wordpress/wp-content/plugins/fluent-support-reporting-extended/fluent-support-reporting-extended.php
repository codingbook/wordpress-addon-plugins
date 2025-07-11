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
            add_action('wp_ajax_fsre_get_agent_responses', array($this, 'get_agent_responses'));
            add_action('wp_ajax_fsre_get_teams', array($this, 'get_teams'));
            add_action('wp_ajax_fsre_save_teams', array($this, 'save_teams'));
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
                wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
                wp_enqueue_script('fsre-admin', FSRE_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'flatpickr', 'chartjs'), '1.0.0', true);
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
                    <a href="#agent-responses" class="nav-tab">Agent Responses</a>
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

                <div id="agent-responses" class="tab-content" style="display:none;">
                    <h2>Agent Response Analytics</h2>

                    <div class="fsre-filters">
                        <label for="agent_responses_date_range">Date Range:</label>
                        <input type="text" id="agent_responses_date_range" placeholder="Select date range" />

                        <label for="agent_filter">Filter by Agent:</label>
                        <select id="agent_filter" multiple>
                            <option value="">Loading agents...</option>
                        </select>

                        <label for="team_filter">Filter by Team:</label>
                        <select id="team_filter" multiple>
                            <option value="">Loading teams...</option>
                        </select>

                        <button id="get_agent_responses" class="button-primary">Get Agent Responses</button>
                    </div>

                    <div id="agent_responses_loading" style="display:none;">
                        <p>Loading agent responses...</p>
                    </div>

                    <div id="agent_responses_container">
                        <div id="agent_responses_summary_container"></div>
                        <div id="agent_responses_table_container"></div>
                        <div id="agent_responses_chart_container">
                            <canvas id="agent_responses_chart"></canvas>
                        </div>
                        <div id="agent_responses_details_container"></div>
                    </div>
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

                public function get_agent_responses() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $credentials = get_option('fsre_credentials', array());

                    if (empty($credentials['user_name']) || empty($credentials['user_pass'])) {
                        wp_send_json_error('Please configure API credentials first.');
                    }

                    $date_range      = sanitize_text_field($_POST['date_range']);
                    $selected_agents = isset($_POST['selected_agents']) ? array_map('intval', $_POST['selected_agents']) : array();
                    $selected_teams  = isset($_POST['selected_teams']) ? array_map('sanitize_text_field', $_POST['selected_teams']) : array();

                    // Parse date range
                    $dates      = explode(' to ', $date_range);
                    $start_date = isset($dates[0]) ? $dates[0] : date('Y-m-d');
                    $end_date   = isset($dates[1]) ? $dates[1] : date('Y-m-d');

                    // Build API URL for getting all responses
                    $api_url = 'https://support.wpmanageninja.com/wp-json/fluent-support/v2/reports/ticket-response-stats';

                    // Build query parameters
                    $query_parts   = array();
                    $query_parts[] = 'date_range[]=' . urlencode($start_date);
                    $query_parts[] = 'date_range[]=' . urlencode($end_date);
                    $query_parts[] = 'person_type=agent';

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

                    if ($response_code !== 200) {
                        wp_send_json_error('API returned HTTP ' . $response_code . ': ' . $body);
                    }

                    $data = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        wp_send_json_error('Invalid JSON response from API. Response: ' . substr($body, 0, 500));
                    }

                    // Process the data to create agent response statistics
                    $agent_stats = $this->process_agent_response_data($data, $selected_agents, $selected_teams);

                    wp_send_json_success($agent_stats);
                }

                private function process_agent_response_data($data, $selected_agents = array(), $selected_teams = array()) {
                    // Load teams configuration
                    $teams_file = FSRE_PLUGIN_DIR . 'teams.json';
                    $teams_data = array();

                    if (file_exists($teams_file)) {
                        $teams_data = json_decode(file_get_contents($teams_file), true);
                    }

                    // Get all agents from teams configuration
                    $all_agents = isset($teams_data['all_agents']) ? $teams_data['all_agents'] : array();

                    // If no agents defined in teams.json, use the default list
                    if (empty($all_agents)) {
                        $all_agents = array(
                            array('id' => 37636, 'full_name' => 'Rakid'),
                            array('id' => 37635, 'full_name' => 'Chinmoy'),
                            array('id' => 37576, 'full_name' => 'Ariful Islam'),
                            array('id' => 36338, 'full_name' => 'Ashim'),
                            array('id' => 35896, 'full_name' => 'Farjana'),
                            array('id' => 35895, 'full_name' => 'Akil Gazi'),
                            array('id' => 35599, 'full_name' => 'Sujoy Chandra Das'),
                            array('id' => 28207, 'full_name' => 'Ashik'),
                            array('id' => 27510, 'full_name' => 'Ruman Ahmed'),
                            array('id' => 18116, 'full_name' => 'Ibrahim Sharif'),
                            array('id' => 18115, 'full_name' => 'Mahfuzur Rahman'),
                            array('id' => 18112, 'full_name' => 'Reza Shartaz Jaman'),
                            array('id' => 18110, 'full_name' => 'Rahul Deb Das'),
                            array('id' => 11968, 'full_name' => 'Ahsan Chowdhury'),
                            array('id' => 11886, 'full_name' => 'Amimul Ihsan Mahdi'),
                            array('id' => 8524, 'full_name' => 'Abul Khoyer'),
                            array('id' => 6352, 'full_name' => 'Nayan Das'),
                            array('id' => 3130, 'full_name' => 'Syed Numan'),
                            array('id' => 587, 'full_name' => 'Md Kamrul Islam'),
                            array('id' => 40735, 'full_name' => 'Anik')
                        );
                    }

                    // Filter agents based on selected teams
                    $filtered_agents = $all_agents;
                    if (!empty($selected_teams)) {
                        $filtered_agents = array_filter($all_agents, function ($agent) use ($selected_teams) {
                            return in_array($agent['team'], $selected_teams);
                        });
                    }

                    // Filter agents based on selected agents
                    if (!empty($selected_agents)) {
                        $filtered_agents = array_filter($filtered_agents, function ($agent) use ($selected_agents) {
                            return in_array($agent['id'], $selected_agents);
                        });
                    }

                    $agents = array_values($filtered_agents);

                    $agent_stats      = array();
                    $time_series_data = array();
                    $agent_responses  = array();

                    // Initialize agent stats
                    foreach ($agents as $agent) {
                        $agent_stats[$agent['id']] = array(
                            'id'                => $agent['id'],
                            'full_name'         => $agent['full_name'],
                            'total_responses'   => 0,
                            'total_tickets'     => 0,
                            'first_response'    => null,
                            'last_response'     => null,
                            'responses_by_hour' => array(),
                            'response_details'  => array()
                        );
                    }

                    // Process response data
                    if (is_array($data)) {
                        foreach ($data as $response) {
                            $agent_id   = $response['person_id'] ?? null;
                            $ticket_id  = $response['ticket_id'] ?? null;
                            $created_at = $response['created_at'] ?? null;
                            $content    = $response['content'] ?? '';

                            if ($agent_id && isset($agent_stats[$agent_id])) {
                                $agent_stats[$agent_id]['total_responses']++;

                                // Track unique tickets
                                if (!isset($agent_stats[$agent_id]['tickets'])) {
                                    $agent_stats[$agent_id]['tickets'] = array();
                                }
                                $agent_stats[$agent_id]['tickets'][$ticket_id] = true;

                                // Track first and last response
                                if (!$agent_stats[$agent_id]['first_response'] || $created_at < $agent_stats[$agent_id]['first_response']) {
                                    $agent_stats[$agent_id]['first_response'] = $created_at;
                                }
                                if (!$agent_stats[$agent_id]['last_response'] || $created_at > $agent_stats[$agent_id]['last_response']) {
                                    $agent_stats[$agent_id]['last_response'] = $created_at;
                                }

                                // Track responses by hour for time series
                                $response_hour = date('Y-m-d H:00', strtotime($created_at));
                                if (!isset($agent_stats[$agent_id]['responses_by_hour'][$response_hour])) {
                                    $agent_stats[$agent_id]['responses_by_hour'][$response_hour] = 0;
                                }
                                $agent_stats[$agent_id]['responses_by_hour'][$response_hour]++;

                                // Track time series data by hour
                                if (!isset($time_series_data[$response_hour])) {
                                    $time_series_data[$response_hour] = array();
                                }
                                if (!isset($time_series_data[$response_hour][$agent_id])) {
                                    $time_series_data[$response_hour][$agent_id] = 0;
                                }
                                $time_series_data[$response_hour][$agent_id]++;

                                // Store detailed response information
                                $agent_stats[$agent_id]['response_details'][] = array(
                                    'id'             => $response['id'],
                                    'ticket_id'      => $ticket_id,
                                    'created_at'     => $created_at,
                                    'content'        => $content,
                                    'formatted_time' => date('Y-m-d H:i:s', strtotime($created_at))
                                );
                            }
                        }
                    }

                    // Calculate total tickets for each agent
                    foreach ($agent_stats as $agent_id => &$agent) {
                        $agent['total_tickets'] = isset($agent['tickets']) ? count($agent['tickets']) : 0;
                        unset($agent['tickets']); // Remove from final output
                    }

                    return array(
                        'agents'      => array_values($agent_stats),
                        'time_series' => $time_series_data
                    );
                }

                public function get_teams() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $teams_file = FSRE_PLUGIN_DIR . 'teams.json';

                    if (file_exists($teams_file)) {
                        $teams_data = json_decode(file_get_contents($teams_file), true);
                        wp_send_json_success($teams_data);
                    } else {
                        wp_send_json_error('Teams configuration file not found.');
                    }
                }

                public function save_teams() {
                    check_ajax_referer('fsre_nonce', 'nonce');

                    if (!current_user_can('manage_options')) {
                        wp_die('Unauthorized');
                    }

                    $teams_data = json_decode(stripslashes($_POST['teams_data']), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        wp_send_json_error('Invalid JSON data provided.');
                    }

                    $teams_file = FSRE_PLUGIN_DIR . 'teams.json';
                    $result     = file_put_contents($teams_file, json_encode($teams_data, JSON_PRETTY_PRINT));

                    if ($result !== false) {
                        wp_send_json_success('Teams configuration saved successfully.');
                    } else {
                        wp_send_json_error('Failed to save teams configuration.');
                    }
                }
            }

            // Initialize the plugin
        new FluentSupportReportingExtended();
