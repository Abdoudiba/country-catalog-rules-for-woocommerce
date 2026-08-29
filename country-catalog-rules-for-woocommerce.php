<?php
/**
 * Plugin Name:       Country Catalog Rules for WooCommerce
 * Plugin URI:         https://github.com/Abdoudiba/country-catalog-rules-for-woocommerce
 * Description:        Restrict product and category visibility by customer country, reusing WooCommerce's built-in MaxMind GeoIP detection instead of a separate geolocation service.
 * Version:            0.3.0
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * Requires Plugins:   woocommerce
 * WC requires at least: 8.0
 * Author:             Abid
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        country-catalog-rules-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'CCRW_VERSION', '0.3.0' );
define( 'CCRW_PLUGIN_FILE', __FILE__ );
define( 'CCRW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCRW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bail early and show an admin notice if WooCommerce isn't active, rather than
 * fatal-erroring on missing WC classes.
 */
function ccrw_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Country Catalog Rules for WooCommerce requires WooCommerce to be installed and active.', 'country-catalog-rules-for-woocommerce' ) .
		'</p></div>';
}

function ccrw_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ccrw_woocommerce_missing_notice' );
		return;
	}

	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-geolocation.php';
	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-rules.php';
	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-product-fields.php';
	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-category-fields.php';
	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-visibility.php';
	require_once CCRW_PLUGIN_DIR . 'includes/class-ccrw-settings.php';

	CCRW_Product_Fields::init();
	CCRW_Category_Fields::init();
	CCRW_Visibility::init();
	CCRW_Settings::init();
}
add_action( 'plugins_loaded', 'ccrw_init' );

/**
 * Declare HPOS (High-Performance Order Storage) compatibility. This plugin
 * doesn't touch orders at all, but declaring compatibility explicitly avoids
 * WooCommerce's "incompatible plugin" admin warning.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
