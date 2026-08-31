<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enforces the resolved country rule against the current visitor: hides
 * restricted products from loops/search/direct access ("hide" mode), or
 * shows them everywhere — grid cards included — but blocks purchase with an
 * Amazon-style "not available for shipping to your country" notice
 * ("unavailable" mode).
 */
class CCRW_Visibility {

	public static function init() {
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'filter_is_visible' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_is_purchasable' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'block_direct_access_when_hidden' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_unavailable_notice' ), 25 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_loop_badge' ), 15 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'filter_loop_add_to_cart_link' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_admin_preview_banner' ) );
	}

	private static function visitor_country() {
		return CCRW_Geolocation::get_visitor_country();
	}

	/**
	 * Single source of truth for "is this product blocked from purchase for
	 * the current visitor, in unavailable mode" — used by the purchasability
	 * filter, the single-product notice, the loop badge, and the loop
	 * add-to-cart swap, so all four always agree.
	 */
	private static function is_blocked_for_visitor( $product_id ) {
		$rule = CCRW_Rules::resolve_for_product( $product_id );
		if ( ! $rule['restricted'] || CCRW_Rules::MODE_UNAVAILABLE !== $rule['mode'] ) {
			return false;
		}
		return ! CCRW_Rules::is_visible_to_country( $product_id, self::visitor_country() );
	}

	public static function filter_is_visible( $visible, $product_id ) {
		if ( ! $visible ) {
			return $visible;
		}
		$rule = CCRW_Rules::resolve_for_product( $product_id );
		if ( ! $rule['restricted'] || CCRW_Rules::MODE_HIDE !== $rule['mode'] ) {
			return $visible;
		}
		return CCRW_Rules::is_visible_to_country( $product_id, self::visitor_country() );
	}

	public static function filter_is_purchasable( $purchasable, $product ) {
		if ( ! $purchasable ) {
			return $purchasable;
		}
		return ! self::is_blocked_for_visitor( $product->get_id() );
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
		$rule = CCRW_Rules::resolve_for_product( $post->ID );
		if ( ! $rule['restricted'] || CCRW_Rules::MODE_HIDE !== $rule['mode'] ) {
			return;
		}
		if ( ! CCRW_Rules::is_visible_to_country( $post->ID, self::visitor_country() ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}

	public static function render_unavailable_notice() {
		global $product;
		if ( ! $product instanceof WC_Product || ! self::is_blocked_for_visitor( $product->get_id() ) ) {
			return;
		}
		$message = CCRW_Settings::get_unavailable_message( self::visitor_country() );
		echo '<p class="ccrw-unavailable-notice">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Amazon-style flag on shop/category/search grid cards, mirroring
	 * WooCommerce's own "Sale!" flash badge placement — so a blocked product
	 * is obvious before a shopper ever clicks through to its page.
	 */
	public static function render_loop_badge() {
		global $product;
		if ( ! $product instanceof WC_Product || ! self::is_blocked_for_visitor( $product->get_id() ) ) {
			return;
		}
		echo '<span class="ccrw-loop-badge">' . esc_html__( 'Not available in your country', 'yuupee-country-catalog-rules-for-woocommerce' ) . '</span>';
	}

	/**
	 * Swaps the grid card's Add to Cart button for a disabled-looking notice
	 * when the product is blocked — without this, is_purchasable() being
	 * false doesn't reliably change a simple product's loop button on its
	 * own, so shoppers could click "Add to cart" only to be rejected.
	 */
	public static function filter_loop_add_to_cart_link( $html, $product ) {
		if ( ! self::is_blocked_for_visitor( $product->get_id() ) ) {
			return $html;
		}
		return '<span class="ccrw-loop-unavailable-cta">' . esc_html__( 'Not available', 'yuupee-country-catalog-rules-for-woocommerce' ) . '</span>';
	}

	public static function enqueue_frontend_assets() {
		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_product() && ! is_search() ) {
			return;
		}
		wp_enqueue_style( 'ccrw-frontend', CCRW_PLUGIN_URL . 'assets/css/ccrw-frontend.css', array(), CCRW_VERSION );
	}

	/**
	 * Visible only to the admin using ?ccrw_preview_country=XX, so it's obvious
	 * during testing that a restriction rule is active — real visitors never
	 * see this.
	 */
	public static function render_admin_preview_banner() {
		if ( ! CCRW_Geolocation::is_previewing() ) {
			return;
		}
		printf(
			'<div style="position:fixed;bottom:0;left:0;right:0;background:#000;color:#fff;padding:8px;text-align:center;z-index:99999;font-size:13px;">%s</div>',
			esc_html(
				sprintf(
					/* translators: %s: two-letter country code */
					__( 'Country Catalog Rules preview active — browsing as %s. Remove ?ccrw_preview_country from the URL to stop.', 'yuupee-country-catalog-rules-for-woocommerce' ),
					self::visitor_country()
				)
			)
		);
	}
}
