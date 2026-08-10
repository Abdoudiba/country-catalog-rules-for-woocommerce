<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enforces the resolved country rule against the current visitor: hides
 * restricted products from loops/search/direct access ("hide" mode), or
 * shows them but blocks purchase with a message ("unavailable" mode).
 */
class WGC_Visibility {

	public static function init() {
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'filter_is_visible' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_is_purchasable' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'block_direct_access_when_hidden' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_unavailable_notice' ), 25 );
		add_action( 'wp_footer', array( __CLASS__, 'render_admin_preview_banner' ) );
	}

	private static function visitor_country() {
		return WGC_Geolocation::get_visitor_country();
	}

	public static function filter_is_visible( $visible, $product_id ) {
		if ( ! $visible ) {
			return $visible;
		}
		$rule = WGC_Rules::resolve_for_product( $product_id );
		if ( ! $rule['restricted'] || WGC_Rules::MODE_HIDE !== $rule['mode'] ) {
			return $visible;
		}
		return WGC_Rules::is_visible_to_country( $product_id, self::visitor_country() );
	}

	public static function filter_is_purchasable( $purchasable, $product ) {
		if ( ! $purchasable ) {
			return $purchasable;
		}
		$rule = WGC_Rules::resolve_for_product( $product->get_id() );
		if ( ! $rule['restricted'] || WGC_Rules::MODE_UNAVAILABLE !== $rule['mode'] ) {
			return $purchasable;
		}
		return WGC_Rules::is_visible_to_country( $product->get_id(), self::visitor_country() );
	}

	/**
	 * woocommerce_product_is_visible only affects loops/search, not a direct
	 * URL hit on the product's own page — without this, a hidden product is
	 * still fully viewable (and indexable) if someone has the link.
	 */
	public static function block_direct_access_when_hidden() {
		if ( ! is_product() ) {
			return;
		}
		global $post;
		if ( ! $post ) {
			return;
		}
		$rule = WGC_Rules::resolve_for_product( $post->ID );
		if ( ! $rule['restricted'] || WGC_Rules::MODE_HIDE !== $rule['mode'] ) {
			return;
		}
		if ( ! WGC_Rules::is_visible_to_country( $post->ID, self::visitor_country() ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}

	public static function render_unavailable_notice() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$rule = WGC_Rules::resolve_for_product( $product->get_id() );
		if ( ! $rule['restricted'] || WGC_Rules::MODE_UNAVAILABLE !== $rule['mode'] ) {
			return;
		}
		if ( WGC_Rules::is_visible_to_country( $product->get_id(), self::visitor_country() ) ) {
			return;
		}
		$message = WGC_Settings::get_unavailable_message();
		echo '<p class="wgc-unavailable-notice" style="color:#a00;">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Visible only to the admin using ?wgc_preview_country=XX, so it's obvious
	 * during testing that a restriction rule is active — real visitors never
	 * see this.
	 */
	public static function render_admin_preview_banner() {
		if ( ! WGC_Geolocation::is_previewing() ) {
			return;
		}
		printf(
			'<div style="position:fixed;bottom:0;left:0;right:0;background:#000;color:#fff;padding:8px;text-align:center;z-index:99999;font-size:13px;">%s</div>',
			esc_html(
				sprintf(
					/* translators: %s: two-letter country code */
					__( 'Geo Catalog preview active — browsing as %s. Remove ?wgc_preview_country from the URL to stop.', 'woo-geo-catalog' ),
					self::visitor_country()
				)
			)
		);
	}
}
