<?php
/**
 * Plugin Name: WoowPay - 2Checkout
 * Plugin URI: https://wordpress.org/plugins/woowpay/
 * Description: 2checkout payment gateway for WooCommerce
 * Version: 1.1.2
 * Author: QuantumCloud
 * Author URI: https://www.quantumcloud.com/
 * Requires at least: 4.6
 * Tested up to: 5.6
 * Text Domain: qc-2co-payment-gateway
 * Domain Path: /lang/
 * License: GPL2
 */

defined('ABSPATH') or die("No direct script access!");

define('QCTWOCO_INLINE_CHECKOUT_URL', plugin_dir_url(__FILE__));
define('QCTWOCO_INLINE_CHECKOUT_PATH', plugin_dir_path(__FILE__));

require_once( QCTWOCO_INLINE_CHECKOUT_PATH . '/gateway/twocheckout/wc-twocheckout.php');

add_action( 'plugins_loaded', 'qctwoco_gateway_init');
function qctwoco_gateway_init(){
	require_once( QCTWOCO_INLINE_CHECKOUT_PATH . '/gateway/class-2co-hosted-gateway.php');

	qctwoco_init_woo_gateway();
}

add_filter( 'woocommerce_payment_gateways', 'qctwoco_gateway_add');
function qctwoco_gateway_add( $methods ) {
    $methods[] = 'QCTWOCO_WC_Gateway';
    return $methods;
}

add_filter( 'plugin_action_links', 'qctwoco_plugin_action_links', 10, 3 );
function qctwoco_plugin_action_links( $links, $file, $data ){
	
	 if ( isset($file) && $file === 'woowpay/qc-2co-payment-gateway.php' ) {
	 	 $new_links = array(
		                '2co-hosted-gateway' => '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=qcwoo_2co_hosted_payment_gateway').'" >'.esc_html__('Hosted Checkout Settings', 'qc-2co-payment-gateway').'</a>',
		                '2co-inline-credit-card' => '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=twocheckout').'" >'.esc_html__('Credit Card Settings', 'qc-2co-payment-gateway').'</a>',
		                );

		$links = array_merge( $new_links, $links );
	 }
	 return $links;
}

if( is_admin() ){
	require_once('class-plugin-deactivate-feedback.php');
	$wpbot_feedback = new WoowPay_Usage_Feedback( __FILE__, 'plugins@quantumcloud.com', false, true );
}