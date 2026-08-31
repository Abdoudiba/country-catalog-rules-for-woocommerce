<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Country Catalog Rules" tab under WooCommerce → Settings for plugin-wide
 * defaults. Per-product/per-category rules live on those screens directly
 * (see CCRW_Product_Fields / CCRW_Category_Fields) — this tab is only for
 * settings that apply everywhere.
 */
class CCRW_Settings {

	const OPTION_UNAVAILABLE_MESSAGE = 'ccrw_unavailable_message';

	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_ccrw', array( __CLASS__, 'render' ) );
		add_action( 'woocommerce_update_options_ccrw', array( __CLASS__, 'save' ) );
	}

	public static function add_tab( $tabs ) {
		$tabs['ccrw'] = __( 'Country Catalog Rules', 'yuupee-country-catalog-rules-for-woocommerce' );
		return $tabs;
	}

	private static function fields() {
		return array(
			array(
				'title' => __( 'Country Catalog Rules', 'yuupee-country-catalog-rules-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Country restriction is configured per-product (Product Data → Country Catalog Rules tab) and per-category (Products → Categories → edit a category). This page only holds settings shared across all restricted products.', 'yuupee-country-catalog-rules-for-woocommerce' ),
				'id'    => 'ccrw_section_title',
			),
			array(
				'title'    => __( 'Unavailable message', 'yuupee-country-catalog-rules-for-woocommerce' ),
				'desc'     => __( 'Shown on the product page and product grids when a visitor is blocked from purchasing (mode: "Show, mark unavailable"). Use {country} to insert the visitor\'s detected country name.', 'yuupee-country-catalog-rules-for-woocommerce' ),
				'id'       => self::OPTION_UNAVAILABLE_MESSAGE,
				'type'     => 'textarea',
				'css'      => 'width:400px; height:75px;',
				'default'  => __( 'This product cannot be shipped to {country}.', 'yuupee-country-catalog-rules-for-woocommerce' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'ccrw_section_end',
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
		$template = get_option( self::OPTION_UNAVAILABLE_MESSAGE ) ?: __( 'This product cannot be shipped to {country}.', 'yuupee-country-catalog-rules-for-woocommerce' );
		$country_name = CCRW_Geolocation::get_country_name( $country_code );
		return str_replace( '{country}', $country_name, $template );
	}
}
