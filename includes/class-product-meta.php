<?php

/**
 * Register a meta box using a class.
 */
class SAVP_Meta_Box {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'load-post.php', array( $this, 'init_metabox' ) );
			add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
		}

		add_action( 'wp_ajax_savp_get_product_attrs', array( $this, 'ajax_get_product_attributes' ) );
		add_action( 'wp_ajax_savp_bulk_update_variation_thumbnail', array( $this, 'ajax_bulk_update_variation_thumbnail' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_custom_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'gallery_product_data_panel' ) );
	}


	public function add_custom_product_data_tab( $product_data_tabs ) {
		$list = array();
		foreach ( $product_data_tabs as $k => $v ) {
			if ( 'variations' == $k ) {
				$list[ $k ] = $v;
				$list['savp_swashes'] = array(
					'label' => esc_html__( 'Swashes', 'savp' ),
					'target' => 'savp_swashes',
					'class'    => array( 'show_if_variable' ),
				);
				$list['savp_bulk_gallery'] = array(
					'label' => esc_html__( 'Bulk Gallery', 'savp' ),
					'target' => 'savp_bulk_gallery',
					'class'    => array( 'show_if_variable' ),
				);
			} else {
				$list[ $k ] = $v;
			}
		}

		return $list;
	}


	public function ajax_bulk_update_variation_thumbnail() {

		$product_id   = absint( wp_unslash( $_REQUEST['post_id'] ) );
		$product_type = ! empty( $_REQUEST['product_type'] ) ? wc_clean( wp_unslash( $_REQUEST['product_type'] ) ) : 'simple';
		$classname    = WC_Product_Factory::get_product_classname( $product_id, $product_type );
		$product      = new $classname( $product_id );
		$attributes = wc_clean( $_REQUEST['attributes'] );
		$image_id = wp_unslash( $_REQUEST['image_id'] );

		$variations     = wc_get_products(
			array(
				'status'  => array( 'private', 'publish' ),
				'type'    => 'variation',
				'parent'  => $product_id,
				'limit'   => -1,
				'page'    => 0,
				'orderby' => array(
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				),
				'return'  => 'objects',
			)
		);

		foreach ( $variations as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation || ! $variation->exists() ) {
				continue;
			}

			$v_attrs = $variation->get_variation_attributes();
			$match = true;
			foreach ( $attributes as $k => $v ) {
				if ( $v ) {
					$k = strtolower( $k );
					if ( ! isset( $v_attrs[ 'attribute_' . $k ] ) ) {
						$match = false;
						break;
					}

					if ( $v_attrs[ 'attribute_' . $k ] != $v ) {
						$match = false;
						break;
					}
				}
			}

			if ( $match ) {
				$variation->set_image_id( $image_id );
				$variation->save();
			}
		}

		wp_send_json_success( 1 );

	}

	public function ajax_get_product_attributes() {

		$product_id   = absint( wp_unslash( $_REQUEST['post_id'] ) );
		$product_type = ! empty( $_REQUEST['product_type'] ) ? wc_clean( wp_unslash( $_REQUEST['product_type'] ) ) : 'simple';
		$classname    = WC_Product_Factory::get_product_classname( $product_id, $product_type );
		$product      = new $classname( $product_id );

		ob_start();

		$attributes = $product->get_attributes();
		$data = array();
		foreach ( $attributes as $attribute ) {

			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$key = $attribute->get_name();
			$attr = array(
				'tax' => $key,
				'label' => wc_attribute_label( $attribute->get_name() ),
				'options' => array(),
			);

			$options = array();

			if ( $attribute->is_taxonomy() ) {
				foreach ( $attribute->get_terms() as $option ) {
					$options[ $option->slug ] = $option->name;
				}
			} else {
				foreach ( $attribute->get_options() as $option ) {
					$options[ $option ] = $option;
				}
			}

			$attr['options'] = $options;

			$data[ $key ] = $attr;
		}
		wp_send_json_success( $data );
		die();
	}

	/**
	 * Meta box initialization.
	 */
	public function init_metabox() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 30, 2 );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Adds the meta box.
	 */
	public function add_metabox() {

		add_meta_box(
			'savp_svg_video',
			esc_html__( 'Video', 'savp' ),
			array( $this, 'video_form' ),
			'product',
			'side',
			'default'
		);
	}

	protected function plus_upgrade() {
		?>
		<p>
			Please install <a href="https://sainwp.com/plus-upgrade/?utm_source=plugin&utm_medium=edit&utm_campaign=variationpress">OneStore Plus</a> plugin to unlock this feature.
		</p>
		<?php
	}

	/**
	 * Renders the meta box.
	 */
	public function gallery_product_data_panel() {
		global $post;
		$product_id = $post->ID;

		wp_nonce_field( 'savp_action', 'savp_gallery_nonce' );
		$value = get_post_meta( $product_id, '_savp_video', true );
		$attachment_id = get_post_meta( $product_id, '_savp_video_thumbnail', true );
		$loop = 0;
		wp_nonce_field( 'savp_action', 'savp_nonce' );
		$galleries = get_post_meta( $product_id, '_savp_bulk_gallery', true );
		if ( ! is_array( $galleries ) ) {
			$galleries = array();
		}
		?>

		<div id="savp_bulk_gallery" class="panel savp-wc-panel woocommerce_options_panel hidden">
			<?php if ( savp_is_plus_activated() ) : ?>
			<div id="savp-gallery-list">

				<?php foreach ( $galleries as $index => $gallery ) {

					$gallery = wp_parse_args(
						$gallery,
						array(
							'attrs' => array(),
							'images' => '',
						)
					)
					?>
				<div class="savp-row savp-gallery-row">
					<div class="savp-head savp-gallery-row-header">
						<div class="savp-title savp-gallery-h-attrs">
							<?php foreach ( $gallery['attrs'] as $tax => $term ) { ?>
								<label class="tax-val" data-tax="<?php echo esc_attr( $tax ); ?>">
									<select data-name="<?php echo esc_attr( $tax ); ?>">
										<option selected value="<?php echo esc_attr( $term ); ?>"><?php echo esc_attr( $term ); ?></option>
									</select>
								</label>
							<?php } ?>
						</div>
						<div class="savp-actions savp-gallery-h-act">
							<span class="savp-toggle" ><span class="dashicons dashicons-arrow-down"></span></span>
							<span class="move"><span class="dashicons dashicons-menu-alt3"></span></span>
							<span class="delete"><span class="dashicons dashicons-trash"></span></span>
						</div>
					</div>
					<div class="savp-body savp-gallery-row-body">
						<div class="form-field savp_images_wrap form-row form-row-full">
							<ul class="savp_images">
								<?php
								$attachments         = SAVP_Main::string_to_numbers( $gallery['images'] );
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
								}
								?>
							</ul>
							<input type="hidden" class="savp_gallery_ids" data-name="images" value="<?php echo esc_attr( join( ',', $updated_gallery_ids ) ); ?>" >
							<a href="#" class="savp_add_images button button-secondary" data-choose="<?php esc_attr_e( 'Add Images', 'savp' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'savp' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'savp' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'savp' ); ?>"><?php esc_html_e( 'Add Images', 'savp' ); ?></a>
							<a href="#" class="savp_set-var-thumb button button-secondary" ><?php esc_html_e( 'Set first image as variation thumbnail', 'savp' ); ?></a>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<button id="savp-add-gallery" class="button button-secondary" type="button">Add Gallery</button>
			<?php else : 
			$this->plus_upgrade();
			endif; ?>

		</div>

		<script type="text/html" id="tmpl-savp_bulk_item">

			<div class="savp-row savp-gallery-row">
				<div class="savp-head savp-gallery-row-header">
					<div class="savp-title savp-gallery-h-attrs"></div>
					<div class="savp-actions savp-gallery-h-act">
						<span class="savp-toggle" ><span class="dashicons dashicons-arrow-down"></span></span>
						<span class="move"><span class="dashicons dashicons-menu-alt3"></span></span>
						<span class="delete"><span class="dashicons dashicons-trash"></span></span>
					</div>
				</div>
				<div class="savp-body savp-gallery-row-body">
					<div class="form-field savp_images_wrap form-row form-row-full">
						<ul class="savp_images"></ul>
						<input type="hidden" class="savp_gallery_ids" value="" data-name="images" id="{{ data.id }}" name=""{{ data.name }}">
						<a href="#" class="savp_add_images button button-secondary" data-choose="<?php esc_attr_e( 'Add Images', 'savp' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'savp' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'savp' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'savp' ); ?>"><?php esc_html_e( 'Add Images', 'savp' ); ?></a>
						<a href="#" class="savp_set-var-thumb button button-secondary" ><?php esc_html_e( 'Set first image as variation thumbnail', 'savp' ); ?></a>
					</div>

				</div>
			</div>

		</script>

		<div id="savp_swashes" class="panel savp-wc-panel woocommerce_options_panel hidden">
		<?php
		$swatches = get_post_meta( $product_id, '_savp_swatches', true );
		$swatches_archive = get_post_meta( $product_id, '_savp_swatches_archive', true );
		if ( ! is_array( $swatches ) ) {
			$swatches = array();
		}

		if ( ! is_array( $swatches_archive ) ) {
			$swatches_archive = array();
		}

		?>

		<div class="savp-archive-attr form-field">
			<strong><?php _e( 'Archive Attributes', 'savp' ); ?></strong><br/>
			<select multiple="multiple" id="_savp_swatches_show_archives" name="_savp_swatches_archive[]" class="savp_block_100">
				<?php foreach ( $swatches_archive as $k ) { ?>
				<option selected="selected" value="<?php echo esc_attr( $k ); ?>"><?php echo esc_attr( $k ); ?></option>
				<?php } ?>
			</select>
			<p class="description"><?php _e( 'Select attributes to show on archive page. Leave empty to use global settings.', 'savp' ); ?></p>
		</div>


		<div id="savp-swashes-list">
		

		<?php foreach ( $swatches as $tax => $args ) {
			$args  = wp_parse_args(
				$args,
				array(
					'type' => '',
					'display' => '',
					'price' => '',
					'stock_status' => '',
					'options' => array(),
				)
			);
			?>
			<div class="savp-row savp-s-row-item" data-tax="<?php echo esc_attr( $tax ); ?>" data-current="<?php echo esc_attr( $args['type'] ); ?>">
				<div class="savp-head">
					<div class="savp-title"><?php echo wc_attribute_label( $tax ); ?></div>
					<select name="_savp_swatches[<?php echo esc_attr( $tax ); ?>][type]" class="current-type">
						<option value=""><?php esc_attr_e( 'Global', 'savp' ); ?></option>
						<?php foreach ( wc_get_attribute_types() as $k => $label ) { ?>
							<option <?php selected( $args['type'], $k ); ?> value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php } ?>
					</select>
					<div class="savp-actions">
						<span class="savp-toggle" ><span class="dashicons dashicons-arrow-down"></span></span>
					</div>
				</div>
				<div class="savp-body">
					<div class="savp-more">
						<label>
							<input type="checkbox"  <?php checked( $args['display'], 'list' ); ?> value="list" class="savp_display-type" name="_savp_swatches[<?php echo esc_attr( $tax ); ?>][display]" />
							<?php esc_html_e( 'Display list', 'savp' ); ?>
						</label>

						<label class="more-item show-on-list">
							<input type="checkbox" <?php checked( $args['price'], 'yes' ); ?> value="yes"  name="_savp_swatches[<?php echo esc_attr( $tax ); ?>][price]" />
							<?php esc_html_e( 'Show Price(for list only)', 'savp' ); ?>
						</label>

						<label class="more-item show-on-list">
							<input type="checkbox" <?php checked( $args['stock_status'], 'yes' ); ?> value="yes" name="_savp_swatches[<?php echo esc_attr( $tax ); ?>][stock_status]" />
							<?php esc_html_e( 'Show stock status(for list only)', 'savp' ); ?>
						</label>

					</div>

					<div class="attr-terms">
						<?php foreach ( $args['options'] as $k => $values ) {
							$values = wp_parse_args(
								$values,
								array(
									'image' => '',
									'label' => '',
									'color' => '',
									'color2' => '',
								)
							);

							$input_name = '_savp_swatches_custom[' . esc_attr( $tax ) . '][' . esc_attr( $k ) . ']';
							$image = false;
							$has_image = false;
							if ( $values['image'] ) {
								$image = wp_get_attachment_image_src( $values['image'] );
								if ( $image ) {
									$image = $image[0];
									$has_image  = true;
								}
							}

							if ( ! $image ) {
								$image = WC()->plugin_url() . '/assets/images/placeholder.png';
							}
							?>
							<div class="savp-row savp-s--item" data-term="<?php echo esc_attr( $k ); ?>">
								<div class="savp-si-item">
									<span class="s-label"><?php echo esc_attr( $k ); ?></span>
									<div class="s-inputs">
										<span class="s-item-val image show-on-image">
											<input type="hidden" class="image_id" value="<?php echo esc_attr( $values['image'] ); ?>" name="<?php echo $input_name; // WPCS: XSS ok. ?>[image]">
											<div class="image-html">
												<img src="<?php echo esc_url( $image ); ?>">
												<?php if ( $has_image ) { ?>
													<a href="#" class="delete"><span class="dashicons dashicons-dismiss"></span></a>
												<?php } ?>
											</div>
										</span>
										<input name="<?php echo $input_name; // WPCS: XSS ok. ?>[label]" value="<?php echo esc_attr( $values['label'] ); ?>" class="s-item-val text show-on-label" placeholder="Label">

										<div class="show-on-color s-item-val">
											<input name="<?php echo $input_name; // WPCS: XSS ok. ?>[color]" value="<?php echo esc_attr( $values['color'] ); ?>"  class=" color" placeholder="Color1">
											<input name="<?php echo $input_name; // WPCS: XSS ok. ?>[color2]" value="<?php echo esc_attr( $values['color2'] ); ?>"  class=" color" placeholder="Color2">
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			</div> 
			<?php } ?>

		</div>
		</div>

		<script type="text/html" id="tmpl-savp_swashes-row">
			<div class="savp-row savp-s-row-item" data-tax="{{ data.tax }}" data-current="">
				<div class="savp-head">
					<div class="savp-title">
						<span>{{ data.label }}</span>
					</div>
					<select name="_savp_swatches[{{ data.tax }}][type]" class="current-type">
							<option value=""><?php esc_attr_e( 'Global', 'savp' ); ?></option>
							<?php foreach ( wc_get_attribute_types() as $k => $label ) { ?>
								<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php } ?>
					</select>
					<div class="savp-actions">
						<span class="savp-toggle" ><span class="dashicons dashicons-arrow-down"></span></span>
						
					</div>
				</div>
				<div class="savp-body">
				<div class="savp-more">
						<label>
							<input type="checkbox" class="savp_display-type" value="yes" name="_savp_swatches[{{ data.tax }}][display]" />
							<?php esc_html_e( 'Display list', 'savp' ); ?>
						</label>

						<label class="more-item show-on-list">
							<input type="checkbox" value="yes" name="_savp_swatches[{{ data.tax }}][price]" />
							<?php esc_html_e( 'Show Price', 'savp' ); ?>
						</label>

						<label class="more-item show-on-list">
							<input type="checkbox" value="yes" name="_savp_swatches[{{ data.tax }}][stock_status]" />
							<?php esc_html_e( 'Show stock status', 'savp' ); ?>
						</label>

					</div>
					<div class="attr-terms"></div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-savp_swashes-item">
			<div class="savp-row savp-s--item" data-term="{{data.name}}">
				<div class="savp-si-item">
					<span class="s-label">{{data.label}}</span>
					<div class="s-inputs">
						<span class="s-item-val image show-on-image">
							<input type="hidden"  class="image_id" name="_savp_swatches_custom[{{ data.tax }}][{{ data.name }}][image]">
							<img src="<?php echo WC()->plugin_url() . '/assets/images/placeholder.png'; ?>">
						</span>
						<input name="_savp_swatches_custom[{{ data.tax }}][{{ data.name }}][label]" class="s-item-val text show-on-label" placeholder="Label">
						<div class="s-item-val show-on-color">
							<input name="_savp_swatches_custom[{{ data.tax }}][{{ data.name }}][color]" class=" color" placeholder="Color1">
							<input name="_savp_swatches_custom[{{ data.tax }}][{{ data.name }}][color2]" class="color" placeholder="Color2">
						</div>
					</div>
				</div>
			</div>
		</script>


		<?php
	}

	/*
	* Renders the meta box.
	*/
	public function video_form( $post ) {
		// Add nonce for security and authentication.
		$value = get_post_meta( $post->ID, '_savp_video', true );
		$attachment_id = get_post_meta( $post->ID, '_savp_video_thumbnail', true );
		?>
		<p>
		<label>
		<?php esc_html_e( 'Paste video URL or embed code here.', 'savp' ); ?><br/>
			<input type="text" class="large-text" id="_savp_video" name="_savp_video" value="<?php echo esc_textarea( $value ); ?>">
		</label>
		</p>
		<button id="savp_add_video" type="button"><?php _e( 'Select video', 'savp' ); ?></button>
		<hr/>
		<div class="form-field savp_images_wrap form-row form-row-full">
			<label><?php esc_html_e( 'Video Thumbnail', 'savp' ); ?></label>
			<ul class="savp_images">
			<?php
			$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );
			if ( $attachment ) {
				?>
					<li class="image" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
						<?php echo $attachment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<ul class="actions">
							<li><a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Delete image', 'savp' ); ?>"><?php esc_html_e( 'Delete', 'savp' ); ?></a></li>
						</ul>
						
					</li>
					<?php } ?>
				</ul>
	
				<input type="hidden" class="savp_gallery_ids" value="<?php echo esc_attr( $attachment_id ); ?>"  name="_savp_video_thumbnail">
				<a href="#" class="savp_add_images" data-multiple="false" data-choose="<?php esc_attr_e( 'Video Thumbnail', 'savp' ); ?>" data-update="<?php esc_attr_e( 'Add video thumbnail', 'savp' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'savp' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'savp' ); ?>">
				<?php esc_html_e( 'Select video thumbnail', 'savp' ); ?></a>
		</div>
			<?php
	}



	/**
	 * Handles saving the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public function save( $post_id, $post ) {
		// Add nonce for security and authentication.
		$nonce_value  = isset( $_POST['savp_nonce'] ) ? sanitize_text_field( $_POST['savp_nonce'] ) : ''; // WPCS: XSS ok.

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_value, 'savp_action' ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$video = isset( $_POST['_savp_video'] ) ? wp_kses_post( $_POST['_savp_video'] ) : ''; // WPCS: XSS ok.
		update_post_meta( $post_id, '_savp_video', $video );

		$thumb_id = isset( $_POST['_savp_video_thumbnail'] ) ? absint( $_POST['_savp_video_thumbnail'] ) : ''; // WPCS: XSS ok.
		update_post_meta( $post_id, '_savp_video_thumbnail', $thumb_id );

		$swatches_archive = isset( $_POST['_savp_swatches_archive'] ) ? wc_clean( $_POST['_savp_swatches_archive'] ) : ''; // WPCS: XSS ok.
		update_post_meta( $post_id, '_savp_swatches_archive', $swatches_archive );

		// _savp_swatches
		$swatches = isset( $_POST['_savp_swatches'] ) ? wc_clean( $_POST['_savp_swatches'] ) : array(); // WPCS: XSS ok.
		$swatches_custom = isset( $_POST['_savp_swatches_custom'] ) ? wc_clean( $_POST['_savp_swatches_custom'] ) : array(); // WPCS: XSS ok.

		$save_swatches = array();
		foreach ( $swatches as $k => $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'type' => '',
					'display' => '',
					'price' => '',
					'stock_status' => '',
				)
			);
			$save_swatches[ $k ] = $args;
			$save_swatches[ $k ]['options'] = array();
			if ( isset( $swatches_custom[ $k ] ) ) {
				$save_swatches[ $k ]['options'] = $swatches_custom[ $k ];
			}
		}

		update_post_meta( $post_id, '_savp_swatches', $save_swatches );

		// Bulk Gallery.
		$gallery_attrs = isset( $_POST['_savp_bulk_gallery_attrs'] ) ? wc_clean( $_POST['_savp_bulk_gallery_attrs'] ) : array(); // WPCS: XSS ok.
		$gallery_attr_values = isset( $_POST['_savp_bulk_gallery_images'] ) ? wc_clean( $_POST['_savp_bulk_gallery_images'] ) : array(); // WPCS: XSS ok.
		$save_gallery = array();
		foreach ( $gallery_attrs as $index => $args ) {
			$save_gallery[ $index ] = array();
			$save_gallery[ $index ]['attrs'] = $args;
			$save_gallery[ $index ]['images'] = isset( $gallery_attr_values[ $index ] ) ? $gallery_attr_values[ $index ] : '';
		}
		update_post_meta( $post_id, '_savp_bulk_gallery', $save_gallery );

	}
}


class SAVP_Media_Meta {
	public function __construct() {
		add_filter( 'attachment_fields_to_edit', array( $this, 'fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save' ), 10, 2 );
	}

	public function fields( $form_fields, $post ) {

		$form_fields['savp_video'] = array(
			'label' => esc_html__( 'Video', 'savp' ),
			'input' => 'textarea',
			'required' => false,
			'value' => get_post_meta( $post->ID, '_savp_video', true ),
			'helps' => esc_html__( 'Enter video URL or paste embed code here.', 'savp' ),
		);

		return $form_fields;
	}

	public function save( $post, $attachment ) {

		if ( isset( $attachment['savp_video'] ) ) {
			update_post_meta( $post['ID'], '_savp_video', esc_textarea( $attachment['savp_video'] ) );
		}

		return $post;
	}


}

new SAVP_Meta_Box();
new SAVP_Media_Meta();









