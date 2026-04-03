<?php
/**
 * Implements the YITH_Shippo_Shipment_Admin class.
 *
 * @class   YITH_Shippo_Shipment_Admin
 * @package YITH/Shippo/Emails
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_Shippo_Shipment_Admin' ) ) {

	/**
	 * YITH_Shippo_Shipment_Admin
	 *
	 * @since 1.0.0
	 */
	class YITH_Shippo_Shipment_Admin extends WC_Email {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id          = 'yith_shippo_shipment_admin';
			$this->title       = _x( '[YITH Shippo Shippings] Shipment tracking for admin', 'Admin: Email title on WooCommerce > Emails', 'yith-shippo-shippings-for-woocommerce' );
			$this->description = _x( 'This email is sent when the shipment tracking goes wrong.', 'Admin: Email description on WooCommerce > Emails', 'yith-shippo-shippings-for-woocommerce' );

			$this->heading = _x( 'Alert: there is an issue with your delivery', 'Header of email that alerts the administrator', 'yith-shippo-shippings-for-woocommerce' );
			$this->subject = _x( '[Shipment details]', 'Subject of email that alerts the administrator', 'yith-shippo-shippings-for-woocommerce' );

			$this->template_base  = YITH_SHIPPO_TEMPLATE_PATH . '/';
			$this->template_html  = 'emails/shipment-tracking.php';
			$this->template_plain = 'emails/plain/shipment-tracking.php';
			$this->customer_email = false;

			// Call parent constructor.
			parent::__construct();

			if ( 'no' === $this->enabled ) {
				return;
			}

			// Triggers for this email.
			add_action( 'yith_shippo_tracking_status_action_request_notification', array( $this, 'trigger' ), 15, 5 );

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		}

		/**
		 * Method triggered to send email
		 *
		 * @param string               $old_status Old Status.
		 * @param string               $current_status Current status.
		 * @param array                $tracking_updated New Tracking.
		 * @param string               $key Rote key.
		 * @param YITH_Shippo_Tracking $tracking Tracking object.
		 *
		 * @return void
		 * @since  1.0
		 */
		public function trigger( $old_status, $current_status, $tracking_updated, $key, $tracking ) {

			$order_id       = $tracking->get_order_id();
			$order          = wc_get_order( $order_id );
			$order_shipping = yith_shippo_get_order_shipping( $order );

			$args = array( 'order' => $order );

			if ( 'no' === $this->enabled ) {
				return;
			}

			// args.
			$this->order                           = $args['order'];
			$this->placeholders['{order_id}']      = '#' . $order_id;
			$this->placeholders['{order_date}']    = $order->get_date_created() ? $order->get_date_created()->date( 'F d, Y' ) : '';
			$this->placeholders['{customer_name}'] = $order->get_billing_first_name();
			$this->placeholders['{carrier_name}']  = yith_shippo_camel_case( $tracking_updated['carrier'] );
			$this->object                          = $args['order'];

			$this->tracking['old_status']       = $old_status;
			$this->tracking['current_status']   = $current_status;
			$this->tracking['tracking_updated'] = $tracking_updated;
			$this->tracking['key']              = $key;
			$this->tracking['tracking']         = $tracking;
			$this->tracking['order_shipping']   = $order_shipping;

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		}

		/**
		 * Get Headers function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_headers() {

			$headers = array();

			if ( get_option( 'woocommerce_email_from_address' ) != '' ) { //phpcs:ignore
				$headers[] = 'Reply-To: ' . $this->get_from_address();
			}

			$headers[] = 'Content-Type: ' . $this->get_content_type();
			$headers   = implode( "\r\n", $headers );

			return apply_filters( 'woocommerce_email_headers', $headers, $this->id, $this->object, $this );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 * @since  1.0
		 */
		public function get_content_html() {
			ob_start();

			wc_get_template(
				$this->template_html,
				array(
					'order'             => $this->order,
					'email_heading'     => $this->format_string( $this->get_heading() ),
					'email_title'       => $this->format_string( $this->get_option( 'email-title' ) ),
					'email_description' => $this->format_string( $this->get_option( 'email-description' ) ),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
					'tracking'          => $this->tracking,
				),
				false,
				$this->template_base
			);

			return ob_get_clean();
		}

		/**
		 * Get Plain content for the mail
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			ob_start();
			wc_get_template(
				$this->template_plain,
				array(
					'order'             => $this->order,
					'email_heading'     => $this->format_string( $this->get_heading() ),
					'email_title'       => $this->format_string( $this->get_option( 'email-title' ) ),
					'email_description' => $this->format_string( $this->get_option( 'email-description' ) ),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
					'tracking'          => $this->tracking,
				),
				false,
				$this->template_base
			);

			return ob_get_clean();
		}

		/**
		 * Get attachment
		 *
		 * @return array|mixed|void
		 */
		public function get_attachments() {
			$attachments = array();
			if ( ! empty( $file ) && file_exists( $file['file'] ) ) {
				$attachments[] = $file['file'];
			}

			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object, $this );
		}

		/**
		 * Get from name for email.
		 *
		 * @param string $from_name From name.
		 *
		 * @return string
		 */
		public function get_from_name( $from_name = '' ) {
			$email_from_name = ( isset( $this->settings['email_from_name'] ) && '' !== $this->settings['email_from_name'] ) ? $this->settings['email_from_name'] : $from_name;

			return wp_specialchars_decode( esc_html( $email_from_name ), ENT_QUOTES );
		}

		/**
		 * Get from email address.
		 *
		 * @param string $from_email From email.
		 *
		 * @return string
		 */
		public function get_from_address( $from_email = '' ) {
			$email_from_email = ( isset( $this->settings['email_from_email'] ) && '' !== $this->settings['email_from_email'] ) ? $this->settings['email_from_email'] : $from_email;

			return sanitize_email( $email_from_email );
		}

		/**
		 * Init form fields to display in WC admin pages
		 *
		 * @return void
		 * @since  1.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'           => array(
					'title'   => __( 'Enable/Disable', 'yith-shippo-shippings-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'yith-shippo-shippings-for-woocommerce' ),
					'default' => 'yes',
				),
				'email_from_name'   => array(
					'title'       => __( '"From" Name', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_name' ),
				),
				'email_from_email'  => array(
					'title'       => __( '"From" Email Address', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					'description' => '',
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_email_from_address' ),
				),
				'subject'           => array(
					'title'       => __( 'Subject', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					'description' => sprintf( // translators: placeholder is default subject.
						__( 'This field lets you modify the email subject line. Leave it blank to use the default subject text: <code>%s</code>. You can use {order_id} as a placeholder that will show the order ID in the email.', 'yith-shippo-shippings-for-woocommerce' ),
						$this->subject
					),
					'placeholder' => '',
					'default'     => '',
				),
				'recipient'         => array(
					'title'       => __( 'Recipient(s)', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Enter further recipients (separated by commas) for this email. By default, it\'s sent to the admin.', 'yith-shippo-shippings-for-woocommerce' ),
					'placeholder' => '',
					'default'     => '',
				),
				'heading'           => array(
					'title'       => __( 'Email heading', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					// translators:placeholder is default heading.
					'description' => sprintf( __( 'This field lets you change the main heading in email notifications. Leave it blank to use the default heading type: <code>%s</code>.', 'yith-shippo-shippings-for-woocommerce' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
				),
				'email-title'       => array(
					'title'       => __( 'Email title', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'text',
					'placeholder' => '',
					'default'     => __( 'Your package is on its way!', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'email-description' => array(
					'title'       => __( 'Email description', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'textarea',
					'placeholder' => '',
					'default'     => __(
						'Hi,
Unfortunately, there is an issue with the delivery of the order {order_id}. We suggest contacting the {carrier_name} carrier to get more information.',
						'yith-shippo-shippings-for-woocommerce'
					),
					'description' => __( 'Available placeholders:', 'yith-shippo-shippings-for-woocommerce' ) . ' {order_id}, {order_date}, {customer_name}, {carrier_name}.',
				),
				'email_type'        => array(
					'title'       => __( 'Email type', 'yith-shippo-shippings-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose email format.', 'yith-shippo-shippings-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}
	}
}


// returns instance of the mail on file include.
return new YITH_Shippo_Shipment_Admin();
