<?php
/*
Plugin Name: Perseo Feedback System
Plugin URI: https://github.com/giovannimanetti11/perseo-feedback-system
Description: An open-source feedback system for your WordPress site.
Version: 0.1
Author: Giovanni Manetti
Author URI: https://github.com/giovannimanetti11
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

// Print feedback HTML
function perseo_feedback_html() {
    echo '<div id="perseo-feedback-widget">';
    echo 'Hai trovato utile questa pagina? <button id="perseo-feedback-yes">SI</button> <button id="perseo-feedback-no">NO</button>';
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
    
    // Localize the script with new data
    $script_data_array = array(
        'nonce' => wp_create_nonce('wp_rest'),  // Nonce for REST API
    );
    wp_localize_script('perseo-feedback-script', 'wpApiSettings', $script_data_array);
}
add_action('wp_enqueue_scripts', 'perseo_enqueue_scripts');



// On plugin activation, create feedback table
register_activation_hook(__FILE__, 'perseo_feedback_install');

function perseo_feedback_install() {
    global $wpdb;

    require_once('db-config.php');

    $table_name = $wpdb->prefix . 'perseo_feedback';

    $charset_collate = $wpdb->get_charset_collate();

    // Create the feedback table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        url varchar(255) DEFAULT '' NOT NULL,
        feedback varchar(3) DEFAULT '' NOT NULL,
        ip varchar(45) DEFAULT '' NOT NULL,
        device varchar(20) DEFAULT '' NOT NULL,
        user_agent varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL query to create the table
    dbDelta($sql);
}



function perseo_save_feedback() {
    global $wpdb;

    require_once('db-config.php');

    // Get the raw POST data
    $raw_data = file_get_contents('php://input');

    // Decode the JSON data into an array
    $data = json_decode($raw_data, true);

    $url = $data['url'];
    $feedback = $data['feedback'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = wp_is_mobile() ? 'mobile' : 'desktop';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $table_name = $wpdb->prefix . 'perseo_feedback';

    // Insert feedback data into the database
    $result = $wpdb->insert(
        $table_name,
        array(
            'time' => current_time('mysql'),
            'url' => $url,
            'feedback' => $feedback,
            'ip' => $ip,
            'device' => $device,
            'user_agent' => $user_agent
        )
    );

    if ($result === false) {
        // The insert failed. Return an error message with the last error occurred in $wpdb.
        wp_send_json_error($wpdb->last_error, 500);
    } else {
        // If everything went well, send a 200 status code and a success message
        wp_send_json_success("Feedback recorded successfully", 200);
    }
}



// Register the AJAX action for saving feedback
add_action('wp_ajax_perseo_save_feedback', 'perseo_save_feedback');
add_action('wp_ajax_nopriv_perseo_save_feedback', 'perseo_save_feedback');

