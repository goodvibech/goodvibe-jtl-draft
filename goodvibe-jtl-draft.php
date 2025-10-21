<?php
/**
 * Plugin Name: JTL Keep Products As Draft (Block Delete/Trash)
 * Description: If JTL Connector tries to delete/trash a product, keep it as draft instead.
 * Author: Your Team
 * Version: 1.1.0
 */

// Quick, cheap check to detect a JTL Connector call by URL.
function jtl_is_connector_request(): bool {
    // Matches /index.php/jtlconnector/?jtlauth=...
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return (stripos($uri, '/jtlconnector/') !== false) && isset($_GET['jtlauth']);
}

/**
 * Core handler: when JTL attempts to trash/delete a product, set status to draft and abort the delete.
 * Runs for both pre-trash and pre-delete flows.
 */
function jtl_keep_product_as_draft($delete, $post, $force_delete = false) {
    // Only act on JTL calls
    if ( ! jtl_is_connector_request() ) {
        return $delete; // do nothing for normal usage
    }

    // Normalize $post to WP_Post
    $post = get_post($post);
    if ( ! $post ) {
        return $delete;
    }

    // Only for Woo products (incl. variations)
    $allowed_types = array('product', 'product_variation');
    if ( ! in_array($post->post_type, $allowed_types, true) ) {
        return $delete;
    }

    // If already draft, just block the delete/trash
    if ($post->post_status !== 'draft') {
        // Update post_status to draft without touching modified dates more than needed
        wp_update_post(array(
            'ID'          => $post->ID,
            'post_status' => 'draft',
        ));
    }

    // Optional: remove from catalog/search visibility as extra safety
    if (function_exists('wc_get_product')) {
        $wc_product = wc_get_product($post->ID);
        if ($wc_product) {
            $wc_product->set_catalog_visibility('hidden');
            $wc_product->save();
        }
    }

    // Log for auditing
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        error_log(sprintf(
            'JTL keep-draft: prevented delete/trash for post ID %d from IP %s (URI=%s)',
            $post->ID,
            $ip,
            $uri
        ));
    }

    // Return true here would allow delete, so we must block:
    // For pre_trash_post: returning a non-null value short-circuits. Return true would force TRASH.
    // For pre_delete_post: returning a non-null value short-circuits. Return true would force DELETE.
    // Therefore we return **false** to block both.
    return false;
}

// Hook both "pre" filters with high priority so we run after most plugins.
add_filter('pre_trash_post',  'jtl_keep_product_as_draft', 999, 2);
add_filter('pre_delete_post', 'jtl_keep_product_as_draft', 999, 3);
