<?php
/**
 * Class to manage the emails.
 *
 * @class   YITH
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Shipping_Email' ) ) {

	/**
	 * Class YITH_Shippo_Shipping_Email
	 */
	class YITH_Shippo_Shipping_Email {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_Shippo_Shipping_Email
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Shippo_Shipping_Email
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 3.0.0
		 */
		private function __construct() {
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			add_action( 'woocommerce_init', array( $this, 'load_wc_mailer' ) );
			add_filter( 'woocommerce_email_styles', array( $this, 'add_email_styles' ), 10, 2 );
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param   array  $emails  .
		 *
		 * @return array
		 * @since 1.0
		 */
		public function add_woocommerce_emails( $emails ) {
			$emails['YITH_Shippo_Shipment_Details'] = include YITH_SHIPPO_INC . 'emails/class-yith-shippo-shipment-details.php';
			$emails['YITH_Shippo_Shipment_Admin']   = include YITH_SHIPPO_INC . 'emails/class-yith-shippo-shipment-admin.php';

			return $emails;
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @return void
		 * @since 1.0
		 */
		public function load_wc_mailer() {
			add_action( 'yith_shippo_tracking_status_updated', array( 'WC_Emails', 'send_transactional_email' ), 10, 5 );
			add_action( 'yith_shippo_tracking_status_action_request', array( 'WC_Emails', 'send_transactional_email' ), 10, 5 );
		}

		/**
		 * Add CSS to WC emails
		 *
		 * @param   string    $css    The email CSS.
		 * @param   WC_Email  $email  The current email object.
		 *
		 * @return string
		 */
		public static function add_email_styles( $css, $email = null ) {
			if ( $email && $email instanceof WC_Email && in_array( $email->id, array( 'yith_shippo_shipment_details', 'yith_shippo_shipment_admin' ), true ) ) {
				ob_start();
				?>
                .yith-shippo-shipping-status-container {
                background-color: #f0f0f1;
                padding: 20px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle.active {
                background-color: #548bc0;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle.failure.active {
                background-color: #c05454;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle {
                width: 35px;
                border-radius: 50%;
                height: 35px;
                background: #e2e2e2;
                padding: 3px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle img {
                width: 27px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle img.waiting {
                margin-top: 6px;
                margin-left: 5px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle img.warning {
                margin-top: 3px;
                margin-left: 4.5px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle img.transit {
                margin-top: 9px;
                margin-left: 4px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step .inner-circle img.delivered {
                margin-top: 5px;
                margin-left: 3px;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step.current {
                border-radius: 50%;
                background: #dbe7f4;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step.current.failure {
                background: #e6b4a5;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step.current.delivered .inner-circle {
                width: 35px;
                border-radius: 50%;
                height: 35px;
                padding: 3px;
                background: #b7b700 !important;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step.current.delivered {
                border-radius: 50%;
                background: #d3e048 !important;
                }
                .yith-shippo-shipping-status-box .progressbar.icons div .status-step {
                position: relative;
                border-radius: 50%;
                }
                .yith-shippo-shipping-status-box .progressbar .status-step.current {
                position: relative;
                padding: 12px;
                margin-left: -12px;
                }
                table.progressbar.icons {
                margin: auto;
                }
                .yith-shippo-shipping-status-box .progressbar .status-listed.done .status-vl {
                margin-left: 18px;
                border-left: 2px solid #548bc0;
                height: 60px;
                margin-top: 10px;
                }
                .yith-shippo-shipping-status-box .progressbar .status-listed.done .status-vl.exist-next {
                margin-bottom: 10px;
                }
                .yith-shippo-shipping-status-box .progressbar .status-listed .status-vl {
                margin-left: 18px;
                border-left: 2px dashed #ccc;
                height: 60px;
                margin-top: 10px;
                }
                .yith-shippo-shipping-status-box .progressbar td.current {
                padding: 0px !important;
                padding-left: 12px !important;
                }
                .yith-shippo-shipping-status-box .progressbar td.status-label {
                vertical-align: top;
                font-weight: 400;
                }
                .yith-shippo-shipping-status-box span.status-date {
                color: #9f9f9f;
                font-size: 12px;
                }
                .yith-shippo-shipping-info{
                text-align: center;
                }
                .yith-shippo-shipping-info-container div.yith-shippo-shipping-info {
                margin: auto;
                width: 60%;
                padding: 20px;
                }
                .yith-shippo-shipping-info p.tracking-code {
                padding-bottom: 15px;
                }
                .yith-shippo-shipping-info-container a.btn.shippo-button {
                background-color: #548bc0;
                color: #FFFFFF !important;
                text-decoration: none !important;
                padding: 15px;
                font-weight: bold !important;
                }
                .shipment-items-title{
                color: #548bc0;
                font-weight: 500;
                }
                .yith-shippo-shipping-info span{
                font-weight: 500;
                }

				<?php
				$newcss = ob_get_clean();

				/**
				 * APPLY_FILTERS: yith_shippo_additional_email_css
				 *
				 * This filter allow to add additional CSS to the emails.
				 *
				 * @param   string  $newcss  The CSS code.
				 *
				 * @return string
				 */
				$css .= apply_filters( 'yith_shippo_additional_email_css', $newcss );
			}


			return $css;
		}

	}
}
