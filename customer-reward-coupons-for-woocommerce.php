<?php
/**
 * Plugin Name: Customer Reward Coupons for WooCommerce
 * Description: Allow customers to create coupons that they can share and then gain rewards when the coupon is used.
 * Version: 1.0
 * Author: Caleb Burks
 * Author URI: http://calebburks.com
 *
 * Text Domain: customer-reward-coupons-for-woocommerce
 * Domain Path: /languages/
 *
 * Requires at least: 4.0
 * Tested up to: 4.4
 *
 * Copyright: (c) 2015 Caleb Burks
 * License: GPL v3 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'customer_reward_coupons_for_woocommerce', 35 );
function customer_reward_coupons_for_woocommerce() {
	if ( ! class_exists( "Customer_Reward_Coupons_For_WooCommerce" ) && class_exists( 'WooCommerce' ) ) {
		require_once( 'includes/class-customer-reward-coupons-for-woocommerce.php' );
		new Customer_Reward_Coupons_For_WooCommerce;
	}
}

/* Silence is Golden */
