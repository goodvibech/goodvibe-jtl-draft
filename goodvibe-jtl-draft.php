<?php
/**
 * Plugin Name: goodvibe JTL Keep Products as Draft (no delete)
 * Description: When JTL Connector calls /jtlconnector/?jtlauth=..., prevent product deletions/trash and set status to 'draft' instead.
 * Author: goodvibe GmbH
 */

/**
 * Detect if current request is coming from the JTL Connector endpoint.
 * We match the known entrypoint and auth pattern: /jtlconnector/?jtlauth=...
 * Optionally restrict by source IPs you trust for the connector.
 */
function jtl_is_connector_request(): bool {
    // 1) URL fingerprint
    $uri  = $_SERVER['REQUEST_URI']  ?? '';
    $qstr = $_SERVER['QUERY_STRING'] ?? '';
    $has_endpoint = (stripos($uri, '/jtlconnector/') !== false);
    $has_token    = (isset($_GET['jtlauth']) || stripos($qstr, 'jtlauth=') !== false);

    if (!($has_endpoint && $has_token)) {
        return false;
    }

    // 2) Optional - lock to known connector IPs (add more if needed)
    $allowed_ips = [
        '212.133.108.36',
        // 'x.x.x.x',
    ];
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($allowed_ips) && $remote_ip && !in_array($remote_ip, $allowed_ips, true)) {
        return false;
    }

    return true;
}

/** Flip to draft and stop deletion/trash. */
function jtl_convert_delete_to_draft($post): void {
    if (!$post || !in_array($post->post_type, ['product','product_variation'], true)) {
        return;
    }
    if (get_post_status($post->ID) !== 'draft') {
        // Remove hooks here if you have 3rd-party listeners that react to status changes to avoid loops.
        wp_update_post(['ID' => $post->ID, 'post_status' => 'draft']);
    }
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('JTL keep-draft: prevented delete/trash for post ID '.$post->ID.' (URI='.$_SERVER['REQUEST_URI'].')');
    }
}

/** Hard delete interceptor */
add_filter('pre_delete_post', function ($delete, $post, $force) {
    if (!jtl_is_connector_request()) return $delete;
    if ($post && in_array($post->post_type, ['product','product_variation'], true)) {
        jtl_convert_delete_to_draft($post);
        return false; // cancel deletion
    }
    return $delete;
}, 10, 3);

/** Move-to-trash interceptor */
add_filter('pre_trash_post', function ($trash, $post) {
    if (!jtl_is_connector_request()) return $trash;
    if ($post && in_array($post->post_type, ['product','product_variation'], true)) {
        jtl_convert_delete_to_draft($post);
        return false; // cancel trash
    }
    return $trash;
}, 10, 2);

/**
 * Extra safety belt:
 * If a plugin bypasses filters and reaches 'before_delete_post', kill the flow gracefully.
 * This is conservative and only triggers on JTL connector requests.
 */
add_action('before_delete_post', function ($post_id) {
    if (!jtl_is_connector_request()) return;
    $post = get_post($post_id);
    if ($post && in_array($post->post_type, ['product','product_variation'], true)) {
        jtl_convert_delete_to_draft($post);
        // Stop execution quietly with a 200 so the connector doesn't treat it as a fatal error.
        wp_die('', '', ['response' => 200]);
    }
}, 0);
