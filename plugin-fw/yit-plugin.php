<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


if( !defined('YIT_CORE_PLUGIN')) {
    define( 'YIT_CORE_PLUGIN', true);
}

if( !defined('YIT_CORE_PLUGIN_PATH')) {
    define( 'YIT_CORE_PLUGIN_PATH', dirname(__FILE__));
}

if( !defined('YIT_CORE_PLUGIN_URL')) {
    define( 'YIT_CORE_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ));
}

if( ! defined( 'YIT_CORE_PLUGIN_TEMPLATE_PATH' ) ){
    define ( 'YIT_CORE_PLUGIN_TEMPLATE_PATH', YIT_CORE_PLUGIN_PATH .  '/templates' );
}


include_once( 'yit-functions.php' );
include_once( 'yit-plugin-registration-hook.php' );
include_once( 'lib/yit-metabox.php' );
include_once( 'lib/yit-plugin-panel.php' );
include_once( 'lib/yit-plugin-panel-wc.php' );
include_once( 'lib/yit-plugin-subpanel.php' );
include_once( 'lib/yit-plugin-common.php' );
include_once( 'lib/yit-plugin-gradients.php');
include_once( 'licence/lib/yit-licence.php');
include_once( 'licence/lib/yit-plugin-licence.php');
include_once( 'licence/lib/yit-theme-licence.php');
include_once( 'lib/yit-video.php');
include_once( 'lib/yit-upgrade.php');
include_once( 'lib/yit-pointers.php');

// load from theme folder...
load_textdomain( 'yith-plugin-fw', get_template_directory() . '/core/plugin-fw/yith-plugin-fw-' . apply_filters( 'plugin_locale', get_locale(), 'yith-plugin-fw' ) . '.mo' )

// ...or from plugin folder
|| load_textdomain( 'yith-plugin-fw', dirname(__FILE__) . '/languages/yith-plugin-fw-' . apply_filters( 'plugin_locale', get_locale(), 'yith-plugin-fw' ) . '.mo' );
