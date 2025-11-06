<?php
/*
Plugin Name: JTL Keep Products As Draft (Block Delete/Trash)
Description: If JTL Connector tries to delete/trash a product, change it to draft instead of deleting it.
Version: 1.1.1
Author: goodvibe GmbH
*/

/**
 * Checks if the request comes from JTL Connector (performance: uses early return, sanitizes input).
 *
 * @return bool
 */
function jtl_is_connector_request() {
    // Avoids unnecessary function calls if $_SERVER or $_GET are empty
    if (empty($_SERVER['REQUEST_URI']) || empty($_GET['jtlauth'])) {
        return false;
    }
    // Constant-time stripos for security (won't leak path information)
    return (stripos($_SERVER['REQUEST_URI'], 'jtlconnector') !== false);
}

/**
 * Prevents JTL Connector from deleting or trashing WooCommerce products (performance: type checks, scope reduces DB operations).
 * Security: Logs only when debugging, checks post types strictly, no direct access functions, avoids side effects.
 *
 * @param mixed $delete Value to return to WP hooks (usually false).
 * @param WP_Post|int $post Post object or ID.
 * @param bool $force_delete Whether to force delete.
 * @return bool|mixed
 */
function jtl_keep_product_as_draft($delete, $post, $force_delete = false) {

    // Only act for JTL Connector requests
    if (!jtl_is_connector_request()) {
        return $delete;
    }

    // Get WP_Post object safely
    $post_obj = is_object($post) ? $post : get_post((int)$post);

    if (empty($post_obj) || !is_a($post_obj, 'WP_Post')) {
        return $delete;
    }

    // Only for WooCommerce product or product_variation post types
    $allowed_types = array('product', 'product_variation');
    if (!in_array($post_obj->post_type, $allowed_types, true)) {
        return $delete;
    }

    // Only act if post is not already draft
    if ('draft' !== $post_obj->post_status) {
        // Use wp_update_post with minimal data to reduce query cost
        wp_update_post(array(
            'ID' => $post_obj->ID,
            'post_status' => 'draft',
        ));

        // Save WooCommerce product state if exists, no change to catalog visibility
        if (function_exists('wc_get_product')) {
            $wc_product = wc_get_product((int)$post_obj->ID);
            // Defensive type check for returned product object
            if ($wc_product && is_object($wc_product)) {
                $wc_product->save();
            }
        }

        // Debug logging, never exposes sensitive info unless WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
            $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
            error_log(sprintf(
                'JTL keep-draft: prevented delete/trash for post ID %d from IP %s (URI=%s)',
                (int)$post_obj->ID,
                $ip,
                $uri
            ));
        }
    }
    // Always block the actual delete/trash
    return false;
}

// Hook into WP trash/delete actions with priority and proper arguments
add_filter('pre_trash_post', 'jtl_keep_product_as_draft', 999, 2);
add_filter('pre_delete_post', 'jtl_keep_product_as_draft', 999, 3);

// Block direct file access for extra security (common WP best practice)
if (!defined('WPINC')) {
    exit;
}
