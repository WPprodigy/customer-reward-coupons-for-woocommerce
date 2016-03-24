<?php
/**
 * Customer Reward Coupons for WooCommerce - Admin Settings
 *
 * @class 	Customer_Reward_Coupons_Admin
 * @version 1.0
 * @author 	Caleb Burks
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Customer_Reward_Coupons_Admin {

	/**
	 * Constructor for the admin class.
	 */
	public function __construct() {
		// Coupon Settings
		add_action( 'woocommerce_coupon_options', array( $this, 'create_coupon_field' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_field' ) );

		// WooCommerce Settings
		add_action( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 60 );
		add_action( 'woocommerce_settings_tabs_reward_coupons', array( $this, 'load_settings' ), 10 );
		add_action( 'woocommerce_update_options_reward_coupons', array( $this, 'save_settings' ), 10 );
	}

	/**
	 * Create coupon field for storing the owners's account ID.
	 */
	public function create_coupon_field() {
		woocommerce_wp_text_input( array( 
			'id' => 'coupon_owner', 
			'label' => __( 'Coupon Owner', 'customer-reward-coupons-for-woocommerce' ), 
			'placeholder' => __( 'Customer Username', 'customer-reward-coupons-for-woocommerce' ), 
			'type' => 'text'
		) );
	}

	/**
	 * Save the coupon field.
	 */
	public function save_coupon_field( $post_id ) {
		$coupon_owner = wc_clean( $_POST['coupon_owner'] );
		update_post_meta( $post_id, 'coupon_owner', $coupon_owner );
	}

	/**
	 * Add settings tab to WooCommerce
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ 'reward_coupons' ] = __( 'Reward Coupons', 'customer-reward-coupons-for-woocommerce' );
		return $settings_tabs;
	}

	/**
	 * Return the settings.
	 */
	public function get_settings() {
		$settings = apply_filters( 'customer_reward_coupons_woocommerce_settings', array(
			array(
				'name' => __( 'Coupon Settings', 'customer-reward-coupons-for-woocommerce' ),
				'type' => 'title',
				'desc' => __( 'These settings will automatically apply to any new customer-created reward coupons.', 'customer-reward-coupons-for-woocommerce' ),
				'id'   => 'customer_reward_coupons'
			),
			array(
				'name'     => __( 'Template Coupon', 'customer-reward-coupons-for-woocommerce' ),
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'desc'     => __( 'Select a coupon that will serve as a "template" for any newly created coupons. All coupon settings will be duplicated.', 'customer-reward-coupons-for-woocommerce' ),
				'options'  => $this->get_coupon_list(),
				'id'       => 'woocommerce_crc_master_coupon',
				'desc_tip' => true
			),
			array(
				'name'    => __( 'Coupon Name', 'customer-reward-coupons-for-woocommerce' ),
				'type'    => 'text',
				'desc'    => __( 'Use at least one of these shortcodes: [owner_username] or [random length="10"]', 'customer-reward-coupons-for-woocommerce' ),
				'default' => 'reward_coupon-[random length="10"]',
				'css'     => 'width: 250px',
				'id'      => 'woocommerce_crc_coupon_name',
			),
			array( 'type' => 'sectionend', 'id' => 'customer_reward_coupons' ),

			array(
				'name' => __( 'Account Page', 'customer-reward-coupons-for-woocommerce' ),
				'type' => 'title',
				'desc' => __( 'Control what shows up on the customer\'s account page.', 'customer-reward-coupons-for-woocommerce' ),
				'id'   => 'customer_reward_coupons_account_page'
			),
			array(
				'name'            => __( 'Allow Coupon Creation', 'customer-reward-coupons-for-woocommerce' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'desc'            => __( 'Allow customers to create a reward coupon from the account page.', 'customer-reward-coupons-for-woocommerce' ),
				'id'              => 'woocommerce_crc_enable_coupon_creation'
			),
			array(
				'name'            => __( 'Show Coupon Table', 'customer-reward-coupons-for-woocommerce' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'desc'            => __( 'Show a list of the customer\'s reward coupons on the account page.', 'customer-reward-coupons-for-woocommerce' ),
				'id'              => 'woocommerce_crc_show_coupon_table'
			),
			array( 'type' => 'sectionend', 'id' => 'customer_reward_coupons_account_page' ),
		) );

		return apply_filters( 'customer_reward_coupons_get_settings', $settings );
	}

	/**
	 * Load settings fields when tab is active.
	 */
	public function load_settings() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Get a list of coupons so the customer can select a "master" coupon.
	 */
	public function get_coupon_list() {
		$coupons = array();

		$loop = new WP_Query( array( 'post_type' => 'shop_coupon', 'posts_per_page'=> -1 ) );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$coupon_id = get_the_ID();
				$owner_id = get_post_meta( $coupon_id, 'coupon_owner', true );
				$coupons[$coupon_id] = get_the_title() . ' (#' . $coupon_id . ')';
			}
		}
		wp_reset_postdata();

		if ( empty( $coupons ) ) {
			$coupons[0] = __( 'No Coupons Available', 'customer-reward-coupons-for-woocommerce' );
		}

		return $coupons;
	}

}
