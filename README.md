# JTL Keep Products As Draft (Block Delete/Trash)

A WordPress/WooCommerce plugin to prevent JTL Connector from deleting or trashing products. Instead, the plugin changes the product status to "draft," preserving product data and catalog integrity.

## How It Works

- When the JTL Connector attempts to delete or trash a WooCommerce product (or product variation), this plugin intercepts the request and changes the product’s status to "draft" rather than allowing the actual deletion.
- Only requests that include the expected JTL Connector identifiers are affected. Regular WordPress or WooCommerce operations are not blocked.

## Key Features

- **Selective Protection:** Acts only on requests coming from JTL Connector, identified by a secure check in the request URL and authentication parameters.
- **Product Type Restriction:** Only WooCommerce `product` and `product_variation` types are protected.
- **Non-Intrusive:** Existing products already in "draft" status are ignored, avoiding unnecessary database operations.
- **Security Best Practices:** Sanitizes input, avoids leaking sensitive information, and prevents direct file access.
- **Catalog Visibility Unchanged:** Product catalog visibility is not modified, only the post status.

### Installation Instructions (MU-plugin)
To install this plugin as a must-use plugin (MU-plugin) in WordPress, place the plugin file directly in the `/wp-content/mu-plugins/` directory. This ensures it's always active and cannot be accidentally deactivated through the admin panel.

- Copy the plugin PHP file into the `/wp-content/mu-plugins/` folder of your WordPress installation.
- If the `mu-plugins` directory does not exist, create it manually.
- No manual activation is required; WordPress loads all MU-plugins automatically during startup.

This approach is best for critical plugins like this, ensuring persistent protection of your WooCommerce products.

## Usage

Simply activate the plugin in WordPress. No further configuration is required.

- Requests by JTL Connector aiming to delete or trash WooCommerce products will result in the product being set to "draft" status.
- All other requests (non-JTL, non-product) are processed through default WordPress logic.

## Technical Summary

- **Hook Integration:** The plugin hooks into `pre_trash_post` and `pre_delete_post` with high priority to override default behavior.
- **Request Filtering:** Function `jtl_is_connector_request` checks whether the operation originates from JTL Connector.
- **State Handling:** If the product status is not "draft", it is changed using `wp_update_post`. WooCommerce product states are saved with `wc_get_product` and its `save` method.
- **Direct File Access Blocked:** The script cannot be executed directly outside WordPress.

## License

Released under the [MIT License](LICENSE), which allows anyone to copy, fork, or modify the code, provided the original author attribution is retained.

---

## Changelog

### 1.2.0 (2025-11-17)
- Refactored code for improved performance and security: stricter type checks, reduced double calls, direct file access blocking, and best-practice sanitization.
- Completely removed all logging and debug output for production-grade privacy and compliance.
- Updated documentation and plugin headers.

### 1.1.x and earlier
- Initial public versions.
- Prevented JTL Connector from deleting/trashing WooCommerce products by setting their status to draft.
- Basic protection logic for WooCommerce product and product variation post types.

---

For more information about plugin security and hooks, visit the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).

Feel free to fork or contribute—just make sure to respect the authoring information in accordance with the license.
