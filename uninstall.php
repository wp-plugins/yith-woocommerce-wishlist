<?php
/**
 * Uninstall plugin
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.1.5
 */

// If uninstall not called from WordPress exit
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
	
// Delete option from options table
delete_option( 'yith_wcwl_version' );
delete_option( 'yith_wcwl_db_version' );

//delete pages created for this plugin
wp_delete_post( get_option( 'yith-wcwl-pageid' ), true );

//remove any additional options and custom table
$sql = "DROP TABLE `" . $wpdb->yith_wcwl_items . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_wcwl_wishlists . "`";
$wpdb->query( $sql );
?>