<?php
/**
 * Color settings page
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.0
 */

if ( ! defined( 'YITH_WCWL' ) ) {
	exit;
} // Exit if accessed directly

$options = apply_filters( 'yith_wcwl_tab_options', YITH_WCWL_Admin_Init()->options );

return array(
	'colors' => array_merge(
		$options['styles'],
		array(
			'custom_color_panel' => array(
				'id' => 'yith_wcwl_color_panel',
				'type' => 'yith_wcwl_color_panel'
			)
		)
	)
);