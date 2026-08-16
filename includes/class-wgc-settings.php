<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Geo Catalog" tab under WooCommerce → Settings for plugin-wide
 * defaults. Per-product/per-category rules live on those screens directly
 * (see WGC_Product_Fields / WGC_Category_Fields) — this tab is only for
 * settings that apply everywhere.
 */
class WGC_Settings {

	const OPTION_UNAVAILABLE_MESSAGE = 'wgc_unavailable_message';

	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_wgc', array( __CLASS__, 'render' ) );
		add_action( 'woocommerce_update_options_wgc', array( __CLASS__, 'save' ) );
	}

	public static function add_tab( $tabs ) {
		$tabs['wgc'] = __( 'Geo Catalog', 'geo-catalog-for-woocommerce' );
		return $tabs;
	}

	private static function fields() {
		return array(
			array(
				'title' => __( 'Geo Catalog', 'geo-catalog-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Country restriction is configured per-product (Product Data → Geo Catalog tab) and per-category (Products → Categories → edit a category). This page only holds settings shared across all restricted products.', 'geo-catalog-for-woocommerce' ),
				'id'    => 'wgc_section_title',
			),
			array(
				'title'    => __( 'Unavailable message', 'geo-catalog-for-woocommerce' ),
				'desc'     => __( 'Shown on the product page and product grids when a visitor is blocked from purchasing (mode: "Show, mark unavailable"). Use {country} to insert the visitor\'s detected country name.', 'geo-catalog-for-woocommerce' ),
				'id'       => self::OPTION_UNAVAILABLE_MESSAGE,
				'type'     => 'textarea',
				'css'      => 'width:400px; height:75px;',
				'default'  => __( 'This product cannot be shipped to {country}.', 'geo-catalog-for-woocommerce' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wgc_section_end',
			),
		);
	}

	public static function render() {
		woocommerce_admin_fields( self::fields() );
	}

	public static function save() {
		woocommerce_update_options( self::fields() );
	}

	/**
	 * @param string $country_code Two-letter country code the message is
	 *                              being shown for; substituted into any
	 *                              {country} token in the template.
	 */
	public static function get_unavailable_message( $country_code = '' ) {
		$template = get_option( self::OPTION_UNAVAILABLE_MESSAGE ) ?: __( 'This product cannot be shipped to {country}.', 'geo-catalog-for-woocommerce' );
		$country_name = WGC_Geolocation::get_country_name( $country_code );
		return str_replace( '{country}', $country_name, $template );
	}
}
