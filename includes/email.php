<?php

namespace GmailEmailApproval;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'auth.php';

function send_email($to, $subject, $body, $cc = '') {
    $id = verify_google_id_token();
    if ($id != false && isset($_COOKIE['google_access_token'])) {
        $accessToken = sanitize_text_field(wp_unslash($_COOKIE['google_access_token']));

        $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages/send";

        $email = "To: {$to}\n" . 
            "Cc: {$cc}\n" .
            "Subject: {$subject}\n" .
            "Content-Type: text/html; charset=\"UTF-8\"\n" . 
            "MIME-Version: 1.0\n\n" . 
            "{$body}\n";

        $raw = base64_encode($email);
        $raw = str_replace(['+', '/', '='], ['-', '_', ''], $raw); // URL-safe base64

        $data = [
            'raw' => $raw
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }
}

function htmlToPlainText($html) {
    if (!$html) return "";

    // 1. Convert blockquotes back into > indentation
    $html = str_replace(
        ['<blockquote>', '</blockquote>'],
        ["> ", ""],
        $html
    );

    // 2. Convert <br> to single line breaks
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    // 3. Convert </p><p> into double line breaks
    $html = preg_replace('/<\/p>\s*<p>/i', "\n\n", $html);

    // 4. Remove outer <p> tags
    $html = preg_replace('/^<p>/i', '', $html);
    $html = preg_replace('/<\/p>$/i', '', $html);

    // 5. Remove any remaining <p> or </p>
    $html = preg_replace('/<\/?p>/i', '', $html);

    // 6. Decode HTML entities
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

    return trim($html);
}

?>