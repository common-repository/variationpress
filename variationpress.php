<?php
/**
 * Plugin Name: VariationPress for WooCommerce
 * Plugin URI: https://sainwp.com/variationpress/
 * Description: An eCommerce Variation Swatches and Gallery for WooCommerce and OneStore theme.
 * Version: 1.1.8
 * Author: sainwp
 * Author URI: https://sainwp.com/
 * Requires at least: 5.5
 * Tested up to: 5.7.2
 * Text Domain: savp
 * Domain Path: /languages
 * WC requires at least: 5.1.0
 * WC tested up to: 5.2.2
 *
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'SAVP_FILE' ) ) {
	define( 'SAVP_FILE', __FILE__ );
}

define( 'SAVP__PATH', dirname( __FILE__ ) );
define( 'SAVP__URL', plugin_dir_url( __FILE__ ) );
define( 'SAVP__VERSION', '1.1.4' );

if ( ! function_exists( 'savp_wc_notice' ) ) {
	/**
	 * Display notice in case of WooCommerce plugin is not activated
	 */
	function savp_wc_notice() {
		?>

		<div class="error">
			<p><?php esc_html_e( 'VariationPress is enabled but not effective. It requires WooCommerce in order to work.', 'savp' ); ?></p>
		</div>

		<?php
	}
}

if ( ! function_exists( 'savp_theme_notice' ) ) {
	/**
	 * Display notice in case of WooCommerce plugin is not activated
	 */
	function savp_theme_notice() {
		?>

		<div class="error">
			<p><?php printf( esc_html__( '%1$s requires OneStore theme to be activated to work. %2$s', 'savp' ), '<strong>VariationPress</strong>', '<a href="https://wordpress.org/themes/onestore/">Download OneStore Theme</a>' ); ?></p>
		</div>
		<?php
	}
}

function savp_is_plus_activated() {
	return defined( 'ONESTORE_PLUS__PATH' );
}


if ( ! function_exists( 'savp_init' ) ) {
	function savp_init() {
		$theme = basename( get_template_directory() );
		if ( ! function_exists( 'WC' ) || 'onestore' != $theme ) {
			if ( ! function_exists( 'WC' ) ) {
				add_action( 'admin_notices', 'savp_wc_notice' );
			}

			if ( 'onestore' != $theme ) {
				add_action( 'admin_notices', 'savp_theme_notice' );
			}
		} else {
			require_once SAVP__PATH . '/includes/class-main.php';
			savp();
		}
	}
}

if ( ! function_exists( 'savp_deactivate' ) ) {
	/**
	 * Deactivation hook.
	 * Backup all unsupported types of attributes then reset them to "select".
	 *
	 * @param bool $network_deactivating Whether the plugin is deactivated for all sites in the network
	 *                                   or just the current site. Multisite only. Default is false.
	 */
	function savp_deactivate( $network_deactivating ) {
		// Early return if WooCommerce is not activated.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		global $wpdb;

		// Backup attribute types.
		$attributes         = wc_get_attribute_taxonomies();
		$savp_attributes = array();

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				$savp_attributes[ $attribute->attribute_id ] = $attribute;
			}
		}

		if ( ! empty( $savp_attributes ) ) {
			set_transient( 'savp_attribute_taxonomies', $savp_attributes );
			delete_transient( 'wc_attribute_taxonomies' );
			update_option( 'savp_backup_attributes_time', time() );
		}

		// Reset attributes.
		if ( ! empty( $savp_attributes ) ) {
			foreach ( $savp_attributes as $id => $attribute ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_attribute_taxonomies',
					array( 'attribute_type' => 'select' ),
					array( 'attribute_id' => $id ),
					array( '%s' ),
					array( '%d' )
				); // WPCS: db call ok.
			}
		}

		// Delete the option of restoring time.
		delete_option( 'savp_restore_attributes_time' );
	}
}

add_action( 'plugins_loaded', 'savp_init', 20 );
register_deactivation_hook( __FILE__, 'savp_deactivate' );


function savp_install() {
	require_once SAVP__PATH . '/includes/class-main.php';
	SAVP_Main::install_db();
}

register_activation_hook( __FILE__, 'savp_install' );




