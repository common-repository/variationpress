<?php


class SAVP_Settings {

	public function __construct() {

		$name = basename( SAVP_FILE );
		$dirname = basename( dirname( SAVP_FILE ) );
		add_filter( 'plugin_action_links_' . $dirname . '/' . $name, array( $this, 'settings_links' ) );
	}

	public function settings_links( $links ) {
		$variation_url = esc_url(
			add_query_arg(
				'section',
				'savp_variations',
				get_admin_url() . 'customize.php?autofocus[panel]=woocommerce&url=' . esc_url( wc_get_page_permalink( 'shop' ) )
			)
		);
		// Create the link.
		$settings_link = "<a href='" . $variation_url . "'>" . esc_html__( 'Settings', 'savp' ) . '</a>';
		
		// Adds the link to the end of the array.
		array_push(
			$links,
			$settings_link
		);
		return $links;
	}



}

new SAVP_Settings();











