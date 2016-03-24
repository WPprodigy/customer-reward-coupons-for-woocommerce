<?php
/**
 * Customer Reward Coupons for WooCommerce
 *
 * @class 	Customer_Reward_Coupons_For_WooCommerce
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Customer_Reward_Coupons_For_WooCommerce {

	/**
	 * Constructor for the main class.
	 */
	public function __construct() {
		if ( is_admin() ) {
			require_once 'admin/class-customer-reward-coupons-admin.php';
			new Customer_Reward_Coupons_Admin;
		}

		require_once 'class-customer-reward-coupons-my-account.php';
		new Customer_Reward_Coupons_My_Account;

		if ( class_exists( 'WC_Account_Funds' ) ) {
			require_once 'compatibility/class-crc-account-funds-compatibility.php';
			new CRC_Account_Funds_Compatibility;
		}

		if ( class_exists( 'WC_Points_Rewards' ) ) {
			require_once 'compatibility/class-crc-points-rewards-compatibility.php';
			new CRC_Points_Rewards_Compatibility;
		}

		// Trigger coupon creation on the account page
		add_action( 'woocommerce_before_my_account', array( $this, 'watch_for_coupon_creation' ), 4 );

		// Don't allow customers to use their own coupons
		add_action( 'woocommerce_coupon_is_valid', array( $this, 'maybe_remove_coupon' ), 20, 2 );

		// Check if a paid order has an customer coupon
		add_action( 'woocommerce_order_status_completed', array( $this, 'customer_reward_coupon_used' ) );
	}

	/**
	 * Get the current user's information.
	 */
	public function user_information( $type = "username" ) {
		$current_user = wp_get_current_user();

		if ( $type == "username" ) {
			$info = $current_user->user_login;
		} elseif ( $type == "id" ) {
			$info = $current_user->ID;
		}

		return $info;
	}

	/**
	 * Get the default coupon meta fields.
	 */
	public static function coupon_meta_fields() {
		$meta_fields = apply_filters( 'crc_default_coupon_meta_fields', array(
			'discount_type', 'coupon_amount', 'individual_use', 'product_ids', 'exclude_product_ids', 'usage_limit', 
			'usage_limit_per_user', 'limit_usage_to_x_items', 'usage_count', 'expiry_date', 'free_shipping', 
			'product_categories', 'exclude_product_categories', 'exclude_sale_items', 'minimum_amount', 'maximum_amount', 
			'customer_email',
		) );

		return $meta_fields;
	}

	/**
	 * Gather coupon information to be used when creating a new coupon.
	 */
	public function master_coupon_meta() {
		$master_coupon = get_option( 'woocommerce_crc_master_coupon' );
		$meta_fields   = $this->coupon_meta_fields();
		$coupon_meta   = array();

		foreach ( $meta_fields as $meta ) {
			$coupon_meta[$meta] = get_post_meta( $master_coupon, $meta, true );
		}

		return apply_filters( 'crc_master_coupon_meta', $coupon_meta );
	}

	/**
	 * Generate a random hash from 1-40 characters.
	 */
	public function random_hash( $shortcode ) {
		if ( ! $shortcode ) {
			return false;
		}

		// Get the length attribute from the shortcode
		add_shortcode( 'random', array( $this, 'random' ) );
		$length = min( do_shortcode( $shortcode ), 40 );
		remove_shortcode( 'random', array( $this, 'random' ) );

		// Get a random 40 character hash
		$hash = wc_rand_hash();

		// Return the hash, but at the length the shortcode declares.
		return substr( $hash, 0, $length );
	}

	/**
	 * Simple shortcode for the random_hash() method.
	 */
	public function random( $atts ) {
		$atts = shortcode_atts( array(
			'length' => '10'
		), $atts );

		return $atts['length'];
	}

	/**
	 * Create the coupon's name based on the user given value.
	 */
	public function get_coupon_name() {
		$name = get_option( 'woocommerce_crc_coupon_name' );
		$shortcode_search = preg_match( '/\[random(.*?)\]/', $name, $match );

		if ( $shortcode_search ) {
			$random_shortcode = $match[0];
		} else {
			$random_shortcode = false;
		}

		$variables = array( $random_shortcode, '[owner_username]' );

		$replacements = array( 
			$this->random_hash( $random_shortcode ), 
			$this->user_information( 'username' ), 
		);

		$new_name = str_replace( $variables, $replacements, $name );
		return apply_filters( 'crc_get_coupon_name', $new_name );
	}

	/**
	 * Create a coupon for the customer.
	 *
	 * @return int The ID of the new coupon
	 */
	private function create_coupon( $coupon_name ) {
		$coupon_meta = $this->master_coupon_meta();
		$coupon_meta['coupon_owner'] = $this->user_information( 'username' );

		$post_information = apply_filters( 'crc_default_new_coupon_information', array(
			'post_title' => $coupon_name,
			'post_content' => '',
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
			'meta_input' => $coupon_meta
		), $coupon_meta );

		$coupon_id = wp_insert_post( $post_information );

		do_action( 'crc_new_coupon_created', $coupon_id );

		return $coupon_id;
	}

	/**
	 * Check if a coupon needs to be created.
	 *
	 * @return int The ID of the coupon
	 */
	private function maybe_create_coupon() {
		$coupon_name = $this->get_coupon_name();
		$all_coupon_ids = $this->all_reward_coupons();
		$total_coupon_count = count( $all_coupon_ids );

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $total_coupon_count >= apply_filters( 'crc_max_coupons_per_customer', 1 ) ) {
			return false;
		}

		if ( ! function_exists( 'post_exists' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
		}

		if ( post_exists( $coupon_name ) ) {
			return post_exists( $coupon_name );
		} else {
			return $this->create_coupon( $coupon_name );
		}
	}

	/**
	 * Trigger coupon creation from the account page.
	 */
	public function watch_for_coupon_creation() {
		if ( isset( $_GET['reward-coupon'] ) && $_GET['reward-coupon'] == "created" ) {
			$this->maybe_create_coupon();
		}
	}

	/**
	 * Get all the coupons belonging to a customer.
	 *
	 * @return arr An array of all coupon ID's belonging to the current user
	 */
	public static function all_reward_coupons() {
		$coupons = array();
		$loop = new WP_Query( array( 'post_type' => 'shop_coupon', 'posts_per_page'=> -1 ) );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$coupon_owner = get_post_meta( get_the_ID(), 'coupon_owner', true );
				$current_user = wp_get_current_user();
				$username = $current_user->user_login;
				
				if ( $coupon_owner == $username ) {
					$coupons[] = get_the_ID();
				}
			}
		}
		wp_reset_postdata();

		return $coupons;
	}

	/**
	 * Check if an customer coupon was used in an order.
	 */
	public function customer_reward_coupon_used( $order_id ) {
		$order = wc_get_order( $order_id );
		$coupons = $order->get_used_coupons();

		foreach ( $coupons as $coupon ) {
			$coupon_object = get_page_by_title( $coupon, "OBJECT", 'shop_coupon' );

			if ( is_object ( $coupon_object ) ) {
				$coupon_id = $coupon_object->ID;
				$owner_username = get_post_meta( $coupon_id, 'coupon_owner', true );
				$owner = get_user_by ( 'login', $owner_username );

				if ( $owner ) {
					do_action( 'customer_reward_coupon_used', $order_id, $owner );
				}
			}
		}
	}

	/**
	 * Remove coupon if used by it's owner.
	 */
	public function maybe_remove_coupon( $valid, $coupon ) {
		$owner_id = get_post_meta( $coupon->id, 'coupon_owner', true );

		if ( $owner_id == $this->user_information( 'username' ) ) {
			$message = __( 'Sorry, you cannot use your own reward coupon.', 'customer-reward-coupons-for-woocommerce' );
			wc_add_notice( $message, 'error' );

			// Hide the default error message
			add_filter( 'woocommerce_coupon_error', 'hide_error_message', 2, 10 );
			function hide_error_message( $err, $err_code ) {
				if ( $err_code == 100 ) {
					$err = '';
					return $err;
				}
			}

			return false;
		}

		return $valid;
	}

}
