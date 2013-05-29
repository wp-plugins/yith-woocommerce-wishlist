<?php
/**
 * Your Inspiration Themes common functions
 *
 * @author Your Inspiration Themes
 * @version 0.0.1
 */

if( !defined('YITH_FUNCTIONS')) {
    define( 'YITH_FUNCTIONS', true);
}

if ( ! function_exists( 'yit_is_woocommerce_active' ) ) {
    /**
     * WC Detection
     */
    function yit_is_woocommerce_active() {
        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }

        $woo = yit_get_plugin_basename_from_slug( 'woocommerce' );
        return in_array( $woo, $active_plugins ) || array_key_exists( $woo, $active_plugins );
    }
}

if( ! function_exists( 'yit_get_plugin_basename_from_slug' ) ) {
    /**
     * Helper function to extract the file path of the plugin file from the
     * plugin slug, if the plugin is installed.
     *
     * @param string $slug Plugin slug (typically folder name) as provided by the developer
     * @return string Either file path for plugin if installed, or just the plugin slug
     */
    function yit_get_plugin_basename_from_slug( $slug ) {
        include_once ABSPATH . '/wp-admin/includes/plugin.php';

        $keys = array_keys( get_plugins() );

        foreach ( $keys as $key ) {
            if ( preg_match( '|^' . $slug .'|', $key ) )
                return $key;
        }

        return $slug;
    }
}

if( ! function_exists( 'yit_debug') ) {
    /**
     * Debug helper function.  This is a wrapper for var_dump() that adds
     * the <pre /> tags, cleans up newlines and indents, and runs
     * htmlentities() before output.
     *
     * @param  mixed  $var   The variable to dump.
     * @param  mixed  $var2  The second variable to dump
     * @param  ...
     * @return string
     */
    function yit_debug() {
        $args = func_get_args();
        if( !empty( $args ) ) {
            foreach( $args as $k=>$arg ) {
                // var_dump the variable into a buffer and keep the output
                ob_start();
                var_dump($arg);
                $output = ob_get_clean();

                // neaten the newlines and indents
                $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);

                if(!extension_loaded('xdebug')) {
                    $output = htmlspecialchars($output, ENT_QUOTES);
                }

                $output = '<pre class="yit-debug">'
                    . '<strong>$param_' . ($k+1) . ": </strong>"
                    . $output
                    . '</pre>';
                echo $output;
            }
        } else {
            trigger_error("yit_debug() expects at least 1 parameter, 0 given.", E_USER_WARNING);
        }

        return $args;
    }
}
