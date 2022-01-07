<?php
/*
 * Plugin Name:       Tazapay Checkout Payment Gateway
 * Plugin URI:        https://www.logicrays.com/
 * Description:       Pay securely with buyer protection.
 * Version:           1.0.0
 * Author:            Logicrays
 * Author URI:        https://www.logicrays.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-tp-payment-gateway
 * Domain Path:       /languages
 */
define('TCPG_CSS_JSS_VERISON', time());
define('TCPG_PUBLIC_ASSETS_DIR', plugins_url('assets/', __FILE__));
$plugin = plugin_basename(__FILE__);
register_activation_hook(__FILE__, 'tcpg_user_install');

/*
* Tazapay user create table.
*/
function tcpg_user_install()
{
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
        environment varchar(255) NOT NULL,
        created varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter('woocommerce_payment_gateways', 'tcpg_add_gateway_class');
function tcpg_add_gateway_class($gateways)
{
    $gateways[] = 'TCPG_Gateway';
    return $gateways;
}

/*
* Frontend css and js
*/
add_action('wp_enqueue_scripts', 'tcpg_frontend_enqueue_styles');
function tcpg_frontend_enqueue_styles()
{
    wp_enqueue_style('tazapay_form_css', TCPG_PUBLIC_ASSETS_DIR . 'css/tazapay-frontend.css', array(), TCPG_CSS_JSS_VERISON, 'all');
    wp_enqueue_script('tazapay_validate_js', TCPG_PUBLIC_ASSETS_DIR . 'js/jquery.validate.min.js', array('jquery'), TCPG_CSS_JSS_VERISON, true);
    wp_enqueue_script('tazapay-admin', TCPG_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TCPG_CSS_JSS_VERISON, true);
}

/*
* Backend css and js
*/
add_action('admin_enqueue_scripts', 'tcpg_enqueue_styles');
function tcpg_enqueue_styles()
{
    wp_enqueue_style('tazapay_form_css', TCPG_PUBLIC_ASSETS_DIR . 'css/tazapay-form.css', array(), TCPG_CSS_JSS_VERISON, 'all');
    wp_enqueue_script('tazapay_validate_js', TCPG_PUBLIC_ASSETS_DIR . 'js/jquery.validate.min.js', array('jquery'), TCPG_CSS_JSS_VERISON, true);
    wp_enqueue_script('tazapay-admin', TCPG_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TCPG_CSS_JSS_VERISON, true);
}

/*
* Plugin settings page
*/
add_filter("plugin_action_links_$plugin", 'tcpg_plugin_settings_link');
function tcpg_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tz_tazapay">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'tcpg_init_gateway_class');
function tcpg_init_gateway_class()
{
    require 'includes/class-wc-tazapay-payment-gateway.php';
    require 'includes/class-wc-tazapay-user-list-table.php';
    require 'includes/class-wc-tazapay-account-form.php';
    require 'includes/wc-order-status-change.php';

    $woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');
    $tazapay_seller_type             = !empty($woocommerce_tz_tazapay_settings['tazapay_seller_type']) ? esc_html($woocommerce_tz_tazapay_settings['tazapay_seller_type']) : '';

    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    if ($tazapay_seller_type == 'multiseller' && is_plugin_active('dokan-lite/dokan.php')) {
        require 'includes/dokan-add-new-menu.php';
    }
    if ($tazapay_seller_type == 'multiseller' && is_plugin_active('wc-vendors/class-wc-vendors.php')) {
        require 'includes/wcvendors-add-new-menu.php';
    }
    if ($tazapay_seller_type == 'multiseller' && is_plugin_active('wc-multivendor-marketplace/wc-multivendor-marketplace.php') && is_plugin_active('wc-frontend-manager/wc_frontend_manager.php')) {
        require 'includes/wcfm-add-new-menu.php';
    }

    load_plugin_textdomain('wc-tp-payment-gateway', false, basename(dirname(__FILE__)) . '/languages/');
}
