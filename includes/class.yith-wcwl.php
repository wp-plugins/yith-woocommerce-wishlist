<?php
/**
 * Main class
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.1.5
 */

if ( ! defined( 'YITH_WCWL' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCWL' ) ) {
    /**
     * WooCommerce Wishlist
     *
     * @since 1.0.0
     */
    class YITH_WCWL {
        /**
         * Single instance of the class
         *
         * @var \YITH_WCWL
         * @since 2.0.0
         */
        protected static $instance;

        /**
         * Errors array
         * 
         * @var array
         * @since 1.0.0
         */
        public $errors;

        /**
         * Last operation token
         *
         * @var string
         * @since 2.0.0
         */
        public $last_operation_token;
        
        /**
         * Details array
         * 
         * @var array
         * @since 1.0.0
         */
        public $details;
        
        /**
         * Messages array
         * 
         * @var array
         * @since 1.0.0
         */
        public $messages;

        /**
         * Returns single instance of the class
         *
         * @return \YITH_WCWL
         * @since 2.0.0
         */
        public static function get_instance(){
            if( is_null( self::$instance ) ){
                self::$instance = new self( $_REQUEST );
            }

            return self::$instance;
        }
        
        /**
         * Constructor.
         * 
         * @param array $details
         * @return \YITH_WCWL
         * @since 1.0.0
         */
        public function __construct( $details ) {
            $this->details = $details;                
            $this->wcwl_init = YITH_WCWL_Init();
            if( is_admin() ){
                $this->wcwl_admin_init = YITH_WCWL_Admin_Init();
            }

            add_action( 'after_setup_theme', array( $this, 'plugin_fw_loader' ), 1 );

            // add rewrite rule
            add_action( 'init', array( $this, 'add_rewrite_rules' ), 0 );
            add_filter( 'query_vars', array( $this, 'add_public_query_var' ) );

            add_action( 'init', array( $this, 'add_to_wishlist' ) );
            add_action( 'wp_ajax_add_to_wishlist', array( $this, 'add_to_wishlist_ajax' ) );
            add_action( 'wp_ajax_nopriv_add_to_wishlist', array( $this, 'add_to_wishlist_ajax' ) );

            add_action( 'init', array( $this, 'remove_from_wishlist' ) );
            add_action( 'wp_ajax_remove_from_wishlist', array( $this, 'remove_from_wishlist_ajax' ) );
            add_action( 'wp_ajax_nopriv_remove_from_wishlist', array( $this, 'remove_from_wishlist_ajax' ) );

	        add_action( 'wp_ajax_reload_wishlist_and_adding_elem', array( $this, 'reload_wishlist_and_adding_elem_ajax' ) );
	        add_action( 'wp_ajax_nopriv_reload_wishlist_and_adding_elem', array( $this, 'reload_wishlist_and_adding_elem_ajax' ) );

            add_action( 'woocommerce_add_to_cart', array( $this, 'remove_from_wishlist_after_add_to_cart' ) );
            add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'redirect_to_cart' ), 10, 2 );

	        add_action( 'yith_wcwl_before_wishlist_title', array( $this, 'print_notices' ) );

	        add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'yith_wfbt_redirect_after_add_to_cart' ), 10, 1 );

	        // add filter for font-awesome compatibility
	        add_filter( 'option_yith_wcwl_add_to_wishlist_icon', array( $this, 'update_font_awesome_classes' ) );
	        add_filter( 'option_yith_wcwl_add_to_cart_icon', array( $this, 'update_font_awesome_classes' ) );
        }

        /* === PLUGIN FW LOADER === */

        /**
         * Loads plugin fw, if not yet created
         *
         * @return void
         * @since 2.0.0
         */
        public function plugin_fw_loader() {
            if ( ! defined( 'YIT' ) || ! defined( 'YIT_CORE_PLUGIN' ) ) {
                require_once( YITH_WCWL_DIR . '/plugin-fw/yit-plugin.php' );
            }
        }

        /* === ITEMS METHODS === */
        
        /**
         * Check if the product exists in the wishlist.
         * 
         * @param int $product_id
         * @return bool
         * @since 1.0.0
         */
        public function is_product_in_wishlist( $product_id, $wishlist_id = false ) {
            global $wpdb;
                
            $exists = false;
                
    		if( is_user_logged_in() ) {		
    			$sql = "SELECT COUNT(*) as `cnt` FROM `{$wpdb->yith_wcwl_items}` WHERE `prod_id` = %d AND `user_id` = %d";
                $sql_args = array(
                    $product_id,
                    $this->details['user_id']
                );

                if( $wishlist_id != false ){
                    $sql .= " AND `wishlist_id` = %d";
                    $sql_args[] = $wishlist_id;
                }
                else{
                    $sql .= " AND `wishlist_id` IS NULL";
                }

    			$results = $wpdb->get_var( $wpdb->prepare( $sql, $sql_args ) );
    			$exists = (bool) ( $results > 0 );
    		}
            else {
                $wishlist = yith_getcookie( 'yith_wcwl_products' );

                foreach( $wishlist as $key => $item ){
                    if( $item['wishlist_id'] == $wishlist_id && $item['prod_id'] == $product_id ){
                        $exists = true;
                    }
                }
    		}
            
            return $exists;
        }
        
        /**
         * Add a product in the wishlist.
         * 
         * @return string "error", "true" or "exists"
         * @since 1.0.0
         */
        public function add() {
            global $wpdb;
            $prod_id = ( isset( $this->details['add_to_wishlist'] ) && is_numeric( $this->details['add_to_wishlist'] ) ) ? $this->details['add_to_wishlist'] : false;
            $wishlist_id = ( isset( $this->details['wishlist_id'] ) && strcmp( $this->details['wishlist_id'], 0 ) != 0 ) ? $this->details['wishlist_id'] : false;
            $quantity = ( isset( $this->details['quantity'] ) ) ? ( int ) $this->details['quantity'] : 1;
            $user_id = ( ! empty( $this->details['user_id'] ) ) ? $this->details['user_id'] : false;
            $wishlist_name = ( ! empty( $this->details['wishlist_name'] ) ) ? $this->details['wishlist_name'] : '';

            if ( $prod_id == false ) {
                $this->errors[] = __( 'An error occurred while adding products to the wishlist.', 'yit' );
                return "error";
            }

            //check for existence,  product ID, variation ID, variation data, and other cart item data
            if( strcmp( $wishlist_id, 'new' ) != 0 && $this->is_product_in_wishlist( $prod_id, $wishlist_id ) ) {
                if( $wishlist_id != false ){
                    $wishlist = $this->get_wishlist_detail( $wishlist_id );
                    $this->last_operation_token = $wishlist['wishlist_token'];
                }
                else{
                    $this->last_operation_token = false;
                }

                return "exists";
            }

            if( $user_id != false ) {

                $insert_args = array(
                    'prod_id' => $prod_id,
                    'user_id' => $user_id,
                    'quantity' => $quantity,
                    'dateadded' => date( 'Y-m-d H:i:s' )
                );

                if( ! empty( $wishlist_id ) && strcmp( $wishlist_id, 'new' ) != 0 ){
                    $insert_args[ 'wishlist_id' ] = $wishlist_id;

                    $wishlist = $this->get_wishlist_detail( $insert_args[ 'wishlist_id' ] );
                    $this->last_operation_token = $wishlist['wishlist_token'];
                }
                elseif( strcmp( $wishlist_id, 'new' ) == 0 ){
                    if( function_exists( 'YITH_WCWL_Premium' ) ){
                        $response = YITH_WCWL_Premium()->add_wishlist();
                    }
                    else{
                        $response = $this->add_wishlist();
                    }

                    if( $response == "error" ){
                        return "error";
                    }
                    else{
                        $insert_args[ 'wishlist_id' ] = $response;

                        $wishlist = $this->get_wishlist_detail( $insert_args[ 'wishlist_id' ] );
                        $this->last_operation_token = $wishlist['wishlist_token'];
                    }
                }
                elseif( empty( $wishlist_id ) ){
                    $wishlist_id = $this->generate_default_wishlist( $user_id );
                    $insert_args[ 'wishlist_id' ] = $wishlist_id;

                    if( $this->is_product_in_wishlist( $prod_id, $wishlist_id ) ){
                        return "exists";
                    }
                }

                $result = $wpdb->insert( $wpdb->yith_wcwl_items, $insert_args );
            }
            else {
                $cookie = array(
                    'prod_id' => $prod_id,
                    'quantity' => $quantity,
                    'wishlist_id' => $wishlist_id
                );

                $wishlist = yith_getcookie( 'yith_wcwl_products' );
                $found = $this->is_product_in_wishlist( $prod_id, $wishlist_id );

                if( ! $found ){
                    $wishlist[] = $cookie;
                }

                yith_setcookie( 'yith_wcwl_products', $wishlist );

                $result = true;
            }

            if( $result ) {
                return "true";
            }
            else {
                $this->errors[] = __( 'An error occurred while adding products to wishlist.', 'yit' );
                return "error";
            }
        }
        
        /**
         * Remove an entry from the wishlist.
         *
         * @return bool
         * @since 1.0.0
         */
        public function remove( $id = false ) {
            global $wpdb;

            if( ! empty( $id ) ) {
                _deprecated_argument( 'YITH_WCWL->remove()', '2.0.0', __( 'The "Remove" option now does not require any parameter' ) );
            }

            $prod_id = ( isset( $this->details['remove_from_wishlist'] ) && is_numeric( $this->details['remove_from_wishlist'] ) ) ? $this->details['remove_from_wishlist'] : false;
            $wishlist_id = ( isset( $this->details['wishlist_id'] ) && is_numeric( $this->details['wishlist_id'] ) ) ? $this->details['wishlist_id'] : false;
            $user_id = ( ! empty( $this->details['user_id'] ) ) ? $this->details['user_id'] : false;

            if( $prod_id == false ){
                return false;
            }

            if ( is_user_logged_in() ) {
                $sql = "DELETE FROM {$wpdb->yith_wcwl_items} WHERE user_id = %d AND prod_id = %d";
                $sql_args = array(
                    $user_id,
                    $prod_id
                );

                if( empty( $wishlist_id ) ){
                    $wishlist_id = $this->generate_default_wishlist( get_current_user_id() );
                }

                $wishlist = $this->get_wishlist_detail( $wishlist_id );
                $this->last_operation_token = $wishlist['wishlist_token'];

                $sql .= " AND wishlist_id = %d";
                $sql_args[] = $wishlist_id;

                $result = $wpdb->query( $wpdb->prepare( $sql, $sql_args ) );

                if ( $result ) {
                    return true;
                }
                else {
                    $this->errors[] = __( 'An error occurred while removing products from the wishlist', 'yit' );
                    return false;
                }
            }
            else {
                $wishlist = yith_getcookie( 'yith_wcwl_products' );

                foreach( $wishlist as $key => $item ){
                    if( $item['wishlist_id'] == $wishlist_id && $item['prod_id'] == $prod_id ){
                        unset( $wishlist[ $key ] );
                    }
                }

                yith_setcookie( 'yith_wcwl_products', $wishlist );

                return true;
            }
        }

        /**
         * Retrieve the number of products in the wishlist.
         *
         * @return int
         * @since 1.0.0
         */
        public function count_products( $wishlist_token = false ) {
            global $wpdb;

            if( is_user_logged_in() || $wishlist_token != false ) {
                $sql = "SELECT COUNT(*) AS `cnt`
                        FROM `{$wpdb->yith_wcwl_items}` AS i
                        LEFT JOIN `{$wpdb->yith_wcwl_wishlists}` AS l ON l.ID = i.wishlist_id";

                if( ! empty( $wishlist_token ) ){
                    $sql .= " WHERE l.`wishlist_token` = %s";
                    $query = $wpdb->prepare( $sql, $wishlist_token );
                }
                else{
                    $sql .= " WHERE l.`is_default` = %d AND l.`user_id` = %d";
                    $query = $wpdb->prepare( $sql, array( 1, get_current_user_id() ) );
                }

                $results = $wpdb->get_var( $query );
                return $results;
            }
            else {
                $cookie = yith_getcookie( 'yith_wcwl_products' );

                return count( $cookie );
            }
        }

        /**
         * Retrieve elements of the wishlist for a specific user
         *
         * @return array
         * @since 2.0.0
         */
        public function get_products( $args = array() ) {
            global $wpdb;

            $default = array(
                'user_id' => ( is_user_logged_in() ) ? get_current_user_id(): false,
                'product_id' => false,
                'wishlist_id' => false, //wishlist_id for a specific wishlist, false for default, or all for any wishlist
                'wishlist_token' => false,
                'wishlist_visibility' => 'all', // all, visible, public, shared, private
                'is_default' => false,
                'id' => false, // only for table select
                'limit' => false,
                'offset' => 0
            );

            $args = wp_parse_args( $args, $default );
            extract( $args );

            if( ! empty( $user_id ) || ! empty( $wishlist_token ) ) {
                $sql = "SELECT *
                        FROM `{$wpdb->yith_wcwl_items}` AS i
                        LEFT JOIN {$wpdb->yith_wcwl_wishlists} AS l ON l.`ID` = i.`wishlist_id` WHERE 1";

                if( ! empty( $user_id ) ){
                    $sql .= " AND i.`user_id` = %d";
                    $sql_args = array( $user_id );
                }

                if( ! empty( $product_id ) ){
                    $sql .= " AND i.`prod_id` = %d";
                    $sql_args[] = $product_id;
                }

                if( ! empty( $wishlist_id ) ){
                    $sql .= " AND i.`wishlist_id` = %d";
                    $sql_args[] = $wishlist_id;
                }
                elseif( empty( $wishlist_id ) && empty( $wishlist_token ) && $is_default != 1 ){
                    $sql .= " AND i.`wishlist_id` IS NULL";
                }

                if( ! empty( $wishlist_token ) ){
                    $sql .= " AND l.`wishlist_token` = %s";
                    $sql_args[] = $wishlist_token;
                }

                if( ! empty( $wishlist_visibility ) && $wishlist_visibility != 'all' ){
                    switch( $wishlist_visibility ){
                        case 'visible':
                            $sql .= " AND ( l.`wishlist_privacy` = %d OR l.`wishlist_privacy` = %d )";
                            $sql_args[] = 0;
                            $sql_args[] = 1;
                            break;
                        case 'public':
                            $sql .= " AND l.`wishlist_privacy` = %d";
                            $sql_args[] = 0;
                            break;
                        case 'shared':
                            $sql .= " AND l.`wishlist_privacy` = %d";
                            $sql_args[] = 1;
                            break;
                        case 'private':
                            $sql .= " AND l.`wishlist_privacy` = %d";
                            $sql_args[] = 2;
                            break;
                        default:
                            $sql .= " AND l.`wishlist_privacy` = %d";
                            $sql_args[] = 0;
                            break;
                    }
                }

                if( $is_default !== false ){
                    if( ! empty( $user_id ) ){
                        $this->generate_default_wishlist( $user_id );
                    }

                    $sql .= " AND l.`is_default` = %d";
                    $sql_args[] = $is_default;
                }

                if( ! empty( $id ) ){
                    $sql .= " AND `i.ID` = %d";
                    $sql_args[] = $id;
                }

                if( ! empty( $limit ) ){
                    $sql .= " LIMIT " . $offset . ", " . $limit;
                }

                $wishlist = $wpdb->get_results( $wpdb->prepare( $sql, $sql_args ), ARRAY_A );
            }
            else{
                $wishlist = yith_getcookie( 'yith_wcwl_products' );

                foreach( $wishlist as $key => $cookie ){
                    if( ! empty( $product_id ) && $cookie['prod_id'] != $product_id ){
                        unset( $wishlist[ $key ] );
                    }

                    if( ( ! empty( $wishlist_id ) && $wishlist_id != 'all' ) && $cookie['wishlist_id'] != $wishlist_id ){
                        unset( $wishlist[ $key ] );
                    }
                }

                if( ! empty( $limit ) ){
                    $wishlist = array_slice( $wishlist, $offset, $limit );
                }
            }

            return $wishlist;
        }

        /**
         * Count product occurrencies in users wishlists
         *
         * @param $product_id int
         *
         * @return int
         * @since 2.0.0
         */
        public function count_product_occurrencies( $product_id ) {
            global $wpdb;
            $sql = "SELECT COUNT(*) FROM {$wpdb->yith_wcwl_items} WHERE `prod_id` = %d";

            return $wpdb->get_var( $wpdb->prepare( $sql, $product_id ) );
        }

        /**
         * Retrieve details of a product in the wishlist.
         *
         * @param int $id
         * @param string $request_from
         * @return array
         * @since 1.0.0
         */
        public function get_product_details( $product_id, $wishlist_id = false ) {
            global $wpdb;

            return $this->get_products(
                array(
                    'prod_id' => $product_id,
                    'wishlist_id' => $wishlist_id
                )
            );
        }

        /**
         * Returns an array of users that created and populated a public wishlist
         *
         * @param $search array Array of arguments for the search
         * @return array
         * @since 2.0.0
         */
        public function get_users_with_wishlist( $args = array() ){
            global $wpdb;

            $default = array(
                'search' => false,
                'limit' => false,
                'offset' => 0
            );

            $args = wp_parse_args( $args, $default );
            extract( $args );

            $sql = "SELECT DISTINCT i.user_id
                    FROM {$wpdb->yith_wcwl_items} AS i
                    LEFT JOIN {$wpdb->yith_wcwl_wishlists} AS l ON i.wishlist_id = l.ID";

            if( ! empty( $search ) ){
                $sql .= " LEFT JOIN `{$wpdb->users}` AS u ON l.`user_id` = u.ID";
                $sql .= " LEFT JOIN `{$wpdb->usermeta}` AS umn ON umn.`user_id` = u.`ID`";
                $sql .= " LEFT JOIN `{$wpdb->usermeta}` AS ums ON ums.`user_id` = u.`ID`";
            }

            $sql .= " WHERE l.wishlist_privacy = %d";
            $sql_args = array( 0 );

            if( ! empty( $search ) ){
                $sql .= " AND ( umn.`meta_key` LIKE %s AND ums.`meta_key` LIKE %s AND ( u.`user_email` LIKE %s OR u.`user_login` LIKE %s OR umn.`meta_value` LIKE %s OR ums.`meta_value` LIKE %s ) )";
                $sql_args[] = 'first_name';
                $sql_args[] = 'last_name';
                $sql_args[] = "%" . $search . "%";
                $sql_args[] = "%" . $search . "%";
                $sql_args[] = "%" . $search . "%";
                $sql_args[] = "%" . $search . "%";
            }

            if( ! empty( $limit ) ){
                $sql .= " LIMIT " . $offset . ", " . $limit;
            }

            $res = $wpdb->get_col( $wpdb->prepare( $sql, $sql_args ) );
            return $res;
        }

        /**
         * Count users that have public wishlists
         *
         * @param $search string
         * @return int
         * @since 2.0.0
         */
        public function count_users_with_wishlists( $search  ){
            return count( $this->get_users_with_wishlist( array( 'search' => $search ) ) );
        }

        /* === WISHLISTS METHODS === */

        /**
         * Add a new wishlist for the user.
         *
         * @return string "error", "exists" or id of the inserted wishlist
         * @since 2.0.0
         */
        public function add_wishlist() {
            $user_id = ( ! empty( $this->details['user_id'] ) ) ? $this->details['user_id'] : false;

            if( $user_id == false ){
                $this->errors[] = __( 'You need to log in before creating a new wishlist', 'yit' );
                return "error";
            }

            return $this->generate_default_wishlist( $user_id );
        }

        /**
         * Update wishlist with arguments passed as second parameter
         *
         * @param $wishlist_id int
         * @param $args array Array of parameters to user in $wpdb->update
         * @return bool
         * @since 2.0.0
         */
        public function update_wishlist( $wishlist_id, $args = array() ) {
            return false;
        }

        /**
         * Delete indicated wishlist
         *
         * @param $wishlist_id int
         * @return bool
         * @since 2.0.0
         */
        public function remove_wishlist( $wishlist_id ) {
            return false;
        }

        /**
         * Checks if a wishlist with the given slug is already in the db
         *
         * @param string $wishlist_slug
         * @param int    $user_id
         * @return bool
         * @since 2.0.0
         */
        public function wishlist_exists( $wishlist_slug, $user_id ) {
            global $wpdb;
            $sql = "SELECT COUNT(*) AS `cnt` FROM `{$wpdb->yith_wcwl_wishlists}` WHERE `wishlist_slug` = %s AND `user_id` = %d";
            $sql_args = array(
                $wishlist_slug,
                $user_id
            );

            $res = $wpdb->get_var( $wpdb->prepare( $sql, $sql_args ) );

            return (bool) ( $res > 0 );
        }

        /**
         * Retrieve all the wishlist matching speciefied arguments
         *
         * @return array
         * @since 2.0.0
         */
        public function get_wishlists( $args = array() ){
            global $wpdb;

            $default = array(
                'id' => false,
                'user_id' => ( is_user_logged_in() ) ? get_current_user_id(): false,
                'wishlist_slug' => false,
                'wishlist_name' => false,
                'wishlist_token' => false,
                'wishlist_visibility' => 'all', // all, visible, public, shared, private
                'user_search' => false,
                'is_default' => false,
                'orderby' => 'ID',
                'order' => 'DESC',
                'limit' =>  false,
                'offset' => 0,
	            'show_empty' => true
            );

            $args = wp_parse_args( $args, $default );
            extract( $args );

            $sql = "SELECT l.*";

            if( ! empty( $user_search ) ){
                $sql .= ", u.user_email, umn.meta_value AS first_name, ums.meta_value AS last_name";
            }

            $sql .= " FROM `{$wpdb->yith_wcwl_wishlists}` AS l";

            if( ! empty( $user_search ) || $orderby == 'user_login' ) {
                $sql .= " LEFT JOIN `{$wpdb->users}` AS u ON l.`user_id` = u.ID";
            }

            if( ! empty( $user_search ) ){
                $sql .= " LEFT JOIN `{$wpdb->usermeta}` AS umn ON umn.`user_id` = u.`ID`";
                $sql .= " LEFT JOIN `{$wpdb->usermeta}` AS ums ON ums.`user_id` = u.`ID`";
            }

            $sql .= " WHERE 1";

            if( ! empty( $user_id ) ){
                $sql .= " AND l.`user_id` = %d";

                $sql_args = array(
                    $user_id
                );
            }

            if( ! empty( $user_search ) ){
                $sql .= " AND ( umn.`meta_key` LIKE %s AND ums.`meta_key` LIKE %s AND ( u.`user_email` LIKE %s OR umn.`meta_value` LIKE %s OR ums.`meta_value` LIKE %s ) )";
                $sql_args[] = 'first_name';
                $sql_args[] = 'last_name';
                $sql_args[] = "%" . $user_search . "%";
                $sql_args[] = "%" . $user_search . "%";
                $sql_args[] = "%" . $user_search . "%";
            }

            if( $is_default !== false ){
                $sql .= " AND l.`is_default` = %d";
                $sql_args[] = $is_default;
            }

            if( ! empty( $id ) ){
                $sql .= " AND l.`ID` = %d";
                $sql_args[] = $id;
            }

            if( $wishlist_slug !== false ){
                $sql .= " AND l.`wishlist_slug` = %s";
                $sql_args[] = sanitize_title_with_dashes( $wishlist_slug );
            }

            if( ! empty( $wishlist_token ) ){
                $sql .= " AND l.`wishlist_token` = %s";
                $sql_args[] = $wishlist_token;
            }

            if( ! empty( $wishlist_name ) ){
                $sql .= " AND l.`wishlist_name` LIKE %s";
                $sql_args[] = "%" . $wishlist_name . "%";
            }

            if( ! empty( $wishlist_visibility ) && $wishlist_visibility != 'all' ){
                switch( $wishlist_visibility ){
                    case 'visible':
                        $sql .= " AND ( l.`wishlist_privacy` = %d OR l.`is_public` = %d )";
                        $sql_args[] = 0;
                        $sql_args[] = 1;
                        break;
                    case 'public':
                        $sql .= " AND l.`wishlist_privacy` = %d";
                        $sql_args[] = 0;
                        break;
                    case 'shared':
                        $sql .= " AND l.`wishlist_privacy` = %d";
                        $sql_args[] = 1;
                        break;
                    case 'private':
                        $sql .= " AND l.`wishlist_privacy` = %d";
                        $sql_args[] = 2;
                        break;
                    default:
                        $sql .= " AND l.`wishlist_privacy` = %d";
                        $sql_args[] = 0;
                        break;
                }
            }

	        if( ! $show_empty ){
		        $sql .= " AND l.`ID` IN ( SELECT wishlist_id FROM {$wpdb->yith_wcwl_items} )";
	        }

            $sql .= " ORDER BY " . $orderby . " " . $order;

            if( ! empty( $limit ) ){
                $sql .= " LIMIT " . $offset . ", " . $limit;
            }

            if( ! empty( $sql_args ) ){
                $sql = $wpdb->prepare( $sql, $sql_args );
            }

            $lists = $wpdb->get_results( $sql, ARRAY_A );

            return $lists;
        }

        /**
         * Returns details of a wishlist, searching it by wishlist id
         *
         * @param $wishlist_id int
         * @return array
         * @since 2.0.0
         */
        public function get_wishlist_detail( $wishlist_id ) {
            global $wpdb;

            $sql = "SELECT * FROM {$wpdb->yith_wcwl_wishlists} WHERE `ID` = %d";
            return $wpdb->get_row( $wpdb->prepare( $sql, $wishlist_id ), ARRAY_A );
        }

        /**
         * Returns details of a wishlist, searching it by wishlist token
         *
         * @param $wishlist_id int
         * @return array
         * @since 2.0.0
         */
        public function get_wishlist_detail_by_token( $wishlist_token ) {
            global $wpdb;

            $sql = "SELECT * FROM {$wpdb->yith_wcwl_wishlists} WHERE `wishlist_token` = %s";
            return $wpdb->get_row( $wpdb->prepare( $sql, $wishlist_token ), ARRAY_A );
        }

        /**
         * Generate default wishlist for a specific user, adding all NULL items of the user to it
         *
         * @param $user_id int
         * @return int Default wishlist id
         * @since 2.0.0
         */
        public function generate_default_wishlist( $user_id ){
            global $wpdb;

            $wishlists = $this->get_wishlists( array(
                'user_id' => $user_id,
                'is_default' => 1
            ) );

            if( ! empty( $wishlists ) ){
                $default_user_wishlist = $wishlists[0]['ID'];
                $this->last_operation_token = $wishlists[0]['wishlist_token'];
            }
            else{
                $token = $this->generate_wishlist_token();
                $this->last_operation_token = $token;

                $wpdb->insert( $wpdb->yith_wcwl_wishlists, array(
                    'user_id' => $user_id,
                    'wishlist_slug' => '',
                    'wishlist_token' => $token,
                    'wishlist_name' => '',
                    'wishlist_privacy' => 0,
                    'is_default' => 1
                ) );

                $default_user_wishlist = $wpdb->insert_id;
            }

            $sql = "UPDATE {$wpdb->yith_wcwl_items} SET wishlist_id = %d WHERE user_id = %d AND wishlist_id IS NULL";
            $sql_args = array(
                $default_user_wishlist,
                $user_id
            );

            $wpdb->query( $wpdb->prepare( $sql, $sql_args ) );

            return $default_user_wishlist;
        }

        /**
         * Generate a token to visit wishlist
         *
         * @return string token
         * @since 2.0.0
         */
        public function generate_wishlist_token(){
            global $wpdb;
            $count = 0;
            $sql = "SELECT COUNT(*) FROM `{$wpdb->yith_wcwl_wishlists}` WHERE `wishlist_token` = %s";

            do {
                $dictionary = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                $nchars = 12;
                $token = "";

                for( $i = 0; $i <= $nchars - 1; $i++ ){
                    $token .= $dictionary[ mt_rand( 0, strlen( $dictionary ) - 1 ) ];
                }

                $count = $wpdb->get_var( $wpdb->prepare( $sql, $token ) );
            }
            while( $count != 0 );

            return $token;
        }

        /* === GENERAL METHODS === */

        /**
         * Add rewrite rules for wishlist
         *
         * @return void
         * @since 2.0.0
         */
        public function add_rewrite_rules() {
            global $wp_query;
            $wishlist_page_id = isset( $_POST['yith_wcwl_wishlist_page_id'] ) ? $_POST['yith_wcwl_wishlist_page_id'] : get_option( 'yith_wcwl_wishlist_page_id' );
	        $wishlist_page_id = function_exists( 'icl_object_id' ) ? icl_object_id( $wishlist_page_id, 'page', true ) : $wishlist_page_id;

            if( empty( $wishlist_page_id ) ){
                return;
            }

            $wishlist_page = get_post( $wishlist_page_id );
	        $wishlist_page_slug = $wishlist_page->post_name;

            add_rewrite_rule( '(([^/]+/)*' . $wishlist_page_slug . ')(/(.*))?/page/([0-9]{1,})/?$', 'index.php?pagename=$matches[1]&wishlist-action=$matches[4]&paged=$matches[5]', 'top' );
            add_rewrite_rule( '(([^/]+/)*' . $wishlist_page_slug . ')(/(.*))?/?$', 'index.php?pagename=$matches[1]&wishlist-action=$matches[4]', 'top' );
        }

        /**
         * Adds public query var for wishlist
         *
         * @param $public_var array
         * @return array
         * @since 2.0.0
         */
        public function add_public_query_var( $public_var ) {
            $public_var[] = 'wishlist-action';
            $public_var[] = 'wishlist_id';

            return $public_var;
        }
        
        /**
         * Get all errors in HTML mode or simple string.
         * 
         * @param bool $html
         * @return string
         * @since 1.0.0
         */
        public function get_errors( $html = true ) {
            return implode( ( $html ? '<br />' : ', ' ), $this->errors );
        }
        
        /**
         * Build wishlist page URL.
         * 
         * @return string
         * @since 1.0.0
         */
        public function get_wishlist_url( $action = 'view' ) {
            $wishlist_page_id = get_option( 'yith_wcwl_wishlist_page_id' );
	        $wishlist_page_id = function_exists( 'icl_object_id' ) ? icl_object_id( $wishlist_page_id, 'page', true ) : $wishlist_page_id;

            if( get_option( 'permalink_structure' ) && ! defined( 'ICL_PLUGIN_PATH' ) ) {
	            $wishlist_permalink = trailingslashit( get_the_permalink( $wishlist_page_id ) );
	            $base_url = trailingslashit( $wishlist_permalink . $action );
            }
            else{
                $base_url = get_the_permalink( $wishlist_page_id );
                $action_params = explode( '/', $action );
                $params = array();

                if( isset( $action_params[1] ) ){
                    $action = $action_params[0];
                    $params['wishlist-action'] = $action;

                    if( $action == 'view' ){
                        $params['wishlist_id'] = $action_params[1];
                    }
                    elseif( $action == 'user' ){
                        $params['user_id'] = $action_params[1];
                    }
                }
                else{
                    $params['wishlist-action'] = $action;
                }

                $base_url = add_query_arg( $params, $base_url );
            }

            if( defined( 'ICL_PLUGIN_PATH' ) ){
		        $base_url = add_query_arg( 'lang', icl_get_current_language(), $base_url );
	        }

            return apply_filters( 'yith_wcwl_wishlist_page_url', esc_url_raw( $base_url ) );
        }

        /**
         * Build the URL used to remove an item from the wishlist.
         *
         * @param int $item_id
         * @return string
         * @since 1.0.0
         */
        public function get_remove_url( $item_id ) {
            return esc_url( add_query_arg( 'remove_from_wishlist', $item_id ) );
        }
        
        /**
         * Build the URL used to add an item in the wishlist.
         *
         * @return string
         * @since 1.0.0
         */
        public function get_addtowishlist_url() {
            global $product;
            	
            return esc_url( add_query_arg( 'add_to_wishlist', $product->id ) );
        }
        
        /**
         * Build the URL used to add an item to the cart from the wishlist.
         * 
         * @param int $id
         * @param int $user_id
         * @return string
         * @since 1.0.0
         */
        public function get_addtocart_url( $id, $user_id = '' ) {
            _deprecated_function( 'YITH_WCWL::get_addtocart_url', '2.0.0' );

            //$product = $yith_wcwl->get_product_details( $id );
            if ( function_exists( 'get_product' ) )    
                $product = get_product( $id );
            else
                $product = new WC_Product( $id );
                
            if ( $product->product_type == 'variable' ) {
                return get_permalink( $product->id );
            }
            
    		$url = YITH_WCWL_URL . 'add-to-cart.php?wishlist_item_id=' . rtrim( $id, '_' );
    		
    		if( $user_id != '' ) {
    			$url .= '&id=' . $user_id;
    		}
            
    		return $url;
    	}

        /**
         * Build the URL used for an external/affiliate product.
         *
         * @deprecated
         * @param $id
         * @return string
         */
        public function get_affiliate_product_url( $id ) {
            _deprecated_function( 'YITH_WCWL::get_affiliate_product_url', '2.0.0' );
            $product = get_product( $id );
            return get_post_meta( $product->id, '_product_url', true );
        }
        
        /**
         * Build an URL with the nonce added.
         * 
         * @param string $action
         * @param string $url
         * @return string
         * @since 1.0.0
         */
        public function get_nonce_url( $action, $url = '' ) {
            return esc_url( add_query_arg( '_n', wp_create_nonce( 'yith-wcwl-' . $action ), $url ) );
        }

	    /**
	     * Prints wc notice for wishlist pages
	     *
	     * @return void
	     * @since 2.0.5
	     */
	    public function print_notices() {
		    global $woocommerce;

		    // Start wishlist page printing
		    if( function_exists( 'wc_print_notices' ) ) {
			    wc_print_notices();
		    }
		    elseif( method_exists( $woocommerce, 'show_message' ) ){
			    $woocommerce->show_messages();
		    }
	    }

	    /* === FONTAWESOME FIX === */
	    /**
	     * Modernize font-awesome class, for old wishlist users
	     *
	     * @param $class string Original font-awesome class
	     * @return string Filtered font-awesome class
	     * @since 2.0.2
	     */
	    public function update_font_awesome_classes( $class ) {
		    $exceptions = array(
			    'icon-envelope' => 'fa-envelope-o',
			    'icon-star-empty' => 'fa-star-o',
			    'icon-ok' => 'fa-check',
			    'icon-zoom-in' => 'fa-search-plus',
			    'icon-zoom-out' => 'fa-search-minus',
			    'icon-off' => 'fa-power-off',
			    'icon-trash' => 'fa-trash-o',
			    'icon-share' => 'fa-share-square-o',
			    'icon-check' => 'fa-check-square-o',
			    'icon-move' => 'fa-arrows',
			    'icon-file' => 'fa-file-o',
			    'icon-time' => 'fa-clock-o',
			    'icon-download-alt' => 'fa-download',
			    'icon-download' => 'fa-arrow-circle-o-down',
			    'icon-upload' => 'fa-arrow-circle-o-up',
			    'icon-play-circle' => 'fa-play-circle-o',
			    'icon-indent-left' => 'fa-dedent',
			    'icon-indent-right' => 'fa-indent',
			    'icon-facetime-video' => 'fa-video-camera',
			    'icon-picture' => 'fa-picture-o',
			    'icon-plus-sign' => 'fa-plus-circle',
			    'icon-minus-sign' => 'fa-minus-circle',
			    'icon-remove-sign' => 'fa-times-circle',
			    'icon-ok-sign' => 'fa-check-circle',
			    'icon-question-sign' => 'fa-question-circle',
			    'icon-info-sign' => 'fa-info-circle',
			    'icon-screenshot' => 'fa-crosshairs',
			    'icon-remove-circle' => 'fa-times-circle-o',
			    'icon-ok-circle' => 'fa-check-circle-o',
			    'icon-ban-circle' => 'fa-ban',
			    'icon-share-alt' => 'fa-share',
			    'icon-resize-full' => 'fa-expand',
			    'icon-resize-small' => 'fa-compress',
			    'icon-exclamation-sign' => 'fa-exclamation-circle',
			    'icon-eye-open' => 'fa-eye',
			    'icon-eye-close' => 'fa-eye-slash',
			    'icon-warning-sign' => 'fa-warning',
			    'icon-folder-close' => 'fa-folder',
			    'icon-resize-vertical' => 'fa-arrows-v',
			    'icon-resize-horizontal' => 'fa-arrows-h',
			    'icon-twitter-sign' => 'fa-twitter-square',
			    'icon-facebook-sign' => 'fa-facebook-square',
			    'icon-thumbs-up' => 'fa-thumbs-o-up',
			    'icon-thumbs-down' => 'fa-thumbs-o-down',
			    'icon-heart-empty' => 'fa-heart-o',
			    'icon-signout' => 'fa-sign-out',
			    'icon-linkedin-sign' => 'fa-linkedin-square',
			    'icon-pushpin' => 'fa-thumb-tack',
			    'icon-signin' => 'fa-sign-in',
			    'icon-github-sign' => 'fa-github-square',
			    'icon-upload-alt' => 'fa-upload',
			    'icon-lemon' => 'fa-lemon-o',
			    'icon-check-empty' => 'fa-square-o',
			    'icon-bookmark-empty' => 'fa-bookmark-o',
			    'icon-phone-sign' => 'fa-phone-square',
			    'icon-hdd' => 'fa-hdd-o',
			    'icon-hand-right' => 'fa-hand-o-right',
			    'icon-hand-left' => 'fa-hand-o-left',
			    'icon-hand-up' => 'fa-hand-o-up',
			    'icon-hand-down' => 'fa-hand-o-down',
			    'icon-circle-arrow-left' => 'fa-arrow-circle-left',
			    'icon-circle-arrow-right' => 'fa-arrow-circle-right',
			    'icon-circle-arrow-up' => 'fa-arrow-circle-up',
			    'icon-circle-arrow-down' => 'fa-arrow-circle-down',
			    'icon-fullscreen' => 'fa-arrows-alt',
			    'icon-beaker' => 'fa-flask',
			    'icon-paper-clip' => 'fa-paperclip',
			    'icon-sign-blank' => 'fa-square',
			    'icon-pinterest-sign' => 'fa-pinterest-square',
			    'icon-google-plus-sign' => 'fa-google-plus-square',
			    'icon-envelope-alt' => 'fa-envelope',
			    'icon-comment-alt' => 'fa-comment-o',
			    'icon-comments-alt' => 'fa-comments-o'
		    );

		    if( in_array( $class, array_keys( $exceptions ) ) ){
			    $class = $exceptions[ $class ];
		    }

		    $class = str_replace( 'icon-', 'fa-', $class );

		    return $class;
	    }

        /* === REQUEST HANDLING METHODS === */

        /**
         * Adds an element to wishlist when default AJAX method cannot be used
         *
         * @return void
         * @since 2.0.0
         */
        public function add_to_wishlist(){
            // add item to wishlist when javascript is not enabled
            if( isset( $_GET['add_to_wishlist'] ) ) {
                $this->add();
            }
        }

        /**
         * Removes an element from wishlist when default AJAX method cannot be used
         *
         * @return void
         * @since 2.0.0
         */
        public function remove_from_wishlist(){
            // remove item from wishlist when javascript is not enabled
            if( isset( $_GET['remove_from_wishlist'] ) ){
                $this->remove();
            }
        }

        /**
         * Removes an element after add to cart, if option is enabled in panel
         *
         * @return void
         * @since 2.0.0
         */
        public function remove_from_wishlist_after_add_to_cart() {
            if( get_option( 'yith_wcwl_remove_after_add_to_cart' ) == 'yes' ){
                if( isset( $_REQUEST['remove_from_wishlist_after_add_to_cart'] ) ) {

                    $this->details['remove_from_wishlist'] = $_REQUEST['remove_from_wishlist_after_add_to_cart'];

                    if ( isset( $_REQUEST['wishlist_id'] ) ) {
                        $this->details['wishlist_id'] = $_REQUEST['wishlist_id'];
                    }
                }
                elseif( yith_wcwl_is_wishlist() ){
                    $this->details['remove_from_wishlist'] = $_REQUEST['add-to-cart'];

                    if ( isset( $_REQUEST['wishlist_id'] ) ) {
                        $this->details['wishlist_id'] = $_REQUEST['wishlist_id'];
                    }
                }

                $this->remove();
            }
        }

        /**
         * Redirect to cart after "Add to cart" button pressed on wishlist table
         *
         * @param $url string Original redirect url
         * @return string Redirect url
         * @since 2.0.0
         */
        public function redirect_to_cart( $url, $product ) {
	        global $yith_wcwl_wishlist_token;

	        $wishlist = $this->get_wishlist_detail_by_token( $yith_wcwl_wishlist_token );
	        $wishlist_id = $wishlist['ID'];

            if( $product->is_type( 'simple' ) && get_option( 'yith_wcwl_redirect_cart' ) == 'yes' ){
                if( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && yith_wcwl_is_wishlist() ){
                    $url = add_query_arg( 'add-to-cart', $product->id, WC()->cart->get_cart_url() );
                }
            }

            if( get_option( 'yith_wcwl_remove_after_add_to_cart' ) == 'yes' ){
                if( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && yith_wcwl_is_wishlist() ) {
                    $url = add_query_arg(
	                    array(
		                    'remove_from_wishlist_after_add_to_cart' => $product->id,
		                    'wishlist_id' => $wishlist_id,
		                    'wishlist_token' => $yith_wcwl_wishlist_token
	                    ),
	                    $url
                    );
                }
            }

            return apply_filters( 'yit_wcwl_add_to_cart_redirect_url', esc_url( $url ) );
        }

        /**
         * AJAX: add to wishlist action
         * 
         * @return void
         * @since 1.0.0
         */
        public function add_to_wishlist_ajax() {
            $return = $this->add();
            $message = '';
            $user_id = isset( $this->details['user_id'] ) ? $this->details['user_id'] : false;
            $wishlists = array();

            if( $return == 'true' ){
                $message = apply_filters( 'yith_wcwl_product_added_to_wishlist_message', get_option( 'yith_wcwl_product_added_text' ) );
            }
            elseif( $return == 'exists' ){
                $message = apply_filters( 'yith_wcwl_product_already_in_wishlist_message', get_option( 'yith_wcwl_already_in_wishlist_text' ) );
            }
            elseif( count( $this->errors ) > 0 ){
                $message = apply_filters( 'yith_wcwl_error_adding_to_wishlist_message', $this->get_errors() );
            }

            if( $user_id != false ){
                $wishlists = $this->get_wishlists( array( 'user_id' => $user_id ) );
            }

            wp_send_json(
                array(
                    'result' => $return,
                    'message' => $message,
                    'user_wishlists' => $wishlists,
                    'wishlist_url' => $this->get_wishlist_url( 'view' . ( isset( $this->last_operation_token ) ? ( '/' . $this->last_operation_token ) : false ) ),
                )
            );
        }
        
        /**
         * AJAX: remove from wishlist action
         * 
         * @return void
         * @since 1.0.0
         */
        public function remove_from_wishlist_ajax() {
            $wishlist_token = isset( $this->details['wishlist_token'] ) ? $this->details['wishlist_token'] : false;
            $count = yith_wcwl_count_products( $wishlist_token );
            $message = '';

            if( $count != 0 ) {
                if ( $this->remove() ) {
                    $message = apply_filters( 'yith_wcwl_product_removed_text', __( 'Product successfully removed.', 'yit' ) );
                    $count --;
                }
                else {
                    $message = apply_filters( 'yith_wcwl_unable_to_remove_product_message', __( 'Error. Unable to remove the product from the wishlist.', 'yit' ) );
                }
            }
            else{
                $message = apply_filters( 'yith_wcwl_no_product_to_remove_message', __( 'No products were added to the wishlist', 'yit' ) );
            }

            wc_add_notice( $message );

            $atts = array( 'wishlist_id' => $wishlist_token );
            if( isset( $this->details['pagination'] ) ){
                $atts['pagination'] = $this->details['pagination'];
            }

            if( isset( $this->details['per_page'] ) ){
                $atts['per_page'] = $this->details['per_page'];
            }

            echo YITH_WCWL_Shortcode::wishlist( $atts );
            die();
        }

	    /*******************************************
	     * INTEGRATION WC Frequently Bought Together
	     *******************************************/

	    /**
	     * AJAX: reload wishlist and adding elem action
	     *
	     * @return void
	     * @since 1.0.0
	     */
	    public function reload_wishlist_and_adding_elem_ajax() {

		    $return     = $this->add();
		    $message    = '';
		    $type_msg   = 'success';

		    if( $return == 'true' ){
			    $message = apply_filters( 'yith_wcwl_product_added_to_wishlist_message', get_option( 'yith_wcwl_product_added_text' ) );
		    }
		    elseif( $return == 'exists' ){
			    $message = apply_filters( 'yith_wcwl_product_already_in_wishlist_message', get_option( 'yith_wcwl_already_in_wishlist_text' ) );
			    $type_msg = 'error';
		    }
		    else {
			    $message = apply_filters( 'yith_wcwl_product_removed_text', __( 'An error as occurred.', 'yit' ) );
			    $type_msg = 'error';
		    }

		    $wishlist_token = isset( $this->details['wishlist_token'] ) ? $this->details['wishlist_token'] : false;

		    $atts = array( 'wishlist_id' => $wishlist_token );
		    if( isset( $this->details['pagination'] ) ){
			    $atts['pagination'] = $this->details['pagination'];
		    }

		    if( isset( $this->details['per_page'] ) ){
			    $atts['per_page'] = $this->details['per_page'];
		    }

		    ob_start();

		    wc_add_notice( $message, $type_msg );

		    echo '<div>'. YITH_WCWL_Shortcode::wishlist( $atts ) . '</div>';

		    echo ob_get_clean();
		    die();

	    }

	    /**
	     * redirect after add to cart from YITH WooCommerce Frequently Bought Together Premium shortcode
	     *
	     * @since 1.0.0
	     */
	    public function yith_wfbt_redirect_after_add_to_cart( $url ){
		    if( ! isset( $_REQUEST['yith_wfbt_shortcode'] ) ) {
			    return $url;
		    }

		    return get_option( 'yith_wcwl_redirect_cart' ) == 'yes' ? WC()->cart->get_cart_url() : $this->get_wishlist_url();
	    }


    }
}

/**
 * Unique access to instance of YITH_WCWL class
 *
 * @return \YITH_WCWL
 * @since 2.0.0
 */
function YITH_WCWL(){
    return YITH_WCWL::get_instance();
}