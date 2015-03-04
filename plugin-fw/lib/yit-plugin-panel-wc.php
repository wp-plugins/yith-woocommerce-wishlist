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

if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
    /**
     * YIT Plugin Panel for WooCommerce
     *
     * Setting Page to Manage Plugins
     *
     * @class      YIT_Plugin_Panel
     * @package    Yithemes
     * @since      1.0
     * @author     Andrea Grillo      <andrea.grillo@yithemes.com>
     * @author     Antonio La Rocca   <antonio.larocca@yithemes.com>
     */

    class YIT_Plugin_Panel_WooCommerce extends YIT_Plugin_Panel {

        /**
         * @var string version of class
         */
        public $version = '1.0.0';

        /**
         * @var array a setting list of parameters
         */
        public $settings = array();

        /**
         * @var array
         */
        protected $_tabs_path_files;

        /**
         * Constructor
         *
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function __construct( $args = array() ) {

            if ( ! empty( $args ) ) {
                $this->settings         = $args;
                $this->_tabs_path_files = $this->get_tabs_path_files();

                if( isset( $this->settings['create_menu_page'] ) && $this->settings[ 'create_menu_page'] ){
                    $this->add_menu_page();
                }
                add_action( 'admin_init', array( $this, 'set_default_options') );
                add_action( 'admin_menu', array( $this, 'add_setting_page' ) );
                add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
                add_action( 'admin_init', array( $this, 'woocommerce_update_options' ) );
	            add_filter( 'woocommerce_screen_ids', array( $this, 'add_allowed_screen_id' ) );

                add_action( 'woocommerce_admin_field_boxinfo', array( $this, 'yit_boxinfo' ), 10, 1 );
                add_action( 'woocommerce_admin_field_videobox', array( $this, 'yit_videobox' ), 10, 1 );

            }
        }


        /**
         * Show a tabbed panel to setting page
         *
         * a callback function called by add_setting_page => add_submenu_page
         *
         * @return   void
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function yit_panel() {
            $additional_info = array(
                'current_tab'    => $this->get_current_tab(),
                'available_tabs' => $this->settings['admin-tabs'],
                'default_tab'    => $this->get_available_tabs( true ), //get default tabs
                'page'           => $this->settings['page']
            );

            $additional_info                    = apply_filters( 'yith_admin_tab_params', $additional_info );
            $additional_info['additional_info'] = $additional_info;

            extract( $additional_info );
            require_once( YIT_CORE_PLUGIN_TEMPLATE_PATH . '/panel/woocommerce/woocommerce-panel.php' );
        }

        /**
         * Show a box panel with specific content in two columns as a new woocommerce type
         *
         *
         * @return   void
         * @since    1.0
         * @author   Emanuela Castorina      <emanuela.castorina@yithemes.com>
         */
        public function yit_boxinfo( $args = array() ) {
        if ( !empty( $args ) ) {
            extract( $args );
            require_once( YIT_CORE_PLUGIN_TEMPLATE_PATH . '/panel/woocommerce/woocommerce-boxinfo.php' );
        }
    }

        /**
         * Show a box panel with specific content in two columns as a new woocommerce type
         *
         *
         * @return   void
         * @since    1.0
         * @author   Emanuela Castorina      <emanuela.castorina@yithemes.com>
         */
        public function yit_videobox( $args = array() ) {
            if ( ! empty( $args ) ) {
                extract( $args );
                require_once( YIT_CORE_PLUGIN_TEMPLATE_PATH . '/panel/woocommerce/woocommerce-videobox.php' );
            }
        }

        /**
         * Show a input fields to upload images
         *
         *
         * @return   void
         * @since    1.0
         * @author   Emanuela Castorina      <emanuela.castorina@yithemes.com>
         */

        public function yit_upload_update( $option_value ) {
            return $option_value;
        }

        /**
         * Show a input fields to upload images
         *
         *
         * @return   void
         * @since    1.0
         * @author   Emanuela Castorina      <emanuela.castorina@yithemes.com>
         */

        public function yit_upload( $args = array() ) {
            if ( ! empty( $args ) ) {
                $args['value'] = ( get_option($args['id'])) ? get_option($args['id']) : $args['default'];
                extract( $args );

                include( YIT_CORE_PLUGIN_TEMPLATE_PATH . '/panel/woocommerce/woocommerce-upload.php' );
            }
        }

	    /**
	     * Add the plugin woocommerce page settings in the screen ids of woocommerce
	     *
	     * @param $screen_ids
	     *
	     * @return mixed
	     * @since 1.0.0
	     * @author   Antonino Scarfì      <antonino.scarfi@yithemes.com>
	     */
	    public function add_allowed_screen_id( $screen_ids ) {
		    global $admin_page_hooks;

		    if ( ! isset( $admin_page_hooks[ $this->settings['parent_page'] ] ) ) {
			    return $screen_ids;
		    }

		    $screen_ids[] = $admin_page_hooks[ $this->settings['parent_page'] ] . '_page_' . $this->settings['page'];

		    return $screen_ids;
	    }

        /**
         * Returns current active tab slug
         *
         * @return string
         * @since    2.0.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function get_current_tab() {
            global $pagenow;
            $tabs = $this->get_available_tabs();

            if ( $pagenow == 'admin.php' && isset( $_REQUEST['tab'] ) && in_array( $_REQUEST['tab'], $tabs ) ) {
                return $_REQUEST['tab'];
            }
            else {
                return $tabs[0];
            }
        }

        /**
         * Return available tabs
         *
         * read all options and show sections and fields
         *
         * @param bool false for all tabs slug, true for current tab
         *
         * @return mixed Array tabs | String current tab
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function get_available_tabs( $default = false ) {
            $tabs = array_keys( $this->settings['admin-tabs'] );
            return $default ? $tabs[0] : $tabs;
        }


        /**
         * Add sections and fields to setting panel
         *
         * read all options and show sections and fields
         *
         * @return void
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function add_fields() {
            $yit_options = $this->get_main_array_options();
            $current_tab = $this->get_current_tab();

            if ( ! $current_tab ) {
                return;
            }

            woocommerce_admin_fields( $yit_options[$current_tab] );
        }

        /**
         * Print the panel content
         *
         * check if the tab is a wc options tab or custom tab and print the content
         *
         * @return void
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function print_panel_content() {
            $yit_options       = $this->get_main_array_options();
            $current_tab       = $this->get_current_tab();
            $custom_tab_action = $this->is_custom_tab( $yit_options, $current_tab );

            if ( $custom_tab_action ) {
                $this->print_custom_tab( $custom_tab_action );
                return;
            }
            else {
                require_once( YIT_CORE_PLUGIN_TEMPLATE_PATH . '/panel/woocommerce/woocommerce-form.php' );
            }
        }

	    /**
	     * Fire the action to print the custom tab
	     *
	     * @param $current_tab string
	     *
	     * @return void
	     * @since    1.0
	     * @author   Antonino Scarfì <antonino.scarfi@yithemes.com>
	     */
	    public function print_video_box() {
		    $file = $this->settings['options-path'] . '/video-box.php';

		    if ( ! file_exists( $file ) ) {
			    return;
		    }

		    $args = include_once( $file );
		    $this->yit_videobox( $args );
	    }

        /**
         * Update options
         *
         * @return void
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         * @see      woocommerce_update_options function
         * @internal fire two action (before and after update): yit_panel_wc_before_update and yit_panel_wc_after_update
         */
        public function woocommerce_update_options() {

            if ( isset( $_POST['yit_panel_wc_options_nonce'] ) && wp_verify_nonce( $_POST['yit_panel_wc_options_nonce'], 'yit_panel_wc_options_'.$this->settings['page'] ) ) {

                do_action( 'yit_panel_wc_before_update' );

                $yit_options = $this->get_main_array_options();
                $current_tab = $this->get_current_tab();

                woocommerce_update_options( $yit_options[ $current_tab ] );

                do_action( 'yit_panel_wc_after_update' );

            } elseif( isset( $_REQUEST['yit-action'] ) && $_REQUEST['yit-action'] == 'wc-options-reset' ){

                $yit_options = $this->get_main_array_options();
                $current_tab = $this->get_current_tab();

                foreach( $yit_options[ $current_tab ] as $id => $option ){
                    if( isset( $option['default'] ) ){
                        update_option( $option['id'], $option['default'] );
                    }
                }
            }
        }

        /**
         * Add Admin WC Style and Scripts
         *
         * @return void
         * @since    1.0
         * @author   Andrea Grillo      <andrea.grillo@yithemes.com>
         * @author   Antonio La Rocca   <antonio.larocca@yithemes.com>
         */
        public function admin_enqueue_scripts() { 
            global $woocommerce;

            wp_enqueue_style( 'raleway-font', '//fonts.googleapis.com/css?family=Raleway:400,500,600,700,800,100,200,300,900' );

            wp_enqueue_media();
            wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), $woocommerce->version );
            wp_enqueue_style( 'yit-plugin-style', YIT_CORE_PLUGIN_URL . '/assets/css/yit-plugin-panel.css', $woocommerce->version );
            wp_enqueue_style ( 'wp-jquery-ui-dialog' );


            wp_enqueue_style( 'jquery-chosen', YIT_CORE_PLUGIN_URL . '/assets/css/chosen/chosen.css' );
            wp_enqueue_script( 'jquery-chosen', YIT_CORE_PLUGIN_URL . '/assets/js/chosen/chosen.jquery.js', array( 'jquery' ), '1.1.0', true );
            wp_enqueue_script( 'woocommerce_settings', $woocommerce->plugin_url() . '/assets/js/admin/settings.min.js', array( 'jquery', 'jquery-ui-datepicker','jquery-ui-dialog', 'jquery-ui-sortable', 'iris', 'chosen' ), $woocommerce->version, true );
            wp_enqueue_script( 'yit-plugin-panel', YIT_CORE_PLUGIN_URL . '/assets/js/yit-plugin-panel.min.js', array( 'jquery', 'jquery-chosen' ), $this->version, true );
            wp_localize_script( 'woocommerce_settings', 'woocommerce_settings_params', array(
                'i18n_nav_warning' => __( 'The changes you have made will be lost if you leave this page.', 'yit' )
            ) );
        }

        /**
         * Default options
         *
         * Sets up the default options used on the settings page
         *
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function set_default_options() {

            $default_options = $this->get_main_array_options();

            foreach ($default_options as $section) {
                foreach ( $section as $value ) {
                    if ( ( isset( $value['std'] ) || isset( $value['default'] ) ) && isset( $value['id'] ) ) {
                        $default_value = ( isset( $value['default'] ) ) ? $value['default'] : $value['std'];

                        if ( $value['type'] == 'image_width' ) {
                            add_option($value['id'].'_width', $default_value);
                            add_option($value['id'].'_height', $default_value);
                        } else {
                            add_option($value['id'], $default_value);
                        }

                    }

                }
            }

        }


    }
}