<?php
/*
Plugin Name: Perseo Feedback System
Plugin URI: https://your-website.com/
Description: An open-source feedback system for your WordPress site.
Version: 0.1
Author: Giovanni Manetti
Author URI: https://github.com/giovannimanetti11
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/


class Perseo_Feedback_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'perseo_feedback_widget', 
            __('Perseo Feedback Widget', 'perseo_feedback_domain'), 
            array( 'description' => __( 'A simple feedback widget', 'perseo_feedback_domain' ), ) 
        );
    }

    public function widget( $args, $instance ) {
        // Widget output
        echo '<div id="perseo-feedback-widget">';
        echo 'Did you find this page useful? <button>YES</button> <button>NO</button>';
        echo '</div>';
    }

    public function form( $instance ) {
        // Handle widget options here
    }

    public function update( $new_instance, $old_instance ) {
        // Save widget options here
    }
}

// Register Perseo_Feedback_Widget
function register_perseo_feedback_widget() {
    register_widget( 'Perseo_Feedback_Widget' );
}
add_action( 'widgets_init', 'register_perseo_feedback_widget' );



register_activation_hook(__FILE__, 'perseo_feedback_install');

function perseo_feedback_install() {
    global $wpdb;

    require_once('db_config.php');

    $table_name = $wpdb->prefix . 'perseo_feedback';

    $charset_collate = $wpdb->get_charset_collate();

    // Create the feedback table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        url varchar(255) DEFAULT '' NOT NULL,
        feedback varchar(1) DEFAULT '' NOT NULL,
        ip varchar(45) DEFAULT '' NOT NULL,
        device varchar(20) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL query to create the table
    dbDelta($sql);
}


function perseo_save_feedback() {
    global $wpdb;

    require_once('db_config.php');

    $table_name = $wpdb->prefix . 'perseo_feedback';

    $url = $_POST['url'];
    $feedback = $_POST['feedback'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = wp_is_mobile() ? 'mobile' : 'desktop';

    // Insert feedback data into the database
    $wpdb->insert(
        $table_name,
        array(
            'time' => current_time('mysql'),
            'url' => $url,
            'feedback' => $feedback,
            'ip' => $ip,
            'device' => $device
        )
    );

    wp_die();
}

// Register the AJAX action for saving feedback
add_action('wp_ajax_perseo_save_feedback', 'perseo_save_feedback');
add_action('wp_ajax_nopriv_perseo_save_feedback', 'perseo_save_feedback');
