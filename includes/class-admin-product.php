<?php

/**
 * Class SAVP_Admin_Product
 */
class SAVP_Admin_Product {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_option_terms', array( $this, 'product_option_terms' ), 10, 2 );
		add_action( 'wp_ajax_savp_add_new_attribute', array( $this, 'add_new_attribute_ajax' ) );
		add_action( 'admin_footer', array( $this, 'add_attribute_term_template' ) );
	}


	/**
	 * Add selector for extra attribute types
	 *
	 * @param $taxonomy
	 * @param $index
	 */
	public function product_option_terms( $taxonomy, $index ) {

		if ( ! array_key_exists( $taxonomy->attribute_type, savp()->types ) ) {
			return;
		}

		$taxonomy_name = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
		global $thepostid;

		$product_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : $thepostid; // WPCS: XSS ok.
		?>
	
		<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'savp' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo $index; ?>][]">
			<?php

			$all_terms = get_terms(
				$taxonomy_name,
				apply_filters(
					'woocommerce_product_attribute_terms',
					array(
						'orderby' => 'name',
						'hide_empty' => false,
					)
				)
			);
			if ( $all_terms ) {
				foreach ( $all_terms as $term ) {
					echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( has_term( absint( $term->term_id ), $taxonomy_name, $product_id ), true, false ) . '>' . esc_attr( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
				}
			}
			?>
		</select>
		<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'savp' ); ?></button>
		<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'savp' ); ?></button>
		<button class="button fr plus savp_add_new_attribute" data-type="<?php echo $taxonomy->attribute_type; ?>"><?php esc_html_e( 'Add new', 'savp' ); ?></button>

		<?php
	}

	/**
	 * Ajax function handles adding new attribute term
	 */
	public function add_new_attribute_ajax() {
		$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$tax    = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : ''; // WPCS: XSS ok.
		$type   = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : ''; // WPCS: XSS ok.
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : ''; // WPCS: XSS ok.
		$slug   = isset( $_POST['slug'] ) ? sanitize_text_field( $_POST['slug'] ) : ''; // WPCS: XSS ok.
		$swatch = isset( $_POST['swatch'] ) ? sanitize_text_field( $_POST['swatch'] ) : ''; // WPCS: XSS ok.
		$swatch2 = isset( $_POST['swatch_2'] ) ? sanitize_text_field( $_POST['swatch_2'] ) : ''; // WPCS: XSS ok.

		if ( ! wp_verify_nonce( $nonce, '_savp_create_attribute' ) ) {
			wp_send_json_error( esc_html__( 'Wrong request', 'savp' ) );
		}

		if ( empty( $name ) || empty( $swatch ) || empty( $tax ) || empty( $type ) ) {
			wp_send_json_error( esc_html__( 'Not enough data', 'savp' ) );
		}

		if ( ! taxonomy_exists( $tax ) ) {
			wp_send_json_error( esc_html__( 'Taxonomy is not exists', 'savp' ) );
		}

		if ( term_exists( $name, $tax ) ) {
			wp_send_json_error( esc_html__( 'This term is exists', 'savp' ) );
		}

		$term = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );

		if ( is_wp_error( $term ) ) {
			wp_send_json_error( $term->get_error_message() );
		} else {
			$term = get_term_by( 'id', $term['term_id'], $tax );
			update_term_meta( $term->term_id, $type, $swatch );
			if ( 'color' == $type ) {
				update_term_meta( $term->term_id, $type . '_2', $swatch2 );
			}
		}

		wp_send_json_success(
			array(
				'msg'  => esc_html__( 'Added successfully', 'savp' ),
				'id'   => $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name,
			)
		);
	}

	/**
	 * Print HTML of modal at admin footer and add js templates
	 */
	public function add_attribute_term_template() {
		global $pagenow, $post;

		if ( $pagenow != 'post.php' || ( isset( $post ) && get_post_type( $post->ID ) != 'product' ) ) {
			return;
		}
		?>

		<div id="savp_modal-container" class="savp_modal-container">
			<div class="savp_modal">
				<button type="button" class="button-link media-modal-close savp_modal-close">
					<span class="media-modal-icon"></span></button>
				<div class="savp_modal-header"><h2><?php esc_html_e( 'Add new term', 'savp' ); ?></h2></div>
				<div class="savp_modal-content">
					<p class="savp_term-name">
						<label>
							<?php esc_html_e( 'Name', 'savp' ); ?>
							<input type="text" class="widefat savp_input" name="name">
						</label>
					</p>
					<p class="savp_term-slug">
						<label>
							<?php esc_html_e( 'Slug', 'savp' ); ?>
							<input type="text" class="widefat savp_input" name="slug">
						</label>
					</p>
					<div class="savp_term-swatch">

					</div>
					<div class="hidden savp_term-tax"></div>

					<input type="hidden" class="savp_input" name="nonce" value="<?php echo wp_create_nonce( '_savp_create_attribute' ); ?>">
				</div>
				<div class="savp_modal-footer">
					<button class="button button-secondary savp_modal-close"><?php esc_html_e( 'Cancel', 'savp' ); ?></button>
					<button class="button button-primary savp_new-attribute-submit"><?php esc_html_e( 'Add New', 'savp' ); ?></button>
					<span class="message"></span>
					<span class="spinner"></span>
				</div>
			</div>
			<div class="savp_modal-backdrop media-modal-backdrop"></div>
		</div>

		<script type="text/template" id="tmpl-savp_input-color">

			<div>
				<label><?php esc_html_e( 'Color Primary', 'savp' ); ?></label><br>
				<input type="text" class="savp_input savp_input-color" name="swatch">
			</div>

			<div>
				<label><?php esc_html_e( 'Color Secondary', 'savp' ); ?></label><br>
				<input type="text" class="savp_input savp_input-color" name="swatch_2">
			</div>

		</script>

		<script type="text/template" id="tmpl-savp_input-image">

			<label><?php esc_html_e( 'Image', 'savp' ); ?></label><br>
			<div class="savp_term-image-thumbnail savp-left savp_mg-l-10" >
				<img src="<?php echo esc_url( WC()->plugin_url() . '/assets/images/placeholder.png' ); ?>" width="60px" height="60px" />
			</div>
			<div class="savp-line-h60">
				<input type="hidden" class="savp_input savp_input-image savp_term-image" name="swatch" value="" />
				<button type="button" class="savp_upload-image-button button"><?php esc_html_e( 'Upload/Add image', 'savp' ); ?></button>
				<button type="button" class="savp_remove-image-button button hidden"><?php esc_html_e( 'Remove image', 'savp' ); ?></button>
			</div>

		</script>

		<script type="text/template" id="tmpl-savp_input-label">

			<label>
				<?php esc_html_e( 'Label', 'savp' ); ?>
				<input type="text" class="widefat savp_input savp_input-label" name="swatch">
			</label>

		</script>

		<script type="text/template" id="tmpl-savp_input-tax">

			<input type="hidden" class="savp_input" name="taxonomy" value="{{data.tax}}">
			<input type="hidden" class="savp_input" name="type" value="{{data.type}}">

		</script>
		<?php
	}


}

new SAVP_Admin_Product();
