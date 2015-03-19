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

if ( ! class_exists( 'YIT_Licence' ) ) {
    /**
     * YIT Licence Panel
     *
     * Setting Page to Manage Products
     *
     * @class      YIT_Licence
     * @package    Yithemes
     * @since      1.0
     * @author     Andrea Grillo      <andrea.grillo@yithemes.com>
     */

    abstract class YIT_Licence {

        /**
         * @var mixed array The registered products info
         * @since 1.0
         */
        protected $_products = array();

        /**
         * @var array The settings require to add the submenu page "Activation"
         * @since 1.0
         */
        protected $_settings = array();

        /**
         * @var string Option name
         * @since 1.0
         */
        protected $_licence_option = 'yit_products_licence_activation';

         /**
         * @var string The yithemes api uri
         * @since 1.0
         */
        protected $_api_uri = 'http://www.yithemes.com';

        /**
         * @var string The yithemes api uri query args
         * @since 1.0
         */
        protected $_api_uri_query_args = '?wc-api=software-api&request=%request%';

        /**
         * Constructor
         *
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        abstract public function __construct();

        /**
         * Premium products registration
         *
         * @param $init         string | The products identifier
         * @param $secret_key   string | The secret key
         * @param $product_id   string | The product id
         *
         * @return void
         *
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        abstract public function register( $init, $secret_key, $product_id );

        /**
         * Get protected array products
         *
         * @return mixed array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_products() {
            return $this->_products;
        }

        /**
         * Get The home url without protocol
         *
         * @return string | The home url
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_home_url() {
            $home_url   = home_url();
            $schemes    = apply_filters( 'yit_licence_url_schemes', array( 'https://', 'http://' ) );

            foreach( $schemes as $scheme ){
                $home_url = str_replace( $scheme, '', $home_url );
            }

            return $home_url;
        }


        /**
         * Check if the request is ajax
         *
         * @return bool true if the request is ajax, false otherwise
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function is_ajax() {
            return defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;
        }

         /**
         * Admin Enqueue Scripts
         *
         * @return void
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function admin_enqueue_scripts(){

            /**
             * Support to YIT Framework < 2.0
             */
            $filename   = function_exists( 'yit_load_js_file' ) ? yit_load_js_file( 'yit-licence.js' ) : 'yit-licence.js';
            $script_path       = defined( 'YIT_CORE_PLUGIN_URL' ) ? YIT_CORE_PLUGIN_URL : get_template_directory_uri() . '/core/plugin-fw';
            $style_path = defined( 'YIT_CORE_PLUGIN_URL' ) ? YIT_CORE_PLUGIN_URL : get_template_directory_uri() . '/core/plugin-fw';

            wp_enqueue_script( 'yit-licence', $script_path . '/licence/assets/js/' . $filename, array( 'jquery' ), '1.0.0', true );
            wp_enqueue_style( 'yit-theme-licence', $style_path . '/licence/assets/css/yit-licence.css' );
        }

        /**
         * Localize Scripts
         *
         * @return void
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function localize_script() {
            wp_localize_script( 'yit-licence', 'licence_message', array(
                    'error'  => __( '%field% field cannot be empty', 'yit' ),
                    'errors' => __( '%field_1% and %field_2% fields cannot be empty', 'yit' ),
                    'server' => __( 'Unable to contact the remote server, please try again later. Thanks!', 'yit' )
                )
            );
        }

        /**
         * Activate Plugins
         *
         * Send a request to API server to activate plugins
         *
         * @return void
         * @use wp_send_json
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function activate() {

            $product_init = $_REQUEST['product_init'];
            $product      = $this->get_product( $product_init );

            $args = array(
                'email'       => urlencode( sanitize_email( $_REQUEST['email'] ) ),
                'licence_key' => sanitize_text_field( $_REQUEST['licence_key'] ),
                'product_id'  => sanitize_text_field( $product['product_id'] ),
                'secret_key'  => sanitize_text_field( $product['secret_key'] ),
                'instance'    => $this->get_home_url()
            );

            $api_uri  = add_query_arg( $args, $this->get_api_uri( 'activation' ) );
            $response = wp_remote_get( $api_uri );

            if ( is_wp_error( $response ) ) {
                $body = false;
            }
            else {
                $body = json_decode( $response['body'] );
                $body = is_object( $body ) ? get_object_vars( $body ) : false;
            }

            if ( $body && is_array( $body ) && isset( $body['activated'] ) && $body['activated'] ) {

                $option[$product['product_id']] = array(
                    'email'                => urldecode( $args['email'] ),
                    'licence_key'          => $args['licence_key'],
                    'licence_expires'      => $body['licence_expires'],
                    'message'              => $body['message'],
                    'activated'            => true,
                    'activation_limit'     => $body['activation_limit'],
                    'activation_remaining' => $body['activation_remaining'],
                );

                /* === Check for other plugins activation === */
                $options                        = $this->get_licence();
                $options[$product['product_id']] = $option[$product['product_id']];

                update_option( $this->_licence_option, $options );

                /* === Licence Activation Template === */
                $body['template'] = $this->show_activation_panel();
            }

            wp_send_json( $body );
        }

        /**
         * Check Plugins Licence
         *
         * Send a request to API server to check if plugins is activated
         *
         * @param string|The plugin init slug $plugin_init
         *
         * @return bool | true if activated, false otherwise
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function check( $product_init ) {

            $status     = false;
            $body       = false;
            $product    = $this->get_product( $product_init );
            $licence    = $this->get_licence();
            $product_id = $product['product_id'];

            if( ! isset( $licence[ $product_id ] ) ) {
                return false;
            }

            $args = array(
                'email'       => urlencode( $licence[$product_id]['email'] ),
                'licence_key' => $licence[$product_id]['licence_key'],
                'product_id'  => $product_id,
                'secret_key'  => $product['secret_key'],
                'instance'    => $this->get_home_url()
            );

            $api_uri  = add_query_arg( $args, $this->get_api_uri( 'check' ) );
            $response = wp_remote_get( $api_uri );

            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( $response['body'] );
                $body = is_object( $body ) ? get_object_vars( $body ) : false;
            }

            if ( $body && is_array( $body ) && isset( $body['success'] ) ) {
                if ( $body['success'] ) {

                    /**
                     * Code 200 -> Licence key is valid
                     */
                    $licence[ $product_id ]['status_code']          = '200';
                    $licence[ $product_id ]['activated']            = $body['activated'];
                    $licence[ $product_id ]['licence_expires']      = $body['licence_expires'];
                    $licence[ $product_id ]['activation_remaining'] = $body['activation_remaining'];
                    $licence[ $product_id ]['activation_limit']     = $body['activation_limit'];
                    $status                                         = (bool) $body['activated'];
                }
                elseif ( isset( $body['code'] ) ) {

                    switch ( $body['code'] ) {

                        /**
                         * Error Code List:
                         *
                         * 100 -> Invalid Request
                         * 101 -> Invalid licence key
                         * 102 -> Software has been deactivate
                         * 103 -> Exceeded maximum number of activations
                         * 104 -> Invalid instance ID
                         * 105 -> Invalid security key
                         * 106 -> Licence key has expired
                         * 107 -> Licence key has be banned
                         *
                         * Only code 101, 106 and 107 have effect on DB
                         *
                         */

                        case '101':
                        case '102':
                            unset( $licence[ $product_id ] );
                            break;

                        case '106':
                            $licence[ $product_id ]['activated']        = false;
                            $licence[ $product_id ]['message']          = $body['additional_info'];
                            $licence[ $product_id ]['status_code']      = $body['code'];
                            $licence[ $product_id ]['licence_expires']  = $body['licence_expires'];
                            break;

                        case '107':
                            $licence[ $product_id ]['activated']   = false;
                            $licence[ $product_id ]['message']     = $body['additional_info'];
                            $licence[ $product_id ]['status_code'] = $body['code'];
                            break;
                    }
                }

                /* === Update Plugin Licence Information === */
                update_option( $this->_licence_option, $licence );
            }
            return $status;
        }

         /**
         * Update Plugins Information
         *
         * Send a request to API server to check activate plugins and update the informations
         *
         * @return void
         * @use YIT_Theme_Licence->check()
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function update_licence_information() {
            foreach ( $this->_products as $init => $info ) {
                $this->check( $init );
            }

            /* === Regenerate Update Plugins Transient === */
            //YIT_Upgrade()->force_regenerate_update_transient();

            do_action( 'yit_licence_after_check' );

            $response['template'] = $this->show_activation_panel();
            wp_send_json( $response );
        }

        /**
         * Include activation page template
         *
         * @return mixed void | string the contents of the output buffer and end output buffering.
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function show_activation_panel() {

            $path = defined( 'YIT_CORE_PLUGIN_PATH' ) ? YIT_CORE_PLUGIN_PATH : get_template_directory() . '/core/plugin-fw/';

            if ( $this->is_ajax() ) {
                ob_start();
                require_once( $path . '/licence/templates/panel/activation/activation-panel.php' );
                return ob_get_clean();
            }
            else {
                require_once( $path . '/licence/templates/panel/activation/activation-panel.php' );
            }
        }

        /**
         * Get activated products
         *
         * @return array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_activated_products() {
            $activated_products = array();
            $licence           = $this->get_licence();

            if ( is_array( $licence ) ) {
                foreach ( $this->_products as $init => $info ) {
                    if ( in_array( $info['product_id'], array_keys( $licence ) ) && isset( $licence[$info['product_id']]['activated'] ) && $licence[$info['product_id']]['activated'] ) {
                        $product[$init]            = $this->_products[$init];
                        $product[$init]['licence'] = $licence[$info['product_id']];
                        $activated_products[$init] = $product[$init];
                    }
                }
            }

            return $activated_products;
        }

        /**
         * Get to active products
         *
         * @return array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_to_active_products() {
            return array_diff_key( $this->get_products(), $this->get_activated_products() );
        }

        /**
         * Get no active products
         *
         * @return array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_no_active_licence_key() {
            $unactive_products = $this->get_to_active_products();
            $licence          = $this->get_licence();
            $licence_key      = array();

            /**
             * Remove banned licence key
             */
            foreach ( $unactive_products as $init => $info ) {
                $product_id = $unactive_products[$init]['product_id'];
                if ( isset( $licence[$product_id]['activated'] ) && ! $licence[$product_id]['activated'] && isset( $licence[$product_id]['status_code'] ) ) {
                    $status_code = $licence[$product_id]['status_code'];

                    switch ( $status_code ) {
                        case '106':
                            $licence_key[$status_code][$init]            = $unactive_products[$init];
                            $licence_key[$status_code][$init]['licence'] = $licence[$product_id];
                            break;

                        case '107':
                            $licence_key[$status_code][$init]            = $unactive_products[$init];
                            $licence_key[$status_code][$init]['licence'] = $licence[$product_id];
                            break;
                    }
                }
            }
            return $licence_key;
        }

        /**
         * Get a specific product information
         *
         * @param $product_init | product init file
         *
         * @return mixed array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_product( $init ) {
            return isset( $this->_products[$init] ) ? $this->_products[$init] : false;
        }

         /**
         * Get product product id information
         *
         * @param $product_init | product init file
         *
         * @return mixed array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_product_id( $init ) {
            return isset( $this->_products[$init]['product_id'] ) ? $this->_products[$init]['product_id'] : false;
        }

        /**
         * Get Renewing uri
         *
         * @param $licence_key The licence key to renew
         *
         * @return mixed The renewing uri if licence_key exists, false otherwise
         *
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_renewing_uri( $licence_key ) {
            return ! empty( $licence_key ) ? str_replace( 'www.', '', $this->_api_uri ) . '?renewing_key=' . $licence_key : false;
        }

        /**
         * Get protected yithemes api uri
         *
         * @param   $request
         *
         * @return mixed array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_api_uri( $request ) {
            return str_replace( '%request%', $request, $this->_api_uri . $this->_api_uri_query_args );
        }

        /**
         * Get the activation page url
         *
         * @return String | Activation page url
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_licence_activation_page_url() {
            return add_query_arg( array( 'page' => $this->_settings['page'] ), admin_url( 'admin.php' ) );
        }


        /**
         * Get the licence information
         *
         * @return array | licence array
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_licence() {
            return get_option( $this->_licence_option );
        }

        /**
         * Get the licence information
         *
         * @param $code string The error code
         *
         * @return string | Error code message
         *
         * @since  1.0
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function get_error_code_message( $code ) {

            $error_strings = array(
                '100' => __( 'Invalid Request', 'yit' ),
                '101' => __( 'Invalid licence key', 'yit' ),
                '102' => __( 'Software has been deactivated', 'yit' ),
                '103' => __( 'Maximum number of activations exceeded', 'yit' ),
                '104' => __( 'Invalid instance ID', 'yit' ),
                '105' => __( 'Invalid security key', 'yit' ),
                '106' => __( 'Licence key has expired', 'yit' ),
                '107' => __( 'Licence key has been banned', 'yit' )
            );

            return isset( $error_strings[$code] ) ? $error_strings[$code] : false;
        }

    }
}