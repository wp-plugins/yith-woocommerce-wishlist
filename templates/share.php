<?php
/**
 * Share template
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.0.0
 */

global $yith_wcwl;

if( !is_user_logged_in() ) { return; }

if( get_option( 'yith_wcwl_share_fb' ) == 'yes' || get_option( 'yith_wcwl_share_twitter' ) == 'yes' || get_option( 'yith_wcwl_share_pinterest' ) == 'yes' )
    { echo YITH_WCWL_UI::get_share_links( $yith_wcwl->get_wishlist_url() . '&user_id=' . get_current_user_id() ); }