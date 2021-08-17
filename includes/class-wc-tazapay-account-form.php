<?php
if(!is_admin())
{
    new WC_TazaPay_Account();
}

class WC_TazaPay_Account {

    public function __construct() {
        
        //add_action( 'admin_enqueue_scripts', array($this, 'tazapay_enqueue_styles') );

        // add_action( 'init', array($this, 'tazapay_add_accountform_endpoint') );
        // add_filter( 'query_vars', array($this, 'tazapay_accountform_query_vars'), 0 );
        // add_filter( 'woocommerce_account_menu_items', array($this, 'tazapay_add_accountform_link_my_account'), 99, 1 );
        // add_action( 'woocommerce_account_tazapay-account_endpoint', array($this, 'tazapay_accountform_content') );
        // add_action( 'wp_loaded', array($this, 'tazapay_flush_rewrite_rules') );
        // add_filter( 'woocommerce_account_menu_items' , array($this, 'tazapay_menu_panel_nav') );
        add_shortcode('tazapay-account', array($this, 'tazapay_accountform_shortcode') );
    }

    // Css and js
    public function tazapay_enqueue_styles()
    {
        wp_enqueue_style('tazapay_form_css', TAZAPAY_PUBLIC_ASSETS_DIR . 'css/tazapay-form.css', array(), TAZAPAY_CSS_JSS_VERISON, 'all');
        wp_enqueue_script('tazapay_validate_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
        wp_enqueue_script('tazapay_public_js', TAZAPAY_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), '', true);        
    }

    // Register new endpoint (URL) for My Account page
    // Note: Re-save Permalinks or it will give 404 error
    public function tazapay_add_accountform_endpoint() {
        add_rewrite_endpoint( 'tazapay-account', EP_ROOT | EP_PAGES );
    }

    // Add new query var
    public function tazapay_accountform_query_vars( $vars ) {
        $vars[] = 'tazapay-account';
        return $vars;
    }

    // Insert the new endpoint into the My Account menu  
    public function tazapay_add_accountform_link_my_account( $items ) {
        $items['tazapay-account'] = 'TazaPay Account';
        return $items;
    }

    // Add content to the new tab  
    public function tazapay_accountform_content() {
        echo do_shortcode( '[tazapay-account]' );      
    }

    // Flush rewrite rules
    public function tazapay_flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    // Reoder menu
    public function tazapay_menu_panel_nav( $items ) {
        $items = array(
            'dashboard'       => __( 'Dashboard', 'woocommerce' ),
            'orders'          => __( 'Orders', 'woocommerce' ),
            'downloads'       => __( 'Downloads', 'woocommerce' ),
            'edit-address'    => __( 'Addresses', 'woocommerce' ),
            'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
            'edit-account'    => __( 'Account Details', 'woocommerce' ),
            'tazapay-account' => __( 'TazaPay Account', 'woocommerce' ), // My custom tab here
            'customer-logout' => __( 'Logout', 'woocommerce' ),
        );    
        return $items;
    }

    // Form shortcode
    public function tazapay_accountform_shortcode($atts)
    {
        if (!is_admin() && !wp_doing_ajax()) {
            ob_start();
            require_once plugin_dir_path(__FILE__) . 'shortcodes/tazapay-accountform-shortcode.php';
            return ob_get_clean();
        } else {
            return '[tazapay-account]';
        }
    }
}