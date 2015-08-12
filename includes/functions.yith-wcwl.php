<?php
/**
 * Install file
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.10
 */

if ( !defined( 'YITH_WCWL' ) ) { exit; } // Exit if accessed directly

if( !function_exists( 'yith_wcwl_is_wishlist' ) ){
    /**
     * Check if current page is wishlist
     *
     * @param string $path
     * @param array $var
     * @return bool
     * @since 2.0.0
     */
    function yith_wcwl_is_wishlist(){
        global $yith_wcwl_is_wishlist;

        return $yith_wcwl_is_wishlist;
    }
}

if( !function_exists( 'yith_wcwl_locate_template' ) ) {
    /**
     * Locate the templates and return the path of the file found
     *
     * @param string $path
     * @param array $var
     * @return void
     * @since 1.0.0
     */
    function yith_wcwl_locate_template( $path, $var = NULL ){
        global $woocommerce;

        if( function_exists( 'WC' ) ){
            $woocommerce_base = WC()->template_path();
        }
        elseif( defined( 'WC_TEMPLATE_PATH' ) ){
            $woocommerce_base = WC_TEMPLATE_PATH;
        }
        else{
            $woocommerce_base = $woocommerce->plugin_path() . '/templates/';
        }

    	$template_woocommerce_path =  $woocommerce_base . $path;
        $template_path = '/' . $path;
        $plugin_path = YITH_WCWL_DIR . 'templates/' . $path;
    	
    	$located = locate_template( array(
            $template_woocommerce_path, // Search in <theme>/woocommerce/
            $template_path,             // Search in <theme>/
        ) );

        if( ! $located && file_exists( $plugin_path ) ){
            return apply_filters( 'yith_wcwl_locate_template', $plugin_path, $path );
        }

        return apply_filters( 'yith_wcwl_locate_template', $located, $path );
    }
}

if( !function_exists( 'yith_wcwl_get_template' ) ) {
    /**
     * Retrieve a template file.
     * 
     * @param string $path
     * @param mixed $var
     * @param bool $return
     * @return void
     * @since 1.0.0
     */
    function yith_wcwl_get_template( $path, $var = null, $return = false ) {
        $located = yith_wcwl_locate_template( $path, $var );      
        
        if ( $var && is_array( $var ) ) 
    		extract( $var );
                               
        if( $return )
            { ob_start(); }   
                                                                     
        // include file located
        include( $located );
        
        if( $return )
            { return ob_get_clean(); }
    }
}

if( !function_exists( 'yith_wcwl_count_products' ) ) {
    /**
     * Retrieve the number of products in the wishlist.
     *
     * @param $wishlist_token string Optional wishlist token
     * 
     * @return int
     * @since 1.0.0
     */
    function yith_wcwl_count_products( $wishlist_token = false ) {
        return YITH_WCWL()->count_products( $wishlist_token );
    }
}

if( !function_exists( 'yith_frontend_css_color_picker' ) ) {
    /**
     * Output a colour picker input box.
     * 
     * This function is not of the plugin YITH WCWL. It is from WooCommerce.
     * We redeclare it only because it is needed in the tab "Styles" where it is not available.
     * The original function name is woocommerce_frontend_css_colorpicker and it is declared in
     * wp-content/plugins/woocommerce/admin/settings/settings-frontend-styles.php
     *
     * @access public
     * @param mixed $name
     * @param mixed $id
     * @param mixed $value
     * @param string $desc (default: '')
     * @return void
     */
    function yith_frontend_css_color_picker( $name, $id, $value, $desc = '' ) {
    	global $woocommerce;

        $value = ! empty( $value ) ? $value : '#ffffff';

        echo '<div  class="color_box">
                  <table><tr><td>
                  <strong>' . $name . '</strong>
       		      <input name="' . esc_attr( $id ). '" id="' . $id . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick colorpickpreview" style="background-color: ' . $value . '" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
       		      </td></tr></table>
       		  </div>';
    
    }
}

if( !function_exists( 'yith_setcookie' ) ) {
    /**
     * Create a cookie.
     * 
     * @param string $name
     * @param mixed $value
     * @return bool
     * @since 1.0.0
     */
    function yith_setcookie( $name, $value = array(), $time = null ) {
        $time = $time != null ? $time : time() + 60 * 60 * 24 * 30;
        
        //$value = maybe_serialize( stripslashes_deep( $value ) );
        $value = json_encode( stripslashes_deep( $value ) );
        $expiration = apply_filters( 'yith_wcwl_cookie_expiration_time', $time ); // Default 30 days

        $_COOKIE[ $name ] = $value;
	    wc_setcookie( $name, $value, $expiration, false );
    }
}

if( !function_exists( 'yith_getcookie' ) ) {
    /**
     * Retrieve the value of a cookie.
     * 
     * @param string $name
     * @return mixed
     * @since 1.0.0
     */
    function yith_getcookie( $name ) {
        if( isset( $_COOKIE[$name] ) ) {
	        return json_decode( stripslashes( $_COOKIE[$name] ), true );
        }
        
        return array();
    }
}

if( !function_exists( 'yith_usecookies' ) ) {
    /**
     * Check if the user want to use cookies or not.
     * 
     * @return bool
     * @since 1.0.0
     */
    function yith_usecookies() {
        return get_option( 'yith_wcwl_use_cookie' ) == 'yes' ? true : false;
    }
}

if( !function_exists ( 'yith_destroycookie' ) ) {
    /**
     * Destroy a cookie.
     * 
     * @param string $name
     * @return void
     * @since 1.0.0
     */
    function yith_destroycookie( $name ) {
        yith_setcookie( $name, array(), time() - 3600 );
    }
}

if( !function_exists( 'yith_wcwl_object_id' ) ){
    /**
     * Retrieve translated page id, if wpml is installed
     *
     * @param $id int Original page id
     * @return int Translation id
     * @since 1.0.0
     */
    function yith_wcwl_object_id( $id ){
        if( function_exists( 'wpml_object_id_filter' ) ){
            return wpml_object_id_filter( $id, 'page', true );
        }
        elseif( function_exists( 'icl_object_id' ) ){
            return icl_object_id( $id, 'page', true );
        }
        else{
            return $id;
        }
    }
}