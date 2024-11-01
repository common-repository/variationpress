<?php


function savp_contvert( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$attrs = $product->get_attributes();
	$meta_attrs = get_post_meta( $product->get_id(), '_product_attributes', true );

	$update_attrs = [];

	$replace_keys = [];

	foreach ( $attrs as $key => $attr ) {

		if ( is_object( $attr ) && $attr->get_id() == 0 && $attr->get_variation() == true ) {
			$options = $attr->get_options();
			$old_tax_name = strtolower( $attr->get_name() );
			$name = $attr->get_name();
			$slug = strtolower( sanitize_title( $old_tax_name ) );

			$args = array(
				'name' => $name,
				'slug' => $slug,
				'type' => 'select',
			);

			$id = wc_create_attribute( $args );

			if ( is_wp_error( $id ) && 'invalid_product_attribute_slug_reserved_name' == $id->get_error_code() ) {
				if ( substr( $slug, 0, 2 ) != 'c_' ) {
					$slug = 'c_' . $slug;
				}
				$args['slug'] = $slug;
				$id = wc_create_attribute( $args );
			}

			$has_id = false;

			if ( is_wp_error( $id ) ) {
				if ( 'invalid_product_attribute_slug_already_exists' == $id->get_error_code() ) {
					$has_id = true;
				}
			} else {
				$has_id = true;
			}

			if ( $has_id ) {

				$new_tax_slug = 'pa_' . $slug;

				if ( ! taxonomy_exists( $new_tax_slug ) ) {
					register_taxonomy( $new_tax_slug, 'product' );
				}

				foreach ( $attr->get_options()  as $opt_name ) {
					$o_slug_name = strtolower( sanitize_title( $opt_name ) );
					$opt_slug = strtolower( sanitize_title( $o_slug_name ) );
					$i = wp_insert_term(
						$opt_name,
						$new_tax_slug,
						array(
							'slug' => $opt_slug,
						)
					);

					$t_id = 0;
					if ( is_wp_error( $i ) ) {
						if ( 'term_exists' == $i->get_error_code() ) {
							$t_id  = $i->get_error_data();
						}
					} else {
						$t_id  = $i['term_id'];
					}

					if ( $t_id ) {
						wp_set_object_terms( $product->get_id(), $t_id, $new_tax_slug, true );
					}

					$replace_keys[] = array(
						'key' => 'attribute_' . $old_tax_name,
						'new_key' => 'attribute_' . $new_tax_slug,
						'value' => $opt_name,
						'new_value' => $opt_slug,
					);
				}

				$update_attrs[ $new_tax_slug ] = array(
					'name' => $new_tax_slug,
					'value' => '',
					'position' => $attr->get_position(),
					'is_visible' => $attr->get_visible(),
					'is_variation' => 1,
					'is_taxonomy' => 1,
				);
			}
		} else {
			if ( $key && isset( $meta_attrs[ $key ] ) ) {
				$update_attrs[ $key ] = $meta_attrs[ $key ];
			}
		}
	}

	if ( ! count( $replace_keys ) ) {
		return;
	}

	update_post_meta( $product->get_id(), '_product_attributes', $update_attrs );

	global $wpdb;
	$children = $product->get_visible_children();
	if ( ! count( $children ) ) {
		return;
	}
	foreach ( $replace_keys as $rags ) {
		$sql = "UPDATE {$wpdb->postmeta} 
		set meta_key = %s, meta_value = %s 
		WHERE meta_key = %s and meta_value = %s AND post_id IN (" . join( ', ', $children ) . ') ';
		$sql = $wpdb->prepare( $sql, $rags['new_key'], $rags['new_value'], $rags['key'], $rags['value'] );
		$wpdb->query( $sql ); // WPCS: db call ok.
	}

	wc_delete_product_transients( $product->get_id() );
	if ( $product->get_parent_id( 'edit' ) ) {
		wc_delete_product_transients( $product->get_parent_id( 'edit' ) );
		WC_Cache_Helper::invalidate_cache_group( 'product_' . $product->get_parent_id( 'edit' ) );
	}
	WC_Cache_Helper::invalidate_attribute_count( array_keys( $product->get_attributes() ) );
	WC_Cache_Helper::invalidate_cache_group( 'product_' . $product->get_id() );
}
