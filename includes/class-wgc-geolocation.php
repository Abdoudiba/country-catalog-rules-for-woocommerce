<?php
defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around WooCommerce's own geolocation (WC_Geolocation, backed by
 * the MaxMind GeoIP2 database WooCommerce already ships and configures).
 *
 * Deliberately NOT a separate IP lookup: reusing the same source WooCommerce
 * already uses for shipping/tax/currency avoids two geolocation systems
 * disagreeing about a visitor's country, which is the main reliability risk
 * with plugins that bring their own IP database.
 */
class WGC_Geolocation {

	const PREVIEW_PARAM = 'wgc_preview_country';

	/**
	 * Resolve the current visitor's two-letter country code, or '' if it
	 * can't be determined (no IP, geolocation disabled, local dev, etc.).
	 */
	public static function get_visitor_country() {
		$preview = self::get_admin_preview_country();
		if ( $preview ) {
			return $preview;
		}

		if ( ! class_exists( 'WC_Geolocation' ) ) {
			return '';
		}

		// Prefer an explicit shipping/billing country if the customer already
		// set one this session (e.g. mid-checkout) — geolocation is a fallback
		// signal, not a source of truth once the customer has told us directly.
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$explicit = WC()->customer->get_shipping_country();
			if ( $explicit ) {
				return $explicit;
			}
		}

		$location = WC_Geolocation::geolocate_ip();
		return isset( $location['country'] ) ? $location['country'] : '';
	}

	/**
	 * Lets a logged-in admin preview the storefront as if browsing from a
	 * specific country, via ?wgc_preview_country=CI — avoids needing a VPN to
	 * test restriction rules. Admin-only and never persisted beyond the
	 * request's query param (no cookie, no session write) to keep it obviously
	 * inert for real visitors.
	 */
	private static function get_admin_preview_country() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}
		if ( empty( $_GET[ self::PREVIEW_PARAM ] ) ) {
			return '';
		}
		$code = strtoupper( sanitize_text_field( wp_unslash( $_GET[ self::PREVIEW_PARAM ] ) ) );
		return preg_match( '/^[A-Z]{2}$/', $code ) ? $code : '';
	}

	public static function is_previewing() {
		return (bool) self::get_admin_preview_country();
	}
}
