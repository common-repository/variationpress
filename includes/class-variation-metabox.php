<?php
class SAVP_Variation_Meta_Box {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );

		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save' ), 10, 2 );

	}

	/**
	 * Enqueue a script in the WordPress admin on edit.php.
	 *
	 * @param int $hook Hook suffix for the current admin page.
	 */
	public function enqueue_admin_script( $hook ) {
		$screen = get_current_screen();

		if ( ! in_array( $screen->id, array( 'product', 'edit-product' ) ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'savp_admin_meta', SAVP__URL . 'assets/js/metabox.js', array( 'jquery', 'media-models' ), '1.0' );
		wp_enqueue_style( 'savp_admin_meta', plugins_url( '/assets/css/admin.css', dirname( __FILE__ ) ) );
	}

	public function fields( $loop, $variation_data, $variation ) {

		?>
	<div class="form-field savp_images_wrap form-row form-row-full">
		<label for="my_text_field3"><?php esc_html_e( 'Gallery', 'savp' ); ?></label>

		<ul class="savp_images">
				<?php

				$product_image_gallery = [];
				$attachments         = isset( $variation_data['_savp_gallery'] ) ? $this->string_to_numbers( $variation_data['_savp_gallery'][0] ) : [];
				$update_meta         = false;
				$updated_gallery_ids = array();

				if ( ! empty( $attachments ) ) {
					foreach ( $attachments as $attachment_id ) {
						$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

						// if attachment is empty skip.
						if ( empty( $attachment ) ) {
							$update_meta = true;
							continue;
						}
						?>
						<li class="image" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
							<?php echo $attachment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<ul class="actions">
								<li><a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Delete image', 'savp' ); ?>"><?php esc_html_e( 'Delete', 'savp' ); ?></a></li>
							</ul>
							
						</li>
						<?php
						// rebuild ids to be saved.
						$updated_gallery_ids[] = $attachment_id;
					}

					// Need to update product meta to set new gallery ids.
					if ( $update_meta ) {
						update_post_meta( $post->ID, '_savp_gallery', implode( ',', $updated_gallery_ids ) );
					}
				}
				?>
			</ul>

			<input type="hidden" class="savp_gallery_ids" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" id="<?php echo esc_attr( "_savp_gallery{$loop}" ); ?>" name="<?php echo esc_attr( "_savp_gallery[{$loop}]" ); ?>">
			<a href="#" class="savp_add_images" data-choose="<?php esc_attr_e( 'Add images to product gallery', 'savp' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'savp' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'savp' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'savp' ); ?>"><?php esc_html_e( 'Add product gallery images', 'savp' ); ?></a>
		
	</div>
		<?php
	}

	public function save( $variation_id, $loop ) {

		if ( ! isset( $_POST['_savp_gallery'] ) ) {
			return;
		}
		$gallery = sanitize_text_field( $_POST['_savp_gallery'][ $loop ] ); // WPCS: XSS ok.

		if ( ! empty( $gallery ) ) {
			update_post_meta( $variation_id, '_savp_gallery', esc_attr( $gallery ) );
		}
	}

	protected function string_to_numbers( $string ) {
		$list = explode( ',', $string );
		$numbers = [];
		foreach ( $list as $v ) {
			$v = absint( trim( $v ) );
			if ( $v > 0 ) {
				$numbers[] = $v;
			}
		}

		return $numbers;
	}
}


new SAVP_Variation_Meta_Box();
