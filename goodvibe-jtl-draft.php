<?php
/**
 * Plugin Name: JTL Keep Products As Draft - Block Delete/Trash
 * Description: Prevents JTL Connector from deleting or trashing WooCommerce products, changes product status to draft instead.
 * Version: 1.2.0
 * Author: goodvibe GmbH
 * License: MIT
 */

// Block direct file access (common WP best practice)
if (!defined('WPINC')) {
    exit;
}

/**
 * Checks if the current request is coming from JTL Connector.
 * @return bool
 */
function jtl_is_connector_request(): bool {
    if (
        empty($_SERVER['REQUEST_URI']) ||
        empty($_GET['jtlauth'])
    ) {
        return false;
    }
    // Constant-time check for 'jtlconnector' in URI
    return stripos((string)$_SERVER['REQUEST_URI'], 'jtlconnector') !== false;
}

/**
 * Intercept WooCommerce product delete/trash actions and set products as draft.
 * @param mixed $delete Value to return to WP hooks (usually false)
 * @param WP_Post|int $post Post object or ID
 * @param bool $force_delete Whether to force deletion
 * @return mixed
 */
function jtl_keep_product_as_draft($delete, $post, $force_delete = false) {
    // Only act for JTL Connector requests
    if (!jtl_is_connector_request()) {
        return $delete;
    }

    $post_obj = is_object($post) ? $post : get_post((int)$post);
    if (empty($post_obj) || !is_a($post_obj, 'WP_Post')) {
        return $delete;
    }

    // Only handle WooCommerce products and product variations
    $allowed_types = array('product', 'product_variation');
    if (!in_array($post_obj->post_type, $allowed_types, true)) {
        return $delete;
    }

    // Only act if post is not already draft
    if ($post_obj->post_status !== 'draft') {
        wp_update_post(array(
            'ID' => (int)$post_obj->ID,
            'post_status' => 'draft',
        ));

        // Save WooCommerce product state if exists
        if (function_exists('wc_get_product')) {
            $wc_product = wc_get_product((int)$post_obj->ID);
            if ($wc_product && is_object($wc_product)) {
                $wc_product->save();
            }
        }
    }
    // Always block the actual delete/trash
    return false;
}

add_filter('pre_trash_post', 'jtl_keep_product_as_draft', 999, 2);
add_filter('pre_delete_post', 'jtl_keep_product_as_draft', 999, 3);
