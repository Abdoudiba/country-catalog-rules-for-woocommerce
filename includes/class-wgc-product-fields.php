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
			'label'    => __( 'Geo Catalog', 'woo-geo-catalog' ),
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
					esc_html_e( 'By default, this product inherits any country restriction set on its category.', 'woo-geo-catalog' );
					if ( 'category' === $rule['source'] ) {
						echo ' ' . esc_html__( 'Currently restricted via category to:', 'woo-geo-catalog' ) . ' <code>' . esc_html( implode( ', ', $rule['countries'] ) ) . '</code>';
					}
					?>
				</p>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => WGC_Rules::PRODUCT_META_OVERRIDE,
						'value'       => 'yes' === $override ? 'yes' : 'no',
						'label'       => __( 'Override category rule for this product', 'woo-geo-catalog' ),
						'description' => __( 'Check this to set a country rule specific to this product, ignoring its category.', 'woo-geo-catalog' ),
					)
				);
				?>
				<p class="form-field">
					<label for="<?php echo esc_attr( WGC_Rules::PRODUCT_META_COUNTRIES ); ?>">
						<?php esc_html_e( 'Visible only in these countries', 'woo-geo-catalog' ); ?>
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
					<span class="description"><?php esc_html_e( 'Leave empty to remove any product-level restriction.', 'woo-geo-catalog' ); ?></span>
				</p>
				<?php
				woocommerce_wp_select(
					array(
						'id'      => WGC_Rules::PRODUCT_META_MODE,
						'value'   => $mode,
						'label'   => __( 'Restriction mode', 'woo-geo-catalog' ),
						'options' => array(
							WGC_Rules::MODE_HIDE        => __( 'Hide completely (not in shop, search, or feeds)', 'woo-geo-catalog' ),
							WGC_Rules::MODE_UNAVAILABLE => __( 'Show, but mark as unavailable (no add-to-cart)', 'woo-geo-catalog' ),
						),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	public static function save( $product_id ) {
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
