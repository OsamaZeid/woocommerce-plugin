<?php
/*
 * Plugin Name:       WooCommerce TazaPay Payment Gateway
 * Plugin URI:        https://www.logicrays.com/
 * Description:       Pay with your TazaPay via our super-cool payment gateway.
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
$plugin = plugin_basename(__FILE__);

register_activation_hook( __FILE__, 'tazapay_user_install' );

function tazapay_user_install() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'tazapay_user';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        account_id varchar(255) NOT NULL,
        user_type varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        contact_code varchar(255) NOT NULL,
        contact_number varchar(255) NOT NULL,
        country varchar(255) NOT NULL,
        ind_bus_type varchar(255) NOT NULL,
        business_name varchar(255) NOT NULL,
        partners_customer_id varchar(255) NOT NULL,
        environment varchar(255) NOT NULL,
        created varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter( 'woocommerce_payment_gateways', 'tazapay_add_gateway_class' );
function tazapay_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_TazaPay_Gateway'; // your class name is here
    return $gateways;
}

/*
* Frontend css
*/
add_action( 'wp_enqueue_scripts', 'tazapay_frontend_enqueue_styles' );
function tazapay_frontend_enqueue_styles()
{
    wp_enqueue_style('tazapay_form_css', TAZAPAY_PUBLIC_ASSETS_DIR . 'css/tazapay-frontend.css', array(), TAZAPAY_CSS_JSS_VERISON, 'all');
    wp_enqueue_script( 'tazapay_validate_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
    
    wp_enqueue_script( 'tazapay-admin', TAZAPAY_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);   
}

/*
* Backend css and js
*/
add_action( 'admin_enqueue_scripts', 'tazapay_enqueue_styles' );
function tazapay_enqueue_styles()
{
    wp_enqueue_style('tazapay_form_css', TAZAPAY_PUBLIC_ASSETS_DIR . 'css/tazapay-form.css', array(), TAZAPAY_CSS_JSS_VERISON, 'all');    
    wp_enqueue_script( 'tazapay_validate_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
    
    wp_enqueue_script( 'tazapay-admin', TAZAPAY_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TAZAPAY_CSS_JSS_VERISON, true);
}

/*
* Plugin settings page
*/
add_filter("plugin_action_links_$plugin", 'tazapay_plugin_settings_link' );
function tazapay_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tz_tazapay">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'tazapay_init_gateway_class' );
function tazapay_init_gateway_class() {
    require 'includes/class-wc-tazapay-payment-gateway.php';
    require 'includes/class-wc-tazapay-user-list-table.php';
    require 'includes/class-wc-tazapay-account-form.php';
    require 'includes/wc-order-status-change.php';

    $woocommerce_tz_tazapay_settings = get_option( 'woocommerce_tz_tazapay_settings' );

    $tazapay_multi_seller_plugin = $woocommerce_tz_tazapay_settings['tazapay_multi_seller_plugin'];

    if($tazapay_multi_seller_plugin == 'dokan'){
        require 'includes/dokan-add-new-menu.php';
    }

    load_plugin_textdomain( 'wc-tp-payment-gateway', false, basename( dirname( __FILE__ ) ) . '/languages/' );
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

