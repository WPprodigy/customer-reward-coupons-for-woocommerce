<?php
/**
 * Points and Rewards Compatibility.
 *
 * @class 	CRC_Points_Rewards_Compatibility
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CRC_Points_Rewards_Compatibility {

	/**
	 * Constructor for the main class.
	 */
	public function __construct() {
		// WooCommerce Settings
		add_filter( 'customer_reward_coupons_woocommerce_settings', array( $this, 'add_settings' ), 10 );

		// Event Description
		add_filter( 'wc_points_rewards_event_description', array( $this, 'event_description' ), 10, 3 );

		// Calculate Rewards
		if ( get_option( 'woocommerce_crc_enable_points_rewards_integration' ) == 'yes' ) {
			add_action( 'customer_reward_coupon_used', array( $this, 'increase_rewards' ), 10, 2 );
		}
	}

	/**
	 * Add WooCommerce settings.
	 */
	public function add_settings( $settings ) {
		$settings[] = array(
			'name' => __( 'Points and Rewards', 'customer-reward-coupons-for-woocommerce' ),
			'type' => 'title',
			'desc' => __( 'Reward points to a customer when their coupon is used.', 'customer-reward-coupons-for-woocommerce' ),
			'id'   => 'customer_reward_coupons_points_rewards'
		);
		$settings[] = array(
			'name'            => __( 'Enable Integration', 'customer-reward-coupons-for-woocommerce' ),
			'type'            => 'checkbox',
			'default'         => 'yes',
			'id'              => 'woocommerce_crc_enable_points_rewards_integration'
		);
		$settings[] = array(
			'name'    => __( 'Reward Amount', 'customer-reward-coupons-for-woocommerce' ),
			'type'    => 'number',
			'desc'    => __( 'Amount of points that will be given to the customer.', 'customer-reward-coupons-for-woocommerce' ),
			'default' => '20',
			'id'      => 'woocommerce_crc_points_rewards_reward_amount',
			'desc_tip' => true
		);
		$settings[] = array( 'type' => 'sectionend', 'id' => 'customer_reward_coupons_points_rewards' );

		return apply_filters( 'customer_reward_coupons_get_points_rewards_settings', $settings );
	}

	/**
	 * Add event description.
	 */
	public function event_description( $event_description, $event_type, $event ) {
		global $wc_points_rewards;
		$points_label = $wc_points_rewards->get_points_label( $event ? $event->points : null );

		if ( $event_type === 'customer-reward-coupon-used' ) {
			$event_description = sprintf( __( '%s earned for reward coupon use', 'customer-reward-coupons-for-woocommerce'  ), $points_label ); 
		}  

		return $event_description;
	}

	/**
	 * Increase funds.
	 */
	public function increase_rewards( $order_id, $owner ) {
		// Settings Information
		$reward_amount = get_option( 'woocommerce_crc_points_rewards_reward_amount' );

		// Order Information
		$order = wc_get_order( $order_id );

		// Add Points
		WC_Points_Rewards_Manager::increase_points( $owner->ID, $reward_amount, 'customer-reward-coupon-used' );

		do_action( 'customer_reward_coupons_points_added', $order, $reward_amount, $owner->ID );

		// Add Order Note
		global $wc_points_rewards;
		$points_label = $wc_points_rewards->get_points_label( $reward_amount );
		$message = sprintf( __( 'A reward coupon was used. Added %1$s %2$s to "%3$s"', 'customer-reward-coupons-for-woocommerce' ), $reward_amount, $points_label, $owner->user_login );
		$order->add_order_note( $message );
	}

}
