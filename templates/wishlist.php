<?php
/**
 * Wishlist pages template; load template parts basing on the url
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.0
 */

global $wpdb, $woocommerce;

// Start wishlist page printing
if( function_exists( 'wc_print_notices' ) ) {
    wc_print_notices();
}
else{
    $woocommerce->show_messages();
}
?>
<div id="yith-wcwl-messages"></div>

<?php yith_wcwl_get_template( 'wishlist-' . $template_part . '.php', $atts ) ?>