<?php
/**
* Plugin Name: YITH WooCommerce Wishlist
* Plugin URI: http://yithemes.com/themes/plugins/yith-woocommerce-wishlist/
* Description: YITH WooCommerce Wishlist allows you to add Wishlist functionality to your e-commerce.
* Version: 2.0.8
* Author: Yithemes
* Author URI: http://yithemes.com/
* Text Domain: yit
* Domain Path: /languages/
* 
* @author Your Inspiration Themes
* @package YITH WooCommerce Wishlist
* @version 2.0.0
*/

/*  Copyright 2013  Your Inspiration Themes  (email : plugins@yithemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Required functions
 */
if( ! defined( 'YITH_FUNCTIONS' ) ) {
    require_once( 'yit-common/yit-functions.php' );
}

if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
    require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

if ( ! defined( 'YITH_WCWL' ) ) {
    define( 'YITH_WCWL', true );
}

if ( ! defined( 'YITH_WCWL_URL' ) ) {
    define( 'YITH_WCWL_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'YITH_WCWL_DIR' ) ) {
    define( 'YITH_WCWL_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YITH_WCWL_INC' ) ) {
    define( 'YITH_WCWL_INC', YITH_WCWL_DIR . 'includes/' );
}

if ( ! defined( 'YITH_WCWL_INIT' ) ) {
    define( 'YITH_WCWL_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_WCWL_FREE_INIT' ) ) {
    define( 'YITH_WCWL_FREE_INIT', plugin_basename( __FILE__ ) );
}

if( ! function_exists( 'yith_wishlist_constructor' ) ) {
    function yith_wishlist_constructor() {

        load_plugin_textdomain( 'yit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        // Load required classes and functions
        require_once( YITH_WCWL_INC . 'functions.yith-wcwl.php' );
        require_once( YITH_WCWL_INC . 'class.yith-wcwl.php' );
        require_once( YITH_WCWL_INC . 'class.yith-wcwl-init.php' );
        require_once( YITH_WCWL_INC . 'class.yith-wcwl-install.php' );

        if ( is_admin() ) {
            require_once( YITH_WCWL_INC . 'class.yith-wcwl-admin-init.php' );
        }

        if ( get_option( 'yith_wcwl_enabled' ) == 'yes' ) {
            require_once( YITH_WCWL_INC . 'class.yith-wcwl-ui.php' );
            require_once( YITH_WCWL_INC . 'class.yith-wcwl-shortcode.php' );
        }

        // Let's start the game!

        /**
         * @deprecated
         */
        global $yith_wcwl;
        $yith_wcwl = YITH_WCWL();
    }
}
add_action( 'yith_wcwl_init', 'yith_wishlist_constructor' );

if( ! function_exists( 'yith_wishlist_install' ) ) {
    function yith_wishlist_install() {

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        if ( ! function_exists( 'WC' ) ) {
            add_action( 'admin_notices', 'yith_wcwl_install_woocommerce_admin_notice' );
        }
        elseif( defined( 'YITH_WCWL_PREMIUM' ) ) {
            add_action( 'admin_notices', 'yith_wcwl_install_free_admin_notice' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
        else {
            do_action( 'yith_wcwl_init' );
        }
    }
}
add_action( 'plugins_loaded', 'yith_wishlist_install', 11 );

if( ! function_exists( 'yith_wcwl_install_woocommerce_admin_notice' ) ) {
    function yith_wcwl_install_woocommerce_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e( 'YITH WooCommerce Wishlist is enabled but not effective. It requires WooCommerce in order to work.', 'yit' ); ?></p>
        </div>
    <?php
    }
}

if( ! function_exists( 'yith_wcwl_install_free_admin_notice' ) ){
    function yith_wcwl_install_free_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e( 'You can\'t activate the free version of YITH WooCommerce Wishlist while you are using the premium one.', 'yit' ); ?></p>
        </div>
    <?php
    }
}