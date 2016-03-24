<?php
/**
 * Account Funds Compatibility.
 *
 * @class 	CRC_Account_Funds_Compatibility
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CRC_Account_Funds_Compatibility {

	/**
	 * Constructor for the main class.
	 */
	public function __construct() {
		// WooCommerce Settings
		add_filter( 'customer_reward_coupons_woocommerce_settings', array( $this, 'add_settings' ), 15 );

		// Calculate Rewards
		if ( get_option( 'woocommerce_crc_enable_account_funds_integration' ) == 'yes' ) {
			add_action( 'customer_reward_coupon_used', array( $this, 'increase_account_funds' ), 15, 2 );
		}
	}

	/**
	 * Add WooCommerce settings.
	 */
	public function add_settings( $settings ) {
		$settings[] = array(
			'name' => __( 'Account Funds', 'customer-reward-coupons-for-woocommerce' ),
			'type' => 'title',
			'desc' => __( 'Reward account funds to a customer when their coupon is used.', 'customer-reward-coupons-for-woocommerce' ),
			'id'   => 'customer_reward_coupons_account_funds'
		);
		$settings[] = array(
			'name'            => __( 'Enable Integration', 'customer-reward-coupons-for-woocommerce' ),
			'type'            => 'checkbox',
			'default'         => 'yes',
			'id'              => 'woocommerce_crc_enable_account_funds_integration'
		);
		$settings[] = array(
			'name'     => __( 'Reward Type', 'customer-reward-coupons-for-woocommerce' ),
			'type'     => 'select',
			'options'  => array(
				'fixed'      => __( 'Fixed Amount', 'customer-reward-coupons-for-woocommerce' ),
				'percentage' => __( 'Percentage', 'customer-reward-coupons-for-woocommerce' )
			),
			'desc'     => __( 'Percentage rewards will be based on the order\'s total amount.', 'customer-reward-coupons-for-woocommerce' ),
			'id'       => 'woocommerce_crc_account_funds_reward_type',
			'desc_tip' => true
		);
		$settings[] = array(
			'name'    => __( 'Reward Amount', 'customer-reward-coupons-for-woocommerce' ),
			'type'    => 'number',
			'desc'    => __( 'Amount of account funds that will be given to the customer.', 'customer-reward-coupons-for-woocommerce' ),
			'default' => '20',
			'id'      => 'woocommerce_crc_account_funds_reward_amount',
			'desc_tip' => true
		);
		$settings[] = array( 'type' => 'sectionend', 'id' => 'customer_reward_coupons_account_funds' );

		return apply_filters( 'customer_reward_coupons_get_account_funds_settings', $settings );
	}

	/**
	 * Increase funds.
	 */
	public function increase_account_funds( $order_id, $owner ) {
		// Settings Information
		$current_funds = get_user_meta( $owner->ID, 'account_funds', true );
		$reward_type = get_option( 'woocommerce_crc_account_funds_reward_type' );
		$reward_amount = get_option( 'woocommerce_crc_account_funds_reward_amount' );

		// Order Information
		$order = wc_get_order( $order_id );
		$order_total = $order->get_total();

		if ( $reward_type == 'percentage' ) {
			$reward_amount = ($reward_amount * $order_total) / 100;
		}

		// Add Funds
		$funds = $current_funds + floatval( $reward_amount );
		update_user_meta( $owner->ID, 'account_funds', max( 0, $funds ) );

		do_action( 'customer_reward_coupons_account_funds_added', $order, $reward_amount, $owner->ID );

		// Add Order Note
		$message = sprintf( __( 'A reward coupon was used. Added %1$s account funds to "%2$s"', 'customer-reward-coupons-for-woocommerce' ), $reward_amount, $owner->user_login );
		$order->add_order_note( $message );
	}

}
