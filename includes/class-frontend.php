<?php

/**
 * Class SAVP_Frontend.
 */
class SAVP_Frontend {
	/**
	 * The single instance of the class.
	 *
	 * @var SAVP_Frontend
	 */
	protected static $instance = null;

	public $all_attributes = [];
	public $all_product_swatches = [];
	public $product_attributes = [];
	public $product_variations = [];
	public $product_gallies = [];
	public $widget_cache_taxs = [];

	public $gallery_single_settings = null;
	public $gallery_loop_settings = null;
	public $variation_settings = null;
	public $in_sidebar = false;
	public $theme_name = false;

	public $loop_sizes = array(
		'variation' => 'woocommerce_gallery_thumbnail',
		'thumb' => 'woocommerce_thumbnail',
	);

	public $gallery_sizes = array();

	public $attr_thumb_size = 'woocommerce_gallery_thumbnail';

	public function responsive_parse_number( $string, $default = 1 ) {
		$array = explode( '-', $string );
		if ( ! isset( $array[1] ) ) {
			$array[1] = 0;
		}
		if ( ! isset( $array[2] ) ) {
			$array[2] = 0;
		}
		$array[0] = intval( $array[0] );
		$array[1] = intval( $array[1] );
		$array[2] = intval( $array[2] );
		if ( $array[0] <= 0 ) {
			$array[0] = $default;
		}
		if ( $array[1] <= 0 ) {
			$array[1] = $array[0];
		}

		if ( $array[2] <= 0 ) {
			$array[2] = $array[0];
		}
		return $array;
	}

	public function get_gallery_single_settings() {
		if ( ! is_null( $this->gallery_single_settings ) ) {
			return $this->gallery_single_settings;
		}
		$n_slides = sanitize_text_field( $this->get_settings( 'savp_gallery_n_slide', '1' ) );
		if ( $n_slides && $n_slides != 0 ) {
			$n_slides = $this->responsive_parse_number( $n_slides, 1 );
		} else {
			$n_slides = [ 'auto', 'auto', 'auto' ];
		}

		$n_thumb = sanitize_text_field( $this->get_settings( 'savp_gallery_n_thumb', '5' ) );
		if ( ! $n_thumb || $n_thumb == 0 ) {
			$n_thumb = [ 'auto', 'auto', 'auto' ];
		} else {
			$n_thumb = $this->responsive_parse_number( $n_thumb, 5 );
		}

		$this->gallery_single_settings = array(
			'gallery_type' => $this->get_settings( 'savp_gallery_type', 'slider' ),
			'nav_pos' => $this->get_settings( 'savp_gallery_nav_pos', 'left' ),
			'nav_size' => $this->get_settings( 'savp_gallery_nav_size', 'variation' ),
			'zoom' => $this->get_settings( 'woocommerce_single_gallery_zoom', 1 ),
			'n_slide' => $n_slides[0],
			'n_slide_md' => $n_slides[1],
			'n_slide_sm' => $n_slides[2],
			'n_thumb_md' => $n_thumb[1],
			'n_thumb_sm' => $n_thumb[2],
			'n_thumb' => $n_thumb[0],
			'nav_width' => intval( $this->get_settings( 'savp_gallery_nav_width', 50 ) ),
			'nav_height' => intval( $this->get_settings( 'savp_gallery_nav_height', 50 ) ),
			'video_post' => sanitize_text_field( $this->get_settings( 'savp_gallery_video_pos', '2' ) ),
			'var_only' => sanitize_text_field( $this->get_settings( 'savp_gallery_var_only', 'yes' ) ),
			'spacing' => intval( $this->get_settings( 'savp_gallery_spacing', 15 ) ),
			'md' => intval( $this->get_settings( 'savp_gallery_md', 768 ) ),
			'sm' => intval( $this->get_settings( 'savp_gallery_sm', 500 ) ),
			'nav_md' => sanitize_text_field( $this->get_settings( 'savp_gallery_nav_md', 'hide' ) ),
			'nav_sm' => sanitize_text_field( $this->get_settings( 'savp_gallery_nav_sm', 'hide' ) ),
		);

		$this->gallery_single_settings = apply_filters( 'savp_get_gallery_single_settings', $this->gallery_single_settings );
		return $this->gallery_single_settings;
	}

	public function get_gallery_loop_settings() {
		if ( ! is_null( $this->gallery_loop_settings ) ) {
			return $this->gallery_loop_settings;
		}

		$this->gallery_loop_settings = array(
			'nav_pos' => $this->get_settings( 'savp_gallery_nav_pos', 'left' ),
			'n_slide' => $this->get_settings( 'savp_gallery_n_slide', 1 ),
			'n_thumb' => $this->get_settings( 'savp_gallery_n_thumb', 1 ),
			'nav_width' => $this->get_settings( 'savp_gallery_nav_width', 50 ),
			'nav_height' => $this->get_settings( 'savp_gallery_nav_height', 50 ),
			'spacing' => $this->get_settings( 'savp_gallery_spacing', 6 ),
			'video_post' => sanitize_text_field( $this->get_settings( 'savp_gallery_video_pos', '2' ) ),
			'var_only' => sanitize_text_field( $this->get_settings( 'savp_gallery_var_only', 50 ) ),
		);
		$this->gallery_loop_settings = apply_filters( 'savp_get_gallery_loop_settings', $this->gallery_loop_settings );
		return $this->gallery_loop_settings;
	}

	public function get_settings( $key, $default = '' ) {
		if ( function_exists( 'onestore_get_theme_mod' ) ) {
			return onestore_get_theme_mod( $key, $default );
		}
		return get_theme_mod( $key, $default );
	}


	public function get_variation_settings() {
		if ( ! is_null( $this->variation_settings ) ) {
			return $this->variation_settings;
		}

		$this->variation_settings = array(
			'canvas' => sanitize_text_field( $this->get_settings( 'savp_single_canvas', 'no' ) ),
			't_limit' => intval( $this->get_settings( 'savp_t_limit', 4 ) ),
			'behavior' => sanitize_text_field( $this->get_settings( 'savp_attr_behavior', 'blur-cross' ) ),
			'behavior_singular' => sanitize_text_field( $this->get_settings( 'savp_attr_behavior_singular', 'blur-cross' ) ),
			'border_width' => sanitize_text_field( $this->get_settings( 'savp_border_width', 2 ) ),
			'border_color' => sanitize_text_field( $this->get_settings( 'savp_border_color', '' ) ),
			'border_active_color' => sanitize_text_field( $this->get_settings( 'savp_border_active_color', '' ) ),
			'show_archive' => sanitize_text_field( $this->get_settings( 'savp_show_on_archive' ), false ),
			'loop_hook' => sanitize_text_field( $this->get_settings( 'savp_loop_hook', 'woocommerce_after_shop_loop_item' ) ),
		);

		foreach ( wc_get_attribute_types() as $k => $label ) {
			$single_size_o = sanitize_text_field( $this->get_settings( 'savp_single_size_' . $k ) );
			$size = sanitize_text_field( $this->get_settings( 'savp_size_' . $k ) );
			$this->variation_settings[ 'size_' . $k ] = $size;
			$this->variation_settings[ 'single_size_' . $k ] = $single_size_o;
		}
		$this->variation_settings = apply_filters( 'savp_get_variation_settings', $this->variation_settings );

		return $this->variation_settings;
	}


	/**
	 * Main instance
	 *
	 * @return SAVP_Frontend
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
		$this->theme_name = basename( get_template_directory() );
		$this->gallery_sizes = SAVP_Main::get_gallery_sizes();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 33 );
		add_action( 'wp_footer', array( $this, 'footer' ) );

		add_filter( 'woocommerce_product_get_image', array( $this, 'woocommerce_product_get_image' ), 15, 5 );

		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woocommerce_single_product_image_thumbnail_html' ), 15, 5 );

		remove_all_actions( 'woocommerce_product_thumbnails' );

		add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_false', 80 );
		add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false', 80 );
		add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false', 80 );

		// Disable variations and gallery when....
		add_action( 'dynamic_sidebar_before', array( $this, 'dynamic_sidebar_before' ), 1 );
		add_action( 'woocommerce_before_cart_contents', array( $this, 'dynamic_sidebar_before' ), 1 );
		add_action( 'dynamic_sidebar_after', array( $this, 'dynamic_sidebar_after' ), 80 );
		add_action( 'woocommerce_cart_contents', array( $this, 'dynamic_sidebar_after' ), 80 );
		add_action( 'woocommerce_before_mini_cart', array( $this, 'dynamic_sidebar_before' ), 80 );

		// Ajax load data.
		add_action( 'wp_ajax_savp_load_variations', array( $this, 'ajax_load_variations' ) );
		add_action( 'wp_ajax_nopriv_savp_load_variations', array( $this, 'ajax_load_variations' ) );

		// Widget fillter li.
		// echo apply_filters( 'woocommerce_layered_nav_term_html', $term_html, $term, $link, $count );
		add_action( 'woocommerce_layered_nav_term_html', array( $this, 'widget_filter_li' ), 30, 4 );

	}

	public function widget_filter_li( $term_html, $term, $link, $count ) {
		$tax_meta = false;
		if ( ! isset( $this->widget_cache_taxs[ $term->taxonomy ] ) ) {
			$tax_meta = savp()->get_tax_attribute( $term->taxonomy );
			$this->widget_cache_taxs[ $term->taxonomy ] = $tax_meta;
		} else {
			$tax_meta = $this->widget_cache_taxs[ $term->taxonomy ];
		}

		if ( ! $tax_meta ) {
			return $term_html;
		}
		$term_settings = $this->get_term_settings( $term, $tax_meta->attribute_type );

		if ( $term_settings['_html'] ) {
			if ( $count > 0 ) {
					$term_html = '<a rel="nofollow" title="' . esc_attr( $term->name ) . '" href="' . esc_url( $link ) . '">' . $term_settings['_html'] . '</a>';
			} else {
				$link      = false;
				$term_html = '<span>' . $term_settings['_html'] . '</span>'; // WPCS: XSS ok.
			}
			$term_html .= ' ' . apply_filters( 'woocommerce_layered_nav_count', '<span class="count">(' . absint( $count ) . ')</span>', $count, $term );
		}

		$term_html = '<div class="v-item-inner vt--' . esc_attr( $tax_meta->attribute_type ) . '">' . $term_html . '</div>';

		return $term_html;
	}



	public function ajax_load_variations() {

		$ids = wc_clean( $_REQUEST['ids'] );
		$single_ids = wc_clean( $_REQUEST['single_ids'] );
		$ids = explode( ',', $ids );
		$single_ids = explode( ',', $single_ids );

		$products = wc_get_products(
			array(
				'include' => $ids,
				'posts_per_page' => 50,
			)
		);

		global $product;
		foreach ( $products as $product ) {
			$in_the_loop  = true;
			if ( in_array( $product->get_id(), $single_ids ) ) {
				$in_the_loop  = false;
			}
			$this->setup_tax_attr_data( $in_the_loop );
		}

		$this->get_variation_settings();

		$data = array();

		$data['savp'] = array(
			'savp_l10n' => $this->l10n(),
			'savp_swatches' => $this->all_product_swatches,
			'savp_variation_settings' => $this->variation_settings,
			'savp_product_variations' => $this->product_variations,
			'savp_product_gallies' => $this->product_gallies,
			'savp_gallery_settings' => array(
				'single' => $this->get_gallery_single_settings(),
				'loop' => $this->get_gallery_loop_settings(),
			),
		);
		wp_send_json( $data );
		die();

	}

	public function set_in_sidebar( $set = true ) {
		$this->in_sidebar = $set;
	}

	public function dynamic_sidebar_after() {
		$this->in_sidebar = false;
	}
	public function dynamic_sidebar_before() {
		$this->in_sidebar = true;
	}

	public function loop_variations() {
		$template = SAVP__PATH . '/templates/loop.php';
		include $template;
	}

	public function woocommerce_single_product_image_thumbnail_html( $html, $post_thumbnail_id ) {
		global $product;
		$class = 'savp_gallery savp_single-gallery loading';
		if ( $product->is_type( 'variable' ) ) {
			$class .= ' savp-variable';
		} else {
			$class .= ' savp-default';
		}
		$html = '<div class="savp-gallery-wrap ">
		<div class="' . $class . '" data-single="true"  data-id="' . $product->get_id() . '">' . $html . '</div>
		</div>';
		return $html;
	}

	public function merge_variation_attrs( $attrs, $add_attrs, $type = '' ) {
		if ( '' == $attrs ) {
			$attrs = array( '_any_' => '_any_' ); // Empty mean all attributes.
			return $attrs;
		}

		if ( '' == $add_attrs ) {
			$add_attrs = array( '_any_' => '_any_' ); // Empty mean all attributes.
		}

		foreach ( $add_attrs as $k => $v ) {
			if ( ! isset( $attrs[ $k ] ) ) {
				$attrs[ $k ] = array();
			}
			if ( ! $v ) {
				$v = '_any_';
			}
			$attrs[ $k ][ $v ] = $v;
		}

		if ( 'bulk' == $type ) {
			unset( $attrs['_any_'] );
		}

		return $attrs;
	}

	public function sanitize_gallery_attrs( $gallery_attrs ) {
		$attrs = array();
		foreach ( $gallery_attrs as $k => $v ) {
			$k = 'attribute_' . strtolower( $k );
			$attrs[ $k ] = $v;
		}
		return $attrs;
	}

	public function get_product_gallery_array( $product, $type = 'single' ) {

		$wc_images = $product->get_gallery_image_ids();
		$thumbnail_id = $product->get_image_id();

		if ( $product->get_type() == 'variable' ) {
			$variations = $this->get_variations( $product );
		} else {
			$variations = array();
		}

		$gallery_images = array();
		$bulk_gallery = $product->get_meta( '_savp_bulk_gallery', true );
		if ( ! is_array( $bulk_gallery ) ) {
			$bulk_gallery = array();
		}

		$variation_html = '';
		// Variation images.
		foreach ( $variations as $variation ) {

			$variation = (object) $variation;
			$variation_id = $variation->variation_id;
			$v_attrs = $variation->attributes;
			// For variation thumbnail.
			if ( isset( $variation->image_id ) && $variation->image_id > 0 ) {

				if ( $thumbnail_id != $variation->image_id ) {
					$image = $this->get_image_data_2( $variation->image_id, $type );
					if ( $image ) {
						$gallery_images[ '_v' . $variation_id ] = $image;
					}
				}
			} // end if.
			// For variation gallery.
			$v_images = $this->string_to_numbers( get_post_meta( $variation->variation_id, '_savp_gallery', true ) );
			if ( $v_images && ! empty( $v_images ) ) {
				$gallery_images[ '_' . $variation->variation_id ] = array();
				foreach ( $v_images as $img_id ) {

					$image = $this->get_image_data_2( $img_id, $type );
					if ( $image ) {
						$gallery_images[ '_vg' . $variation->variation_id ][] = $image;
					}
				} // End for variation gallery and thumbnail.
			} //End check if has variation gallery.
		} // End loop variation

		// For Bulk gallery.
		$gallery_images['bulk'] = [];
		$bulk_images = [];
		foreach ( $bulk_gallery as $bulk_item_gallery ) {
			if ( ! isset( $bulk_item_gallery['attrs'] ) ) {
				continue;
			}
			$match = true;
			$gallery_attrs = $this->sanitize_gallery_attrs( $bulk_item_gallery['attrs'] );
			$bulk_gallery_image_ids = $this->string_to_numbers( $bulk_item_gallery['images'] );
			$bulk_gallery_images = [];
			if ( count( $bulk_gallery_image_ids ) ) {
				foreach ( $bulk_gallery_image_ids as $img_id ) {
					if ( ! isset( $items[ $img_id ] ) ) {
						$image = $this->get_image_data_2( $img_id, $type );
						if ( $image ) {
							$bulk_gallery_images[] = $image;
						}
					}
				}
			}
			$key = '_' . md5( wp_json_encode( $gallery_attrs ) );
			if ( count( $bulk_gallery_images ) ) {
				$gallery_images['bulk'][ $key ] = [
					'key' => $key,
					'attrs' => $gallery_attrs,
					'images' => $bulk_gallery_images,
				];
			}
		}  // End loop bulk gallery.

		// Product thumbnail.
		if ( $thumbnail_id ) {
			$image = $this->get_image_data_2( $thumbnail_id, $type );
			if ( $image ) {
				$gallery_images['thumb'] = $image;
			}

			// Video.
			$video = get_post_meta( $product->get_id(), '_savp_video', true );
			$video_thumb = get_post_meta( $product->get_id(), '_savp_video_thumbnail', true );
			if ( $video ) {
				global $wp_embed;
				$video_code = $wp_embed->autoembed( $video );
				$video_code = do_shortcode( $video_code );
				$image = $this->get_image_data_2( $video_thumb, $type );
				if ( ! $image ) {
					$image = array(
						'thumb' => array(
							0 => SAVP__URL . 'assets/images/video-play.svg',
						),
					);
				}
				$gallery_images['video'] = [
					'code' => $video_code,
					'thumb' => $image,
					'is_video' => true,
				];
			}

			// Gallery images.
			$gallery_images['gallery'] = [];
			if ( ! empty( $wc_images ) ) {
				foreach ( $wc_images as $id ) {
					$image = $this->get_image_data_2( $id, $type );
					if ( $image ) {
						$gallery_images['gallery'][] = $image;
					}
				}
			}
		}

		return $gallery_images;
	}

	public function woocommerce_single_product_image_gallery_classes( $classes ) {
		global $product;
		$classes[] = 'product-gallery-' . $product->get_id();
		return $classes;
	}

	public function woocommerce_product_get_image( $image, $product, $size, $attr, $placeholder ) {
		if ( $this->in_sidebar ) {
			return $image;
		}

		if ( $image ) {
			if ( 'variation' == $product->get_type() ) {
				$thumbnail_id = $product->get_image_id();
				if ( $thumbnail_id ) {
					$image = wp_get_attachment_image( $thumbnail_id, $size, false, $attr );
				}
			}
			$image = '<div class="savp-thumbnail loading" data-id="' . esc_attr( $product->get_id() ) . '">' . $image . '</div>';
		}

		return $image;
	}

	public function wc_get_template( $template, $template_name, $args, $template_path, $default_path ) {

		if ( 'single-product/product-image.php' == $template_name ) {
			$template = SAVP__PATH . '/templates/product-image.php';
		}
		return $template;
	}

	protected function string_to_numbers( $string ) {
		return SAVP_Main::string_to_numbers( $string );
	}

	public function load_variation_fields( $data, $product, $variation ) {
		$string_images = get_post_meta( $variation->get_id(), '_savp_gallery', true );
		$data['_savp_gallery'] = $this->string_to_numbers( $string_images );
		return $data;
	}

	public function get_image_data_2( $attachment_id, $type = '' ) {

		$size_options  = $this->gallery_sizes;
		switch ( $type ) {
			case 'loop':
				$size_options  = $this->loop_sizes;
				break;
		}

		$alt_text  = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
		$data = array(
			'title'    => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
			'caption'  => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
			'alt'      => $alt_text,
			'id'      => $attachment_id,
			'sizes' => [],
		);

		$has_img = false;
		$size_options['full'] = 'full';
		foreach ( $size_options as $k => $size_name ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size_name );
			$srcset = wp_get_attachment_image_srcset( $attachment_id, $size_name );
			if ( $image ) {
				$has_img = true;
			}
			$data['sizes'][ $k ] = [ $image, $srcset ];
		}

		if ( ! $has_img ) {
			return false;
		}

		return $data;
	}

	public function get_gallery_image_html( $attachment_id, $for_variant_id = 'all', $size = '' ) {
		$image = $this->get_image_data( $attachment_id, $size );
		return '<img src="' . esc_url( $image['thumb'] ) . '" 
		data-for="' . esc_attr( $for_variant_id ) . '" 
		alt="' . esc_attr( $image['alt'] ) . '" 
		data-caption="' . esc_url( $image['caption'] ) . '" 
		data-title="' . esc_url( $image['title'] ) . '"  
		data-src="' . esc_url( $image['src'] ) . '"  
		data-full="' . esc_url( $image['full_image'] ) . '" 
		data-fw="' . esc_attr( $image['full_image_width'] ) . '"
		data-fh="' . esc_attr( $image['full_image_height'] ) . '"
		class="savp__image" />';
	}

	public function get_term_settings( $term, $type, $thumb_as_image = false ) {
		$data = array(
			'_html' => '',
		);
		$item_class = 's-inner it-' . $type;
		switch ( $type ) {
			case 'color':
			case 'color_list':
				$color = get_term_meta( $term->term_id, 'color', true );
				$color2 = get_term_meta( $term->term_id, 'color_2', true );
				$data['_type_value'] = $color;
				$data['_type_value_2'] = $color2;
				$data['_html'] = '';
				$css = '';
				if ( $color && $color2 ) {
					$css = 'background: linear-gradient(-45deg, ' .
					$color .
					' 0%, ' .
					$color .
					' 50%, ' .
					$color2 .
					' 50%, ' .
					$color2 .
					' 100%);';
					$item_class .= ' dual-color';
				} else {
					$css = 'background-color: ' . $color . '; ';
				}

				$data['_html'] = '<div style="' . $css . '" class="' . $item_class . '"></div>'; // WPCS: XSS ok.

				break;
			case 'image':
			case 'image_list':
				$image = get_term_meta( $term->term_id, 'image', true );
				$image = $image ? wp_get_attachment_image_src( $image, $this->attr_thumb_size ) : '';
				$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
				$data['_type_value'] = $image;
				if ( $image ) {
					$item_class . ' has-img';
				}

				$data['_html'] = '<div class="' . $item_class . '"><img  alt="" src="' . esc_url( $image ) . '"/></div>'; // WPCS: XSS ok.
				break;
			case 'thumbnail':
				if ( $thumb_as_image ) {
					$image = get_term_meta( $term->term_id, 'image', true );
					$image = $image ? wp_get_attachment_image_src( $image, $this->attr_thumb_size ) : '';
					$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
					$data['_type_value'] = $image;
					if ( $image ) {
						$item_class . ' has-img';
					}

					$data['_html'] = '<div class="' . $item_class . '"><img  alt="" src="' . esc_url( $image ) . '"/></div>'; // WPCS: XSS ok.
				} else {
					$data['_html'] = "<div class='s-inner no-thumb'></div>";
				}
				break;
			default:
				$label = $term->name;
				$data['_type_value'] = $label;
				$data['_html'] = '<div class="' . $item_class . '">' . esc_html( $label ) . '</div>'; // WPCS: XSS ok.
		}
		return $data;
	}

	public function get_meta_settings( $meta_values, $type, $data_type = 'tax' ) {
		$meta_values = wp_parse_args(
			$meta_values,
			array(
				'image' => '',
				'label' => '',
				'color' => '',
				'color2' => '',
			)
		);
		$data = array();
		switch ( $type ) {
			case 'color':
			case 'color_list':
				$data['_type_value'] = $meta_values['color'];
				$data['_type_value_2'] = $meta_values['color2'];
				break;
			case 'image':
			case 'image_list':
				if ( 'custom' == $data_type ) {
					$image = wp_get_attachment_image_src( $meta_values['image'], $this->attr_thumb_size );
				} else {
					$image = get_term_meta( $meta_values['image'], 'image', true );
					$image = $image ? wp_get_attachment_image_src( $image, $this->attr_thumb_size ) : wp_get_attachment_image_src( $meta_values['image'], $this->attr_thumb_size );
				}
				$image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
				$data['_type_value'] = $image;
				break;
			case 'label':
			case 'label_list':
				$label = $meta_values['label'];
				if ( $label ) {
					$data['_type_value'] = $label;
				}

				break;
			default:
				$label = $meta_values['label'];
				if ( $label ) {
					$data['_type_value'] = $label;
				}
		}
		return $data;
	}

	/**
	 * Main function setup product data.
	 *
	 * @return void
	 */
	public function setup_tax_attr_data( $in_the_loop = true ) {
		global $product;

		$product_id = $product->get_id();

		if ( $in_the_loop ) {
			if ( ! isset( $this->product_gallies[ '_' . $product_id ] ) ) {
				$this->product_gallies[ '_' . $product_id ] = $this->get_product_gallery_array( $product, 'loop' );
			}
		} else {
			if ( ! isset( $this->product_gallies[ '_' . $product_id ] ) ) {

				$this->product_gallies[ '_single_' . $product_id ] = $this->get_product_gallery_array( $product, 'single' );
			}
		}

		if ( $product->get_type() !== 'variable' ) {
			return;
		}

		$this->get_variations( $product );

		$_pid = '_' . $product_id;
		if ( isset( $this->all_product_swatches[ $_pid ] ) ) {
			return;
		}

		$attrs = $product->get_attributes();
		$swatches_meta = $product->get_meta( '_savp_swatches', true );

		foreach ( $attrs as $attr_key => $attr ) {
			if ( ! $attr->get_variation() ) {
				continue;
			}

			$label = wc_attribute_label( $attr_key, $product );

			$custom_tax_settings = isset( $swatches_meta[ $attr->get_name() ] ) ? $swatches_meta[ $attr->get_name() ] : array();
			$custom_tax_settings = wp_parse_args(
				$custom_tax_settings,
				array(
					'type' => '',
					'display' => '',
					'price' => '',
					'stock_status' => '',
					'options' => array(),
				)
			);

			// If use global settings.
			if ( ! $custom_tax_settings['type'] ) {
				$custom_tax_settings = false;
			}

			$label_format = '<span class="savp_tax_label">%s: </span><span class="savp-current-term"></span>';
			$tax_slug = strtolower( $attr->get_name() );
			$tax_data = array(
				'name' => $label,
				'label' => sprintf( $label_format, $label ),
				'slug' => $tax_slug,
				'attribute_type' => 'label',
			);

			$tax_settings = array();

			$terms = array();
			if ( $attr->is_taxonomy() ) {
				$meta = savp()->get_tax_attribute( $tax_slug );
				$meta_settings = array();
				if ( $meta ) {
					$meta_settings  = $meta->savp_meta;
				}
				$meta_settings = wp_parse_args(
					$meta_settings,
					array(
						'display' => '',
					)
				);

				$tax_data['attribute_type'] = $meta->attribute_type;
				if ( $custom_tax_settings ) {
					$tax_data['attribute_type'] = $custom_tax_settings['type'];
				}

				$tax_settings = $meta_settings;

				foreach ( $attr->get_terms() as $option ) {
					$term = array(
						'name' => $option->name,
						'slug' => $option->slug,
						'_type' => $meta->attribute_type,
						'_type_value' => $option->name,
					);
					$t_settings = $this->get_term_settings( $option, $meta->attribute_type );
					$term = array_merge( $term, $t_settings );

					if ( $custom_tax_settings && ! empty( $custom_tax_settings['options'] ) ) {
						if ( isset( $custom_tax_settings['options'][ $option->slug ] ) ) {
							$c_settings = $this->get_meta_settings( $custom_tax_settings['options'][ $option->slug ], $custom_tax_settings['type'], 'term' );
							$c_settings['_type'] = $custom_tax_settings['type'];
							$tax_data['attribute_type'] = $custom_tax_settings['type'];
							$term = array_merge( $term, $c_settings );

						}
					}

					$terms[ $option->slug ] = $term;
				}
			} else {
				$tax_data['attribute_type'] = 'label';
				if ( $custom_tax_settings ) {
					$tax_data['attribute_type'] = $custom_tax_settings['type'];
				}
				foreach ( $attr->get_options() as $k => $opt_name ) {

					$term = array(
						'name' => $opt_name,
						'slug' => $opt_name,
						'_type' => 'label',
						'_type_value' => $opt_name,
					);

					if ( $custom_tax_settings && ! empty( $custom_tax_settings['options'] ) ) {

						if ( isset( $custom_tax_settings['options'][ $opt_name ] ) ) {
							$c_settings = $this->get_meta_settings( $custom_tax_settings['options'][ $opt_name ], $custom_tax_settings['type'], 'custom' );
							$c_settings['_type'] = $custom_tax_settings['type'];

							$term = array_merge( $term, $c_settings );
						}
					}

					$terms[ $opt_name ] = $term;
				}
			}

			if ( $custom_tax_settings ) {
				$tax_data['attribute_type'] = $custom_tax_settings['type'];
				$copy_setting = $custom_tax_settings;
				unset( $copy_setting['options'] );
				$tax_settings = array_merge( $term, $copy_setting );
			}

			if ( ! isset( $this->all_product_swatches[ $_pid ] ) ) {
				$product_attributes[ $_pid ] = array();
			}

			$this->all_product_swatches[ $_pid ][ $tax_slug ] = array(
				'tax' => $tax_data,
				'type' => $tax_data['attribute_type'],
				'terms' => $terms,
				'settings' => $tax_settings,
			);
			$this->all_product_swatches[ $_pid ]['_default'] = $product->get_default_attributes();

		}

	}

	public function get_variations( $product ) {
		$product_id  = $product->get_id();
		if ( ! isset( $this->product_variations[ '_' . $product_id ] ) ) {
			$variations = $product->get_available_variations( 'array' );
			$this->product_variations[ '_' . $product_id ] = $variations;
		}

		return isset( $this->product_variations[ '_' . $product_id ] ) ? $this->product_variations[ '_' . $product_id ] : array();
	}


	public function css_unit( $input ) {
		if ( ! $input ) {
			return false;
		}
		$unit1 = substr( $input, -1 );
		if ( '%' == $unit1 ) {
			return array(
				floatval( $input ),
				$unit1,
			);
		}
		$unit2 = substr( $input, -2 );

		if ( in_array( $unit2, [ 'px', 'em', 'wh', 'ww', 'cm' ] ) ) {
			return array(
				floatval( $input ),
				$unit2,
			);
		}
		$unit3 = substr( $input, -3 );
		if ( 'rem' == $unit3 ) {
			return array(
				floatval( $input ),
				$unit3,
			);
		}

		return array(
			floatval( $input ),
			'px',
		);

		return false;
	}


	public function l10n() {
		$nep = new WC_Product_Simple();
		return array(
			'add_to_cart' => $nep->single_add_to_cart_text(),
			'add_to_cart_url' => $nep->add_to_cart_url(),
			'unavailable' => esc_html__( 'Unavailable', 'savp' ),
			'in_stock' => esc_html__( 'In stock', 'savp' ),
			'out_of_stock' => esc_html__( 'Out of stock', 'savp' ),
		);
	}

	/**
	 * Enqueue scripts and stylesheets
	 */
	public function enqueue_scripts() {

		wp_deregister_script( 'zoom' );
		wp_deregister_script( 'flexslider' );
		wp_deregister_script( 'photoswipe' );
		wp_deregister_script( 'photoswipe-ui-default' );
		wp_deregister_style( 'photoswipe-default-skin' );

		wp_dequeue_script( 'zoom' );
		wp_dequeue_script( 'flexslider' );
		wp_dequeue_script( 'photoswipe' );
		wp_dequeue_script( 'photoswipe-ui-default' );
		wp_dequeue_style( 'photoswipe-default-skin' );
		wp_deregister_script( 'wc-add-to-cart-variation' );

		wp_enqueue_style( 'savp-slick', SAVP__URL . 'assets/slick/slick.css', array(), SAVP__VERSION );
		wp_enqueue_style( 'photoswipe', SAVP__URL . 'assets/photoswipe/photoswipe.css', array(), SAVP__VERSION );
		wp_enqueue_style( 'photoswipe-skin', SAVP__URL . 'assets/photoswipe/default-skin/default-skin.css', array(), SAVP__VERSION );

		wp_enqueue_style( 'swiper', SAVP__URL . 'assets/swiper/swiper-bundle.css', array(), SAVP__VERSION );
		wp_enqueue_style( 'savp-frontend', SAVP__URL . 'assets/css/frontend.css', array(), SAVP__VERSION );
		wp_register_script( 'savp-photoswipe', SAVP__URL . 'assets/photoswipe/photoswipe.js', array(), SAVP__VERSION, true );
		wp_register_script( 'savp-photoswipe-ui', SAVP__URL . 'assets/photoswipe/photoswipe-ui-default.js', array(), SAVP__VERSION, true );
		wp_register_script( 'savp-zoom', SAVP__URL . 'assets/js/js-image-zoom.js', array(), SAVP__VERSION, true );
		wp_register_script( 'swiper', SAVP__URL . 'assets/swiper/swiper-bundle.js', array(), SAVP__VERSION, true );
		wp_enqueue_script( 'savp', SAVP__URL . 'assets/js/savp.js', array( 'jquery' ), SAVP__VERSION, true );
		wp_enqueue_script( 'savp-gallery', SAVP__URL . 'assets/js/gallery.js', array( 'jquery', 'swiper', 'savp-photoswipe', 'savp-photoswipe-ui', 'savp-zoom', 'savp' ), SAVP__VERSION, true );
		wp_register_script( 'wc-add-to-cart-variation', SAVP__URL . 'assets/js/variation.js', array( 'jquery', 'wp-util', 'jquery-blockui', 'savp' ), SAVP__VERSION, true );
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		wp_localize_script(
			'savp',
			'savpConfig',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'settings' => array(
					'canvas' => sanitize_text_field( $this->get_settings( 'savp_single_canvas', 'no' ) ),
					't_limit' => intval( $this->get_settings( 'savp_t_limit', 4 ) ),
					'behavior' => sanitize_text_field( $this->get_settings( 'savp_attr_behavior', 'blur-cross' ) ),
					'behavior_singular' => sanitize_text_field( $this->get_settings( 'savp_attr_behavior_singular', 'blur-cross' ) ),
				),
				'gallerySettings' => $this->get_gallery_single_settings(),
			)
		);
	}

	public function footer() {
		?>
		<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

<!-- Background of PhotoSwipe. 
	 It's a separate element as animating opacity is faster than rgba(). -->
<div class="pswp__bg"></div>

<!-- Slides wrapper with overflow:hidden. -->
<div class="pswp__scroll-wrap">

	<!-- Container that holds slides. 
		PhotoSwipe keeps only 3 of them in the DOM to save memory.
		Don't modify these 3 pswp__item elements, data is added later on. -->
	<div class="pswp__container">
		<div class="pswp__item"></div>
		<div class="pswp__item"></div>
		<div class="pswp__item"></div>
	</div>

	<!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
	<div class="pswp__ui pswp__ui--hidden">

		<div class="pswp__top-bar">

			<!--  Controls are self-explanatory. Order can be changed. -->

			<div class="pswp__counter"></div>
			<button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
			<button class="pswp__button pswp__button--share" title="Share"></button>
			<button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
			<button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
			<div class="pswp__preloader">
				<div class="pswp__preloader__icn">
				  <div class="pswp__preloader__cut">
					<div class="pswp__preloader__donut"></div>
				  </div>
				</div>
			</div>
		</div>

		<div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
			<div class="pswp__share-tooltip"></div> 
		</div>

		<button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
		</button>

		<button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
		</button>

		<div class="pswp__caption">
			<div class="pswp__caption__center"></div>
		</div>

	</div>

</div>

</div>
		<?php
	}

}
