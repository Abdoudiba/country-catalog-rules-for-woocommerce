<?php
defined( 'ABSPATH' ) || exit;

/**
 * Resolves the effective country-restriction rule for a product: an explicit
 * product-level rule always wins; otherwise a rule on one of the product's
 * own assigned categories applies; otherwise a rule inherited from an
 * ancestor category applies (nearest ancestor first), so restricting a
 * parent category also covers its subcategories; otherwise there's no
 * restriction at all (visible everywhere — the safe default, so installing
 * the plugin never silently hides products nobody configured).
 */
class WGC_Rules {

	const PRODUCT_META_COUNTRIES = '_wgc_countries';
	const PRODUCT_META_MODE      = '_wgc_mode';
	const PRODUCT_META_OVERRIDE  = '_wgc_override_category';

	const TERM_META_COUNTRIES = 'wgc_countries';
	const TERM_META_MODE      = 'wgc_mode';

	const MODE_HIDE        = 'hide';
	const MODE_UNAVAILABLE = 'unavailable';

	/**
	 * @return array{restricted:bool, countries:string[], mode:string, source:string}
	 *   source is 'product', 'category', or 'none' — kept for admin debug UI,
	 *   not used in the visibility decision itself.
	 */
	public static function resolve_for_product( $product_id ) {
		$no_restriction = array(
			'restricted' => false,
			'countries'  => array(),
			'mode'       => self::MODE_HIDE,
			'source'     => 'none',
		);

		$override = get_post_meta( $product_id, self::PRODUCT_META_OVERRIDE, true );
		$product_countries = get_post_meta( $product_id, self::PRODUCT_META_COUNTRIES, true );

		if ( $override === 'yes' && ! empty( $product_countries ) ) {
			return array(
				'restricted' => true,
				'countries'  => (array) $product_countries,
				'mode'       => get_post_meta( $product_id, self::PRODUCT_META_MODE, true ) ?: self::MODE_HIDE,
				'source'     => 'product',
			);
		}

		$term_ids = wc_get_product_term_ids( $product_id, 'product_cat' );

		// A category directly assigned to the product always wins over an
		// inherited ancestor rule — most specific wins, same principle as
		// the product-override-beats-category rule above.
		foreach ( $term_ids as $term_id ) {
			$countries = get_term_meta( $term_id, self::TERM_META_COUNTRIES, true );
			if ( ! empty( $countries ) ) {
				return array(
					'restricted' => true,
					'countries'  => (array) $countries,
					'mode'       => get_term_meta( $term_id, self::TERM_META_MODE, true ) ?: self::MODE_HIDE,
					'source'     => 'category',
				);
			}
		}

		// Nothing directly assigned has a rule — a restriction on a parent
		// category should still cover its subcategories (that's how a shop
		// owner expects "restrict this category" to behave), so walk up each
		// assigned term's ancestor chain, nearest ancestor first.
		foreach ( $term_ids as $term_id ) {
			$ancestor_ids = get_ancestors( $term_id, 'product_cat', 'taxonomy' );
			foreach ( $ancestor_ids as $ancestor_id ) {
				$countries = get_term_meta( $ancestor_id, self::TERM_META_COUNTRIES, true );
				if ( ! empty( $countries ) ) {
					return array(
						'restricted' => true,
						'countries'  => (array) $countries,
						'mode'       => get_term_meta( $ancestor_id, self::TERM_META_MODE, true ) ?: self::MODE_HIDE,
						'source'     => 'category-inherited',
					);
				}
			}
		}

		return $no_restriction;
	}

	/**
	 * Is this product visible to a visitor from $country_code (2-letter, may
	 * be '' if undetected)? An empty/undetectable country is treated as
	 * "allowed" — failing open on unknown location is safer than accidentally
	 * hiding products from real customers because of a geolocation miss.
	 */
	public static function is_visible_to_country( $product_id, $country_code ) {
		$rule = self::resolve_for_product( $product_id );
		if ( ! $rule['restricted'] ) {
			return true;
		}
		if ( '' === $country_code ) {
			return true;
		}
		return in_array( $country_code, $rule['countries'], true );
	}
}
