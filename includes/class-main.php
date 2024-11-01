<?php

if ( ! class_exists( 'SAVP_Main' ) ) {

	/**
	 * The main plugin class
	 */
	final class SAVP_Main {
		/**
		 * The single instance of the class
		 *
		 * @var SAVP_Main
		 */
		protected static $instance = null;

		/**
		 * Extra attribute types
		 *
		 * @var array
		 */
		public $types = array();

		/**
		 * Main instance
		 *
		 * @return SAVP_Main
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
			$this->types = array(
				'color' => esc_html__( 'Color', 'savp' ),
				'image' => esc_html__( 'Image', 'savp' ),
				'label' => esc_html__( 'Label', 'savp' ),
				'thumbnail' => esc_html__( 'Thumbnail', 'savp' ),
			);

			$this->includes();
			$this->init_hooks();
		}
		public static function get_gallery_sizes() {
			return apply_filters(
				'savp_gallery_image_sizes',
				array(
					'variation' => 'woocommerce_thumbnail',
					'medium' => 'medium',
					'full' => 'full',
					'thumb' => 'woocommerce_gallery_thumbnail',
					'large' => 'woocommerce_single',
				)
			);
		}

		public static function string_to_numbers( $string ) {
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

		public static function install_db() {
			if ( ! class_exists( 'WC' ) ) {
				return;
			}
			global $wpdb;
			$table_name = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
			$charset_collate = $wpdb->get_charset_collate();
			$sql = 'ALTER TABLE `$table_name`ADD `savp_meta` text NULL;';
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql ); // WPCS: db call ok.
		}


		public static function get_wc_attribute_by_id( $id ) {
			global $wpdb;
			$attr = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE attribute_id = %d', $id ) ); // WPCS: db call ok.
			if ( $attr ) {
				if ( ! property_exists( $attr, 'savp_meta' ) ) {
					self::install_db();
				}
			}
			return self::parse_wc_attribute_data( $attr );
		}

		public static function parse_wc_attribute_data( $data ) {
			if ( ! $data ) {
				return $data;
			}
			if ( $data && property_exists( $data, 'savp_meta' ) ) {
				$meta = json_decode( $data->savp_meta, true );
				if ( ! is_array( $meta ) ) {
					$meta = array();
				}
				$data->savp_meta = $meta;
			} else {
				$data->savp_meta = array();
			}

			return $data;
		}


		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			require_once dirname( __FILE__ ) . '/class-frontend.php';
			if ( is_admin() ) {
				require_once dirname( __FILE__ ) . '/class-admin.php';
			}
		}

		/**
		 * Initialize hooks
		 */
		public function init_hooks() {
			add_action( 'init', array( $this, 'load_textdomain' ) );

			add_filter( 'product_attributes_type_selector', array( $this, 'add_attribute_types' ) );

			if ( is_admin() ) {
				add_action( 'init', array( 'SAVP_Admin', 'instance' ) );
			}

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				add_action( 'init', array( 'SAVP_Frontend', 'instance' ) );
			}
		}

		/**
		 * Load plugin text domain
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'savp', false, dirname( plugin_basename( SAVP_FILE ) ) . '/languages/' );
		}

		/**
		 * Add extra attribute types
		 * Add color, image and label type
		 *
		 * @param array $types
		 *
		 * @return array
		 */
		public function add_attribute_types( $types ) {
			$types = array_merge( $types, $this->types );

			return $types;
		}

		/**
		 * Get attribute's properties
		 *
		 * @param string $taxonomy
		 *
		 * @return object
		 */
		public function get_tax_attribute( $taxonomy ) {
			global $wpdb;

			$attr = substr( $taxonomy, 3 );
			$attr = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE attribute_name = %s', $attr ) ); // WPCS: db call ok.
			return self::parse_wc_attribute_data( $attr );
		}

		/**
		 * Instance of admin
		 *
		 * @return object
		 */
		public function admin() {
			return SAVP_Admin::instance();
		}

		/**
		 * Instance of frontend
		 *
		 * @return object
		 */
		public function frontend() {
			return SAVP_Frontend::instance();
		}
	}
}

if ( ! function_exists( 'savp' ) ) {
	/**
	 * Main instance of plugin
	 *
	 * @return SAVP_Main
	 */
	function savp() {
		return SAVP_Main::instance();
	}
}
