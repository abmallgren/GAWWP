<?php

namespace GmailEmailApproval;

if ( ! defined( 'ABSPATH' ) ) exit;

function gmail_header($headers, $name) {
    foreach ($headers as $header) {
        if (isset($header['name']) && strtolower($header['name']) === strtolower($name)) {
            return $header['value'];
        }
    }
    return '';
}

function gmail_extract_body($payload) {
    // Case 1: Simple email (no parts)
    if (!empty($payload['body']['data'])) {
        return base64_decode(strtr($payload['body']['data'], '-_', '+/'));
    }

    // Case 2: Multipart email
    if (!empty($payload['parts'])) {
        foreach ($payload['parts'] as $part) {
            // HTML body
            if (isset($part['mimeType']) && $part['mimeType'] === 'text/html') {
                return base64_decode(strtr($part['body']['data'], '-_', '+/'));
            }

            // Plain text fallback
            if (isset($part['mimeType']) && $part['mimeType'] === 'text/plain') {
                return base64_decode(strtr($part['body']['data'], '-_', '+/'));
            }

            // Nested parts (multipart/alternative)
            if (!empty($part['parts'])) {
                foreach ($part['parts'] as $subpart) {
                    if ($subpart['mimeType'] === 'text/html') {
                        return base64_decode(strtr($subpart['body']['data'], '-_', '+/'));
                    }
                    if ($subpart['mimeType'] === 'text/plain') {
                        return base64_decode(strtr($subpart['body']['data'], '-_', '+/'));
                    }
                }
            }
        }
    }

    return '';
}

function gmail_get_messages($accessToken, $pageToken = null) {
    $headers = [
        "Authorization" => "Bearer $accessToken",
        "Accept" => "application/json"
    ];

    $params = [
        "maxResults" => 10,
        "labelIds" => "INBOX"
    ];

    if ($pageToken) {
        $params["pageToken"] = $pageToken;
    }

    $listUrl = "https://gmail.googleapis.com/gmail/v1/users/me/messages?" . http_build_query($params);
    $listResponse = wp_remote_get($listUrl, ['headers' => $headers]);
    $listBody = json_decode(wp_remote_retrieve_body($listResponse), true);
    $messages = $listBody['messages'] ?? [];
    $nextPageToken = $listBody['nextPageToken'] ?? null;

    $results = [];

    foreach ($messages as $msg) {
        $id = $msg['id'];

        $msgUrl = "https://gmail.googleapis.com/gmail/v1/users/me/messages/$id?format=full";

        $msgResponse = wp_remote_get($msgUrl, ['headers' => $headers]);
        $msgBody = json_decode(wp_remote_retrieve_body($msgResponse), true);
        $payload = $msgBody['payload'];
        $headersList = $msgBody['payload']['headers'];

        $results[] = [
            'id' => $id,
            'from' => gmail_header($headersList, 'From'),
            'subject' => gmail_header($headersList, 'Subject'),
            'date' => gmail_header($headersList, 'Date'),
            'body'    => gmail_extract_body($payload)
        ];
    }

    return [
        "emails" => $results,
        "nextPageToken" => $nextPageToken
    ];
}

?>