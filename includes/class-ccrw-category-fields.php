<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds country-restriction fields to the product_cat taxonomy add/edit
 * screens. Category rules are the bulk mechanism — tag a whole category once
 * instead of every product individually (the free-tier limitation of most
 * competing plugins).
 */
class CCRW_Category_Fields {

	public static function init() {
		add_action( 'product_cat_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_product_cat', array( __CLASS__, 'save' ) );
		add_action( 'edited_product_cat', array( __CLASS__, 'save' ) );
	}

	private static function country_options( $selected ) {
		$html = '';
		foreach ( WC()->countries->get_countries() as $code => $name ) {
			$html .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $code ),
				selected( in_array( $code, $selected, true ), true, false ),
				esc_html( $name )
			);
		}
		return $html;
	}

	public static function render_add_fields() {
		?>
		<div class="form-field">
			<label for="ccrw_countries"><?php esc_html_e( 'Visible only in these countries', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></label>
			<select id="ccrw_countries" name="ccrw_countries[]" multiple="multiple" style="width:95%;" class="wc-enhanced-select">
				<?php echo self::country_options( array() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</select>
			<p><?php esc_html_e( 'Leave empty for no restriction. Products in this category can still override this individually.', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></p>
		</div>
		<div class="form-field">
			<label for="ccrw_mode"><?php esc_html_e( 'Restriction mode', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></label>
			<select id="ccrw_mode" name="ccrw_mode">
				<option value="<?php echo esc_attr( CCRW_Rules::MODE_HIDE ); ?>"><?php esc_html_e( 'Hide completely', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></option>
				<option value="<?php echo esc_attr( CCRW_Rules::MODE_UNAVAILABLE ); ?>"><?php esc_html_e( 'Show, mark unavailable', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></option>
			</select>
		</div>
		<?php
	}

	public static function render_edit_fields( $term ) {
		$countries = (array) get_term_meta( $term->term_id, CCRW_Rules::TERM_META_COUNTRIES, true );
		$mode      = get_term_meta( $term->term_id, CCRW_Rules::TERM_META_MODE, true ) ?: CCRW_Rules::MODE_HIDE;
		?>
		<tr class="form-field">
			<th scope="row"><label for="ccrw_countries"><?php esc_html_e( 'Visible only in these countries', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></label></th>
			<td>
				<select id="ccrw_countries" name="ccrw_countries[]" multiple="multiple" style="width:95%;" class="wc-enhanced-select">
					<?php echo self::country_options( $countries ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</select>
				<p class="description"><?php esc_html_e( 'Leave empty for no restriction. Products in this category can still override this individually.', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="ccrw_mode"><?php esc_html_e( 'Restriction mode', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></label></th>
			<td>
				<select id="ccrw_mode" name="ccrw_mode">
					<option value="<?php echo esc_attr( CCRW_Rules::MODE_HIDE ); ?>" <?php selected( $mode, CCRW_Rules::MODE_HIDE ); ?>><?php esc_html_e( 'Hide completely', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></option>
					<option value="<?php echo esc_attr( CCRW_Rules::MODE_UNAVAILABLE ); ?>" <?php selected( $mode, CCRW_Rules::MODE_UNAVAILABLE ); ?>><?php esc_html_e( 'Show, mark unavailable', 'yuupee-country-catalog-rules-for-woocommerce' ); ?></option>
				</select>
			</td>
		</tr>
		<?php
	}

	public static function save( $term_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// This callback is shared by created_product_cat and edited_product_cat,
		// which are fired by WordPress core only after it verifies its own nonce
		// for that path (add-tag vs. update-tag_{id} — different actions for
		// adding vs. editing a term). A single explicit check here would
		// incorrectly reject whichever of the two paths it wasn't written for,
		// so this relies on that upstream core verification instead.
		$countries = isset( $_POST['ccrw_countries'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['ccrw_countries'] ) )
			: array();
		update_term_meta( $term_id, CCRW_Rules::TERM_META_COUNTRIES, $countries );

		if ( isset( $_POST['ccrw_mode'] ) ) {
			update_term_meta( $term_id, CCRW_Rules::TERM_META_MODE, sanitize_text_field( wp_unslash( $_POST['ccrw_mode'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
