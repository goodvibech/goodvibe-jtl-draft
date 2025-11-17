# JTL Keep Products As Draft - Block Delete/Trash

**Version:** 1.2.0  
**Author:** goodvibe GmbH  
**License:** MIT

## Description

A WordPress/WooCommerce plugin to prevent JTL Connector from deleting or trashing products. Instead, the plugin changes the product status to draft, preserving product data and catalog integrity.

## How It Works

- **Selective Protection:** Acts only on requests coming from JTL Connector, identified by a secure check in the request URL and authentication parameters.
- **Product Type Restriction:** Only WooCommerce `product` and `product_variation` types are protected.
- **Non-Intrusive:** Existing products already in draft status are ignored, avoiding unnecessary database operations.
- **Security Best Practices:** Sanitizes input, avoids leaking sensitive information, and prevents direct file access. Debug logging is protected and only enabled under `WP_DEBUG`.
- **Catalog Visibility Unchanged:** Product catalog visibility is not modified, only the post status.

## Installation Instructions (MU-plugin)

To install this plugin as a must-use plugin (MU-plugin) in WordPress:

1. Copy the plugin PHP file into the `wp-content/mu-plugins` folder.
2. If the `mu-plugins` directory does not exist, create it manually.
3. No manual activation required – WordPress loads all MU-plugins automatically during startup.

## Usage

- Requests by JTL Connector aiming to delete or trash WooCommerce products will result in the product being set to `draft` status.
- All other requests (non-JTL, non-product) are processed through default WordPress logic.

## Technical Summary

- **Hook Integration:** The plugin hooks into `pre_trash_post` and `pre_delete_post` with high priority.
- **Request Filtering:** Function `jtl_is_connector_request()` checks the origin of the operation.
- **State Handling:** If the product status is not `draft`, it is changed using `wp_update_post`. WooCommerce product states are saved with `wc_get_product` and its `save` method.
- **Debug Logging:** Logs the prevention of delete/trash actions only during debugging, and does not expose personal data.
- **Direct File Access Blocked:** The script cannot be executed directly outside WordPress.

## License

Released under the MIT License. For more information about plugin security and hooks, visit the WordPress Plugin Developer Handbook.

Feel free to fork or contribute, just make sure to respect the authoring information in accordance with the license.
