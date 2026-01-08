<?php

namespace GmailEmailApproval;

if ( ! defined( 'ABSPATH' ) ) exit;

function verify_google_id_token() {
    if (isset($_COOKIE['google_id_token'])) {
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . 
         sanitize_text_field(wp_unslash($_COOKIE['google_id_token']));

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Validate audience
        if (!isset($data['aud']) || $data['aud'] !== get_option('google_client_id')) {
            return false;
        }

        // Validate issuer
        if (!in_array($data['iss'], ['accounts.google.com', 'https://accounts.google.com'])) {
            return false;
        }

        // Validate expiration
        if ($data['exp'] < time()) {
            return false;
        }

        return $data; // Token is valid, return decoded claims
    }
}

?>