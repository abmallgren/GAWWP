<?php
/**
 * Plugin Name: Gmail Email Approval
 * Description: Provides email approval workflow functionality for Gmail.
 * Version: 1.0
 * Author: Anthony Brian Mallgren
 * License: GPLv2
 */

namespace GmailEmailApproval;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'includes/core.php';
require_once plugin_dir_path(__FILE__) . 'public/client.php';

function my_plugin_install() {
    global $wpdb;

    $table = $wpdb->prefix . 'email_approvals';

    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `from` VARCHAR(255) NOT NULL,
        `to` VARCHAR(255) NOT NULL,
        `subject` VARCHAR(1000) NOT NULL,
        content TEXT NOT NULL,
        history TEXT,
        signature TEXT,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        feedback TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}


register_activation_hook(__FILE__, function () {
    if (!get_page_by_path('email-approval')) {
        wp_insert_post([
            'post_title'   => 'Email Approval',
            'post_name'    => 'email-approval',
            'post_status'  => 'private',
            'post_type'    => 'page',
            'post_content' => '[google_login_button]' // shortcode placeholder
        ]);
    }
});

register_activation_hook(__FILE__, 'install');

?>