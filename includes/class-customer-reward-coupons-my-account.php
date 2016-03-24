<?php
/**
 * Customer Reward Coupons for WooCommerce
 *
 * @class 	Customer_Reward_Coupons_My_Account
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Customer_Reward_Coupons_My_Account {

	/**
	 * Constructor for the main class.
	 */
	public function __construct() {
		// My Account Display
		add_action( 'woocommerce_before_my_account', array( $this, 'account_page_ouput' ), 5 );
	}

	/**
	 * Display reward coupon information on the account page.
	 */
	public function account_page_ouput() {
		$current_file = plugin_dir_path( __FILE__ );
		$template_path = str_replace( '/customer-reward-coupons-for-woocommerce/includes', '/customer-reward-coupons-for-woocommerce', $current_file );

		wc_get_template( 'myaccount/reward-coupons.php', 
			array(
				'max_coupons' => apply_filters( 'crc_max_coupons_per_customer', 1 ),
				'coupon_table_coupons' => apply_filters( 'crc_account_coupon_table_coupons', Customer_Reward_Coupons_For_WooCommerce::all_reward_coupons() ),
			), false, $template_path . 'templates/' );
	}

	/**
	 * Get the coupon's information.
	 */
	public static function get_coupon_information( $coupon_id ) {
		$meta_fields = Customer_Reward_Coupons_For_WooCommerce::coupon_meta_fields();
		$coupon_meta = array();

		foreach ( $meta_fields as $meta ) {
			$coupon_meta[$meta] = get_post_meta( $coupon_id, $meta, true );
		}

		$coupon_meta['coupon_name']      = get_the_title( $coupon_id );
		$coupon_meta['formatted_amount'] = self::formatted_coupon_amount( $coupon_meta['coupon_amount'], $coupon_meta['discount_type'] );

		return $coupon_meta;
	}

	/**
	 * Format the coupon's amount.
	 */
	public static function formatted_coupon_amount( $coupon_amount, $discount_type ) {
		if ( $discount_type == 'percent' || $discount_type == 'percent_product' ) {
			$amount = $coupon_amount . '%';
		} else {
			$amount = get_woocommerce_currency_symbol() . $coupon_amount;
		}

		return apply_filters( 'crc_coupon_formatted_amount', $amount, $coupon_amount, $discount_type );
	}

}
