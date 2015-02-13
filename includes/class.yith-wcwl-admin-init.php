<?php
/**
 * Admin init class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 2.0.0
 */

if ( ! defined( 'YITH_WCWL' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCWL_Admin_Init' ) ) {
    /**
     * Initiator class. Create and populate admin views.
     *
     * @since 1.0.0
     */
    class YITH_WCWL_Admin_Init {

        /**
         * Single instance of the class
         *
         * @var \YITH_WCWL_Admin_Init
         * @since 2.0.0
         */
        protected static $instance;

        /**
         * Plugin version
         *
         * @var string
         * @since 1.0.0
         */
        public $version = '2.0.1';

        /**
         * Plugin database version
         *
         * @var string
         * @since 1.0.0
         */
        public $db_version = '2.0.0';

        /**
         * Wishlist panel
         *
         * @var string Panel hookname
         * @since 2.0.0
         */
        protected $_panel = null;

        /**
         * Tab name
         *
         * @var string
         * @since 1.0.0
         */
        public $tab;

        /**
         * Various links
         *
         * @var string
         * @access public
         * @since 1.0.0
         */
        public $banner_url = 'http://cdn.yithemes.com/plugins/yith_wishlist.php?url';
        public $banner_img = 'http://cdn.yithemes.com/plugins/yith_wishlist.php';
        public $doc_url = 'http://yithemes.com/docs-plugins/yith-woocommerce-wishlist/';
        public $premium_landing_url = 'http://yithemes.com/themes/plugins/yith-woocommerce-wishlist/';

        /**
         * Plugin options
         *
         * @var array
         * @since 1.0.0
         */
        public $options;

        /**
         * List of available tab for wishlist panel
         *
         * @var array
         * @access public
         * @since 2.0.0
         */
        public $available_tabs = array();

        /**
         * Default tab to show when no selected
         *
         * @var string
         * @access public
         * @since 2.0.0
         */
        public $default_tab = 'settings';

        /**
         * Returns single instance of the class
         *
         * @return \YITH_WCWL_Admin_Init
         * @since 2.0.0
         */
        public static function get_instance(){
            if( is_null( self::$instance ) ){
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor of the class
         *
         * @return \YITH_WCWL_Admin_Init
         * @since 2.0.0
         */
        public function __construct(){
            define( 'YITH_WCWL_VERSION', $this->version );
            define( 'YITH_WCWL_DB_VERSION', $this->db_version );

            // init premium features for admin panel
            if( function_exists( 'YITH_WCWL_Admin_Premium' ) ){
                YITH_WCWL_Admin_Premium();
            }

            /**
             * Support to WC 2.0.x
             */
            global $woocommerce;
            $is_woocommerce_2_0 = version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.1', '<' );

            $this->options = $this->_plugin_options();

            if ( ! defined( 'DOING_AJAX' ) ) {
                $this->install();
            }

            add_action( 'init', array( $this, 'init' ), 0 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
            add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCWL_DIR . 'init.php' ), array( $this, 'action_links' ) );
            add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta' ), 10, 2 );

            if( $is_woocommerce_2_0 ) {
                add_filter( 'woocommerce_page_settings', array( $this, 'add_page_setting_woocommerce' ) );
            }

            // saves panel options
            add_action( 'woocommerce_update_option_yith_wcwl_color_panel', array( $this, 'update_color_options' ) );

            // handles custom wc option type
            add_action( 'woocommerce_admin_field_yith_wcwl_color_panel', array( $this, 'print_color_panel' ) );

            // register wishlist panel
            add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
            add_action( 'yith_wcwl_premium_tab', array( $this, 'print_premium_tab' ) );

            // register pointer methods
            add_action( 'admin_init', array( $this, 'register_pointer' ) );

            //Apply filters
            $this->banner_url = apply_filters( 'yith_wcmg_banner_url', $this->banner_url );
        }

        /* === INITIALIZATION SECTION === */

        /**
         * Initiator method. Initiate properties.
         *
         * @return void
         * @access private
         * @since 1.0.0
         */
        public function init() {
            $this->tab     = __( 'Wishlist', 'yit' );
            $this->available_tabs = apply_filters( 'yith_wcwl_available_admin_tabs', array(
                'settings' => __( 'Settings', 'yit' ),
                'colors' => __( 'Colors', 'yit' ),
                'premium' => __( 'Premium Version', 'yit' )
            ) );
            $this->default_tab = apply_filters( 'yith_wcwl_default_admin_tab', $this->default_tab );

            wp_register_style( 'yith-wcwl-admin', YITH_WCWL_URL . 'assets/css/admin.css' );
        }

        /**
         * Run the installation
         *
         * @return void
         * @since 1.0.0
         */
        public function install() {
            $stored_db_version = get_option( 'yith_wcwl_db_version' );

            if( $stored_db_version == '1.0.0' ){
                add_action( 'init', array( YITH_WCWL_Install(), 'update' ) );
                add_action( 'init', 'flush_rewrite_rules' );
                YITH_WCWL_Install()->default_options( $this->options );

                // Plugin installed
                do_action( 'yith_wcwl_installed' );
                do_action( 'yith_wcwl_updated' );
            }
            elseif ( $this->db_version != $stored_db_version || ! YITH_WCWL_Install()->is_installed() ) {
                add_action( 'init', array( YITH_WCWL_Install(), 'init' ) );
                YITH_WCWL_Install()->default_options( $this->options );

                // Plugin installed
                do_action( 'yith_wcwl_installed' );
            }
        }

        /**
         * Update plugin color options.
         *
         * @return void
         * @since 1.0.0
         */
        public function update_color_options() {
            global $pagenow;

            $colors_options = array();

            foreach ( YITH_WCWL_Init()->colors_options as $name => $option ) {
                foreach ( $option as $id => $color ) {
                    $default_value = isset( $colors_options[$name][$id] ) ? $colors_options[$name][$id] : '';
                    $colors_options[$name][$id] = isset( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) && ! empty( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) ? woocommerce_format_hex( $_POST['yith_wcwl_color_' . $name . '_' . $id] ) : $default_value;
                }
            }

            update_option( 'yith_wcwl_frontend_css_colors', maybe_serialize( $colors_options ) );
        }

        /**
         * Print color panel.
         *
         * @return void
         * @since 1.0.0
         */
        public function print_color_panel() {
            ?>
            <div id="yith_wcwl_styles_colors">
                <h3><?php _e( 'Colors', 'yit' ) ?></h3>
                <?php $this->_styles_options() ?>
            </div> <?php
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
         * action_links function.
         *
         * @access public
         *
         * @param mixed $links
         * @return array
         */
        public function action_links( $links ) {
            $plugin_links = array(
                '<a href="' . admin_url( 'admin.php?page=yith_wcwl_panel&tab=settings' ) . '">' . __( 'Settings', 'yit' ) . '</a>'
            );

            if( ! function_exists( 'YITH_WCWL_Premium' ) ){
                $plugin_links[] = '<a target="_blank" href="' . $this->premium_landing_url . '">' . __( 'Premium Version', 'yit' ) . '</a>';
            }

            return array_merge( $links, $plugin_links );
        }

        /**
         * Adds plugin row meta
         *
         * @param $plugin_meta array
         * @param $plugin_file string
         * @return array
         * @since 2.0.0
         */
        public function add_plugin_meta( $plugin_meta, $plugin_file ){
            global $woocommerce;

            if ( $plugin_file == plugin_basename( YITH_WCWL_DIR . 'init.php' ) ) {

                // outdated wc alert

                if( version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.2', '<' ) ){
                    $woocommerce_file = $woocommerce->plugin_path;
                    if ( ! is_multisite() && current_user_can( 'delete_plugins' ) ) {
                        $plugin_meta['outdated_wc_alert'] = '<a class="outdated-wc-alert" style="color: red" href="' . wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $woocommerce_file, 'upgrade-plugin_' . $woocommerce_file ) . '">' . __( 'WARNING: This plugin requires at least WooCommerce 2.2! Plase, use this link to update it.', 'yit' ) . '</a>';
                    }
                    else{
                        $plugin_meta['outdated_wc_alert'] = '<span class="outdated-wc-alert" style="color: red">' . __( 'WARNING: This plugin requires at least WooCommerce 2.2!', 'yit' ) . '</span>';
                    }
                }

                // documentation link
                $plugin_meta['documentation'] = '<a target="_blank" href="' . $this->doc_url . '">' . __( 'Plugin Documentation', 'yit' ) . '</a>';
            }

            return $plugin_meta;
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
            }
            else{
                $settings_page = 'in this settings page';
            }

            $general_settings_start = array(

                'section_general_settings_videobox' => array(
                    'name'    => __( 'Upgrade to the PREMIUM VERSION', 'yit' ),
                    'type'    => 'videobox',
                    'default' => array(
                        'plugin_name'               => __( 'YITH WooCommerce Wishlist', 'yit' ),
                        'title_first_column'        => __( 'Discover the Advanced Features', 'yit' ),
                        'description_first_column'  => __( 'Upgrade to the PREMIUM VERSION
of YITH WOOCOMMERCE WISHLIST to benefit from all features!', 'yit' ),
                        'video'                     => array(
                            'video_id'          => '118797844',
                            'video_image_url'   => YITH_WCWL_URL . '/assets/images/video-thumb.jpg',
                            'video_description' => '',
                        ),
                        'title_second_column'       => __( 'Get Support and Pro Features', 'yit' ),
                        'description_second_column' => __( 'By purchasing the premium version of the plugin, you will take advantage of the advanced features of the product and you will get one year of free updates and support through our platform available 24h/24.', 'yit' ),
                        'button'                    => array(
                            'href'  => 'http://yithemes.com/themes/plugins/yith-woocommerce-wishlist/',
                            'title' => 'Get Support and Pro Features'
                        )
                    ),
                    'id'      => 'yith_wcwl_general_videobox'
                ),

                'general_section_start' => array(
                    'name' => __( 'General Settings', 'yit' ),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'yith_wcwl_general_settings'
                ),

                'wishlist_enable' => array(
                    'name'    => __( 'Enable YITH Wishlist', 'yit' ),
                    'desc'    => sprintf( __( 'Enable all plugin features. <strong>Be sure to select a voice in the wishlist page menu in %s.</strong> Also, please read the plugin <a href="%s" target="_blank">documentation</a>.', 'yit' ), $settings_page, esc_url( $this->doc_url ) ),
                    'id'      => 'yith_wcwl_enabled',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'default_wishlist_title' => array(
                    'name'    => __( 'Default wishlist title', 'yit' ),
                    'id'      => 'yith_wcwl_wishlist_title',
                    'std'     => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce < 2.0
                    'default' => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                )
            );

            $general_settings_end = array(
                'add_to_wishlist_position' => array(
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
                'redirect_to_cart' => array(
                    'name'    => __( 'Redirect to cart', 'yit' ),
                    'desc'    => __( 'Redirect to cart page if "Add to cart" button is clicked in the wishlist page.', 'yit' ),
                    'id'      => 'yith_wcwl_redirect_cart',
                    'std'     => 'no', // for woocommerce < 2.0
                    'default' => 'no', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'remove_after_add_to_cart' => array(
                    'name'    => __( 'Remove if added to the cart', 'yit' ),
                    'desc'    => __( 'Remove the product from the wishlist if is been added to the cart.', 'yit' ),
                    'id'      => 'yith_wcwl_remove_after_add_to_cart',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'add_to_wishlist_text' => array(
                    'name'    => __( '"Add to Wishlist" text', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_wishlist_text',
                    'std'     => __( 'Add to Wishlist', 'yit' ), // for woocommerce < 2.0
                    'default' => __( 'Add to Wishlist', 'yit' ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                'add_to_cart_text' => array(
                    'name'    => __( '"Add to Cart" text', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_cart_text',
                    'std'     => __( 'Add to Cart', 'yit' ), // for woocommerce < 2.0
                    'default' => __( 'Add to Cart', 'yit' ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                'show_unit_price' => array(
                    'name'    => __( 'Show Unit price', 'yit' ),
                    'desc'    => __( 'Show unit price for each product in wishlist', 'yit' ),
                    'id'      => 'yith_wcwl_price_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),
                'show_add_to_cart' => array(
                    'name'    => __( 'Show "Add to Cart" button', 'yit' ),
                    'desc'    => __( 'Show "Add to cart" button for each product in wishlist', 'yit' ),
                    'id'      => 'yith_wcwl_add_to_cart_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),
                'show_stock_status' => array(
                    'name'    => __( 'Show Stock status', 'yit' ),
                    'desc'    => __( 'Show "In stock" or "Out of stock" label for each product in wishlist', 'yit' ),
                    'id'      => 'yith_wcwl_stock_show',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox',
                    'css'     => 'min-width:300px;',
                ),

                'general_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'yith_wcwl_general_settings'
                )
            );

            if( $is_woocommerce_2_0 ) {
                $options['general_settings'] = array_merge( $general_settings_start, $general_settings_end );
            }
            else{
                $options['general_settings'] = array_merge( $general_settings_start,  array( $this->get_wcwl_page_option() ), $general_settings_end );
            }

            $options['styles'] = array(
                'styles_section_start' => array(
                    'name' => __( 'Styles', 'yit' ),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'yith_wcwl_styles'
                ),

                'use_buttons' => array(
                    'name'    => __( 'Use buttons', 'yit' ),
                    'desc'    => __( 'Use buttons instead of a simple anchors.', 'yit' ),
                    'id'      => 'yith_wcwl_use_button',
                    'std'     => 'no', // for woocommerce < 2.0
                    'default' => 'no', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'custom_css' => array(
                    'name'    => __( 'Custom CSS', 'yit' ),
                    'id'      => 'yith_wcwl_custom_css',
                    'css'     => 'width:100%; height: 75px;',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'textarea'
                ),
                'use_theme_style' => array(
                    'name'    => __( 'Use theme style', 'yit' ),
                    'desc'    => __( 'Use the theme style.', 'yit' ),
                    'id'      => 'yith_wcwl_frontend_css',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'rounded_buttons' => array(
                    'name'    => __( 'Button rounded corners', 'yit' ),
                    'desc'    => __( 'Make buttons corner rounded', 'yit' ),
                    'id'      => 'yith_wcwl_rounded_corners',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'add_to_wishlist_icon' => array(
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
                'add_to_cart_icon' => array(
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

                'styles_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'yith_wcwl_styles'
                )
            );

            $options['socials_share'] = array(
                'socials_section_start' => array(
                    'name' => __( 'Socials &amp; Share', 'yit' ),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'yith_wcwl_socials_share'
                ),

                'share_on_facebook' => array(
                    'name'    => __( 'Share on Facebook', 'yit' ),
                    'desc'    => __( 'Show "Share on Facebook" button', 'yit' ),
                    'id'      => 'yith_wcwl_share_fb',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'share_on_twitter' => array(
                    'name'    => __( 'Tweet on Twitter', 'yit' ),
                    'desc'    => __( 'Show "Tweet on Twitter" button', 'yit' ),
                    'id'      => 'yith_wcwl_share_twitter',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'share_on_pinterest' => array(
                    'name'    => __( 'Pin on Pinterest', 'yit' ),
                    'desc'    => __( 'Show "Pin on Pinterest" button', 'yit' ),
                    'id'      => 'yith_wcwl_share_pinterest',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'share_on_googleplus' => array(
                    'name'    => __( 'Share on Google+', 'yit' ),
                    'desc'    => __( 'Show "Share on Facebook" button', 'yit' ),
                    'id'      => 'yith_wcwl_share_googleplus',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'share_by_email' => array(
                    'name'    => __( 'Share by Email', 'yit' ),
                    'desc'    => __( 'Show "Share by Email" button', 'yit' ),
                    'id'      => 'yith_wcwl_share_email',
                    'std'     => 'yes', // for woocommerce < 2.0
                    'default' => 'yes', // for woocommerce >= 2.0
                    'type'    => 'checkbox'
                ),
                'socials_title' => array(
                    'name'    => __( 'Social title', 'yit' ),
                    'id'      => 'yith_wcwl_socials_title',
                    'std'     => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce < 2.0
                    'default' => sprintf( __( 'My wishlist on %s', 'yit' ), get_bloginfo( 'name' ) ), // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),
                'socials_text' =>  array(
                    'name'    => __( 'Social text', 'yit' ),
                    'desc'    => __( 'Will be used by Facebook, Twitter and Pinterest. Use <strong>%wishlist_url%</strong> where you want the URL of your wishlist to appear.', 'yit' ),
                    'id'      => 'yith_wcwl_socials_text',
                    'css'     => 'width:100%; height: 75px;',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'textarea'
                ),
                'socials_image' => array(
                    'name'    => __( 'Social image URL', 'yit' ),
                    'id'      => 'yith_wcwl_socials_image_url',
                    'std'     => '', // for woocommerce < 2.0
                    'default' => '', // for woocommerce >= 2.0
                    'type'    => 'text',
                    'css'     => 'min-width:300px;',
                ),

                'socials_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'yith_wcwl_styles'
                )
            );

            return apply_filters( 'yith_wcwl_admin_options', $options );
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

            foreach ( YITH_WCWL_Init()->colors_options as $color => $attrs ) {
                if ( ! isset( $colors[$color] ) ) {
                    $colors[$color] = $attrs;
                }
            }

            ?>
            <div class="color-panel">
            <div class="clear"></div>
            <h4><?php _e( '"Add to wishlist" button', 'yit' ) ?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background', 'yit' ), 'yith_wcwl_color_add_to_wishlist_background', $colors['add_to_wishlist']['background'] );
            yith_frontend_css_color_picker( __( 'Text', 'yit' ), 'yith_wcwl_color_add_to_wishlist_color', $colors['add_to_wishlist']['color'] );
            yith_frontend_css_color_picker( __( 'Border', 'yit' ), 'yith_wcwl_color_add_to_wishlist_border_color', $colors['add_to_wishlist']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div>
            <?php

            // hover
            yith_frontend_css_color_picker( __( 'Background (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_background', $colors['add_to_wishlist_hover']['background'] );
            yith_frontend_css_color_picker( __( 'Text (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_color', $colors['add_to_wishlist_hover']['color'] );
            yith_frontend_css_color_picker( __( 'Border (hover)', 'yit' ), 'yith_wcwl_color_add_to_wishlist_hover_border_color', $colors['add_to_wishlist_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div>
            <h4><?php _e( '"Add to Cart" button', 'yit' ) ?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background', 'yit' ), 'yith_wcwl_color_add_to_cart_background', $colors['add_to_cart']['background'] );
            yith_frontend_css_color_picker( __( 'Text', 'yit' ), 'yith_wcwl_color_add_to_cart_color', $colors['add_to_cart']['color'] );
            yith_frontend_css_color_picker( __( 'Border', 'yit' ), 'yith_wcwl_color_add_to_cart_border_color', $colors['add_to_cart']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div>
            <?php

            // hover
            yith_frontend_css_color_picker( __( 'Background (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_background', $colors['add_to_cart_hover']['background'] );
            yith_frontend_css_color_picker( __( 'Text (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_color', $colors['add_to_cart_hover']['color'] );
            yith_frontend_css_color_picker( __( 'Border (hover)', 'yit' ), 'yith_wcwl_color_add_to_cart_hover_border_color', $colors['add_to_cart_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div>
            <h4><?php _e( '"Style 1" button', 'yit' ) ?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background', 'yit' ), 'yith_wcwl_color_button_style_1_background', $colors['button_style_1']['background'] );
            yith_frontend_css_color_picker( __( 'Text', 'yit' ), 'yith_wcwl_color_button_style_1_color', $colors['button_style_1']['color'] );
            yith_frontend_css_color_picker( __( 'Border', 'yit' ), 'yith_wcwl_color_button_style_1_border_color', $colors['button_style_1']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div>
            <?php

            // hover
            yith_frontend_css_color_picker( __( 'Background (hover)', 'yit' ), 'yith_wcwl_color_button_style_1_hover_background', $colors['button_style_1_hover']['background'] );
            yith_frontend_css_color_picker( __( 'Text (hover)', 'yit' ), 'yith_wcwl_color_button_style_1_hover_color', $colors['button_style_1_hover']['color'] );
            yith_frontend_css_color_picker( __( 'Border (hover)', 'yit' ), 'yith_wcwl_color_button_style_1_hover_border_color', $colors['button_style_1_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div>
            <h4><?php _e( '"Style 2" button', 'yit' ) ?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background', 'yit' ), 'yith_wcwl_color_button_style_2_background', $colors['button_style_2']['background'] );
            yith_frontend_css_color_picker( __( 'Text', 'yit' ), 'yith_wcwl_color_button_style_2_color', $colors['button_style_2']['color'] );
            yith_frontend_css_color_picker( __( 'Border', 'yit' ), 'yith_wcwl_color_button_style_2_border_color', $colors['button_style_2']['border_color'] );

            ?>
            <div class="clear" style="height:10px;"></div>
            <?php

            // hover
            yith_frontend_css_color_picker( __( 'Background (hover)', 'yit' ), 'yith_wcwl_color_button_style_2_hover_background', $colors['button_style_2_hover']['background'] );
            yith_frontend_css_color_picker( __( 'Text (hover)', 'yit' ), 'yith_wcwl_color_button_style_2_hover_color', $colors['button_style_2_hover']['color'] );
            yith_frontend_css_color_picker( __( 'Border (hover)', 'yit' ), 'yith_wcwl_color_button_style_2_hover_border_color', $colors['button_style_2_hover']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div>
            <h4><?php _e( 'Wishlist table', 'yit' )?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background', 'yit' ), 'yith_wcwl_color_wishlist_table_background', $colors['wishlist_table']['background'] );
            yith_frontend_css_color_picker( __( 'Text', 'yit' ), 'yith_wcwl_color_wishlist_table_color', $colors['wishlist_table']['color'] );
            yith_frontend_css_color_picker( __( 'Border', 'yit' ), 'yith_wcwl_color_wishlist_table_border_color', $colors['wishlist_table']['border_color'] );

            ?>
            <div class="clear" style="height:30px;"></div>
            <h4><?php _e( 'Headers', 'yit' ) ?></h4>
            <?php

            yith_frontend_css_color_picker( __( 'Background color', 'yit' ), 'yith_wcwl_color_headers_background', $colors['headers']['background'] );

            do_action( 'yith_wcwl_admin_color_pickers' );

            ?>
            <div class="clear"></div>
            </div>
            <div class="clear" style="height:30px;"></div>

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

                jQuery('#yith_wcwl_multi_wishlist_enable').on('change', function () {
                    if (jQuery(this).is(':checked')) {
                        jQuery('#yith_wcwl_wishlist_create_title').parents('tr').show();
                        jQuery('#yith_wcwl_wishlist_manage_title').parents('tr').show();
                    }
                    else{
                        jQuery('#yith_wcwl_wishlist_create_title').parents('tr').hide();
                        jQuery('#yith_wcwl_wishlist_manage_title').parents('tr').hide();
                    }
                }).change();
            </script>
        <?php
        }

        /* === WISHLIST SUBPANEL SECTION === */

        /**
         * Register wishlist panel
         *
         * @return void
         * @since 2.0.0
         */
        public function register_panel() {

            $args = array(
                'create_menu_page' => true,
                'parent_slug'   => '',
                'page_title'    => __( 'Wishlist', 'yit' ),
                'menu_title'    => __( 'Wishlist', 'yit' ),
                'capability'    => 'manage_options',
                'parent'        => '',
                'parent_page'   => 'yit_plugin_panel',
                'page'          => 'yith_wcwl_panel',
                'admin-tabs'    => $this->available_tabs,
                'options-path'  => YITH_WCWL_DIR . 'plugin-options'
            );

            /* === Fixed: not updated theme  === */
            if( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
                require_once( YITH_WCWL_DIR . 'plugin-fw/lib/yit-plugin-panel-wc.php' );
            }

            $this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
        }

        /**
         * Load admin style.
         *
         * @return void
         * @since 1.0.0
         */
        public function enqueue() {
            global $woocommerce;
            wp_enqueue_style( 'yith-wcwl-admin' );
        }

        /**
         * Prints tab premium of the plugin
         *
         * @return void
         * @since 2.0.0
         */
        public function print_premium_tab() {
            $premium_tab = YITH_WCWL_DIR . 'templates/admin/wishlist-panel-premium.php';

            if( file_exists( $premium_tab ) ){
                include( $premium_tab );
            }
        }

        /* === POINTER SECTION === */

        /**
         * Register pointers for notify plugin updates to user
         *
         * @return void
         * @since 2.0.0
         */
        public function register_pointer(){

            if( ! class_exists( 'YIT_Pointers' ) ){
                include_once( 'plugin-fw/lib/yit-pointers.php' );
            }

            $args[] = array(
                'screen_id'     => 'plugins',
                'pointer_id' => 'yith_wcwl_panel',
                'target'     => '#toplevel_page_yit_plugin_panel',
                'content'    => sprintf( '<h3> %s </h3> <p> %s </p>',
                    __( 'Wishlist Activated', 'yit' ),
                    apply_filters( 'yith_wcwl_activated_pointer_content', sprintf( __( 'In the YIT Plugin tab you can find the Wishlist options. With this menu, you can access to all the settings of our plugins that you have activated. Wishlist is available in an outstanding PREMIUM version with many new options, <a href="%s">discover it now</a>.', 'yit' ), $this->premium_landing_url ) )
                ),
                'position'   => array( 'edge' => 'left', 'align' => 'center' ),
                'init'  => YITH_WCWL_INIT
            );

            $args[] = array(
                'screen_id'     => 'update',
                'pointer_id' => 'yith_wcwl_panel',
                'target'     => '#toplevel_page_yit_plugin_panel',
                'content'    => sprintf( '<h3> %s </h3> <p> %s </p>',
                    __( 'Wishlist Updated', 'yit' ),
                    apply_filters( 'yith_wcwl_updated_pointer_content', sprintf( __( 'From now on, you can find all the options of Wishlist under YIT Plugin -> Wishlist instead of WooCommerce -> Settings -> Wishlist, as in the previous version. When one of our plugins updates, a new voice will be added to this menu. Wishlist renovates with new available options, <a href="%s">discover the PREMIUM version.</a>', 'yit' ), $this->premium_landing_url ) )
                ),
                'position'   => array( 'edge' => 'left', 'align' => 'center' ),
                'init'  => YITH_WCWL_INIT
            );

            YIT_Pointers()->register( $args );
        }
    }
}

/**
 * Unique access to instance of YITH_WCWL_Admin_Init class
 *
 * @return \YITH_WCWL_Admin_Init
 * @since 2.0.0
 */
function YITH_WCWL_Admin_Init(){
    return YITH_WCWL_Admin_Init::get_instance();
}