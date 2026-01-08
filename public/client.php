<?php

namespace GmailEmailApproval;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'gmail.php';
require_once plugin_dir_path(__FILE__) . '../includes/auth.php';
require_once plugin_dir_path(__FILE__) . '../includes/email.php';

add_action('wp_ajax_get_gmail_messages', 'ajax_get_gmail_messages');
add_action('wp_ajax_nopriv_get_gmail_messages', 'ajax_get_gmail_messages');
add_action('wp_ajax_submit_email_for_approval', 'ajax_submit_email_for_approval');
add_action('wp_ajax_nopriv_submit_email_for_approval', 'ajax_submit_email_for_approval');
add_action('wp_ajax_approve_email', 'ajax_approve_email');
add_action('wp_ajax_nopriv_approve_email', 'ajax_approve_email');
add_action('wp_ajax_submit_email_feedback', 'ajax_submit_email_feedback');
add_action('wp_ajax_nopriv_submit_email_feedback', 'ajax_submit_email_feedback');
add_action('wp_ajax_send_email', 'ajax_send_email');
add_action('wp_ajax_nopriv_send_email', 'ajax_send_email');

function ajax_send_email() {
    if (
        ! isset( $_POST['send_email_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['send_email_nonce']) ), 'send_email' )
    ) {
        wp_die( 'Security check failed' );
    }
    $id = verify_google_id_token();
    if ($id != false) {
        global $wpdb;

        $data = json_decode(file_get_contents('php://input'), true);

        $emailId  = intval($data['emailId'] ?? '');

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $emailId
            )
        );

        send_email($row->to, 
            $row->subject, 
            "<html>" .
                    "<body>" . 
                        "{$row->content}<br/>{$row->signature}<br/>{$row->history}" .
                    "</body>" .
                "</html>", 
        $cc = get_option('approver_email'));

        $table = $wpdb->prefix . 'email_approvals';
        
        $wpdb->update($table, [
            'status' => 'sent'
        ], [
            'id' => $emailId
        ]);

        wp_send_json_success("Email sent");
    }
}

function ajax_submit_email_feedback() {
    if (
        ! isset( $_POST['submit_email_feedback_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['submit_email_feedback_nonce']) ), 'submit_email_feedback' )
    ) {
        wp_die( 'Security check failed' );
    }
    $id = verify_google_id_token();
    if ($id != false) {
        global $wpdb;

        $data = json_decode(file_get_contents('php://input'), true);

        $emailId  = intval($data['emailId'] ?? '');
        $feedback = wp_kses_post($data['feedback'] ?? '');

        $table = $wpdb->prefix . 'email_approvals';

        $wpdb->update($table, [
            'status' => 'rejected',
            'feedback' => $feedback
        ], [
            'id' => $emailId
        ]);

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $emailId
            )
        );

        $url = home_url('/email-approval?email_draft_id=' . $row->id);

        send_email($row->from, 
            "Feedback: " . $row->subject, 
            "<html>" . 
                    "<body>" . 
                        "<div>Feedback has been submitted for the following email:</div><br/>" .
                        "<div>Subject: {$row->subject}</div><br/>" . 
                        "<div>Feedback: {$row->feedback}</div><br/>" . 
                        "<div>Please visit: {$url} to revise this email.</div>" . 
                    "</body>" . 
                "</html>");

        wp_send_json_success("Feedback submitted");
    }
}

function ajax_approve_email() {
    if (
        ! isset( $_POST['approve_email_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['approve_email_nonce']) ), 'approve_email' )
    ) {
        wp_die( 'Security check failed' );
    }
    $id = verify_google_id_token();
    if ($id != false && $id['email'] === get_option('approver_email')) {
        global $wpdb;

        $data = json_decode(file_get_contents('php://input'), true);

        $emailId  = intval($data['emailId'] ?? '');

        $table = $wpdb->prefix . 'email_approvals';

        $wpdb->update($table, [
            'status' => 'approved'
        ], [
            'id' => $emailId
        ]);

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $emailId
            )
        );

        $url = home_url('/email-approval?send_email_id=' . $emailId);

        send_email($row->from, 
            "Approved: " . $row->subject, 
            "<html>" . 
                    "<body>" .
                        "<div>An email was approved.</div><br/>" . 
                        "<div>Subject: {$row->subject}</div><br/>" .
                        "<div>Please visit: {$url} to send the email.</div>" . 
                    "</body>" .
                "</html>");

        wp_send_json_success("Email approved");
    }
}
function ajax_submit_email_for_approval() {
    if (
        ! isset( $_POST['submit_email_for_approval_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['submit_email_for_approval_nonce']) ), 'submit_email_for_approval' )
    ) {
        wp_die( 'Security check failed' );
    }
    $id = verify_google_id_token();
    if ($id != false) {
        global $wpdb;

        $data = json_decode(file_get_contents('php://input'), true);

        $to       = sanitize_email($data['to'] ?? '');
        $subject  = sanitize_text_field($data['subject'] ?? '');
        $content  = wp_kses_post($data['content'] ?? '');
        $history  = wp_kses_post($data['history'] ?? '');
        $signature  = wp_kses_post($data['signature'] ?? '');

        $table = $wpdb->prefix . 'email_approvals';

        $wpdb->insert($table, [
            'from' => $id['email'],
            'to' => $to,
            'subject' => $subject,
            'content' => $content,
            'history' => $history,
            'signature' => $signature,
            'status' => 'pending'
        ]);

        $url = home_url('/email-approval?email_id=' . $wpdb->insert_id);

        send_email(get_option('approver_email'), 
            "Approval needed: " . $subject, 
            "<html>" .
                    "<body>" . 
                        "<div>An email needs your approval.</div><br/>" . 
                        "<div>Subject: {$subject}</div><br/>" .
                        "<div>Please visit: {$url} to approve or provide feedback for the email.</div>" . 
                    "</body>" . 
                "</html>");

        wp_send_json_success("Email submitted for approval");
    }
}
function ajax_get_gmail_messages() {
    if (
        ! isset( $_POST['get_gmail_messages_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['get_gmail_messages_nonce']) ), 'get_gmail_messages' )
    ) {
        wp_die( 'Security check failed' );
    }

    if (isset($_COOKIE['google_access_token']) && isset($_GET['pageToken'])) {
        $token = sanitize_text_field(wp_unslash($_COOKIE['google_access_token'])) ?? null;
        $pageToken = intval(wp_unslash($_GET['pageToken'])) ?? null;

        if (!$token) {
            wp_send_json_error("Not authenticated");
        }

        $result = gmail_get_messages($token, $pageToken);

        wp_send_json_success($result);
    }
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'google-oauth',
        'https://accounts.google.com/gsi/client',
        [],
        null,
        true
    );
});

add_action('init', function () {
    if (isset($_GET['code']) && !isset($_COOKIE['google_access_token'])) {
        handle_google_callback();
    }
});

function handle_google_callback() {
    if (!isset($_GET['code'])) {
        echo "Missing authorization code";
        return;
    }

    $code = sanitize_text_field(wp_unslash($_GET['code']));

    $token_url = "https://oauth2.googleapis.com/token";

    $response = wp_remote_post($token_url, [
        'body' => [
            'code' => $code,
            'client_id' => get_option('google_client_id'),
            'client_secret' => get_option('google_client_secret'),
            'redirect_uri' => home_url('/email-approval?action=oauth_callback'),
            'grant_type' => 'authorization_code'
        ]
    ]);

    if (is_wp_error($response)) {
        echo "Token request failed";
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Access token, refresh token, id token
    $access_token  = $body['access_token'] ?? null;
    $refresh_token = $body['refresh_token'] ?? null;
    $id_token      = $body['id_token'] ?? null;

    // Store tokens however you want
    setcookie("google_access_token", $access_token, time()+3600, "/");
    setcookie("google_id_token", $id_token, time()+3600, "/");

    // Now notify the opener window
    callback_success_page($access_token);
}

add_action('init', function () {
    wp_register_script(
        'gmail-script',
        plugin_dir_url(__FILE__) . '../assets/gmail.js',
        [],
        null,
        true // load in footer
    );
    wp_register_script(
        'workflow-script',
        plugin_dir_url(__FILE__) . '../assets/workflow.js',
        [],
        null,
        true // load in footer
    );
});


add_shortcode('google_login_button', function () {
    $auth_url = add_query_arg([
        'client_id'     => get_option('google_client_id'),
        'redirect_uri'  => home_url('/email-approval?action=oauth_callback'),
        'response_type' => 'code',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
        'scope'         => implode(' ', [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/gmail.settings.basic'
        ]),
    ], 'https://accounts.google.com/o/oauth2/v2/auth');

    wp_enqueue_script('gmail-script');

    wp_localize_script( 'gmail-script', 'GmailEmailApproval', [
        'get_gmail_messages_nonce' => wp_create_nonce( 'get_gmail_messages' ),
        'submit_email_for_approval_nonce' => wp_create_nonce( 'submit_email_for_approval' ),
        'approve_email_nonce' => wp_create_nonce( 'approve_email' ),
        'submit_email_feedback_nonce' => wp_create_nonce( 'submit_email_feedback' ),
        'send_email_nonce' => wp_create_nonce( 'send_email' ),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
    ] );

    if (!isset($_COOKIE['google_access_token'])) {
        if (isset($_GET['email_id'])) {
            setcookie("email_approval_email_id", $_GET['email_id'], time()+3600, "/");
        }
        if (isset($_GET["email_draft_id"])) {  
            setcookie("email_approval_email_draft_id", $_GET['email_draft_id'], time()+3600, "/");
        }
        if (isset($_GET["send_email_id"])) {  
            setcookie("email_approval_send_email_id", $_GET['send_email_id'], time()+3600, "/");
        }
        
        ob_start();
        ?>

        <a href="<?php echo esc_url($auth_url); ?>" class="google-btn">
            <img src="<?php echo esc_html(plugin_dir_url(dirname(__FILE__, 1)) . 'assets/g-logo.png'); ?>" alt="Google logo">
            <span>Sign in with Google</span>
        </a>

        <?php
        return ob_get_clean();
    }

    else if (isset($_GET['email_id']) || isset($_COOKIE['email_approval_email_id'])) {
        $emailId = isset($_GET['email_id']) ? intval($_GET['email_id']) : intval($_COOKIE['email_approval_email_id']);
        setcookie("email_approval_email_id", "", time() - 3600, "/");
        unset($_COOKIE["email_approval_email_id"]);
        ob_start();

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $emailId
            )
        );

        ?>

        <div>
            <input type="hidden" id="emailApprovalId" value="<?php echo intval($emailId); ?>" />
            <div>
                <button type="button" id="topAppoveButton">Approve</button>
                <button type="button" id="topRejectButton">Reject</button>
            </div>
            <div style="display:none;" id="feedbackDiv">
                <label for="feedbackTextarea">Feedback:</label><br/>
                <textarea id="feedbackTextarea"></textarea>
                <button type="button" id="submitFeedbackButton">Submit Feedback</button>
            </div>
            <div>
                <div style="margin-top: 10px;">To: <span id="toEmailSpan"><?php echo esc_html($row->to); ?></span></div>
                <div>Subject: <span id="subjectSpan"><?php echo esc_html($row->subject); ?></span></div>
                <div style="margin: 10px 0px" id="contentDiv"><?php echo wp_kses_post($row->content); ?></div>
                <div style="margin: 10px 0px" id="signatureDiv"><?php echo wp_kses_post($row->signature); ?></div>
                <div id="historyDiv"><?php echo wp_kses_post($row->history); ?></div>
            </div>
        </div>

        <?php
        wp_enqueue_script('workflow-script');
        return ob_get_clean();
    }

    else if (isset($_GET['email_draft_id']) || isset($_COOKIE['email_approval_email_draft_id'])) {
        $emailDraftId = isset($_GET['email_draft_id']) ? intval($_GET['email_draft_id']) : intval($_COOKIE['email_approval_email_draft_id']);
        setcookie("email_approval_email_draft_id", "", time() - 3600, "/");
        unset($_COOKIE["email_approval_email_draft_id"]);
        ob_start();

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $emailDraftId
            )
        );

        ?>

        <div>
            <div>To: <input type="email" id="toEmail" value="<?php echo esc_html($row->to); ?>" /></div>
            <div>Subject: <input type="text" id="emailSubjectTextbox" value="<?php echo esc_html($row->subject); ?>" /></div>
            <div><textarea id="emailContentTextarea"><?php echo wp_kses_post($row->content); ?></textarea></div>
            <div id="signatureDiv"></div>
            <button type="button" id="sendForApprovalButton">Send for Approval</button>
            <div id="emailHistoryDiv">
                <?php echo wp_kses_post($row->history); ?>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }

    else if (isset($_GET['send_email_id']) || isset($_COOKIE['email_approval_send_email_id'])) {
        $sendEmailId = isset($_GET['send_email_id']) ? intval($_GET['send_email_id']) : intval($_COOKIE['email_approval_send_email_id']);
        setcookie("email_approval_email_draft_id", "", time() - 3600, "/");
        unset($_COOKIE["email_approval_email_draft_id"]);
        ob_start();

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}email_approvals WHERE id = %d",
                $sendEmailId
            )
        );

        ?>

        <div>
            <input type="hidden" id="sendEmailId" value="<?php echo intval($sendEmailId); ?>" />
            <div>To: <span id="toEmailSpan"><?php echo esc_html($row->to); ?></span></div>
            <div>Subject: <span id="subjectSpan"><?php echo esc_html($row->subject); ?></span></div>
            <div style="margin: 10px 0px" id="contentDiv"><?php echo wp_kses_post($row->content); ?></div>
            <div id="signatureDiv"><?php echo wp_kses_post($row->signature); ?></div>
            <button type="button" id="sendEmailButton">Send Email</button>
            <div id="emailHistoryDiv">
                <?php echo wp_kses_post($row->history); ?>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }
    else {
        ob_start();

        ?>
        <div>
            <div>
                <table id="emailGrid">
                    <thead>
                        <tr><th>From</th><th>Subject</th><th>Date</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <button id="loadMoreBtn">Load more</button>
            </div>
            <div>To: <input type="email" id="toEmail" /></div>
            <div>Subject: <input type="text" id="emailSubjectTextbox" /></div>
            <div><textarea id="emailContentTextarea"></textarea></div>
            <div id="signatureDiv"></div>
            <button type="button" id="sendForApprovalButton">Send for Approval</button>
            <div id="emailHistoryDiv">

            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
});

?>