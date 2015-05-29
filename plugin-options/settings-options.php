<?php
/**
 * General settings page
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.0
 */

if ( ! defined( 'YITH_WCWL' ) ) {
	exit;
} // Exit if accessed directly


$options = apply_filters( 'yith_wcwl_tab_options', YITH_WCWL_Admin_Init()->options );
$premium_options = isset( $options['premium'] ) ? $options['premium'] : array();

$options['general_settings']['section_general_settings_videobox']['default']['button']['href'] = YITH_WCWL_Admin_Init()->get_premium_landing_uri();

return array(
	'settings' => array_merge( $options['general_settings'], $options['socials_share'], $options['yith_wfbt_integration'], $premium_options )
);