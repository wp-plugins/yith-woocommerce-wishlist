<?php
/**
 * Install file
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.1.5
 */

if ( !defined( 'YITH_WCWL' ) ) {
    exit;
} // Exit if accessed directly

if ( !class_exists( 'YITH_WCWL_Install' ) ) {
    /**
     * Install plugin table and create the wishlist page
     *
     * @since 1.0.0
     */
    class YITH_WCWL_Install {

        /**
         * Single instance of the class
         *
         * @var \YITH_WCWL_Install
         * @since 2.0.0
         */
        protected static $instance;

        /**
         * Items table name
         *
         * @var string
         * @access private
         * @since 1.0.0
         */
        private $_table_items;

        /**
         * Items table name
         *
         * @var string
         * @access private
         * @since 1.0.0
         */
        private $_table_wishlists;

        /**
         * Returns single instance of the class
         *
         * @return \YITH_WCWL_Install
         * @since 2.0.0
         */
        public static function get_instance(){
            if( is_null( self::$instance ) ){
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @since 1.0.0
         */
        public function __construct() {
            global $wpdb;

            // define local private attribute
            $this->_table_items = $wpdb->prefix . 'yith_wcwl';
            $this->_table_wishlists = $wpdb->prefix . 'yith_wcwl_lists';

            // add custom field to global $wpdb
            $wpdb->yith_wcwl_items = $this->_table_items;
            $wpdb->yith_wcwl_wishlists = $this->_table_wishlists;

            // define constant to use allover the application
            define( 'YITH_WCWL_ITEMS_TABLE', $this->_table_items );
            define( 'YITH_WCWL_WISHLISTS_TABLE', $this->_table_wishlists );

            /**
             * @deprecated
             */
            define( 'YITH_WCWL_TABLE', $this->_table_items );
        }

        /**
         * Initiator. Replace the constructor.
         *
         * @since 1.0.0
         */
        public function init() {
            $this->_add_tables();
            $this->_add_pages();

            update_option( 'yith_wcwl_version', YITH_WCWL_VERSION );
            update_option( 'yith_wcwl_db_version', YITH_WCWL_DB_VERSION );
        }

        /**
         * Update db from version 1.0 to 2.0
         *
         * @since 1.0.0
         */
        public function update() {
            $this->_add_tables();

            update_option( 'yith_wcwl_version', YITH_WCWL_VERSION );
            update_option( 'yith_wcwl_db_version', YITH_WCWL_DB_VERSION );
        }

        /**
         * Set options to their default value.
         *
         * @param array $options
         * @return bool
         * @since 1.0.0
         */
        public function default_options( $options ) {
            foreach( $options as $section ) {
                foreach( $section as $value ) {
                    if ( isset( $value['std'] ) && isset( $value['id'] ) ) {
                        add_option($value['id'], $value['std']);
                    }
                }
            }
        }

        /**
         * Check if the table of the plugin already exists.
         *
         * @return bool
         * @since 1.0.0
         */
        public function is_installed() {
            global $wpdb;
            $number_of_tables = $wpdb->query("SHOW TABLES LIKE '{$this->_table_items}%'" );

            return (bool) ( $number_of_tables == 2 );
        }

        /**
         * Add tables for a fresh installation
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        private function _add_tables() {
            $this->_add_wishlists_table();
            $this->_add_items_table();
        }

        /**
         * Add the wishlists table to the database.
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        private function _add_wishlists_table() {
            global $wpdb;

            if( ! $this->is_installed() ){
                $sql = "CREATE TABLE {$this->_table_wishlists} (
                            ID INT( 11 ) NOT NULL AUTO_INCREMENT,
                            user_id INT( 11 ) NOT NULL,
                            wishlist_slug VARCHAR( 200 ) NOT NULL,
                            wishlist_name TEXT,
                            wishlist_token VARCHAR( 64 ) NOT NULL UNIQUE,
                            wishlist_privacy TINYINT( 1 ) NOT NULL DEFAULT 0,
                            is_default TINYINT( 1 ) NOT NULL DEFAULT 0,
                            PRIMARY KEY  ( ID ),
                            KEY ( wishlist_slug )
                        ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
            }

            return;
        }

        /**
         * Add the items table to the database.
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        private function _add_items_table() {
            global $wpdb;

            if( ! $this->is_installed() || get_option( 'yith_wcwl_db_version' ) != '2.0.0' ) {
                $sql = "CREATE TABLE {$this->_table_items} (
                            ID int( 11 ) NOT NULL AUTO_INCREMENT,
                            prod_id int( 11 ) NOT NULL,
                            quantity int( 11 ) NOT NULL,
                            user_id int( 11 ) NOT NULL,
                            wishlist_id int( 11 ) NULL,
                            dateadded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY  ( ID ),
                            KEY ( prod_id )
                        ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
            }

            return;
        }

        /**
         * Add a page "Wishlist".
         *
         * @return void
         * @since 1.0.0
         */
        private function _add_pages() {
            global $wpdb;

            $option_value = get_option( 'yith-wcwl-page-id' );

            if ( $option_value > 0 && get_post( $option_value ) )
                return;

            $page_found = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_name` = 'wishlist' LIMIT 1;" );
            if ( $page_found ) :
                if ( ! $option_value )
                    update_option( 'yith-wcwl-page-id', $page_found );
                return;
            endif;

            $page_data = array(
                'post_status' 		=> 'publish',
                'post_type' 		=> 'page',
                'post_author' 		=> 1,
                'post_name' 		=> esc_sql( _x( 'wishlist', 'page_slug', 'yit' ) ),
                'post_title' 		=> __( 'Wishlist', 'yit' ),
                'post_content' 		=> '[yith_wcwl_wishlist]',
                'post_parent' 		=> 0,
                'comment_status' 	=> 'closed'
            );
            $page_id = wp_insert_post( $page_data );

            update_option( 'yith-wcwl-page-id', $page_id );
            update_option( 'yith_wcwl_wishlist_page_id', $page_id );
        }
    }
}

/**
 * Unique access to instance of YITH_WCWL_Install class
 *
 * @return \YITH_WCWL_Install
 * @since 2.0.0
 */
function YITH_WCWL_Install(){
    return YITH_WCWL_Install::get_instance();
}