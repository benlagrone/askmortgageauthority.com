<?php
/**
 * Google Pay class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;
/**
 * Class YITH_PayPal_Google_Pay
 */
class YITH_PayPal_Google_Pay {

	/**
	 * Gateway environment
	 *
	 * @var string
	 */
	protected $environment = '';

	/**
	 * Enabled
	 *
	 * @var bool
	 */
	protected $enabled = false;

	/**
	 * Paypal Gateway Enabled
	 *
	 * @var bool
	 */
	protected $paypal_enabled = true;

	/**
	 * Is valid for country currency combination
	 *
	 * @var bool
	 */
	protected $is_valid = false;

	/**
	 * Payment Title
	 *
	 * @var string
	 */
	protected $payment_title = '';

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * SDK url
	 *
	 * @var array
	 */
	protected static $sdk_url = 'https://pay.google.com/gp/p/js/pay.js';

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var class YITH_PayPal_Google_Pay
	 */
	protected static $instance;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Get settings.
		$this->payment_title  = __( 'Pay with Google Pay', 'yith-paypal-payments-for-woocommerce' );
		$this->settings       = get_option( 'yith_ppwc_gp_gateway_options', array( 'enabled' => false ) );
		$this->is_valid       = yith_ppwc_check_currency_country_validity( $this->get_supported_country_currency(), wc_get_base_location()['country'], get_woocommerce_currency() );
		$this->enabled        = $this->is_enabled();
		$this->paypal_enabled = yith_ppwc_is_paypal_gateway_enabled();

		if ( $this->is_valid ) {
			add_filter( 'yith_ppwc_payment_methods_settings', array( $this, 'add_gpay_payment_method_settings' ), 15 );
		}

		if ( $this->enabled && $this->paypal_enabled ) {

			/* hook google pay button to the paypal buttons container */
			add_action( 'yith_ppwc_after_buttons', array( $this, 'add_button' ) );

			/* load scripts */
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_style' ), 30 );
			add_filter( 'yith_ppwc_sdk_parameters', array( $this, 'add_google_pay_to_sdk' ) );

			add_filter( 'yith_ppwc_customization_settings', array( $this, 'add_gpay_style_settings' ), 15 );

			add_filter( 'yith_ppwc_method_title_icons', array( $this, 'add_icon_to_payment_method_title' ) );
		}
	}

	/**
	 * Add google pay icon to paypal payment method title
	 *
	 * @param array $icons list of icons.
	 * @return array
	 */
	public function add_icon_to_payment_method_title( $icons ) {
		array_push( $icons, 'googlepay' );
		return $icons;
	}
	/**
	 * Get google pay capability status
	 *
	 * @return boolean
	 */
	public function can_google_pay() {
		$can = false;
		$m   = YITH_PayPal_Merchant::get_merchant();
		$m->check_status();
		$caps = $m->get( 'capabilities' );
		if ( $caps ) {
			foreach ( $caps as $i => $values ) {
				if ( isset( $values['name'] ) && 'GOOGLE_PAY' === $values['name'] ) {
					$can = 'ACTIVE' === $caps[ $i ]['status'] ? true : false;
					break;
				}
			}
		}

		return $can;
	}

	/**
	 * Add Google Pay button payment method to related tab settings
	 *
	 * @param array $settings array of options.
	 * @return array $settings list of settings.
	 */
	public function add_gpay_payment_method_settings( $settings ) {
		$merchant = YITH_PayPal_Merchant::get_merchant();
		/* Google Pay Options */
		$gpay_activate_url       = $this->get_google_pay_activation_url();
		$can_google_pay          = $this->can_google_pay() && $merchant->are_payments_receivable();
		$gpay_option_description = _x( 'Enable to allow your customers to use Google Pay to pay their orders', 'Admin: option description', 'yith-paypal-payments-for-woocommerce' );
		if ( ! $can_google_pay ) {
			$gpay_option_description  = wp_kses_post( sprintf( _x( 'Google Pay is not active in your Paypal account.', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ), $gpay_activate_url ) );
			$gpay_option_description .= ' <a href="' . $gpay_activate_url . '" target="_blank">' . esc_html_x( 'Please Activate it.', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ) . '</a>';

			if ( 'not-active' === $merchant->is_active() || ! $merchant->are_payments_receivable() ) {
				$gpay_option_description = esc_html__( 'Please connect before you can enable this option.', 'yith-paypal-payments-for-woocommerce' );
			}

			if ( ! $merchant->are_payments_receivable() ) {
				// translators: placoholder is a link.
				$gpay_option_description = sprintf( __( 'Payment no receivable. Please check <a href="%s">Onboarding Status</a>.', 'yith-paypal-payments-for-woocommerce' ), esc_url( admin_url( 'admin.php?page=yith_paypal_payments' ) ) );
			}
		}

		if ( ! $this->paypal_enabled ) {
			$gpay_option_description = esc_html__( 'It seems Paypal gateway is disabled so you cannot enable Google Pay', 'yith-paypal-payments-for-woocommerce' );
		}

		if ( ! $can_google_pay || ! $this->paypal_enabled ) {
			/* force the option to be no */
			$gp_options            = get_option( 'yith_ppwc_gp_gateway_options', array() );
			$gp_options['enabled'] = 'no';
			update_option( 'yith_ppwc_gp_gateway_options', $gp_options );
		}

		$google_pay = array(
			array(
				'title' => esc_html_x( 'Google Pay Options', 'Admin option section title', 'yith-paypal-payments-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'yith_google_pay_options',
			),

			array(
				'id'        => 'yith_ppwc_gp_gateway_options[enabled]',
				'title'     => esc_html_x( 'Enable Google Pay payment', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
				'desc'      => $gpay_option_description,
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'default'   => 'no',
				'class'     => ! $can_google_pay || ! $this->paypal_enabled ? 'disabled' : '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'yith_google_pay_options_end',
			),
		);

		return array_merge( $settings, $google_pay );
	}

	/**
	 * Add Google Pay button style settings to related tab settings
	 *
	 * @param array $settings array of options.
	 * @return array $settings list of settings.
	 */
	public function add_gpay_style_settings( $settings ) {
		/* Google Pay Options */
		$can_google_pay = $this->can_google_pay();
		$gp_options     = get_option( 'yith_ppwc_gp_gateway_options', array() );

		if ( ! $can_google_pay || 'no' === $gp_options['enabled'] ) {
			return $settings;
		}

		$google_pay = array(
			array(
				'title' => esc_html_x( 'GooglePay Button', 'Admin option section title', 'yith-paypal-payments-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'yith_google_pay_options',
			),

			array(
				'id'        => 'yith_ppwc_gp_gateway_options[buttonType]',
				'title'     => esc_html_x( 'Button Type', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
				'desc'      => esc_html_x( 'Select the type of the button based on Google types', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'select',
				'options'   => array(
					'book'      => esc_html_x( 'Book', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'buy'       => esc_html_x( 'Buy', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'checkout'  => esc_html_x( 'Checkout', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'donate'    => esc_html_x( 'Donate', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'order'     => esc_html_x( 'Order', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'pay'       => esc_html_x( 'Pay', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'plain'     => esc_html_x( 'Plain', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'subscribe' => esc_html_x( 'Subscribe', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
				),
				'default'   => 'pay',
			),

			array(
				'id'        => 'yith_ppwc_gp_gateway_options[buttonColor]',
				'title'     => esc_html_x( 'Button Color', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
				// translators:placeholders are html tags.
				'desc'      => sprintf( esc_html_x( 'Choose the GooglePay button color. The recommended color is %1$sblack%2$s.', 'Admin option, the placeholder are tags', 'yith-paypal-payments-for-woocommerce' ), '<strong>', '</strong>' ),
				'type'      => 'yith-field',
				'yith-type' => 'select-images',
				'options'   => array(
					'black-rect' => array(
						'label' => esc_html_x( 'Black', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
						'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/gpay_black.png',
					),
					'white-rect' => array(
						'label' => esc_html_x( 'White', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
						'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/gpay_white.png',
					),
				),
				'default'   => 'black-rect',
			),

			array(
				'id'        => 'yith_ppwc_gp_gateway_options[buttonLocale]',
				'title'     => esc_html_x( 'Button Language', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
				'desc'      => esc_html_x( 'Set the button language. Set the button language. Browser means that broswer country and language is set.', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'select',
				'options'   => array(
					'browser' => esc_html_x( 'Browser Language', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ar'      => esc_html_x( 'Arabic', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'bg'      => esc_html_x( 'Bulgarian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ca'      => esc_html_x( 'Catalan', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'zh'      => esc_html_x( 'Chinese', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'hr'      => esc_html_x( 'Croatian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'cs'      => esc_html_x( 'Czech', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'da'      => esc_html_x( 'Danish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'nl'      => esc_html_x( 'Dutch', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'en'      => esc_html_x( 'English', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'et'      => esc_html_x( 'Estonian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'fi'      => esc_html_x( 'Finnish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'fr'      => esc_html_x( 'French', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'de'      => esc_html_x( 'German', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'el'      => esc_html_x( 'Greek', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'id'      => esc_html_x( 'Indonesian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'it'      => esc_html_x( 'Italian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ja'      => esc_html_x( 'Japanese', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ko'      => esc_html_x( 'Korean', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ms'      => esc_html_x( 'Malay', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'no'      => esc_html_x( 'Norwegian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'pl'      => esc_html_x( 'Polish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'pt'      => esc_html_x( 'Portuguese', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'ru'      => esc_html_x( 'Russian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'sr'      => esc_html_x( 'Serbian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'sk'      => esc_html_x( 'Slovak', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'sl'      => esc_html_x( 'Slovenian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'es'      => esc_html_x( 'Spanish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'sv'      => esc_html_x( 'Swedish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'th'      => esc_html_x( 'Thai', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'tr'      => esc_html_x( 'Turkish', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
					'uk'      => esc_html_x( 'Ukrainian', 'Admin option value label', 'yith-paypal-payments-for-woocommerce' ),
				),
				'default'   => 'browser',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'yith_google_pay_options_end',
			),
		);

		$settings['customization'] = array_merge( $settings['customization'], $google_pay );

		return $settings;
	}

	/**
	 * Get google pay activation url
	 *
	 * @return string
	 */
	public function get_google_pay_activation_url() {
		if ( 'sandbox' === $this->environment ) {
			$url = 'https://www.sandbox.paypal.com/bizsignup/add-product?product=payment_methods&capabilities=GOOGLE_PAY';
		} else {
			$url = 'https://www.paypal.com/bizsignup/add-product?product=payment_methods&capabilities=GOOGLE_PAY';
		}

		return $url;
	}

	/**
	 * Returns single instance of the class
	 *
	 * @return YITH_PayPal_Google_Pay
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns google sdk url
	 *
	 * @return string
	 */
	public static function get_sdk_url() {
		return self::$sdk_url;
	}

	/**
	 * Returns if we are in google pay payment process sdk
	 *
	 * @return string
	 */
	public static function is_googleplay_flow() {
		$payed_with_gpay = WC()->session->get( 'yith_ppwc_funding_source' );
		if ( self::get_funding_source() === $payed_with_gpay ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns localized args for google pay js
	 *
	 * @return array $data array of args.
	 */
	public function get_script_localize_args() {
		$settings    = get_option( 'yith_ppwc_gp_gateway_options' );
		$environment = YITH_PayPal::get_instance()->get_gateway()->get_environment();
		$context     = '';
		$order_id    = '';
		if ( is_product() ) {
			$context = 'product';
		} elseif ( is_cart() ) {
			$context = 'cart';
		} elseif ( is_checkout() ) {
			$context = 'checkout';
		}

		$data = array(
			'environment'    => $environment,
			'context'        => $context,
			'buttonColor'    => isset( $settings['buttonColor'] ) ? str_replace( '-rect', '', $settings['buttonColor'] ) : 'black',
			'buttonType'     => isset( $settings['buttonType'] ) ? $settings['buttonType'] : 'pay',
			'buttonSizeMode' => apply_filters( 'yith_ppwc_googlepay_button_size_mode', 'fill' ),
			'buttonLocale'   => isset( $settings['buttonLocale'] ) ? $settings['buttonLocale'] : 'browser',
			'orderId'        => is_wc_endpoint_url( 'order-pay' ) ? $order_id : '',
			'fundingSource'  => $this::get_funding_source(),
			'countries'      => yith_ppwc_get_wc_countries(),
		);

		return $data;
	}

	/**
	 * Return if google pay is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = $this->settings['enabled'];

		$is_https = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'];
		return $is_https && 'yes' === $enabled && $this->is_valid;
	}

	/**
	 * Enqueue the google pay script
	 *
	 * @return void
	 */
	public function enqueue_scripts_style() {
		if ( is_checkout() && has_block( 'woocommerce/checkout-totals-block' ) || ( is_cart() && has_block( 'woocommerce/cart-totals-block' ) ) ) {
			/* blocks js are loaded by blocks integration class */
			wp_dequeue_script( 'yith-ppwc-googlepay-sdk' );
			wp_dequeue_script( 'yith-ppwc-googlepay' );
		}
	}

	/**
	 * Add google pay button
	 *
	 * @return void
	 */
	public function add_button() {
		if ( $this->enabled ) {
			$funding_source = WC()->session->get( 'yith_ppwc_funding_source' );
			if ( is_cart() && $funding_source === $this->get_funding_source() ) {
				return;
			}
			if ( ( is_checkout() && has_block( 'woocommerce/checkout-totals-block' ) ) || is_wc_endpoint_url( 'order-pay' ) ) {
				// avoid to add container on checkout block or if it is order pay page.
				return;
			}

			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				return;
			}
			$display = '';
			if ( is_product() ) {
				global $post;
				if ( ! empty( $post ) ) {
					$product = wc_get_product( $post->ID );
				}
				/* if product has no default variations set we hide the button - on reset or variations change it will be managed by js */
				if ( ! empty( $product ) && $product->is_type( 'variable' ) && empty( $product->get_default_attributes() ) ) {
					$display = 'display:none;';
				}
			}
			/**
			 * APPLY_FILTERS: yith_ppwc_googlepay_button_custom_height
			 *
			 * Change the button height. Value is in px.
			 *
			 * @param int $value default is 50.
			 */
			$custom_height = 'height:' . apply_filters( 'yith_ppwc_googlepay_button_custom_height', '50' ) . 'px;';

			$w = isset( get_option( 'yith_ppwc_button_size' )['dimensions']['width'] ) ? get_option( 'yith_ppwc_button_size' )['dimensions']['width'] : '100';
			$u = isset( get_option( 'yith_ppwc_button_size' )['unit'] ) ? get_option( 'yith_ppwc_button_size' )['unit'] : 'percentage';
			$u = 'percentage' === $u ? '%' : 'px';
			/**
			 * APPLY_FILTERS: yith_ppwc_googlepay_button_custom_width
			 *
			 * Change the button width. Value can be % or px.
			 *
			 * @param int $value default is the buttons width in settings.
			 */
			$custom_width = 'width:' . apply_filters( 'yith_ppwc_googlepay_button_custom_width', $w . $u ) . ';';
			echo '<div id="googlepay-container" style="' . esc_html( $display ) . esc_html( $custom_width ) . esc_html( $custom_height ) . '"></div>';
		}
	}

	/**
	 * Add the Google Pay to sdk
	 *
	 * @param array $args List of args for the add_query_arg.
	 *
	 * @return array
	 */
	public function add_google_pay_to_sdk( $args ) {
		$args['components'] = $args['components'] . ',googlepay';
		return $args;
	}

	/**
	 * Get Google Pay funding source slug
	 *
	 * @return string
	 */
	public static function get_funding_source() {
		return 'yith_ppwc_google_pay';
	}

	/**
	 * Get Google Pay funding source label (default: Google Pay)
	 *
	 * @return string
	 */
	public static function get_funding_source_label() {
		/**
		* APPLY_FILTERS: yith_ppwc_google_play_funding_label
		*
		* Set the google pay funding title label.
		*
		* @param string $label (Google Pay is default);
		*/
		return esc_html( apply_filters( 'yith_ppwc_google_play_funding_label', __( 'Google Pay (via PayPal)', 'yith-paypal-payments-for-woocommerce' ) ) );
	}

	/**
	 * Provide the matrix of supported country and currency matrix
	 *
	 * @return array $list .
	 */
	public function get_supported_country_currency() {
		return apply_filters(
			'yith_ppwc_googlepay_supported_country_currency',
			array(
				'AU' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'AT' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'BE' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'BG' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'CA' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'CY' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'CZ' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'DK' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'EE' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'FI' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'FR' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'DE' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'GR' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'HU' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'IE' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'IT' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'LV' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'LI' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'LT' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'LU' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'MT' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'NO' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'NL' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'PL' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'PT' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'RO' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'SK' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'SI' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'ES' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'SE' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'GB' => array(
					'AUD',
					'BRL',
					'CAD',
					'CHF',
					'CZK',
					'DKK',
					'EUR',
					'GBP',
					'HKD',
					'HUF',
					'ILS',
					'JPY',
					'MXN',
					'NOK',
					'NZD',
					'PHP',
					'PLN',
					'SEK',
					'SGD',
					'THB',
					'TWD',
					'USD',
				),
				'US' => array(
					'AUD',
					'CAD',
					'EUR',
					'GBP',
					'JPY',
					'USD',
				),
			)
		);
	}
}
