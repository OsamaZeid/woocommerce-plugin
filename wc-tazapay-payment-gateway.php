<?php
/*
 * Plugin Name:       WooCommerce TazaPay Payment Gateway
 * Plugin URI:        https://www.logicrays.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Logicrays
 * Author URI:        https://www.logicrays.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-tp-payment-gateway
 * Domain Path:       /languages
 */

define( 'TAZAPAY_CSS_JSS_VERISON', time() );
define( 'TAZAPAY_PUBLIC_ASSETS_DIR', plugins_url('assets/', __FILE__));
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'tazapay_add_gateway_class' );

function tazapay_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_TazaPay_Gateway'; // your class name is here
    return $gateways;
}

add_action( 'admin_enqueue_scripts', 'tazapay_enqueue_styles' );
function tazapay_enqueue_styles()
{
    wp_enqueue_style('tazapay_form_css', TAZAPAY_PUBLIC_ASSETS_DIR . 'css/tazapay-form.css', array(), TAZAPAY_CSS_JSS_VERISON, 'all');    
    wp_enqueue_script( 'tazapay_validate_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
    wp_enqueue_script( 'tazapay_public_js', TAZAPAY_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
}


// register_activation_hook(__FILE__, 'add_tazapay_signup_page');
// function add_tazapay_signup_page() {
//     // Create post object
//     $tazapay_signup_page = array(
//       'post_title'    => 'Create TazaPay Account',
//       'post_content'  => '[tazapay-account]',
//       'post_status'   => 'publish',
//       'post_author'   => 1,
//       'post_type'     => 'page',
//     );
//     // Insert the post into the database
//     wp_insert_post( $tazapay_signup_page );
// }

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'tazapay_init_gateway_class' );
function tazapay_init_gateway_class() {
    require 'includes/class-wc-tazapay-payment-gateway.php';
    require 'includes/class-wc-tazapay-user-list-table.php';
    require 'includes/class-wc-tazapay-account-form.php';

    load_plugin_textdomain( 'wc-tp-payment-gateway', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}