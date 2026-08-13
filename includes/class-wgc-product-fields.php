<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Geo Catalog" tab to the Product Data metabox for per-product
 * country restriction, overriding whatever the product's category says.
 */
class WGC_Product_Fields {

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
	}

	public static function add_tab( $tabs ) {
		$tabs['wgc'] = array(
			'label'    => __( 'Geo Catalog', 'geo-catalog-for-woocommerce' ),
			'target'   => 'wgc_product_data',
			'class'    => array(),
			'priority' => 80,
		);
		return $tabs;
	}

	public static function render_panel() {
		global $post;
		$product_id = $post->ID;

		$override  = get_post_meta( $product_id, WGC_Rules::PRODUCT_META_OVERRIDE, true );
		$countries = (array) get_post_meta( $product_id, WGC_Rules::PRODUCT_META_COUNTRIES, true );
		$mode      = get_post_meta( $product_id, WGC_Rules::PRODUCT_META_MODE, true ) ?: WGC_Rules::MODE_HIDE;

		$rule = WGC_Rules::resolve_for_product( $product_id );
		?>
		<div id="wgc_product_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<p class="form-field">
					<?php
					esc_html_e( 'By default, this product inherits any country restriction set on its category.', 'geo-catalog-for-woocommerce' );
					if ( 'category' === $rule['source'] ) {
						echo ' ' . esc_html__( 'Currently restricted via category to:', 'geo-catalog-for-woocommerce' ) . ' <code>' . esc_html( implode( ', ', $rule['countries'] ) ) . '</code>';
					}
					?>
				</p>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => WGC_Rules::PRODUCT_META_OVERRIDE,
						'value'       => 'yes' === $override ? 'yes' : 'no',
						'label'       => __( 'Override category rule for this product', 'geo-catalog-for-woocommerce' ),
						'description' => __( 'Check this to set a country rule specific to this product, ignoring its category.', 'geo-catalog-for-woocommerce' ),
					)
				);
				?>
				<p class="form-field">
					<label for="<?php echo esc_attr( WGC_Rules::PRODUCT_META_COUNTRIES ); ?>">
						<?php esc_html_e( 'Visible only in these countries', 'geo-catalog-for-woocommerce' ); ?>
					</label>
					<select
						id="<?php echo esc_attr( WGC_Rules::PRODUCT_META_COUNTRIES ); ?>"
						name="<?php echo esc_attr( WGC_Rules::PRODUCT_META_COUNTRIES ); ?>[]"
						multiple="multiple"
						style="width:50%;"
						class="wc-enhanced-select"
					>
						<?php foreach ( WC()->countries->get_countries() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( in_array( $code, $countries, true ) ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="description"><?php esc_html_e( 'Leave empty to remove any product-level restriction.', 'geo-catalog-for-woocommerce' ); ?></span>
				</p>
				<?php
				woocommerce_wp_select(
					array(
						'id'      => WGC_Rules::PRODUCT_META_MODE,
						'value'   => $mode,
						'label'   => __( 'Restriction mode', 'geo-catalog-for-woocommerce' ),
						'options' => array(
							WGC_Rules::MODE_HIDE        => __( 'Hide completely (not in shop, search, or feeds)', 'geo-catalog-for-woocommerce' ),
							WGC_Rules::MODE_UNAVAILABLE => __( 'Show, but mark as unavailable (no add-to-cart)', 'geo-catalog-for-woocommerce' ),
						),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	public static function save( $product_id ) {
		// WooCommerce always renders its own woocommerce_meta_nonce field on the
		// product edit screen and verifies it (in WC_Meta_Box_Product_Data::save())
		// before this hook ever fires — but check it explicitly too, both as
		// defense-in-depth and because static analysis can't see that upstream
		// protection.
		check_admin_referer( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$override = isset( $_POST[ WGC_Rules::PRODUCT_META_OVERRIDE ] ) ? 'yes' : 'no';
		update_post_meta( $product_id, WGC_Rules::PRODUCT_META_OVERRIDE, $override );

		$countries = isset( $_POST[ WGC_Rules::PRODUCT_META_COUNTRIES ] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST[ WGC_Rules::PRODUCT_META_COUNTRIES ] ) )
			: array();
		update_post_meta( $product_id, WGC_Rules::PRODUCT_META_COUNTRIES, $countries );

		if ( isset( $_POST[ WGC_Rules::PRODUCT_META_MODE ] ) ) {
			$mode = sanitize_text_field( wp_unslash( $_POST[ WGC_Rules::PRODUCT_META_MODE ] ) );
			update_post_meta( $product_id, WGC_Rules::PRODUCT_META_MODE, $mode );
		}
	}
}
