<?php
/**
 * Init class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.1.5
 */

if ( ! defined( 'YITH_WCWL' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCWL_Init' ) ) {
    /**
     * Initiator class. Install the plugin database and load all needed stuffs.
     *
     * @since 1.0.0
     */
    class YITH_WCWL_Init {
        /**
         * Plugin version
         *
         * @var string
         * @since 1.0.0
         */
        public $version = '1.1.7';

        /**
         * Plugin database version
         *
         * @var string
         * @since 1.0.0
         */
        public $db_version = '1.0.0';

        /**
         * Tab name
         *
         * @var string
         * @since 1.0.0
         */
        public $tab;

        /**
         * Plugin options
         *
         * @var array
         * @since 1.0.0
         */
        public $options;

        /**
         * Front end colors options.
         *
         * @var array
         * @since 1.0.0
         */
        public $colors_options;

        /**
         * CSS selectors used to style buttons.
         *
         * @var array
         * @since 1.0.0
         */
        public $rules;

        /**
         * Various links
         *
         * @var string
         * @access public
         * @since 1.0.0
         */
        public $banner_url = 'http://cdn.yithemes.com/plugins/yith_wishlist.php?url';
        public $banner_img = 'http://cdn.yithemes.com/plugins/yith_wishlist.php';
        public $doc_url = 'http://yithemes.com/docs-plugins/yith_wishlist/';

        /**
         * Positions of the button "Add to Wishlist"
         *
         * @var array
         * @access private
         * @since 1.0.0
         */
        private $_positions;

        /**
         * Store class yith_WCWL_Install.
         *
         * @var object
         * @access private
         * @since 1.0.0
         */
        private $_yith_wcwl_install;

        /**
         * Constructor
         *
         * @since 1.0.0
         */
        public function __construct() {
            define( 'YITH_WCWL_VERSION', $this->version );
            define( 'YITH_WCWL_DB_VERSION', $this->db_version );

            /**
             * Support to WC 2.0.x
             */
            global $woocommerce;

            $is_woocommerce_2_0 = version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.1', '<' );

            $this->tab     = __( 'Wishlist', 'yit' );
            $this->options = $this->_plugin_options();

            $this->_positions         = apply_filters( 'yith_wcwl_positions', array(
                'add-to-cart' => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 31 ),
                'thumbnails'  => array( 'hook' => 'woocommerce_product_thumbnails', 'priority' => 21 ),
                'summary'     => array( 'hook' => 'woocommerce_after_single_product_summary', 'priority' => 11 )
            ) );
            $this->_yith_wcwl_install = new YITH_WCWL_Install();

            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                $this->install();
            }



            add_action( 'init', array( $this, 'init' ), 0 );
            add_action( 'admin_init', array( $this, 'load_admin_style' ) );

            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab_woocommerce' ), 30 );
            add_action( 'woocommerce_update_options_yith_wcwl', array( $this, 'update_options' ) );
            add_action( 'woocommerce_settings_tabs_yith_wcwl', array( $this, 'print_plugin_options' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( plugin_basename( dirname( __FILE__ ) . '/init.php' ) ), array( $this, 'action_links' ) );

            if( $is_woocommerce_2_0 ) {
                add_filter( 'woocommerce_page_settings', array( $this, 'add_page_setting_woocommerce' ) );
            }

            if ( get_option( 'yith_wcwl_enabled' ) == 'yes' ) {
                add_action( 'wp_head', array( $this, 'add_button' ) );
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_stuffs' ) );
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                add_filter( 'body_class', array( $this, 'add_body_class' ) );

                // YITH WCWL Loaded
                do_action( 'yith_wcwl_loaded' );
            }

            //Apply filters
            $this->banner_url = apply_filters( 'yith_wcmg_banner_url', $this->banner_url );
        }

        /**
         * Initiator method. Initiate properties.
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        public function init() {
            global $yith_wcwl;

            $db_colors = get_option( 'yith_wcwl_frontend_css_colors' );

            $this->colors_options = ! empty( $db_colors ) ? maybe_unserialize( $db_colors ) : apply_filters( 'yith_wcwl_colors_options', array(
                'add_to_wishlist'       => array( 'background' => '#4F4F4F', 'color' => '#FFFFFF', 'border_color' => '#4F4F4F' ),
                'add_to_wishlist_hover' => array( 'background' => '#2F2F2F', 'color' => '#FFFFFF', 'border_color' => '#2F2F2F' ),
                'add_to_cart'           => array( 'background' => '#4F4F4F', 'color' => '#FFFFFF', 'border_color' => '#4F4F4F' ),
                'add_to_cart_hover'     => array( 'background' => '#2F2F2F', 'color' => '#FFFFFF', 'border_color' => '#2F2F2F' ),
                'wishlist_table'        => array( 'background' => '#FFFFFF', 'color' => '#676868', 'border_color' => '#676868' )
            ) );

            if ( empty( $db_colors ) ) {
                update_option( 'yith_wcwl_frontend_css_colors', maybe_serialize( $this->colors_options ) );
            }

            $this->rules = apply_filters( 'yith_wcwl_colors_rules', array(
                'add_to_wishlist'       => '.woocommerce .yith-wcwl-add-button > a.button.alt',
                'add_to_wishlist_hover' => '.woocommerce .yith-wcwl-add-button > a.button.alt:hover',
                'add_to_cart'           => '.woocommerce .wishlist_table a.add_to_cart.button.alt',
                'add_to_cart_hover'     => '.woocommerce .wishlist_table a.add_to_cart.button.alt:hover',
                'wishlist_table'        => '.wishlist_table'
            ) );

            if ( is_user_logged_in() ) {
                $yith_wcwl->details['user_id'] = get_current_user_id();

                //check whether any products are added to wishlist, then after login add to the wishlist if not added
                if ( yith_usecookies() ) {
                    $cookie = yith_getcookie( 'yith_wcwl_products' );
                    foreach ( $cookie as $details ) {
                        $yith_wcwl->details            = $details;
                        $yith_wcwl->details['user_id'] = get_current_user_id();

                        $ret_val = $yith_wcwl->add();
                    }

                    yith_destroycookie( 'yith_wcwl_products' );
                }
                else {
                    if ( isset( $_SESSION['yith_wcwl_products'] ) ) {
                        foreach ( $_SESSION['yith_wcwl_products'] as $details ) {
                            $yith_wcwl->details            = $details;
                            $yith_wcwl->details['user_id'] = get_current_user_id();

                            $ret_val = $yith_wcwl->add();
                        }

                        unset( $_SESSION['yith_wcwl_products'] );
                    }
                }
            }

            wp_register_style( 'yith-wcwl-admin', YITH_WCWL_URL . 'assets/css/admin.css' );
        }

        /**
         * Load admin style.
         *
         * @return void
         * @since 1.0.0
         */
        public function load_admin_style() {
            wp_enqueue_style( 'yith-wcwl-admin' );
        }

        /**
         * Run the installation
         *
         * @return void
         * @since 1.0.0
         */
        public function install() {
            if ( $this->db_version != get_option( 'yith_wcwl_db_version' ) || ! $this->_yith_wcwl_install->is_installed() ) {
                add_action( 'init', array( $this->_yith_wcwl_install, 'init' ) );
                //$this->_yith_wcwl_install->init();
                $this->_yith_wcwl_install->default_options( $this->options );

                // Plugin installed
                do_action( 'yith_wcwl_installed' );
            }
        }

        /**
         * Add the "Add to Wishlist" button. Needed to use in wp_head hook.
         *
         * @return void
         * @since 1.0.0
         */
        public function add_button() {
            global $post;

            if ( ! isset( $post ) || ! is_object( $post ) ) {
                return;
            }

            // Add the link "Add to wishlist"
            $position = get_option( 'yith_wcwl_button_position' );

            $position = empty( $position ) ? 'add-to-cart' : $position;

            if ( $position != 'shortcode' ) {
                add_action( $this->_positions[$position]['hook'], create_function( '', 'echo do_shortcode( "[yith_wcwl_add_to_wishlist]" );' ), $this->_positions[$position]['priority'] );
            }

            // Free the memory. Like it needs a lot of memory... but this is rock!
        }


        /**
         * Add specific body class when the Wishlist page is opened
         *
         * @param array $classes
         *
         * @return array
         * @since 1.0.0
         */
        public function add_body_class( $classes ) {
            $wishlist_page_id = get_option( 'yith_wcwl_wishlist_page_id' );

            if ( is_page( $wishlist_page_id ) ||

                //WPML Compatibility
                defined( 'ICL_PLUGIN_PATH' ) && is_page( icl_object_id( $wishlist_page_id, 'page', false ) )
            ) {
                $classes[] = 'woocommerce-wishlist';
                $classes[] = 'woocommerce';
                $classes[] = 'woocommerce-page';
            }

            return $classes;
        }

        /**
         * Enqueue styles, scripts and other stuffs needed in the <head>.
         *
         * @return void
         * @since 1.0.0
         */
        public function enqueue_styles_and_stuffs() {
            $located = locate_template( array(
                'woocommerce/wishlist.css',
                'wishlist.css'
            ) );

            if ( ! $located ) {
                wp_enqueue_style( 'yith-wcwl-main', YITH_WCWL_URL . 'assets/css/style.css' );
            }
            else {
                wp_enqueue_style( 'yith-wcwl-user-main', str_replace( get_template_directory(), get_template_directory_uri(), $located ) );
            }

            if ( get_option( 'yith_wcwl_add_to_wishlist_icon' ) != 'none' ) {
                wp_enqueue_style( 'yith-wcwl-font-awesome', YITH_WCWL_URL . 'assets/css/font-awesome.css' );
                wp_enqueue_style( 'yith-wcwl-font-awesome-ie7', YITH_WCWL_URL . 'assets/css/font-awesome-ie7.css' );
            }

            // Add frontend CSS for buttons
            $colors_styles = array();
            $frontend_css  = '';
            if ( get_option( 'yith_wcwl_frontend_css' ) == 'no' ) {
                foreach ( $this->colors_options as $name => $option ) {
                    $colors_styles[$name] = '';

                    foreach ( $option as $id => $value ) {
                        $colors_styles[$name] .= str_replace( '_', '-', $id ) . ':' . $value . ';';
                    }
                }

                foreach ( $this->rules as $id => $rule ) {
                    $frontend_css .= $rule . '{' . $colors_styles[$id] . '}';
                }
            }

            ?>
            <style>
                <?php
                echo get_option( 'yith_wcwl_custom_css' ) . $frontend_css;
                
                if( get_option( 'yith_wcwl_rounded_corners' ) == 'yes' ) {
                    echo '.wishlist_table .add_to_cart, .yith-wcwl-add-button > a.button.alt { border-radius: 16px; -moz-border-radius: 16px; -webkit-border-radius: 16px; }';
                }
                ?>
            </style>
            <script type="text/javascript">
                var yith_wcwl_plugin_ajax_web_url = '<?php echo admin_url('admin-ajax.php') ?>';
                var login_redirect_url = '<?php echo wp_login_url() . '?redirect_to=' . urlencode( $_SERVER['REQUEST_URI'] ) ?>';
            </script>
        <?php
        }

        /**
         * Enqueue plugin scripts.
         *
         * @return void
         * @since 1.0.0
         */
        public function enqueue_scripts() {
            wp_register_script( 'jquery-yith-wcwl', YITH_WCWL_URL . 'assets/js/jquery.yith-wcwl.js', array( 'jquery' ), '1.0', true );
            wp_enqueue_script( 'jquery-yith-wcwl' );

            $yith_wcwl_l10n = array(
                'out_of_stock' => __( 'Cannot add to the cart as product is Out of Stock!', 'yit' ),
            );
            wp_localize_script( 'jquery-yith-wcwl', 'yith_wcwl_l10n', $yith_wcwl_l10n );
        }

        /**
         * Add the tab of the plugin to the WooCommerce theme options
         *
         * @param array $tabs
         *
         * @return array
         * @since 1.0.0
         */
        public function add_tab_woocommerce( $tabs ) {
            $tabs['yith_wcwl'] = $this->tab;

            return $tabs;
        }

        /**
         * Add the select for the Wishlist page in WooCommerce > Settings > Pages
         *
         * @param array $settings
         *
         * @return array
         * @since 1.0.0
         */
        public function add_page_setting_woocommerce( $settings ) {
            unset( $settings[count( $settings ) - 1] );

            $setting[] = $this->get_wcwl_page_option();

            $settings[] = array( 'type' => 'sectionend', 'id' => 'page_options' );

            return $settings;
        }

        /**
         * Update plugin options.
         *
         * @return void
         * @since 1.0.0
         */
        public function update_options() {
            foreach ( $this->options as $option ) {
                woocommerce_update_options( $option );
            }

            foreach ( $this->colors_options as $name => $option ) {
                foreach ( $option as $id => $color ) {
                    $this->colors_options[$name][$id] = isset( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) && ! empty( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) ? woocommerce_format_hex( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) : '';
                }
            }

            update_option( 'yith_wcwl_frontend_css_colors', maybe_serialize( $this->colors_options ) );
        }

        /**
         * Print all plugin options.
         *
         * @return void
         * @since 1.0.0
         */
        public function print_plugin_options() {
            $links = apply_filters( 'yith_wcwl_tab_links', array(
                '<a href="#yith_wcwl_general_settings">' . __( 'General Settings', 'yit' ) . '</a>',
                '<a href="#yith_wcwl_styles">' . __( 'Styles', 'yit' ) . '</a>',
                '<a href="#yith_wcwl_socials_share">' . __( 'Socials &amp; Share', 'yit' ) . '</a>',
            ) );

            $this->_printBanner();

            ?>
            <div class="subsubsub_section">
                <!--<ul class="subsubsub">
                    <li>
                        <?php /*echo implode( ' | </li><li>', $links ) */?>
                    </li>
                </ul>-->
                <br class="clear" />
                <?php $this->options = apply_filters( 'yith_wcwl_tab_options', $this->options ); ?>
                <?php foreach ( $this->options as $id => $tab ) : ?>
                    <!-- tab #<?php echo $id ?> -->
                    <div class="section" id="yith_wcwl_<?php echo $id ?>">
                        <?php woocommerce_admin_fields( $this->options[$id] ) ?>

                        <?php if ( $id == 'styles' ) : ?>
                            <div id="yith_wcwl_styles_colors">
                                <h3><?php _e( 'Colors', 'yit' ) ?></h3>
                                <?php $this->_styles_options() ?>
                            </div>
                        <?php endif ?>

                    </div>
                <?php endforeach ?>
            </div>
        <?php
        }

        /**
         * Add colors options to the panel.
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        private function _styles_options() {
            $colors = maybe_unserialize( get_option( 'yith_wcwl_frontend_css_colors' ) );

            foreach ( $this->colors_options as $color => $attrs ) {
                if ( ! isset( $colors[$color] ) ) {
                    $colors[$color] = $attrs;
                }
            }

            ?>
            <div class="clear"></div><?php

            yith_frontend_css_color_picker( __( '"Add to Wishlist" button background', 'yit' ), 'yith_wcwl_color_add_to_wishlist_background', $colors['add_to_wishlist']['background'] );
            yith_frontend_css_color_picker( __( '"Add to Wishlist" button text', 'yit' ), 'yith_wcwl_color_add_to_wishlist_color', $colors['add_to_wishlist']['color'] );
            yith_frontend_css_color_picker( __( '"Add to Wishlist" button border', 'yit' ), 'yith_wcwl_color_add_to_wishlist_border_color', $colors['add_to_wishlist']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div><?php

            // hover
            yith_frontend_css_color_picker( __( '"Add to Wishlist" button background (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_background', $colors['add_to_wishlist_hover']['background'] );
            yith_frontend_css_color_picker( __( '"Add to Wishlist" button text (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_color', $colors['add_to_wishlist_hover']['color'] );
            yith_frontend_css_color_picker( __( '"Add to Wishlist" button border (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_border_color', $colors['add_to_wishlist_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div><?php

            yith_frontend_css_color_picker( __( '"Add to Cart" button background', 'yit' ), 'yith_wcwl_color_add_to_cart_background', $colors['add_to_cart']['background'] );
            yith_frontend_css_color_picker( __( '"Add to Cart" button text', 'yit' ), 'yith_wcwl_color_add_to_cart_color', $colors['add_to_cart']['color'] );
            yith_frontend_css_color_picker( __( '"Add to Cart" button border', 'yit' ), 'yith_wcwl_color_add_to_cart_border_color', $colors['add_to_cart']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div><?php

            // hover
            yith_frontend_css_color_picker( __( '"Add to Cart" button background (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_background', $colors['add_to_cart_hover']['background'] );
            yith_frontend_css_color_picker( __( '"Add to Cart" button text (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_color', $colors['add_to_cart_hover']['color'] );
            yith_frontend_css_color_picker( __( '"Add to Cart" button border (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_border_color', $colors['add_to_cart_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div><?php

            yith_frontend_css_color_picker( __( 'Wishlist table background', 'yit' ), 'yith_wcwl_color_wishlist_table_background', $colors['wishlist_table']['background'] );
            yith_frontend_css_color_picker( __( 'Wishlist table text', 'yit' ), 'yith_wcwl_color_wishlist_table_color', $colors['wishlist_table']['color'] );
            yith_frontend_css_color_picker( __( 'Wishlist table border', 'yit' ), 'yith_wcwl_color_wishlist_table_border_color', $colors['wishlist_table']['border_color'] );

            do_action( 'yith_wcwl_admin_color_pickers' );

            ?>
            <div class="clear"></div>

            <script type="text/javascript">
                jQuery('input#yith_wcwl_frontend_css').on('change',function () {
                    if (jQuery(this).is(':checked')) {
                        jQuery('#yith_wcwl_styles_colors').hide();
                        jQuery('#yith_wcwl_rounded_corners').parents('tr').hide();
                        jQuery('#yith_wcwl_add_to_wishlist_icon').parents('tr').hide();
                        jQuery('#yith_wcwl_add_to_cart_icon').parents('tr').hide();
                    } else {
                        jQuery('#yith_wcwl_styles_colors').show();
                        if (jQuery('#yith_wcwl_use_button').is(':checked')) {
                            jQuery('#yith_wcwl_rounded_corners').parents('tr').show();
                            jQuery('#yith_wcwl_add_to_wishlist_icon').parents('tr').show();
                            jQuery('#yith_wcwl_add_to_cart_icon').parents('tr').show();
                        }
                    }
                }).change();

                jQuery('input#yith_wcwl_use_button').on('change',function () {
                    if (jQuery(this).is(':checked') && !jQuery('#yith_wcwl_frontend_css').is(':checked')) {
                        jQuery('#yith_wcwl_rounded_corners').parents('tr').show();
                        jQuery('#yith_wcwl_add_to_wishlist_icon').parents('tr').show();
                        jQuery('#yith_wcwl_add_to_cart_icon').parents('tr').show();
                    } else {
                        jQuery('#yith_wcwl_rounded_corners').parents('tr').hide();
                        jQuery('#yith_wcwl_add_to_wishlist_icon').parents('tr').hide();
                        jQuery('#yith_wcwl_add_to_cart_icon').parents('tr').hide();
                    }
                }).change();
            </script>
        <?php
        }


        /**
         * action_links function.
         *
         * @access public
         *
         * @param mixed $links
         *
         * @return void
         */
        public function action_links( $links ) {
            global $woocommerce;

            if ( version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.1', '<' ) ) {
                $wc_settings_page = "woocommerce_settings";
            }
            else {
                $wc_settings_page = "wc-settings";
            }

            $plugin_links = array(
                    '<a href="' . admin_url( 'admin.php?page=' . $wc_settings_page . '&tab=yith_wcwl' ) . '">' . __( 'Settings', 'yit' ) . '</a>',
                    '<a href="' . $this->doc_url . '">' . __( 'Docs', 'yit' ) . '</a>',
                );

            return array_merge( $plugin_links, $links );
        }

        /**
         * Return the option to add the wishlist page
         *
         * @access public
         * @return mxied array
         * @since 1.1.3
         */
        public function get_wcwl_page_option(){

            return array(
                'name'     => __( 'Wishlist Page', 'yit' ),
                'desc'     => __( 'Page contents: [yith_wcwl_wishlist]', 'yit' ),
                'id'       => 'yith_wcwl_wishlist_page_id',
                'type'     => 'single_select_page',
                'std'      => '', // for woocommerce < 2.0
                'default'  => '', // for woocommerce >= 2.0
                'class'    => 'chosen_select_nostd',
                'css'      => 'min-width:300px;',
                'desc_tip' => false,
            );
        }


        /**
         * Print the banner
         *
         * @access protected
         * @return void
         * @since 1.0.0
         */
        protected function _printBanner() {
            ?>
            <div class="yith_banner">
                <a href="<?php echo $this->banner_url ?>" target="_blank">
                    <img src="<?php echo $this->banner_img ?>" alt="" />
                </a>
            </div>
        <?php
        }

        /**
         * Plugin options and tabs.
         *
         * @return array
         * @since 1.0.0
         */
        private function _plugin_options() {
            $icons = array(
                'icon-glass'              => 'Glass',
                'icon-music'              => 'Music',
                'icon-search'             => 'Search',
                'icon-envelope'           => 'Envelope',
                'icon-heart'              => 'Heart',
                'icon-star'               => 'Star',
                'icon-star-empty'         => 'Star empty',
                'icon-user'               => 'User',
                'icon-film'               => 'Film',
                'icon-th-large'           => 'Th large',
                'icon-th'                 => 'Th',
                'icon-th-list'            => 'Th list',
                'icon-ok'                 => 'Ok',
                'icon-remove'             => 'Remove',
                'icon-zoom-in'            => 'Zoom In',
                'icon-zoom-out'           => 'Zoom Out',
                'icon-off'                => 'Off',
                'icon-signal'             => 'Signal',
                'icon-cog'                => 'Cog',
                'icon-trash'              => 'Trash',
                'icon-home'               => 'Home',
                'icon-file'               => 'File',
                'icon-time'               => 'Time',
                'icon-road'               => 'Road',
                'icon-download-alt'       => 'Download alt',
                'icon-download'           => 'Download',
                'icon-upload'             => 'Upload',
                'icon-inbox'              => 'Inbox',
                'icon-play-circle'        => 'Play circle',
                'icon-repeat'             => 'Repeat',
                'icon-refresh'            => 'Refresh',
                'icon-list-alt'           => 'List alt',
                'icon-lock'               => 'Lock',
                'icon-flag'               => 'Flag',
                'icon-headphones'         => 'Headphones',
                'icon-volume-off'         => 'Volume Off',
                'icon-volume-down'        => 'Volume Down',
                'icon-volume-up'          => 'Volume Up',
                'icon-qrcode'             => 'QR code',
                'icon-barcode'            => 'Barcode',
                'icon-tag'                => 'Tag',
                'icon-tags'               => 'Tags',
                'icon-book'               => 'Book',
                'icon-bookmark'           => 'Bookmark',
                'icon-print'              => 'Print',
                'icon-camera'             => 'Camera',
                'icon-font'               => 'Font',
                'icon-bold'               => 'Bold',
                'icon-italic'             => 'Italic',
                'icon-text-height'        => 'Text height',
                'icon-text-width'         => 'Text width',
                'icon-align-left'         => 'Align left',
                'icon-align-center'       => 'Align center',
                'icon-align-right'        => 'Align right',
                'icon-align-justify'      => 'Align justify',
                'icon-list'               => 'List',
                'icon-indent-left'        => 'Indent left',
                'icon-indent-right'       => 'Indent right',
                'icon-facetime-video'     => 'Facetime video',
                'icon-picture'            => 'Picture',
                'icon-pencil'             => 'Pencil',
                'icon-map-marker'         => 'Map marker',
                'icon-adjust'             => 'Adjust',
                'icon-tint'               => 'Tint',
                'icon-edit'               => 'Edit',
                'icon-share'              => 'Share',
                'icon-check'              => 'Check',
                'icon-move'               => 'Move',
                'icon-step-backward'      => 'Step backward',
                'icon-fast-backward'      => 'Fast backward',
                'icon-backward'           => 'Backward',
                'icon-play'               => 'Play',
                'icon-pause'              => 'Pause',
                'icon-stop'               => 'Stop',
                'icon-forward'            => 'Forward',
                'icon-fast-forward'       => 'Fast forward',
                'icon-step-forward'       => 'Step forward',
                'icon-eject'              => 'Eject',
                'icon-chevron-left'       => 'Chevron left',
                'icon-chevron-right'      => 'Chevron right',
                'icon-plus-sign'          => 'Plus sign',
                'icon-minus-sign'         => 'Minus sign',
                'icon-remove-sign'        => 'Remove sign',
                'icon-ok-sign'            => 'Ok sign',
                'icon-question-sign'      => 'Question sign',
                'icon-info-sign'          => 'Info sign',
                'icon-screenshot'         => 'Screenshot',
                'icon-remove-circle'      => 'Remove circle',
                'icon-ok-circle'          => 'Ok circle',
                'icon-ban-circle'         => 'Ban circle',
                'icon-arrow-left'         => 'Arrow left',
                'icon-arrow-right'        => 'Arrow right',
                'icon-arrow-up'           => 'Arrow up',
                'icon-arrow-down'         => 'Arrow down',
                'icon-share-alt'          => 'Share alt',
                'icon-resize-full'        => 'Resize full',
                'icon-resize-small'       => 'Resize small',
                'icon-plus'               => 'Plus',
                'icon-minus'              => 'Minus',
                'icon-asterisk'           => 'Asterisk',
                'icon-exclamation-sign'   => 'Exclamation sign',
                'icon-gift'               => 'Gift',
                'icon-leaf'               => 'Leaf',
                'icon-fire'               => 'Fire',
                'icon-eye-open'           => 'Eye open',
                'icon-eye-close'          => 'Eye close',
                'icon-warning-sign'       => 'Warning sign',
                'icon-plane'              => 'Plane',
                'icon-calendar'           => 'Calendar',
                'icon-random'             => 'Random',
                'icon-comment'            => 'Comment',
                'icon-magnet'             => 'Magnet',
                'icon-chevron-up'         => 'Chevron up',
                'icon-chevron-down'       => 'Chevron down',
                'icon-retweet'            => 'Retweet',
                'icon-shopping-cart'      => 'Shopping cart',
                'icon-folder-close'       => 'Folder close',
                'icon-folder-open'        => 'Folder open',
                'icon-resize-vertical'    => 'Resize vertical',
                'icon-resize-horizontal'  => 'Resize horizontal',
                'icon-bar-chart'          => 'Bar chart',
                'icon-twitter-sign'       => 'Twitter sign',
                'icon-facebook-sign'      => 'Facebook sign',
                'icon-camera-retro'       => 'Camera retro',
                'icon-key'                => 'Key',
                'icon-cogs'               => 'Cogs',
                'icon-comments'           => 'Comments',
                'icon-thumbs-up'          => 'Thumbs up',
                'icon-thumbs-down'        => 'Thumbs down',
                'icon-star-half'          => 'Star half',
                'icon-heart-empty'        => 'Heart empty',
                'icon-signout'            => 'Signout',
                'icon-linkedin-sign'      => 'LinkedIn sign',
                'icon-pushpin'            => 'Push pin',
                'icon-external-link'      => 'External link',
                'icon-signin'             => 'Sign in',
                'icon-trophy'             => 'Trophy',
                'icon-github-sign'        => 'Github sign',
                'icon-upload-alt'         => 'Upload alt',
                'icon-lemon'              => 'Lemon',
                'icon-phone'              => 'Phone',
                'icon-check-empty'        => 'Check empty',
                'icon-bookmark-empty'     => 'Bookmark empty',
                'icon-phone-sign'         => 'Phone sign',
                'icon-twitter'            => 'Twitter',
                'icon-facebook'           => 'Facebook',
                'icon-github'             => 'Github',
                'icon-unlock'             => 'Unlock',
                'icon-credit-card'        => 'Credit card',
                'icon-rss'                => 'RSS',
                'icon-hdd'                => 'HDD',
                'icon-bullhorn'           => 'Bullhorn',
                'icon-bell'               => 'Bell',
                'icon-certificate'        => 'Certificate',
                'icon-hand-right'         => 'Hand right',
                'icon-hand-left'          => 'Hand left',
                'icon-hand-up'            => 'Hand up',
                'icon-hand-down'          => 'Hand down',
                'icon-circle-arrow-left'  => 'Circle arrow left',
                'icon-circle-arrow-right' => 'Circle arrow right',
                'icon-circle-arrow-up'    => 'Circle arrow up',
                'icon-circle-arrow-down'  => 'Circle arrow down',
                'icon-globe'              => 'Globe',
                'icon-wrench'             => 'Wrench',
                'icon-tasks'              => 'Tasks',
                'icon-filter'             => 'Filter',
                'icon-briefcase'          => 'Briefcase',
                'icon-fullscreen'         => 'Fullscreen',
                'icon-group'              => 'Group',
                'icon-link'               => 'Link',
                'icon-cloud'              => 'Cloud',
                'icon-beaker'             => 'Beaker',
                'icon-cut'                => 'Cut',
                'icon-copy'               => 'Copy',
                'icon-paper-clip'         => 'Paper clip',
                'icon-save'               => 'Save',
                'icon-sign-blank'         => 'Sign blank',
                'icon-reorder'            => 'Reorder',
                'icon-list-ul'            => 'List ul',
                'icon-list-ol'            => 'List ol',
                'icon-strikethrough'      => 'Strike through',
                'icon-underline'          => 'Underline',
                'icon-table'              => 'Table',
                'icon-magic'              => 'Magic',
                'icon-truck'              => 'Truck',
                'icon-pinterest'          => 'Pinterest',
                'icon-pinterest-sign'     => 'Pinterest sign',
                'icon-google-plus-sign'   => 'Google Plus sign',
                'icon-google-plus'        => 'Google Plus',
                'icon-money'              => 'Money',
                'icon-caret-down'         => 'Caret down',
                'icon-caret-up'           => 'Caret up',
                'icon-caret-left'         => 'Caret left',
                'icon-caret-right'        => 'Caret right',
                'icon-columns'            => 'Columns',
                'icon-sort'               => 'Sort',
                'icon-sort-down'          => 'Sort down',
                'icon-sort-up'            => 'Sort up',
                'icon-envelope-alt'       => 'Envelope alt',
                'icon-linkedin'           => 'LinkedIn',
                'icon-undo'               => 'Undo',
                'icon-legal'              => 'Legal',
                'icon-dashboard'          => 'Dashboard',
                'icon-comment-alt'        => 'Comment alt',
                'icon-comments-alt'       => 'Comments alt',
                'icon-bolt'               => 'Bolt',
                'icon-sitemap'            => 'Sitemap',
                'icon-umbrella'           => 'Umbrella',
                'icon-paste'              => 'Paste',
                'icon-user-md'            => 'User medical'
            );

            ksort( $icons );

            global $woocommerce;

            $is_woocommerce_2_0 =version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.1', '<' );

            $options['general_settings'] = array();

            if( $is_woocommerce_2_0 ){

                $settings_page = 'WooCommerce &gt; Settings &gt; Pages' ;
            }else{
                $settings_page = 'in this settings page';
            }

            $general_settings_start = array(

                array( 'name' => __( 'General Settings', 'yit' ), 'type' => 'title', 'desc' => '', 'id' => 'yith_wcwl_general_settings' ),

                array(
                    'name'    => __( 'Enable YITH Wishlist', 'yit' ),
                    'desc'    => sprintf( __( 'Enable all plugin features. <strong>Be sure to select a voice in the wishlist page menu in %s.</strong> Also, please read the plugin <a href="%s" target="_blank">documentation</a>.', 'yit' ), $settings_page, esc_url( $this->doc_url ) ),
                    'id'      => 'yith_wcwl_enabled',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Use cookies', 'yit' ),
                    'desc'    => __( 'Use cookies instead of sessions. With this feature, the wishlist will be available for each not logged user for 30 days. Use the filter yith_wcwl_cookie_expiration_time to change the expiration time ( needs timestamp ).', 'yit' ),
                    'id'      => 'yith_wcwl_use_cookie',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Wishlist title', 'yit' ),
                    'id'      => 'yith_wcwl_wishlist_title',
                    'std'     => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce < 2.0
                    'default' => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                )
            );


            $general_settings_end = array(
                array(
                    'name'     => __( 'Position', 'yit' ),
                    'desc'     => __( 'On variable products you can add it only After "Add to Cart" or use the shortcode [yith_wcwl_add_to_wishlist].', 'yit' ),
                    'id'       => 'yith_wcwl_button_position',
                    'type'     => 'select',
                    'class'    => 'chosen_select',
                    'css'      => 'min-width:300px;',
                    'options'  => array(
                        'add-to-cart' => __( 'After "Add to cart"', 'yit' ),
                        'thumbnails'  => __( 'After thumbnails', 'yit' ),
                        'summary'     => __( 'After summary', 'yit' ),
                        'shortcode'   => __( 'Use shortcode', 'yit' )
                    ),
                    'desc_tip' => true
                ),
                array(
                    'name'    => __( 'Redirect to cart', 'yit' ),
                    'desc'    => __( 'Redirect to cart page if "Add to cart" button is clicked in the wishlist page.', 'yit' ),
                    'id'      => 'yith_wcwl_redirect_cart',
                    'std'     => 'no', // for woocommerce < 2.0
                    'default' => 'no', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Remove if added to the cart', 'yit' ),
                    'desc'    => __( 'Remove the product from the wishlist if is been added to the cart.', 'yit' ),
                    'id'      => 'yith_wcwl_remove_after_add_to_cart',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( '"Add to Wishlist" text', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_wishlist_text',
                    'std'     => __( 'Add to Wishlist', 'yit' ), // for woocommerce < 2.0
                    'default' => __( 'Add to Wishlist', 'yit' ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                array(
                    'name'    => __( '"Add to Cart" text', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_cart_text',
                    'std'     => __( 'Add to Cart', 'yit' ), // for woocommerce < 2.0
                    'default' => __( 'Add to Cart', 'yit' ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                array(
                    'name'    => __( 'Show Unit price', 'yit' ),
                    'id'      => 'yith_wcwl_price_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),
                array(
                    'name'    => __( 'Show "Add to Cart" button', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_cart_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),
                array(
                    'name'    => __( 'Show Stock status', 'yit' ),
                    'id'      => 'yith_wcwl_stock_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),

                array( 'type' => 'sectionend', 'id' => 'yith_wcwl_general_settings' )
            );

            $options['styles'] = array(
                array( 'name' => __( 'Styles', 'yit' ), 'type' => 'title', 'desc' => '', 'id' => 'yith_wcwl_styles' ),

                array(
                    'name'    => __( 'Use buttons', 'yit' ),
                    'desc'    => __( 'Use buttons instead of a simple anchors.', 'yit' ),
                    'id'      => 'yith_wcwl_use_button',
                    'std'     => 'no', // for woocommerce < 2.0
                    'default' => 'no', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Custom CSS', 'yit' ),
                    'id'      => 'yith_wcwl_custom_css',
                    'css'     => 'width:100%; height: 75px;',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'textarea'
                ),
                array(
                    'name'    => __( 'Use theme style', 'yit' ),
                    'desc'    => __( 'Use the theme style.', 'yit' ),
                    'id'      => 'yith_wcwl_frontend_css',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Buttons rounded corners', 'yit' ),
                    'desc'    => __( 'Make buttons corner rounded', 'yit' ),
                    'id'      => 'yith_wcwl_rounded_corners',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'     => __( '"Add to Wishlist" icon', 'yit' ),
                    'desc'     => __( 'Add an icon to the "Add to Wishlist" button', 'yit' ),
                    'id'       => 'yith_wcwl_add_to_wishlist_icon',
                    'css'      => 'min-width:300px;width:300px;',
                    'std'      => apply_filters( 'yith_wcwl_add_to_wishlist_std_icon', 'none' ), // for woocommerce < 2.0
                    'default'  => apply_filters( 'yith_wcwl_add_to_wishlist_std_icon', 'none' ), // for woocommerce >= 2.0
                    'type'     => 'select',
                    'class'    => 'chosen_select',
                    'desc_tip' => true,
                    'options'  => array( 'none' => 'None' ) + $icons
                ),
                array(
                    'name'     => __( '"Add to Cart" icon', 'yit' ),
                    'desc'     => __( 'Add an icon to the "Add to Cart" button', 'yit' ),
                    'id'       => 'yith_wcwl_add_to_cart_icon',
                    'css'      => 'min-width:300px;width:300px;',
                    'std'      => apply_filters( 'yith_wcwl_add_to_cart_std_icon', 'icon-shopping-cart' ), // for woocommerce < 2.0
                    'default'  => apply_filters( 'yith_wcwl_add_to_cart_std_icon', 'icon-shopping-cart' ), // for woocommerce >= 2.0
                    'type'     => 'select',
                    'class'    => 'chosen_select',
                    'desc_tip' => true,
                    'options'  => array( 'none' => 'None' ) + $icons
                ),

                array( 'type' => 'sectionend', 'id' => 'yith_wcwl_styles' )
            );

            $options['socials_share'] = array(
                array( 'name' => __( 'Socials &amp; Share', 'yit' ), 'type' => 'title', 'desc' => '', 'id' => 'yith_wcwl_socials_share' ),

                array(
                    'name'    => __( 'Share on Facebook', 'yit' ),
                    'id'      => 'yith_wcwl_share_fb',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Tweet on Twitter', 'yit' ),
                    'id'      => 'yith_wcwl_share_twitter',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Pin on Pinterest', 'yit' ),
                    'id'      => 'yith_wcwl_share_pinterest',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Share on Google+', 'yit' ),
                    'id'      => 'yith_wcwl_share_googleplus',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                 array(
                    'name'    => __( 'Share by Email', 'yit' ),
                    'id'      => 'yith_wcwl_share_email',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                array(
                    'name'    => __( 'Socials title', 'yit' ),
                    'id'      => 'yith_wcwl_socials_title',
                    'std'     => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce < 2.0
                    'default' => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                array(
                    'name'    => __( 'Socials text', 'yit' ),
                    'desc'    => __( 'Will be used by Facebook, Twitter and Pinterest. Use <strong>%wishlist_url%</strong> where you want the URL of your wishlist to appear.', 'yit' ),
                    'id'      => 'yith_wcwl_socials_text',
                    'css'     => 'width:100%; height: 75px;',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'textarea'
                ),
                array(
                    'name'    => __( 'Socials image URL', 'yit' ),
                    'id'      => 'yith_wcwl_socials_image_url',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),

                array( 'type' => 'sectionend', 'id' => 'yith_wcwl_styles' )
            );

            if( $is_woocommerce_2_0 ) {

                $options['general_settings'] = array_merge( $general_settings_start, $general_settings_end );

            }else{

                $options['general_settings'] = array_merge( $general_settings_start,  array( $this->get_wcwl_page_option() ), $general_settings_end );
            }

            return $options;
        }
    }
}