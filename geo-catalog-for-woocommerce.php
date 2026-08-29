<?php
/**
 * Plugin Name:       Geo Catalog for WooCommerce
 * Plugin URI:         https://github.com/Abdoudiba/geo-catalog-for-woocommerce
 * Description:        Restrict product and category visibility by customer country, reusing WooCommerce's built-in MaxMind GeoIP detection instead of a separate geolocation service.
 * Version:            0.2.1
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * Requires Plugins:   woocommerce
 * WC requires at least: 8.0
 * Author:             Abid
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        geo-catalog-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'WGC_VERSION', '0.2.1' );
define( 'WGC_PLUGIN_FILE', __FILE__ );
define( 'WGC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bail early and show an admin notice if WooCommerce isn't active, rather than
 * fatal-erroring on missing WC classes.
 */
function wgc_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Geo Catalog for WooCommerce requires WooCommerce to be installed and active.', 'geo-catalog-for-woocommerce' ) .
		'</p></div>';
}

function wgc_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wgc_woocommerce_missing_notice' );
		return;
	}

	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-geolocation.php';
	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-rules.php';
	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-product-fields.php';
	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-category-fields.php';
	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-visibility.php';
	require_once WGC_PLUGIN_DIR . 'includes/class-wgc-settings.php';

	WGC_Product_Fields::init();
	WGC_Category_Fields::init();
	WGC_Visibility::init();
	WGC_Settings::init();
}
add_action( 'plugins_loaded', 'wgc_init' );

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
