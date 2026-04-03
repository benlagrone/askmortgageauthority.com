<?php
/**
 * This class extend the WC_Shipping class
 *
 * @package YITH\Shippo
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The shipping class
 */
class YITH_Shippo_WC_Shipping extends WC_Shipping_Method {

	/**
	 * The logger
	 *
	 * @var YITH_Shippo_Logger
	 */
	protected $logger;


	/**
	 * The construct
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {

		$this->id                 = 'yith_shippo';
		$this->method_title       = 'YITH Shippo Shippings';
		$this->title              = 'YITH Shippo Shippings';
		$this->method_description = $this->get_method_description();
		$this->supports           = array();
		$this->instance_id        = absint( $instance_id );
		if ( yith_shippo_support_shipping_zones() ) {
			$this->supports[] = 'shipping-zones';
		}
		$this->logger = YITH_Shippo_Logger::get_instance();
		parent::__construct( $instance_id );
	}

	/**
	 * Check if is possible calculate the shipping in live
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	protected function can_calculate_shipping() {
		$can_calculate = yith_plugin_fw_is_true( get_option( 'yith-shippo-show-live-shipping-rates-cart-checkout', 'no' ) );

		$show_rates      = get_option( 'yith-shippo-calculate-shipping-rates-in', 'cart_checkout' );
		$is_cart_request = yith_shippo_is_request_for_url( wc_get_cart_url() );
		$is_checkout_url = yith_shippo_is_request_for_url( wc_get_checkout_url() );
		$is_api_url      = yith_shippo_is_request_for_url( '/wp-json/wc/store/v1/batch' ) ||
		                   yith_shippo_is_request_for_url( '/wp-json/wc/store/v1/checkout' ) ||
		                   yith_shippo_is_request_for_url( '/wp-json/wc/store/v1/cart/select-shipping-rate' ) ;

		if ( $is_api_url ) {
			$page_cond = true;
		} elseif ( 'cart_checkout' === $show_rates ) {
			$page_cond = $is_cart_request || $is_checkout_url || $is_api_url;
		} else {
			$page_cond = $is_checkout_url || $is_api_url;
		}

		return $can_calculate && $page_cond;
	}

	/**
	 * Check if the shipping method is enabled
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function is_enabled() {
		$this->is_enabled = yith_shippo()->request->validate_token();

		return $this->enabled;
	}

	/**
	 * Get the method description
	 *
	 * @return string
	 * @since
	 */
	public function get_method_description() {

		$admin_url = admin_url( 'admin.php' );
		$args      = array(
			'page' => 'yith_shippo_shipping_for_woocommerce',
		);

		$args['tab']       = 'shipping';
		$args['sub_tab']   = 'shipping-sender-info';
		$service_info_url  = esc_url( add_query_arg( $args, $admin_url ) );
		$args['sub_tab']   = 'shipping-rules';
		$service_rules_url = esc_url( add_query_arg( $args, $admin_url ) );
		$args['tab']       = 'parcel';
		$args['sub_tab']   = 'parcel-parcel-boxes';
		$parcel_url        = esc_url( add_query_arg( $args, $admin_url ) );
		$html              = '<p>' . esc_html__( 'Enable the real-time shipping rates to print the labels and manage trackings!', 'yith-shippo-shippings-for-woocommerce' ) . '</p>';

		$service_info = sprintf( // translators: the placeholders are tag html.
			__(
				'%1$sAdd sender info for this zone%2$s',
				'yith-shippo-shippings-for-woocommerce'
			),
			"<a href='$service_info_url'>",
			'</a>'
		);

		$rule_info = sprintf( // translators: the placeholders are tag html.
			__(
				'%1$sAdd service rules for this zone%2$s',
				'yith-shippo-shippings-for-woocommerce'
			),
			"<a href='$service_rules_url'>",
			'</a>'
		);

		$parcel_info = sprintf( // translators: the placeholders are tag html.
			__(
				'%1$sAdd parcels for this zone%2$s',
				'yith-shippo-shippings-for-woocommerce'
			),
			"<a href='$parcel_url'>",
			'</a>'
		);

		$html .= '<ul style="margin-top:10px">';
		$html .= '<li>' . wp_kses_post( $service_info ) . '</li>';
		$html .= '<li>' . wp_kses_post( $rule_info ) . '</li>';
		$html .= '<li>' . wp_kses_post( $parcel_info ) . '</li>';
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Calculate the shipping
	 *
	 * @param array $package The package.
	 *
	 * @throws Exception The exeption.
	 * @since  1.0.0
	 */
	public function calculate_shipping( $package = array() ) {

		try {
			$this->logger->shipments( 'Calculate shipping' );
			$zone_id = false;

			if ( $this->can_calculate_shipping() && ! empty( $package['contents'] ) ) {
				if ( yith_shippo_support_shipping_zones() ) {
					$zone_id = yith_shippo_get_shipping_zone_id_by_package( $package );
				}

				// Get the parcel.
				$parcels = YITH_Shippo_Parcels::get_instance()->calculate_parcels_on_cart( $package['contents'], $zone_id );

				$this->logger->shipments( 'Parcels found ' . print_r( $parcels, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$new_rates = $this->build_rates( $parcels, $zone_id );
				$new_rates = $this->prepare_rates( $new_rates, $zone_id );

				$no_rates_message = __( 'YITH Shippo Shippings: No rates found, please check your products or your parcels', 'yith-shippo-shipping-for-woocommerce' );
				if ( current_user_can( 'manage_options' ) && 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' ) && 0 === count( $new_rates ) && ! wc_has_notice( $no_rates_message, 'notice' ) ) {
					wc_add_notice( $no_rates_message, 'notice' );
				}

				foreach ( $new_rates as $rate_id => $rate ) {
					$this->add_rate(
						array(
							'id'        => $this->get_rate_id( $rate['service'] ),
							'label'     => $rate['label'],
							'cost'      => $rate['cost'],
							'meta_data' => array(
								'shipping_service' => $rate['service'],
								'rates'            => $rate['meta_data']['rates'],
								'rate_id'          => $rate['id'],
								'parcels'          => $parcels,
								'created_via'      => 'checkout',
							),
						)
					);
				}
			}
		} catch ( Exception $e ) {

			$this->logger->shipments( 'Error on calculate shippings :' . $e->getMessage() );
		}
	}

	/**
	 * Build the rates by parcels
	 *
	 * @param array    $parcels The parcels.
	 * @param int|bool $zone_id The zone id, false no zone.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function build_rates( $parcels, $zone_id ) {
		$new_rates = array();
		$args      = array(
			'zone_id' => $zone_id,
		);

		foreach ( $parcels as $parcel_id => $parcel ) {
			$global_args   = $args + $parcel;
			$current_rates = yith_shippo()->request->shipment_manager->get_rates( $global_args );
			$this->logger->shipments( 'For parcel ' . $parcel_id . ' found these rates : ' . print_r( $current_rates, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			if ( empty( $current_rates ) ) {
				return array();
			} else {
				$new_rates = $this->add_rates( $new_rates, $current_rates, $parcel );
			}
		}
		$this->logger->shipments( 'The rates found for parcels are ' . print_r( $new_rates, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		return $new_rates;
	}

	/**
	 * Add in the stored rates the new calculated rates
	 *
	 * @param array $stored_rates The stored rates.
	 * @param array $current_rates The new rates.
	 * @param array $parcel The parcel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function add_rates( $stored_rates, $current_rates, $parcel ) {

		$is_empty = empty( $stored_rates );
		foreach ( $stored_rates as $service_id => $stored_rate ) {
			if ( empty( $current_rates[ $service_id ] ) ) {
				unset( $stored_rates[ $service_id ] );
			}
		}

		foreach ( $current_rates as $service_id => $current_rate ) {

			if ( ! isset( $stored_rates[ $service_id ] ) ) {
				if ( $is_empty ) {
					$rate                         = $current_rate;
					$rate['meta_data']['parcels'] = array();
					$rate['meta_data']['rates']   = array();
				} else {
					continue;
				}
			} else {
				$rate         = $stored_rates[ $service_id ]; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				$rate['cost'] += $current_rate['cost'];
			}

			$rate['meta_data']['rates'][ 'rate_' . count( $rate['meta_data']['rates'] ) ] = array(
				'rate'   => $current_rate,
				'parcel' => $parcel,
			);

			$rate['meta_data']['created_via']      = 'checkout';
			$rate['meta_data']['shipping_service'] = $service_id;
			$stored_rates[ $service_id ]           = $rate;
		}

		return $stored_rates;
	}

	/**
	 * Return the final rates
	 *
	 * @param array $rates The original rates.
	 * @param int   $zone_id The zone id.
	 *
	 * @return array
	 * @throws Exception The exception.
	 * @since  1.0.0
	 */
	public function prepare_rates( $rates, $zone_id ) {
		$global_rule = yith_shippo_get_shipping_rules(
			array(
				'enabled'          => 1,
				'shipping_service' => 'all',
				'shipping_zone'    => $zone_id,
			)
		);
		$global_rule = current( $global_rule );
		$new_rates   = $this->filter_visible_rates( $rates, $global_rule, $zone_id );
		$new_rates   = $this->change_rate_costs( $new_rates, $global_rule, $zone_id );
		$new_rates   = $this->change_label_rates( $new_rates, $zone_id );
		usort( $new_rates, 'yith_shippo_sort_rates' );
		$new_rates = $this->limit_rates( $new_rates );

		return $new_rates;
	}


	/**
	 * Limit the rates
	 *
	 * @param array $rates The rates.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function limit_rates( $rates ) {
		$can_limit = get_option( 'yith-shippo-enable-limit-rate', 'no' );

		if ( yith_plugin_fw_is_true( $can_limit ) ) {
			$this->logger->shipments( 'Limit rates option active' );
			$limit = get_option( 'yith-shippo-limit', 3 );
			if ( count( $rates ) > $limit ) {
				$this->logger->shipments( 'Found more than ' . $limit . ' rates limit it' );
				$rates = array_splice( $rates, 0, $limit );
			}
		}

		return $rates;
	}

	/**
	 * Return only visible rates
	 *
	 * @param array                     $rates The rates.
	 * @param YITH_Shippo_Shipping_Rule $global_rule The global rule.
	 * @param int                       $zone_id The zone id.
	 *
	 * @return array
	 * @throws Exception The exception.
	 * @since  1.0.0
	 */
	protected function filter_visible_rates( $rates, $global_rule, $zone_id ) {

		$rule_args     = $this->get_product_args();
		$visible_rates = $rates;

		foreach ( $rates as $service_id => $rate ) {

			$rule = yith_shippo_get_shipping_rules(
				array(
					'enabled'          => 1,
					'shipping_service' => $service_id,
					'shipping_zone'    => $zone_id,
				)
			);

			$rule = current( $rule );
			// If no rule found for specific service, check the global.
			if ( ! $rule ) {
				$this->logger->shipments( 'No local global rule for ' . $service_id . ' use the global ' );
				$rule = $global_rule;
			}

			if ( $rule instanceof YITH_Shippo_Shipping_Rule ) {
				$this->logger->shipments( 'Check if the rule' . $rule->get_id() . ' is valid' );
				$is_valid = $rule->is_valid( $rule_args );
				if ( 'show' === $rule->get_condition_type() ) {
					if ( ! $is_valid ) {
						$this->logger->shipments( 'Unset the service ' . $service_id . ' because the rule is not valid for show' );
						unset( $visible_rates[ $service_id ] );
					}
				} else {
					if ( $is_valid ) {
						$this->logger->shipments( 'Unset the service ' . $service_id . ' because the rule is valid to hide' );
						unset( $visible_rates[ $service_id ] );
					}
				}
			}

			// Check for the shipping cost.
			if ( isset( $visible_rates[ $service_id ] ) && ! $this->is_valid_by_cost( $rate ) ) {
				unset( $visible_rates[ $service_id ] );
			}
		}

		return $visible_rates;
	}

	/**
	 * Check if the rate is valid by cost rule
	 *
	 * @param array $rate The rate.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	protected function is_valid_by_cost( $rate ) {
		$is_valid = true;
		$opt      = get_option( 'yith-shippo-enable-limit-cost', 'no' );
		if ( yith_plugin_fw_is_true( $opt ) ) {
			$cost = $rate['cost'];
			$opt  = get_option( 'yith-shippo-min-max-cost', array() );
			$min  = isset( $opt['min'] ) ? $opt['min'] : '';
			$max  = isset( $opt['max'] ) ? $opt['max'] : '';

			if ( '' !== $min && $cost <= $min ) {
				$is_valid = false;
			}

			if ( $is_valid && ( '' !== $max && $cost >= $max ) ) {
				$is_valid = false;
			}
		}
		if ( ! $is_valid ) {
			$this->logger->shipments( 'The service ' . $rate['service'] . 'cost ' . $rate['cost'] . ' so is out of limit cost ( ' . $min . ' ' . $max . ' ) remove it.' );
		}

		return $is_valid;
	}

	/**
	 * Change the rate cost
	 *
	 * @param array                     $rates The rates.
	 * @param YITH_Shippo_Shipping_Rule $global_rule The global rule.
	 * @param int                       $zone_id The zone id.
	 *
	 * @return array
	 * @throws Exception The exception.
	 * @since  1.0.0
	 */
	protected function change_rate_costs( $rates, $global_rule, $zone_id ) {
		foreach ( $rates as $service_id => $rate ) {

			$rule = yith_shippo_get_shipping_rules(
				array(
					'enabled'          => 1,
					'shipping_service' => $service_id,
					'shipping_zone'    => $zone_id,
				)
			);
			$rule = current( $rule );
			// If no rule found for specific service, check the global.
			if ( ! $rule ) {
				$rule = $global_rule;
			}
			if ( $rule instanceof YITH_Shippo_Shipping_Rule ) {
				if ( $rule->is_fee_enabled() ) {
					$cost      = $rate['cost'];
					$fee_value = $rule->get_fee_value();
					if ( 'fixed' === $rule->get_fee_type() ) {
						$cost += $fee_value;
					} else {
						$cost = $cost + ( ( $cost * $fee_value ) / 100 );
					}
					$rates[ $service_id ]['cost'] = $cost;
				}
			}
		}

		return $rates;
	}

	/**
	 * Customize the label rates
	 *
	 * @param array $rates The rates.
	 * @param int   $zone_id The zone id.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function change_label_rates( $rates, $zone_id ) {
		$show_delivery_time = yith_plugin_fw_is_true( get_option( 'yith-shippo-show-delivery-time', 'no' ) );
		foreach ( $rates as $service_id => $rate ) {

			$rule = yith_shippo_get_shipping_rules(
				array(
					'enabled'          => 1,
					'shipping_service' => $service_id,
					'shipping_zone'    => $zone_id,
				)
			);
			$rule = current( $rule );

			if ( $rule instanceof YITH_Shippo_Shipping_Rule ) {

				if ( $rule->is_label_enabled() ) {
					$rates[ $service_id ]['label'] = $rule->get_custom_label();
				}
			}

			if ( $show_delivery_time && ! empty( $rates[ $service_id ]['delivery_time_description'] ) ) {
				$rates[ $service_id ]['label'] .= '( ' . $rates[ $service_id ]['delivery_time_description'] . ' )';
			}
		}

		return $rates;
	}

	/**
	 * Return an array with product ids and with product category id
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function get_product_args() {

		$products_args = array(
			'product_ids'        => array(),
			'product_categories' => array(),
		);

		if ( isset( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart_contents() as $item_id => $item ) {
				$product                             = $item['data'];
				$parent_id                           = 'variation' === $product->get_type() ? $product->get_parent_id() : $product->get_id();
				$product_id                          = $product->get_id();
				$product_cat                         = array_unique( wc_get_product_cat_ids( $parent_id ) );
				$products_args['product_ids'][]      = $product_id;
				$products_args['product_categories'] = array_merge( $products_args['product_categories'], $product_cat );
			}
		}

		return $products_args;
	}


	public function get_rates_for_package( $package ) {
		$this->rates = array();
		if ( $this->is_available( $package ) ) {
			$this->calculate_shipping( $package );
		}

		return $this->rates;
	}
}
