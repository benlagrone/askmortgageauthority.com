<?php
/**
 * The Shipping rule object
 *
 * @package YITH\Shippo
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class
 */
class YITH_Shippo_Shipping_Rule extends YITH_Shippo_Abstract_Object {
	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shippo_shipping_rules';

	/**
	 * The construct
	 *
	 * @param YITH_Shippo_Shipping_Rule|int $shipping_rule The shipping rule.
	 */
	public function __construct( $shipping_rule = 0 ) {
		$this->data       = array(
			'name'              => '',
			'enabled'           => 0,
			'shipping_service'  => '',
			'condition_enabled' => 0,
			'condition_type'    => 'show',
			'condition_mode'    => 'and',
			'fee_enabled'       => 0,
			'fee_type'          => 'fixed',
			'fee_value'         => 0.00,
			'label_enabled'     => 0,
			'custom_label'      => '',
		);
		$this->extra_data = array(
			'conditions'     => array(
				'product_ids'        => array(),
				'product_categories' => array(),
			),
			'shipping_zones' => array(),
		);

		$this->object_type = 'shipping_rule';
		parent::__construct( $shipping_rule );

		if ( is_numeric( $shipping_rule ) && $shipping_rule > 0 ) {
			$this->set_id( $shipping_rule );
		} elseif ( $shipping_rule instanceof self ) {
			$this->set_id( $shipping_rule->get_id() );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'shippo_shipping_rule' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}

	}

	/* == GETTER METHODS  */
	/**
	 * Get the rule name
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Return enabled property for current rule
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int Enabled property.
	 */
	public function get_enabled( $context = 'view' ) {
		return (int) $this->get_prop( 'enabled', $context );
	}

	/**
	 * Return true is rule is enabled
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool Whether rule is enabled.
	 */
	public function is_enabled( $context = 'view' ) {
		return ! ! $this->get_enabled( $context );
	}

	/**
	 * Return the shipping service
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.
	 */
	public function get_shipping_service( $context = 'view' ) {
		return $this->get_prop( 'shipping_service', $context );
	}

	/**
	 * Return condition enabled property for current rule
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int Enabled property.
	 */
	public function get_condition_enabled( $context = 'view' ) {
		return (int) $this->get_prop( 'condition_enabled', $context );
	}

	/**
	 * Return the condition mode ( and|or ) for current rule
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_condition_mode( $context = 'view' ) {
		return $this->get_prop( 'condition_mode', $context );
	}

	/**
	 * Check if the condition is in AND
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_and_condition( $context = 'view' ) {
		$condition = strtolower( $this->get_condition_mode( $context ) );

		return 'and' === $condition;
	}

	/**
	 * Check if the condition is in OR
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_or_condition( $context = 'view' ) {
		$condition = strtolower( $this->get_condition_mode( $context ) );

		return 'or' === $condition;
	}

	/**
	 * Return true if the condition is enabled
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool Whether condition is enabled.
	 */
	public function is_condition_enabled( $context = 'view' ) {
		return ! ! $this->get_condition_enabled( $context );
	}

	/**
	 * Return the condition type property
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_condition_type( $context = 'view' ) {
		return $this->get_prop( 'condition_type', $context );
	}

	/**
	 * Return fee enabled property for current rule
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int Enabled property.
	 */
	public function get_fee_enabled( $context = 'view' ) {
		return (int) $this->get_prop( 'fee_enabled', $context );
	}

	/**
	 * Return true if the fee is enabled
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool Whether fee is enabled.
	 */
	public function is_fee_enabled( $context = 'view' ) {
		return ! ! $this->get_fee_enabled( $context );
	}

	/**
	 * Get the fee type property
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_fee_type( $context = 'view' ) {
		return $this->get_prop( 'fee_type', $context );
	}

	/**
	 * Get the fee value property
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return float
	 * @since 1.0.0
	 */
	public function get_fee_value( $context = 'view' ) {
		return (float) $this->get_prop( 'fee_value', $context );
	}

	/**
	 * Get the label enabled property
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public function get_label_enabled( $context = 'view' ) {
		return (int) $this->get_prop( 'label_enabled', $context );
	}

	/**
	 * Return true if the custom label is enabled
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return bool Whether fee is enabled.
	 */
	public function is_label_enabled( $context = 'view' ) {
		return ! ! $this->get_label_enabled( $context );
	}

	/**
	 * Get the custom label property
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_custom_label( $context = 'view' ) {
		return $this->get_prop( 'custom_label', $context );
	}

	/**
	 * Return all conditions
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_conditions( $context = 'view' ) {
		return $this->get_prop( 'conditions', $context );
	}

	/**
	 * Get the shipping zone
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_shipping_zones( $context = 'view' ) {
		return $this->get_prop( 'shipping_zones', $context );
	}

	/**
	 * Return the product ids
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_condition_product_ids() {

		$conditions = $this->get_conditions();

		return isset( $conditions['product_ids'] ) ? $conditions['product_ids'] : array();
	}

	/**
	 * Return the product category ids
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_condition_product_categories() {

		$conditions = $this->get_conditions();

		return isset( $conditions['product_categories'] ) ? $conditions['product_categories'] : array();
	}


	/**
	 * Return the conditions array
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_conditions_array() {
		$conditions_array = array();
		$conditions       = $this->get_conditions();

		foreach ( $conditions as $condition_type => $condition_value ) {

			if ( ! empty( $condition_value ) ) {
				$condition_option  = array();
				$condition_subtype = $condition_type;

				if ( 'product_id' === $condition_subtype ) {
					$condition_subtype                      = 'product_ids';
					$condition_option[ $condition_subtype ] = $condition_value;
				} else {
					$condition_subtype                      = 'product_categories';
					$condition_option[ $condition_subtype ] = $condition_value;
				}

				$condition_option['subtype_type'] = $condition_subtype;
				$conditions_array[]               = $condition_option;
			}
		}
		if ( empty( $conditions_array ) ) {
			$option             = array(
				'subtype_type' => 'product_ids',
				'product_ids'  => array(),
			);
			$conditions_array[] = $option;
		}

		return $conditions_array;

	}

	/* === SETTERS === */

	/**
	 * Set rule's name
	 *
	 * @param string $name Name for the rule.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set rule's enabled property
	 *
	 * @param int $enabled Enabled property for the rule.
	 */
	public function set_enabled( $enabled ) {
		$this->set_prop( 'enabled', (int) $enabled );
	}

	/**
	 * Enable rule.
	 */
	public function enable() {
		$this->set_enabled( 1 );
	}

	/**
	 * Disable rule.
	 */
	public function disable() {
		$this->set_enabled( 0 );
	}

	/**
	 * Set the shipping service
	 *
	 * @param string $shipping_service The shipping service.
	 *
	 * @since 1.0.
	 */
	public function set_shipping_service( $shipping_service ) {
		$this->set_prop( 'shipping_service', $shipping_service );
	}

	/**
	 * Set condition enabled property for current rule
	 *
	 * @param int $condition_enabled Is enabled or not.
	 */
	public function set_condition_enabled( $condition_enabled ) {
		$this->set_prop( 'condition_enabled', $condition_enabled );
	}

	/**
	 * Set the condition mode ( and|or ) for current rule
	 *
	 * @param string $condition_mode Set condition mode.
	 *
	 * @since 1.0.0
	 */
	public function set_condition_mode( $condition_mode ) {
		$this->set_prop( 'condition_mode', $condition_mode );
	}


	/**
	 * Enable condition rule.
	 */
	public function enable_condition() {
		$this->set_condition_enabled( 1 );
	}

	/**
	 * Disable condition rule.
	 */
	public function disable_condition() {
		$this->set_condition_enabled( 0 );
	}

	/**
	 * Set the condition type property
	 *
	 * @param string $condition_type The condition type ( show|hide ).
	 *
	 * @since 1.0.0
	 */
	public function set_condition_type( $condition_type ) {
		$this->set_prop( 'condition_type', $condition_type );
	}

	/**
	 * Set fee enabled property for current rule
	 *
	 * @param string $fee_enabled If the fee is enabled or not.
	 *
	 * @since 1.0.0
	 */
	public function set_fee_enabled( $fee_enabled ) {
		$this->set_prop( 'fee_enabled', $fee_enabled );
	}

	/**
	 * Enable the fee feature.
	 *
	 * @since 1.0.0
	 */
	public function enable_fee() {
		$this->set_fee_enabled( 1 );
	}

	/**
	 * Disable the fee feature.
	 *
	 * @since 1.0.0
	 */
	public function disable_fee() {
		$this->set_fee_enabled( 0 );
	}

	/**
	 * Set the fee type property
	 *
	 * @param string $fee_type The fee type.
	 *
	 * @since 1.0.0
	 */
	public function set_fee_type( $fee_type ) {
		$this->set_prop( 'fee_type', $fee_type );
	}

	/**
	 * Set the fee value property
	 *
	 * @param float $fee_value The fee value.
	 *
	 * @since 1.0.0
	 */
	public function set_fee_value( $fee_value ) {
		$this->set_prop( 'fee_value', $fee_value );
	}

	/**
	 * Set the label enabled property
	 *
	 * @param int $label_enabled If custom label is enabled or not.
	 *
	 * @since 1.0.0
	 */
	public function set_label_enabled( $label_enabled ) {
		$this->set_prop( 'label_enabled', $label_enabled );
	}

	/**
	 * Enable the custom label feature.
	 *
	 * @since 1.0.0
	 */
	public function enable_custom_label() {
		$this->set_label_enabled( 1 );
	}

	/**
	 * Disable the custom label feature.
	 *
	 * @since 1.0.0
	 */
	public function disable_custom_label() {
		$this->set_label_enabled( 0 );
	}

	/**
	 * Set the custom label property
	 *
	 * @param string $custom_label The custom label.
	 *
	 * @since 1.0.0
	 */
	public function set_custom_label( $custom_label ) {
		$this->set_prop( 'custom_label', $custom_label );
	}

	/**
	 * Set the conditions
	 *
	 * @param array $conditions The conditions.
	 *
	 * @since 1.0.0
	 */
	public function set_conditions( $conditions ) {
		$this->set_prop( 'conditions', $conditions );
	}

	/**
	 * Set the shipping zone
	 *
	 * @param array $zones The shipping zones.
	 *
	 * @since 1.0.0
	 */
	public function set_shipping_zones( $zones ) {
		$this->set_prop( 'shipping_zones', $zones );
	}

	/**
	 * Check if this rule is valid
	 *
	 * @param array $args The args to check.
	 *
	 * @since 1.0.0
	 */
	public function is_valid( $args ) {
		$is_valid   = true;
		$conditions = $this->get_conditions_array();
		if ( $this->is_condition_enabled() ) {
			foreach ( $conditions as $condition ) {

				$type = $condition['subtype_type'];

				if ( 'shipping_cost' === $type ) {
					$mode  = $condition['shipping_cost_mode'];
					$value = $condition['shipping_cost_value'];

					if ( 'higher_than' === $mode ) {
						$is_valid = $args['shipping_cost'] >= $value;
					} else {
						$is_valid = $args['shipping_cost'] <= $value;
					}
				} else {
					$intersect = array_intersect( $condition[ $type ], $args[ $type ] );
					$is_valid  = count( $intersect ) > 0;
				}

				if ( ! $is_valid && $this->is_and_condition() ) {
					break;
				} elseif ( $is_valid && $this->is_or_condition() ) {
					break;
				}
			}
		}

		return $is_valid;
	}
}
