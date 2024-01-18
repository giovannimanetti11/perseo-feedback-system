<?php
/*
Plugin Name: Perseo Feedback System
Plugin URI: https://github.com/giovannimanetti11/perseo-feedback-system
Description: An open-source feedback system for your WordPress site.
Version: 1.1
Author: Giovanni Manetti
Author URI: https://github.com/giovannimanetti11
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

// Print feedback HTML
function perseo_feedback_html() {
    $options = get_option('perseo_options');
    echo '<div id="perseo-feedback-widget" class="' . esc_attr($options['position']) . '">';
    echo '<span>' . esc_html($options['text']) . '</span>';

    echo '<div class="buttons-container">';
    echo '<button id="perseo-feedback-yes">' . esc_html($options['yes']) . '</button>';
    echo '<button id="perseo-feedback-no">' . esc_html($options['no']) . '</button>';
    echo '</div>';

    echo '<textarea id="perseo-feedback-comment" placeholder="Lascia un commento..." style="display: none;"></textarea>';
    echo '<button id="perseo-feedback-submit" style="display: none;">Invia</button>';

    echo '<div id="perseo-feedback-close"><i class="fa fa-times-circle" aria-hidden="true"></i></div>';
    echo '</div>';
}


add_action('wp_footer', 'perseo_feedback_html');


// Register REST API route
function perseo_register_rest_route() {
    register_rest_route('perseo/v1', '/feedback', array(
        'methods' => 'POST',
        'callback' => 'perseo_save_feedback',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'perseo_register_rest_route');


// Register scripts and styles
function perseo_enqueue_scripts() {
    wp_enqueue_script('wp-api-fetch');
    wp_enqueue_script('perseo-feedback-script', plugin_dir_url(__FILE__) . 'feedback.js', array('wp-api-fetch'), '0.1', true);
    wp_enqueue_style('perseo-feedback-style', plugin_dir_url(__FILE__) . 'style.css', array(), '0.1');

    $options = get_option('perseo_options');
    
    // Localize the script with new data
    $script_data_array = array(
        'nonce' => wp_create_nonce('wp_rest'),  // Nonce for REST API
        'followupText' => $options['followup_text'],
        'thankYouText' => $options['thank_you_text']
    );
    wp_localize_script('perseo-feedback-script', 'perseoSettings', $script_data_array);
}
add_action('wp_enqueue_scripts', 'perseo_enqueue_scripts');

function perseo_enqueue_admin_scripts() {
    wp_enqueue_style('perseo-feedback-style', plugin_dir_url(__FILE__) . 'style.css', array(), '0.1');
}

add_action('admin_enqueue_scripts', 'perseo_enqueue_admin_scripts');



// On plugin activation, create feedback table
register_activation_hook(__FILE__, 'perseo_feedback_install');

function perseo_feedback_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'perseo_feedback';

    $charset_collate = $wpdb->get_charset_collate();

    // Create the feedback table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        url varchar(255) DEFAULT '' NOT NULL,
        feedback varchar(3) DEFAULT '' NOT NULL,
        comment text DEFAULT NULL,
        ip varchar(45) DEFAULT '' NOT NULL,
        device varchar(20) DEFAULT '' NOT NULL,
        user_agent varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL query to create the table
    dbDelta($sql);
}

function perseo_getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}


function perseo_save_feedback() {
    error_log('perseo_save_feedback was called');

    // Get the nonce from the headers
    $headers = perseo_getallheaders();
    if (!isset($headers['X-Wp-Nonce'])) {
        wp_send_json_error('Nonce not provided', 403);
        exit;
    }

    // Verify the nonce
    $nonce = $headers['X-Wp-Nonce'];

    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        wp_send_json_error('Invalid nonce', 403);
        exit;
    }

    global $wpdb;

    error_log(print_r($_POST, true));

    // Get the raw POST data
    $raw_data = file_get_contents('php://input');

    // Decode the JSON data into an array
    $data = json_decode($raw_data, true);

    // Validate the data
    if (!isset($data['url']) || !isset($data['feedback'])) {
        wp_send_json_error('Missing data', 400);
        exit;
    }

    if (!filter_var($data['url'], FILTER_VALIDATE_URL) || !in_array($data['feedback'], ['yes', 'no'])) {
        wp_send_json_error('Invalid data', 400);
        exit;
    }

    // Sanitize the data
    $url = sanitize_text_field($data['url']);
    $feedback = sanitize_text_field($data['feedback']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = wp_is_mobile() ? 'mobile' : 'desktop';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $table_name = $wpdb->prefix . 'perseo_feedback';

    $data = json_decode($raw_data, true);
    $comment = isset($data['comment']) ? sanitize_textarea_field($data['comment']) : '';

    // Insert feedback data into the database
    $result = $wpdb->insert(
        $table_name,
        array(
            'time' => current_time('mysql'),
            'url' => $url,
            'feedback' => $feedback,
            'comment' => $comment,
            'ip' => $ip,
            'device' => $device,
            'user_agent' => $user_agent
        )
    );

    if ($result === false) {
        // The insert failed. Log the error and then return an error message with the last error occurred in $wpdb.
        error_log('Failed to insert feedback into database: ' . $wpdb->last_error);
        wp_send_json_error($wpdb->last_error, 500);
    } else {
        // If everything went well, log a success message and then send a 200 status code and a success message
        error_log('Successfully inserted feedback into database');
        wp_send_json_success("Feedback recorded successfully", 200);
    }


    
    
}

function perseo_validate_options($input) {
    // All our options are text fields, so sanitize them
    $input['position'] = sanitize_text_field($input['position']);
    $input['text'] = sanitize_text_field($input['text']);
    $input['yes'] = sanitize_text_field($input['yes']);
    $input['no'] = sanitize_text_field($input['no']);

    // Validate position option
    if (!in_array($input['position'], ['top', 'bottom'])) {
        add_settings_error(
            'perseo_options', // Setting title
            'perseo_text_error', // Error ID
            'Please select a valid position.', // Error message
            'error' // Type of message
        );
    }

    return $input;
}

register_setting('perseo', 'perseo_options', 'perseo_validate_options');


// Register the AJAX action for saving feedback
add_action('wp_ajax_perseo_save_feedback', 'perseo_save_feedback');
add_action('wp_ajax_nopriv_perseo_save_feedback', 'perseo_save_feedback');



function perseo_feedback_menu() {
    // Add the top-level admin menu
    $page_title = 'Perseo Feedback';
    $menu_title = 'Perseo Feedback';
    $capability = 'manage_options';
    $menu_slug = 'perseo-feedback-settings';
    $function = 'perseo_feedback_settings_page';
    $icon_url = 'dashicons-chart-bar';  // Use a dashicon for bar charts
    $position = 100;  // Position in menu. Higher number is lower.
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);

    // Add submenu page with same slug as parent to ensure no duplicates
    $sub_menu_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

    // Now add the submenu page for Statistics
    $submenu_page_title = 'Perseo Feedback Statistics (Beta)';
    $submenu_title = 'Statistics (Beta)';
    $submenu_slug = 'perseo-feedback-statistics';
    $submenu_function = 'perseo_feedback_statistics_page';
    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
}
add_action('admin_menu', 'perseo_feedback_menu');

// Display the settings page content
function perseo_feedback_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('perseo');
            do_settings_sections('perseo');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}


function perseo_feedback_settings_init() {
    register_setting('perseo', 'perseo_options');
    
    add_settings_section(
        'perseo_settings_section',
        'Perseo Feedback Settings',
        null,
        'perseo'
    );

    add_settings_field(
        'perseo_settings_field_position',
        'Feedback Position',
        'perseo_feedback_settings_field_position_cb',
        'perseo',
        'perseo_settings_section'
    );

    add_settings_field(
        'perseo_settings_field_text',
        'Feedback Text',
        'perseo_feedback_settings_field_text_cb',
        'perseo',
        'perseo_settings_section'
    );

    add_settings_field(
        'perseo_settings_field_followup_text',
        'Follow-up Text',
        'perseo_feedback_settings_field_followup_text_cb',
        'perseo',
        'perseo_settings_section'
    );

    add_settings_field(
        'perseo_settings_field_yes',
        'Yes Button Text',
        'perseo_feedback_settings_field_yes_cb',
        'perseo',
        'perseo_settings_section'
    );

    add_settings_field(
        'perseo_settings_field_no',
        'No Button Text',
        'perseo_feedback_settings_field_no_cb',
        'perseo',
        'perseo_settings_section'
    );

    add_settings_field(
        'perseo_settings_field_thank_you_text',
        'Thank You Text',
        'perseo_feedback_settings_field_thank_you_text_cb',
        'perseo',
        'perseo_settings_section'
    );
}
add_action('admin_init', 'perseo_feedback_settings_init');

function perseo_feedback_settings_field_position_cb() {
    $options = get_option('perseo_options');
    ?>
    <select id="perseo_settings_field_position" name="perseo_options[position]">
        <option value="top" <?php selected($options['position'], 'top'); ?>>Top</option>
        <option value="bottom" <?php selected($options['position'], 'bottom'); ?>>Bottom</option>
    </select>
    <?php
}

function perseo_feedback_settings_field_text_cb() {
    $options = get_option('perseo_options');
    ?>
    <textarea id="perseo_settings_field_text" name="perseo_options[text]" rows="5" cols="30"><?php echo esc_textarea($options['text']); ?></textarea>
    <?php
}

function perseo_feedback_settings_field_followup_text_cb() {
    $options = get_option('perseo_options');
    ?>
    <textarea id="perseo_settings_field_followup_text" name="perseo_options[followup_text]" rows="4" cols="50"><?php echo esc_textarea($options['followup_text']); ?></textarea>
    <?php
}

function perseo_feedback_settings_field_yes_cb() {
    $options = get_option('perseo_options');
    ?>
    <input type="text" id="perseo_settings_field_yes" name="perseo_options[yes]" value="<?php echo esc_attr($options['yes']); ?>" />
    <?php
}

function perseo_feedback_settings_field_no_cb() {
    $options = get_option('perseo_options');
    ?>
    <input type="text" id="perseo_settings_field_no" name="perseo_options[no]" value="<?php echo esc_attr($options['no']); ?>" />
    <?php
}

function perseo_feedback_settings_field_thank_you_text_cb() {
    $options = get_option('perseo_options');
    ?>
    <textarea id="perseo_settings_field_thank_you_text" name="perseo_options[thank_you_text]" rows="4" cols="50"><?php echo esc_textarea($options['thank_you_text']); ?></textarea>
    <?php
}




function perseo_feedback_statistics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perseo_feedback';

    // Get total feedback count
    $total_feedback_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Get feedback count for the last 7 days
    $last_7_days_feedback_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE time > NOW() - INTERVAL 7 DAY");

    // Get Yes and No feedback counts
    $yes_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback = 'yes'");
    $no_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback = 'no'");

    // Get desktop and mobile counts
    $desktop_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE device = 'desktop'");
    $mobile_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE device = 'mobile'");

    // Get latest 5 feedbacks
    $latest_feedbacks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 5");



    // Get top 5 pages with best feedback
    $top_pages = $wpdb->get_results("
    SELECT url, COUNT(*) as count
    FROM $table_name
    WHERE feedback = 'yes'
    GROUP BY url
    ORDER BY count DESC
    LIMIT 10
    ");

    // Get top 5 pages with worst feedback
    $worst_pages = $wpdb->get_results("
    SELECT url, COUNT(*) as count
    FROM $table_name
    WHERE feedback = 'no'
    GROUP BY url
    ORDER BY count DESC
    LIMIT 10
    ");

    // Include Google Charts
    echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div id="charts">
            <div id="piechart_feedback"></div>
            <div id="piechart_device"></div>
        </div>
        <div class="statistics">
            <?php
            echo "<div><h2>Total feedbacks:</h2> " . $total_feedback_count . "</div>";
            echo "<div><h2>Feedbacks in the last 7 days:</h2> " . $last_7_days_feedback_count . "</div>";
            ?>
        </div>
        <div id="tables">
            <div id="table-best">
                <h2>Top 10 pages with good feedback</h2>
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_pages as $page) : ?>
                            <tr>
                                <td><?php echo esc_html($page->url); ?></td>
                                <td><?php echo esc_html($page->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="table-worst">
                <h2>Top 10 pages with bad feedback</h2>
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worst_pages as $page) : ?>
                            <tr>
                                <td><?php echo esc_html($page->url); ?></td>
                                <td><?php echo esc_html($page->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="latest-feedbacks">
            <h2>Latest 5 Feedbacks</h2>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>URL</th>
                        <th>Feedback</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_feedbacks as $feedback) : ?>
                        <tr>
                            <td><?php echo esc_html($feedback->time); ?></td>
                            <td><?php echo esc_html($feedback->url); ?></td>
                            <td><?php echo esc_html($feedback->feedback); ?></td>
                            <td><?php echo esc_html($feedback->comment); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart', 'table']});
            google.charts.setOnLoadCallback(drawCharts);

            function drawCharts() {
                // Draw Yes/No feedback pie chart
                var data1 = google.visualization.arrayToDataTable([
                    ['Feedback', 'Count'],
                    ['Yes', <?php echo $yes_count; ?>],
                    ['No', <?php echo $no_count; ?>]
                ]);
                var options1 = {
                    title: 'Feedback Yes/No'
                };
                var chart1 = new google.visualization.PieChart(document.getElementById('piechart_feedback'));
                chart1.draw(data1, options1);

                // Draw Desktop/Mobile pie chart
                var data2 = google.visualization.arrayToDataTable([
                    ['Device', 'Count'],
                    ['Desktop', <?php echo $desktop_count; ?>],
                    ['Mobile', <?php echo $mobile_count; ?>]
                ]);
                var options2 = {
                    title: 'Desktop vs Mobile'
                };
                var chart2 = new google.visualization.PieChart(document.getElementById('piechart_device'));
                chart2.draw(data2, options2);

            }
        </script>
    </div>
    <?php
}




