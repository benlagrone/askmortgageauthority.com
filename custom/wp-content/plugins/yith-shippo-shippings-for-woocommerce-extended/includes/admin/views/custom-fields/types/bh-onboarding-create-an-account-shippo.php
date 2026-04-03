<ul class="yith-bh-onboarding-connect-list">
    <li>
        <h3><?php esc_html_e( 'Streamlines shipping process', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>

        <p>
			<?php
			esc_html_e(
				'Shippo makes shipping easier and faster for businesses such as online retailers and fulfillment
        companies. That is because it is scalable, simple, and it allows them to instantly connect with the most
        widely used carrier service providers. Companies are also able to customize the shipping processes to
        suit their specifications and needs. With these, Shippo is able to streamline shipping processes, provide
        high satisfaction to customers, and make delivery of orders faster.',
				'yith-shippo-shippings-for-woocommerce'
			);
			?>
        </p>
    </li>
    <li>
        <h3><?php esc_html_e( 'Supports multiple carrier services', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>

        <p>
			<?php
			esc_html_e(
				'The application supports major carrier services in the market such as FedEx, DHL, USPS, and UPS. Also,
users aren\'t required to integrate with them one at a time. After just one integration, users are freely
able lo connect with their preferred carriers, send their items anywhere, and optimize their shipments.',
				'yith-shippo-shippings-for-woocommerce'
			);
			?>
        </p>
    </li>
    <li>
        <h3><?php esc_html_e( 'Minimize costs and risks', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>

        <p>
			<?php
			esc_html_e(
				'Since Shippo will be taking care or every shipping process for users, they will no longer need to worry
about products being sent to the wrong address. The system does a validation check to see if the
shipment is made to an existing and real place. This way, items are delivered to clients more easily and
companies are able to save from expenses that would have been incurred if a product was sent to a wrong address.',
				'yith-shippo-shippings-for-woocommerce'
			);
			?>
        </p>
    </li>
    <li>
        <h3><?php echo esc_html__( 'Tracks shipments', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>
        <p>
			<?php
			esc_html_e(
				'The platform also provides a tracking feature for customers that want to know their shipment\'s status.
				Clients could be notified via email about the current location of their item and when it is probably arriving.',
				'yith-shippo-shippings-for-woocommerce'
			);
			?>
        </p>
    </li>
</ul>
<?php
if ( 'live' === get_option( 'yith_shippo_environment', 'live' ) ) {
	$token = get_option( 'yith_shippo_live_token', '' );
} else {
	$token = get_option( 'yith_shippo_sandbox_token', '' );
}

?>
<div class="yith-bh-onboarding-connect-cta" <?php echo ! empty( $token ) ? "style= 'display:none;'" : '' ?>>
    <p><?php echo wp_kses_post( __( 'Start shipping with the best<br>delivery options in the business', 'yith-shippo-shippings-for-woocommerce' ) ); ?></p>
    <a href="https://goshippo.com/?utm_source=bluehost&utm_medium=partner-referral&utm_campaign=newfold-digital-wordpress-integration" class="yith-shippo-connect-account"><?php esc_html_e( 'Sign Up Free', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
</div>


