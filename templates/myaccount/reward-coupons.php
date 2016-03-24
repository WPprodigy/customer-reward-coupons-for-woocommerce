<?php
/**
 * Customer Reward Coupons Template
 *
 * This template can be overridden by copying it to 'yourtheme/woocommerce/myaccount/reward-coupons.php.php'
 *
 * HOWEVER, on occasion this template may need to be updated, and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. Hopefully this will not happen often, but it is possible. 
 * When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$coupon_table_columns = apply_filters( 'crc_account_coupon_table_columns', array(
	'coupon_name'       => __( 'Coupon Name', 'customer-reward-coupons-for-woocommerce' ),
	'formatted_amount' => __( 'Amount', 'customer-reward-coupons-for-woocommerce' ),
	'expiry_date'      => __( 'Expiration', 'customer-reward-coupons-for-woocommerce' ),
	'free_shipping'    => __( 'Free Shipping', 'customer-reward-coupons-for-woocommerce' ),
) );

// Display a table for the coupons connected to the customers account.
if ( ! empty( $coupon_table_coupons ) && get_option( 'woocommerce_crc_show_coupon_table' ) == 'yes' ) : ?>

	<h2 class="reward_coupons_header"><?php esc_html_e( 'Your Reward Coupons', 'customer-reward-coupons-for-woocommerce' ) ?></h2>

	<?php do_action( 'crc_before_account_coupon_table' ) ?>

	<table class="shop_table shop_table_responsive customer_reward_coupons_table">
		<thead>
			<tr>
				<?php foreach ( $coupon_table_columns as $column_id => $column_name ) : ?>
					<th class="<?php echo esc_attr( $column_id ); ?>"><?php echo esc_html( $column_name ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<tbody>
			<?php foreach ( $coupon_table_coupons as $coupon_id ) : 
				$coupon = Customer_Reward_Coupons_My_Account::get_coupon_information( $coupon_id );
				?>
				<tr class="coupon">
					<?php foreach ( $coupon_table_columns as $column_id => $column_name ) : ?>
						<td class="<?php echo esc_attr( $column_id ); ?>">
							<?php if ( 'coupon_name' === $column_id ) : ?>
								<input type="text" name="coupon_name" value="<?php echo esc_html( $coupon[ 'coupon_name' ]); ?>" readonly>
							<?php elseif ( 'formatted_amount' === $column_id ) : ?>
								<span><?php echo esc_html( $coupon[$column_id]); ?></span>
							<?php elseif ( 'expiry_date' === $column_id ) : ?>
								<span><?php echo esc_html( $coupon[$column_id]); ?></span>
							<?php elseif ( 'free_shipping' === $column_id ) : ?>
								<span><?php echo esc_html( $coupon[$column_id]); ?></span>
							<?php endif; ?>
							<?php do_action( 'crc_account_coupon_table_data', $column_id, $coupon ) ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<!-- Show message when no coupons are found. -->
<?php elseif ( empty( $coupon_table_coupons ) ) : ?>
	<h2 class="reward_coupons_header"><?php esc_html_e( 'Your Reward Coupons', 'customer-reward-coupons-for-woocommerce' ) ?></h2>
	<p><?php esc_html_e( 'There are currently no reward coupons associated with this account.', 'customer-reward-coupons-for-woocommerce' ) ?></p>
<?php endif; ?>

<!-- Display a button to create new coupons. -->
<?php if ( count( $coupon_table_coupons ) < apply_filters( 'crc_max_coupons_per_customer', 1 ) && get_option( 'woocommerce_crc_enable_coupon_creation' ) == 'yes' ) : ?>
	<?php do_action( 'crc_before_account_coupon_creation' ) ?>
	
	<form>
		<input type="submit" value="<?php esc_html_e( 'Create Reward Coupon', 'customer-reward-coupons-for-woocommerce' ) ?>">
		<input type="hidden" name="reward-coupon" value="created">
	</form>
<?php endif; ?>
