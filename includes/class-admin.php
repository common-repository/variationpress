<?php

/**
 * Class SAVP_Admin
 */
class SAVP_Admin {
	/**
	 * The single instance of the class
	 *
	 * @var SAVP_Admin
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return SAVP_Admin
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->includes();
		add_action( 'admin_init', array( $this, 'init_attribute_hooks' ) );
		add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );

		// Restore attributes.
		add_action( 'admin_notices', array( $this, 'restore_attributes_notice' ) );
		add_action( 'admin_init', array( $this, 'restore_attribute_types' ) );

		// Display attribute fields.
		add_action( 'savp_product_attribute_field', array( $this, 'attribute_fields' ), 10, 3 );
		add_action( 'woocommerce_after_edit_attribute_fields', array( $this, 'attribute_meta_fields' ), 10, 3 );
		add_action( 'admin_head', array( $this, 'save_meta_fields' ), 10, 3 );

	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		require_once SAVP__PATH . '/includes/class-admin-product.php';
		require_once SAVP__PATH . '/includes/class-variation-metabox.php';
		require_once SAVP__PATH . '/includes/class-product-meta.php';
		require_once SAVP__PATH . '/includes/class-settings.php';
		require_once SAVP__PATH . '/includes/convert-attr.php';
	}

	public function save_meta_fields() {
			// Action to perform: add, edit, delete or none.
		if ( ! isset( $_POST['save_attribute'] ) || ! isset($_GET['edit'] ) ) {
			return;
		}

		// Sanitize bellow.
		$meta = isset( $_POST['attribute_meta'] ) ? wc_clean( $_POST['attribute_meta'] ) : array(); // WPCS: XSS ok.
		foreach( $meta as $k => $v ) {
			$meta[ $k ] = sanitize_text_field( $v );
		}
		$attribute_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		global $wpdb;
		$table_name = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
		$wpdb->update( $table_name, 
			array( 'savp_meta' => wp_json_encode($meta) ), 
			array( 'attribute_id' => $attribute_id ) 
		); // WPCS: db call ok.

	}

	/**
	 * @see /wp-content/plugins/woocommerce/includes/admin/class-wc-admin-attributes.php
	 *
	 * @return void
	 */
	public function attribute_meta_fields() {
		$edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		if ( ! $edit ) {
			return ;
		}
		$data = SAVP_Main::get_wc_attribute_by_id( $edit );
		$meta = array();
		if ( $data ) {
			$meta  = $data->savp_meta;
		}
		$meta = wp_parse_args(
			$meta,
			array(
				'display' => '',
				'price' => '',
				'stock_status' => '',
			)
		);

		?>
		<tr class="form-field form-required">
				<th scope="row" valign="top">
					<label for="attribute_meta_display"><?php esc_html_e( 'Display', 'savp' ); ?></label>
				</th>
				<td>
					<select name="attribute_meta[display]" id="attribute_meta_display">
						<option value="default" <?php selected( $meta['display'], 'default' ); ?>><?php esc_html_e( 'Default', 'savp' ); ?></option>
						<option value="list" <?php selected( $meta['display'], 'list' ); ?>><?php esc_html_e( 'List', 'savp' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="form-field form-required show_on_att_meta_display_list">
				<th scope="row" valign="top">
					<label for="attribute_meta_show_price"><?php esc_html_e( 'Show Price', 'savp' ); ?></label>
				</th>
				<td>
					<select name="attribute_meta[price]" id="attribute_meta_show_price">
						<option value="no" <?php selected( $meta['price'], 'no' ); ?>><?php esc_html_e( 'No', 'savp' ); ?></option>
						<option value="yes" <?php selected( $meta['price'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'savp' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="form-field form-required show_on_att_meta_display_list">
				<th scope="row" valign="top">
					<label for="attribute_meta_stock_status"><?php esc_html_e( 'Show Stock Status', 'savp' ); ?></label>
				</th>
				<td>
					<select name="attribute_meta[stock_status]" id="attribute_meta_stock_status">
						<option value="no" <?php selected( $meta['stock_status'], 'no' ); ?>><?php esc_html_e( 'No', 'savp' ); ?></option>
						<option value="yes" <?php selected( $meta['stock_status'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'savp' ); ?></option>
					</select>


					<script>
						jQuery( function( $ ) {
							$( '.show_on_att_meta_display_list' ).hide();
							$( '#attribute_meta_display' ).on( 'change', function(){
								var v =  $( this).val();
								if ( 'list' === v ) {
									$( '.show_on_att_meta_display_list' ).show();
								} else {
									$( '.show_on_att_meta_display_list' ).hide();
								}
							} );
							$( '#attribute_meta_display' ).trigger( 'change' );
						} );
					</script>
				</td>
			</tr>
		<?php
	}

	/**
	 * Init hooks for adding fields to attribute screen
	 * Save new term meta
	 * Add thumbnail column for attribute term
	 */
	public function init_attribute_hooks() {
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( empty( $attribute_taxonomies ) ) {
			return;
		}

		foreach ( $attribute_taxonomies as $tax ) {
			add_action( 'pa_' . $tax->attribute_name . '_add_form_fields', array( $this, 'add_attribute_fields' ) );
			add_action( 'pa_' . $tax->attribute_name . '_edit_form_fields', array( $this, 'edit_attribute_fields' ), 10, 2 );

			add_filter( 'manage_edit-pa_' . $tax->attribute_name . '_columns', array( $this, 'add_attribute_columns' ) );
			add_filter( 'manage_pa_' . $tax->attribute_name . '_custom_column', array( $this, 'add_attribute_column_content' ), 10, 3 );
		}

		add_action( 'created_term', array( $this, 'save_term_meta' ), 10, 2 );
		add_action( 'edit_term', array( $this, 'save_term_meta' ), 10, 2 );
	}

	/**
	 * Load stylesheet and scripts in edit product attribute screen
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		
		if ( 'woocommerce_page_wc-settings' == $screen->id ) {
			wp_enqueue_script( 'savp_admin_settings', SAVP__URL . '/assets/js/admin-settings.js', array( 'jquery' ), SAVP__VERSION, true );
			return;
		}

		if ( strpos( $screen->id, 'edit-pa_' ) === false && strpos( $screen->id, 'product' ) === false ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( 'savp_admin', SAVP__URL . '/assets/css/admin.css', array( 'wp-color-picker' ), SAVP__VERSION );
		wp_enqueue_script( 'savp_admin', SAVP__URL . '/assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'wp-util' ), SAVP__VERSION, true );

		wp_localize_script(
			'savp_admin',
			'savp_admin_args',
			array(
				'i18n'        => array(
					'mediaTitle'  => esc_html__( 'Choose an image', 'savp' ),
					'mediaButton' => esc_html__( 'Use image', 'savp' ),
					'any' => esc_html__( 'Any', 'savp' ),
					'any_format' => esc_html__( 'Any %s', 'savp' ),
					'confirm_set_thumb' => esc_html__( 'Set first image for variation thumbnail. This action will update thumbnail for all variations which matching attribute pairs. Are you sure ?', 'savp' ),
					'variation_updated' => esc_html__( 'Variation thumbnails updated.', 'savp' ),
				),
				'placeholder' => WC()->plugin_url() . '/assets/images/placeholder.png',
			
			)
		);
	}

	/**
	 * Display a notice of restoring attribute types
	 */
	public function restore_attributes_notice() {
		if ( get_transient( 'savp_attribute_taxonomies' ) && ! get_option( 'savp_restore_attributes_time' ) ) {
			?>
			<div class="notice-warning notice is-dismissible">
				<p>
					<?php
					esc_html_e( 'Found a backup of product attributes types. This backup was generated at', 'savp' );
					echo ' ' . date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), get_option( 'savp_backup_attributes_time' ) ) . '.';
					?>
				</p>
				<p>
					<a href="<?php echo esc_url(
						add_query_arg(
							array(
								'savp_action' => 'restore_attributes_types',
								'savp_nonce' => wp_create_nonce( 'restore_attributes_types' ),
							)
						)
							 ); ?>">
						<strong><?php esc_html_e( 'Restore product attributes types', 'savp' ); ?></strong>
					</a>
					|
					<a href="<?php echo esc_url(
						add_query_arg(
							array(
								'savp_action' => 'dismiss_restore_notice',
								'savp_nonce' => wp_create_nonce( 'dismiss_restore_notice' ),
							)
						)
                    ); ?>">
						<strong><?php esc_html_e( 'Dismiss this notice', 'savp' ); ?></strong>
					</a>
				</p>
			</div>
			<?php
		} elseif ( isset( $_GET['savp_message'] ) && 'restored' == $_GET['savp_message'] ) {
			?>
			<div class="notice-warning settings-error notice is-dismissible">
				<p><?php esc_html_e( 'All attributes types have been restored.', 'savp' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Restore attribute types
	 */
	public function restore_attribute_types() {
		if ( ! isset( $_GET['savp_action'] ) || ! isset( $_GET['savp_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['savp_nonce'], $_GET['savp_action'] ) ) {
			return;
		}

		if ( 'restore_attributes_types' == $_GET['savp_action'] ) {
			global $wpdb;

			$attribute_taxnomies = get_transient( 'savp_attribute_taxonomies' );

			foreach ( $attribute_taxnomies as $id => $attribute ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_attribute_taxonomies',
					array( 'attribute_type' => $attribute->attribute_type ),
					array( 'attribute_id' => $id ),
					array( '%s' ),
					array( '%d' )
				); // WPCS: db call ok.
			}

			update_option( 'savp_restore_attributes_time', time() );
			delete_transient( 'savp_attribute_taxonomies' );
			delete_transient( 'wc_attribute_taxonomies' );

			$url = remove_query_arg( array( 'savp_action', 'savp_nonce' ) );
			$url = add_query_arg( array( 'savp_message' => 'restored' ), $url );
		} elseif ( 'dismiss_restore_notice' == $_GET['savp_action'] ) {
			update_option( 'savp_restore_attributes_time', 'ignored' );
			$url = remove_query_arg( array( 'savp_action', 'savp_nonce' ) );
		}

		if ( isset( $url ) ) {
			wp_redirect( $url );
			exit;
		}
	}

	/**
	 * Create hook to add fields to add attribute term screen
	 *
	 * @param string $taxonomy
	 */
	public function add_attribute_fields( $taxonomy ) {
		$attr = savp()->get_tax_attribute( $taxonomy );

		do_action( 'savp_product_attribute_field', $attr->attribute_type, '', 'add' );
	}

	/**
	 * Create hook to fields to edit attribute term screen
	 *
	 * @param object $term
	 * @param string $taxonomy
	 */
	public function edit_attribute_fields( $term, $taxonomy ) {
		$attr  = savp()->get_tax_attribute( $taxonomy );
		$value = get_term_meta( $term->term_id, $attr->attribute_type, true );
		if ( 'color' == $attr->attribute_type ) {
			$value2 = get_term_meta( $term->term_id, $attr->attribute_type . '_2', true );
			$values = [
				$value,
				$value2,
			];
			do_action( 'savp_product_attribute_field', $attr->attribute_type, $values, 'edit' );
		} else {
			do_action( 'savp_product_attribute_field', $attr->attribute_type, $value, 'edit' );
		}
	}

	/**
	 * Print HTML of custom fields on attribute term screens
	 *
	 * @param $type
	 * @param $value
	 * @param $form
	 */
	public function attribute_fields( $type, $value, $form ) {
		// Return if this is a default attribute type
		if ( in_array( $type, array( 'select', 'text', 'radio' ) ) ) {
			return;
		}

		// Print the open tag of field container.
		printf(
			'<%s class="form-field">%s<label for="term-%s">%s</label>%s',
			'edit' == $form ? 'tr' : 'div',
			'edit' == $form ? '<th>' : '',
			esc_attr( $type ),
			savp()->types[ $type ],
			'edit' == $form ? '</th><td>' : ''
		);

		switch ( $type ) {
			case 'image':
			case 'thumbnail':
				$image = $value ? wp_get_attachment_image_src( $value ) : '';
				$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
				?>
				<div class="savp-image-wrapper">
					<div class="savp_term-image-thumbnail" class="savp-left savp_mg-l-10">
						<img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" />
					</div>
					<div class="savp-line-h60">
						<input type="hidden" class="savp_term-image" name="image" value="<?php echo esc_attr( $value ); ?>" />
						<button type="button" class="savp_upload-image-button button"><?php esc_html_e( 'Upload/Add image', 'savp' ); ?></button>
						<button type="button" class="savp_remove-image-button button <?php echo $value ? '' : 'hidden'; ?>"><?php esc_html_e( 'Remove image', 'savp' ); ?></button>
					</div>
				</div>
				
				<?php
				break;

			case 'color':
				if ( ! is_array( $value ) ) {
					$value = array( $value );
					$value = wp_parse_args(
						$value,
						array(
							0 => '',
							1 => '',
						)
					);
				}
				?>
				<div>
					<?php esc_html_e( 'Primary', 'savp' ); ?><br/><input type="text" class="term-color" id="term-<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $value[0] ); ?>" />
				</div>
				<div>
					<?php esc_html_e( 'Secondary', 'savp' ); ?><br/><input type="text" class="term-color" id="term-<?php echo esc_attr( $type ); ?>_2" name="<?php echo esc_attr( $type ); ?>_2" value="<?php echo esc_attr( $value[1] ); ?>" />
				</div>
				<?php
				break;
		}

		// Print the close tag of field container.
		echo 'edit' == $form ? '</td></tr>' : '</div>';
	}

	/**
	 * Save term meta
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 */
	public function save_term_meta( $term_id, $tt_id ) {
		foreach ( savp()->types as $type => $label ) {
			if ( isset( $_POST[ $type ] ) ) {
				update_term_meta( $term_id, $type, sanitize_text_field( $_POST[ $type ] ) ); // WPCS: XSS ok.
			}

			if ( 'color' == $type && isset( $_POST[ $type . '_2' ] ) ) {
				update_term_meta( $term_id, $type . '_2', sanitize_text_field( $_POST[ $type . '_2' ] ) ); // WPCS: XSS ok.
			}
		}
	}

	/**
	 * Add thumbnail column to column list
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_attribute_columns( $columns ) {
		if ( ! isset( $columns['cb'] ) ) {
			return $columns;
		}
		$new_columns          = array();
		$new_columns['cb']    = $columns['cb'];
		$new_columns['thumb'] = '';
		unset( $columns['cb'] );
		return array_merge( $new_columns, $columns );
	}

	/**
	 * Render thumbnail HTML depend on attribute type
	 *
	 * @param $columns
	 * @param $column
	 * @param $term_id
	 */
	public function add_attribute_column_content( $columns, $column, $term_id ) {
		if ( 'thumb' !== $column ) {
			return $columns;
		}

		$attr  = savp()->get_tax_attribute( $_REQUEST['taxonomy'] );
		$value = get_term_meta( $term_id, $attr->attribute_type, true );

		switch ( $attr->attribute_type ) {
			case 'color':
				$value2 = get_term_meta( $term_id, $attr->attribute_type . '_2', true );
				$value = sanitize_hex_color( $value );
				$value2 = sanitize_hex_color( $value2 );
				$color_value = $value;
				if ( $value && $value2 ) {
					$color_value = 'linear-gradient(-45deg, ' . $value . ' 0%, ' . $value . ' 50%, ' . $value2 . ' 50%,' . $value2 . ' 100%)';  // WPCS: XSS ok.
				}
				printf( '<div class="swatch-preview swatch-color" style="background:%s;"></div>', $color_value ); // WPCS: XSS ok.
				break;

			case 'image':
			case 'thumbnail':
				$image = $value ? wp_get_attachment_image_src( $value ) : '';
				$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
				printf( '<img class="swatch-preview swatch-image" src="%s" width="44px" height="44px">', esc_url( $image ) );
				break;

			case 'label':
				printf( '<div class="swatch-preview swatch-label">%s</div>', esc_html( $value ) );
				break;
		}
	}
}
