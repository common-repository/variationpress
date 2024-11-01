<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.5
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( 'variable' !== $product->get_type() ) {
	return;
}

$attributes  = $product->get_variation_attributes( 'array' );
$available_variations  = $product->get_available_variations();

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
$limit = 1;
$count = 0;

$opt_show_attr = get_option( 'savp_attrs_show' );

$custom_attrs = false;
$swatches_archive = get_post_meta( $product->get_id(), '_savp_swatches_archive', true );

if ( ! is_array( $swatches_archive ) ) {
	$swatches_archive = array();
}

if ( $swatches_archive && ! empty( $swatches_archive ) ) {
	$custom_attrs = $swatches_archive;
	$opt_show_attr = 'custom';
}

$show_attrs = array();
switch ( $opt_show_attr ) {
	case '1':
		$limit = 1;
		$count = 0;
		foreach ( $attributes as $attribute_name => $options ) {
			$show_attrs[ $attribute_name ] = $options;
			$count ++;
			if ( $count >= $limit ) {
				break;
			}
		}
		break;
	case '2':
		$limit = 2;
		$count = 0;
		foreach ( $attributes as $attribute_name => $options ) {
			$show_attrs[ $attribute_name ] = $options;
			$count ++;
			if ( $count >= $limit ) {
				break;
			}
		}
		break;
	case 'custom':
		if ( false === $custom_attrs ) {
			$custom_attrs = get_option( 'savp_custom_attrs' );
		}


		if ( empty( $custom_attrs ) || ! is_array( $custom_attrs ) ) {
			$show_attrs = $attributes;
		} else {
			foreach ( $attributes as $attribute_name => $options ) {

				$is_pa = substr( $attribute_name, 0, 3 ) === 'pa_' ? true : false;
				if ( $is_pa ) {
					$sub_name = substr( $attribute_name, 3 );
				} else {
					$sub_name = $attribute_name;
				}


				if ( in_array( $sub_name, $custom_attrs, true ) ) {
					$show_attrs[ $attribute_name ] = $options;
				}
			}
		}

		break;
	default:
		$show_attrs = $attributes;
		break;
}


?>
<form class="variations_form cart savp-loop-form" data-attr-all="<?php echo count( $show_attrs ) == count( $attributes ) ? 'y' : 'n'; ?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>">
	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', esc_html__( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="savp_tb variations" cellspacing="0">
			<div>
				<?php foreach ( $show_attrs as $attribute_name => $options ) { ?>
					<div class="savp_row savp-row-pa-attr">
						<div class="value">
							<?php
								wc_dropdown_variation_attribute_options(
									array(
										'options'   => $options,
										'attribute' => $attribute_name,
										'product'   => $product,
									)
								);
							?>
						</div>
					</div>
					<?php } ?>
			</div>
		</div>
	<?php endif; ?>
</form>

