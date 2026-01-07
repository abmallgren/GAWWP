<?php

add_filter('use_curl_transport', '__return_false');

add_action('admin_menu', function () {
    add_menu_page(
        'Email Approval Settings',
        'Email Approval',
        'manage_options',
        'email-approval-settings',
        'email_approval_settings_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_email-approval-settings') {
        return;
    }

    wp_enqueue_style(
        'email-approval-settings-admin-style',
        plugin_dir_url(__FILE__) . '../assets/admin.css'
    );
});

add_action('wp_enqueue_scripts', function ($hook) {
    wp_enqueue_style(
        'email-approval-public-style',
        plugin_dir_url(__FILE__) . '../assets/public.css'
    );
});



function email_approval_settings_page() {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approver_email'])) {
        check_admin_referer('email_approval_settings_save');
        update_option('google_client_id', sanitize_text_field($_POST['google_client_id']));
        update_option('google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        update_option('approver_email', sanitize_email($_POST['approver_email']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Email Approval Settings</h1>
        <form method="post">
            <?php wp_nonce_field('email_approval_settings_save'); ?>

            <table>
                <tr>
                    <td>Google Client ID:</td>
                    <td>
                        <input type="text" name="google_client_id" value="<?php echo esc_attr(get_option('google_client_id')); ?>" />
                    </td>
                </tr>
                <tr>
                    <td>Google Client Secret:</td>
                    <td><input type="password" name="google_client_secret" value="<?php echo esc_attr(get_option('google_client_secret')); ?>" /></td>
                </tr>
                <tr>
                    <td>Approver Email:</td>
                    <td><input type="email" name="approver_email" value="<?php echo esc_attr(get_option('approver_email')); ?>" /></td>
                </tr>
                <tr>
                    <td>
                        <button type="submit">Save Settings</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
}

?>