<?php
/**
 * Shipping rule data store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Shipping_Rule_Data_Store' ) ) {
	/**
	 * This class implements CRUD methods for Rate rules
	 *
	 * @since 1.0.0
	 */
	class YITH_Shippo_Shipping_Rule_Data_Store implements YITH_Shippo_Object_Data_Store_Interface {

		use YITH_Shippo_Trait_DB_Object, YITH_Shippo_Trait_Cacheable;

		/**
		 * Expected meta structure.
		 *
		 * @var array
		 */
		protected $meta = array(
			'product_id',
			'product_cat',
			'shipping_zone',
		);

		/**
		 * Maps object properties to database columns
		 * Every prop not included in this list, match the column name
		 *
		 * @var array
		 */
		protected $props_to_meta = array(
			'product_ids'        => 'product_id',
			'product_categories' => 'product_cat',
			'shipping_zones'     => 'shipping_zone',
		);

		/**
		 * Constructor method
		 */
		public function __construct() {
			global $wpdb;

			$this->table = $wpdb->yith_shippo_shipping_rules;

			$this->cache_group = 'shippo_shipping_rules';

			$this->columns = array(
				'name'              => '%s',
				'enabled'           => '%d',
				'shipping_service'  => '%s',
				'condition_enabled' => '%d',
				'condition_type'    => '%s',
				'condition_mode'    => '%s',
				'fee_enabled'       => '%d',
				'fee_type'          => '%s',
				'fee_value'         => '%s',
				'label_enabled'     => '%d',
				'custom_label'      => '%s',
			);

			$this->orderby = array(
				'ID',
				'name',
			);
		}

		/* === CRUD === */

		/**
		 * Method to create a new record of a WC_Data based object.
		 *
		 * @param YITH_Shippo_Shipping_Rule $rule Data object.
		 *
		 * @throws Exception When rule cannot be created with current information.
		 */
		public function create( &$rule ) {
			global $wpdb;

			if ( ! $rule->get_name() ) {
				throw new Exception( _x( 'Unable to register rule. Missing required params.', '[DEV] Debug message triggered when unable to create shipping rule record.', 'yith-shippo-shippings-for-woocommerce' ) );
			}

			$rule_exists = $this->query(
				array(
					'fields'           => 'count',
					'shipping_service' => $rule->get_shipping_service(),
					'shipping_zone'    => $rule->get_shipping_zones(),
				)
			);

			if ( ( is_array( $rule_exists ) && count( $rule_exists ) > 0 ) || ( ! is_array( $rule_exists ) && $rule_exists > 0 ) ) {
				throw  new Exception( _x( 'Unable to register rule. A rule for this shipping service already exists.', '[DEV] Debug message triggered if a rule already exists.', 'yith-shippo-shippings-for-woocommerce' ) );
			}
			$res = $this->save_object( $rule );

			if ( $res ) {
				/**
				 * APPLY_FILTERS: yith_shippo_shipping_rule_correctly_created
				 *
				 * This filter allow to change the rule id created.
				 *
				 * @param int $rule_id the rule id.
				 *
				 * @return int
				 */
				$id = apply_filters( 'yith_shippo_shipping_rule_correctly_created', intval( $wpdb->insert_id ) );

				$rule->set_id( $id );

				// create metadata.
				$changes = $rule->get_changes();

				foreach ( $this->meta as $meta ) {
					$meta_prop = $this->get_meta_prop_name( $meta );

					if ( 'shipping_zones' === $meta_prop && ! empty( $changes[ $meta_prop ] ) ) {
						$zones = $changes[ $meta_prop ];
						foreach ( $zones as $zone ) {
							add_metadata( 'shippo_shipping_rule', $rule->get_id(), $meta, $zone );
						}
					} else {
						if ( 'shipping_zones' !== $meta_prop && ! empty( $changes['conditions'][ $meta_prop ] ) ) {

							foreach ( $changes['conditions'][ $meta_prop ] as $meta_value ) {
								add_metadata( 'shippo_shipping_rule', $rule->get_id(), $meta, $meta_value );
							}
						}
					}
				}

				$rule->apply_changes();

				$this->clear_cache( $rule );
				$this->invalidate_versioned_cache();
				do_action( 'yith_shippo_new_shipping_rule', $rule->get_id(), $rule );
			}
		}

		/**
		 * Method to read a record. Creates a new WC_Data based object.
		 *
		 * @param YITH_Shippo_Shipping_Rule $rule Data object.
		 *
		 * @throws Exception When rule cannot be retrieved with current information.
		 */
		public function read( &$rule ) {
			global $wpdb;

			$rule->set_defaults();

			$id = $rule->get_id();

			if ( ! $id ) {
				throw new Exception( _x( 'Invalid shipping rule.', '[DEV] Debug message triggered when unable to find shipping rule record.', 'yith-shippo-shippings-for-woocommerce' ) );
			}

			$rule_data = $id ? $this->cache_get( 'shippo_shipping_rule-' . $id ) : false;

			if ( ! $rule_data ) {
				// format query to retrieve rule.
				$query = $wpdb->prepare( "SELECT * FROM {$wpdb->yith_shippo_shipping_rules} WHERE ID = %d", $id );

				// retrieve rule data.
				$rule_data = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

				if ( $rule_data ) {
					// now also read useful meta, to store them in cache as well.
					$rule_data->metadata = get_metadata( 'shippo_shipping_rule', $rule_data->ID );

					$this->cache_set( 'shippo_shipping_rule-' . $rule_data->ID, $rule_data );
				}
			}

			if ( ! $rule_data ) {
				throw new Exception( _x( 'Invalid shipping rule.', '[DEV] Debug message triggered when unable to find click record.', 'yith-shippo-shippings-for-woocommerce' ) );
			}

			$rule->set_id( (int) $rule_data->ID );

			// set rule props.
			foreach ( array_keys( $this->columns ) as $column ) {
				$rule->{"set_{$this->get_column_prop_name( $column )}"}( $rule_data->$column );
			}

			// set rule meta.
			$conditions_metadata = array(
				'product_ids'        => array(),
				'product_categories' => array(),
			);
			$stored_metadata     = ! empty( $rule_data->metadata ) ? $rule_data->metadata : array();
			$zones_metadata      = array();

			if ( $stored_metadata ) {
				foreach ( $this->meta as $meta ) {
					if ( empty( $stored_metadata[ $meta ] ) ) {
						continue;
					}
					if ( 'shipping_zone' === $meta ) {
						$zones_metadata = $stored_metadata[ $meta ];
					} else {
						$conditions_metadata[ $meta ] = $stored_metadata[ $meta ];
					}
				}
			}
			$rule->set_conditions( $conditions_metadata );
			$rule->set_shipping_zones( $zones_metadata );
			$rule->set_object_read( true );
		}

		/**
		 * Updates a record in the database.
		 *
		 * @param YITH_Shippo_Shipping_Rule $rule Data object.
		 */
		public function update( &$rule ) {
			if ( ! $rule->get_id() ) {
				return;
			}

			$this->update_object( $rule );

			// update metadata.
			$changes = $rule->get_changes();
			if ( isset( $changes['conditions'] ) ) {
				foreach ( $this->meta as $meta ) {
					if ( 'shipping_zone' !== $meta ) {
						delete_metadata( 'shippo_shipping_rule', $rule->get_id(), $meta );
					}
				}
			}
			if ( isset( $changes['shipping_zones'] ) ) {
				delete_metadata( 'shippo_shipping_rule', $rule->get_id(), 'shipping_zone' );
			}
			foreach ( $this->meta as $meta ) {
				$meta_prop = $this->get_meta_prop_name( $meta );
				if ( 'shipping_zones' === $meta_prop && ! empty( $changes[ $meta_prop ] ) ) {
					$zones = $changes[ $meta_prop ];
					foreach ( $zones as $zone ) {
						add_metadata( 'shippo_shipping_rule', $rule->get_id(), $meta, $zone );
					}
				} else {
					if ( 'shipping_zones' !== $meta_prop && ! empty( $changes['conditions'][ $meta_prop ] ) ) {

						foreach ( $changes['conditions'][ $meta_prop ] as $meta_value ) {
							add_metadata( 'shippo_shipping_rule', $rule->get_id(), $meta, $meta_value );
						}
					}
				}
			}

			$rule->apply_changes();

			$this->clear_cache( $rule );

			/**
			 * DO_ACTION: yith_shippo_update_shipping_rule
			 *
			 * Action triggered after update a rule.
			 *
			 * @param int                       $id the rule id.
			 * @param YITH_Shippo_Shipping_Rule $rule The rule.
			 */
			do_action( 'yith_shippo_update_shipping_rule', $rule->get_id(), $rule );
		}

		/**
		 * Deletes a record from the database.
		 *
		 * @param YITH_Shippo_Shipping_Rule $rule Data object.
		 * @param array                     $args Not in use.
		 *
		 * @return bool result
		 */
		public function delete( &$rule, $args = array() ) {
			global $wpdb;

			$id = $rule->get_id();

			if ( ! $id ) {
				return false;
			}
			/**
			 * DO_ACTION: yith_shippo_before_delete_shipping_rule
			 *
			 * Action triggered before delete a rule.
			 *
			 * @param int                       $id the rule id.
			 * @param YITH_Shippo_Shipping_Rule $rule The rule.
			 */
			do_action( 'yith_shippo_before_delete_shipping_rule', $id, $rule );

			$this->clear_cache( $rule );

			// delete affiliate.
			$res = $wpdb->delete( $wpdb->yith_shippo_shipping_rules, array( $this->id_column => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( $res ) {
				/**
				 * DO_ACTION: yith_shippo_delete_shipping_rule
				 *
				 * Action triggered just before delete a rule.
				 *
				 * @param int                       $id the rule id.
				 * @param YITH_Shippo_Shipping_Rule $rule The rule.
				 */
				do_action( 'yith_shippo_delete_shipping_rule', $id, $rule );

				// delete meta.
				$wpdb->delete( $wpdb->yith_shippo_shipping_rulemeta, array( 'shippo_shipping_rule_id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$rule->set_id( 0 );
				/**
				 * DO_ACTION: yith_shippo_deleted_shipping_rule
				 *
				 * Action triggered before delete a rule.
				 *
				 * @param int                       $id the rule id.
				 * @param YITH_Shippo_Shipping_Rule $rule The rule.
				 */
				do_action( 'yith_shippo_deleted_shipping_rule', $id, $rule );
			}

			return $res;
		}

		/* === QUERY === */

		/**
		 * Return count of rules matching filtering criteria
		 *
		 * @param array $args Filtering criteria (@see \YITH_Shippo_Shipping_Rule::query).
		 *
		 * @return int Count of matching rules.
		 */
		public function count( $args = array() ) {
			$args['fields'] = 'count';

			return (int) $this->query( $args );
		}

		/**
		 * Return rules matching filtering criteria
		 *
		 * @param array $args Filtering criteria<br/>:
		 *              [<br/>
		 *              'enabled'          => 'all', // the status ( int|bool )
		 *              'shipping_service' => 'all', // the service ( string )
		 *              'condition_enabled'    => 'all', // if there is condition  (int|bool )
		 *              'condition_type'   => 'show', // condition type ( string )
		 *              'condition_mode'   => 'and', // condition mode ( string )
		 *              'fee_enabled'          => 'all', // if there is fee (int|bool)
		 *              'fee_type'         => 'fixed', // fee type ( string )
		 *              'fee_value'        => '', // fee value ( float )
		 *              'order' => 'DESC',       // sorting direction (ASC/DESC)<br/>
		 *              'orderby' => 'ID',       // sorting column (any table valid column)<br/>
		 *              'limit' => 0,            // limit (int)<br/>
		 *              'offset' => 0            // offset (int)<br/>
		 *              ].
		 *
		 * @return int|array|YITH_Shippo_Shipping_Rule[]
		 */
		public function query( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'enabled'           => 'all',
				'shipping_service'  => '',
				'condition_enabled' => 'all',
				'condition_type'    => 'show',
				'condition_mode'    => 'and',
				'fee_enabled'       => 'all',
				'fee_type'          => 'fixed',
				'fee_value'         => '',
				'label_enabled'     => 'all',
				'custom_label'      => '',
				'product_id'        => false,
				'product_cat'       => false,
				'shipping_zone'     => false,
				'order'             => 'ASC',
				'orderby'           => 'priority',
				'limit'             => 0,
				'offset'            => 0,
				'fields'            => '',
			);

			$args = wp_parse_args( $args, $defaults );

			// checks if we're performing a count query.
			$is_counting = ! empty( $args['fields'] ) && 'count' === $args['fields'];

			// retrieve data from cache, when possible.
			$res = $this->cache_get( $this->get_versioned_cache_key( 'query', $args ) );

			// if no data found in cache, query database.
			if ( false === $res ) {
				$query      = "SELECT yssr.*
					FROM {$wpdb->yith_shippo_shipping_rules} AS yssr
					WHERE 1 = 1";
				$query_args = array();

				if ( $is_counting ) {
					$query = "SELECT COUNT(ID)
						FROM {$wpdb->yith_shippo_shipping_rules} AS yssr
						WHERE 1 = 1";
				}

				if ( isset( $args['enabled'] ) && 'all' !== $args['enabled'] ) {

					$query .= ' AND yssr.enabled = %d';

					$query_args[] = (int) ! ! $args['enabled'];
				}

				if ( isset( $args['has_condition'] ) && 'all' !== $args['has_condition'] ) {

					$query .= ' AND yssr.has_condition = %d';

					$query_args[] = (int) ! ! $args['has_condition'];
				}

				if ( isset( $args['has_fee'] ) && 'all' !== $args['has_fee'] ) {

					$query .= ' AND yssr.has_fee = %d';

					$query_args[] = (int) ! ! $args['has_fee'];
				}
				if ( isset( $args['label_enabled'] ) && 'all' !== $args['label_enabled'] ) {

					$query .= ' AND yssr.label_enabled = %d';

					$query_args[] = (int) ! ! $args['label_enabled'];
				}

				// if at least one meta is specified in the args, run sub-queries, to intersect results for each meta.
				$metaquery_args = array_intersect( $this->meta, array_keys( array_filter( $args, array( $this, 'remove_empty_args' ) ) ) );

				foreach ( $metaquery_args as $meta_key ) {
					$matching_ids = $this->get_rule_ids_by_meta_query( $meta_key, $args[ $meta_key ] );
					if ( empty( $matching_ids ) ) {
						return array();
					}
					$query_part = trim( str_repeat( '%d, ', count( $matching_ids ) ), ', ' );

					$query .= ' AND yssr.ID IN ( ' . $query_part . ' )';

					$query_args = array_merge( $query_args, $matching_ids );
				}
				if ( isset( $args['shipping_service'] ) && '' !== $args['shipping_service'] ) {

					$query .= ' AND yssr.shipping_service = %s';

					$query_args[] = $args['shipping_service'];
				}

				if ( ! empty( $args['orderby'] ) && ! $is_counting ) {
					$query .= $this->generate_query_orderby_clause( $args['orderby'], $args['order'] );
				}

				if ( ! empty( $args['limit'] ) && 0 < (int) $args['limit'] && ! $is_counting ) {
					$query .= sprintf( ' LIMIT %d, %d', ! empty( $args['offset'] ) ? $args['offset'] : 0, $args['limit'] );
				}

				if ( ! empty( $query_args ) ) {
					$query = $wpdb->prepare( $query, $query_args ); // phpcs:ignore WordPress.DB
				}

				if ( $is_counting ) {
					$res = (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB
				} else {
					$res = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB
				}

				$this->cache_set( $this->get_versioned_cache_key( 'query', $args ), $res );
			}

			// if we're counting, return count found.
			if ( $is_counting ) {
				return $res;
			}

			// if we have an empty set from db, return empty arrayand skip next steps.
			if ( ! $res ) {
				return array();
			}

			$ids = array_map( 'intval', wp_list_pluck( $res, 'ID' ) );

			if ( ! empty( $args['fields'] ) ) {
				// extract required field.
				$indexed = 0 === strpos( $args['fields'], 'id=>' );
				$field   = $indexed ? substr( $args['fields'], 4 ) : $args['fields'];
				$field   = 'ids' === $field ? 'ID' : $field;

				$res = wp_list_pluck( $res, $field );

				if ( $indexed ) {
					$res = array_combine( $ids, $res );
				}
			} else {
				// or get the complete object.
				$res = array_filter( array_map( 'yith_shippo_get_shipping_rule', $ids ) );
			}

			return $res;
		}

		/* === UTILITIES === */


		/**
		 * Returns id of rate rules matching a specific meta query
		 *
		 * @param string $meta_key Meta key.
		 * @param string $meta_value Meta value; if passed value is empty, method will return array of rules that don't have specified meta ky.
		 *
		 * @return int[] Array of rules id.
		 */
		protected function get_rule_ids_by_meta_query( $meta_key, $meta_value ) {
			global $wpdb;

			if ( ! in_array( $meta_key, $this->meta, true ) ) {
				return array();
			}

			if ( '' !== $meta_value ) {
				$meta_value = (array) $meta_value;
				$where_list = trim( str_repeat( '%s, ', count( $meta_value ) ), ', ' );
				$query      = "SELECT shippo_shipping_rule_id FROM {$wpdb->yith_shippo_shipping_rulemeta} WHERE meta_key = %s AND meta_value IN ( $where_list )";
				$query_args = array_merge( (array) $meta_key, $meta_value );
			} else {
				$query      = "SELECT shippo_shipping_rule_id FROM {$wpdb->yith_shippo_shipping_rulemeta} WHERE shippo_shipping_rule_id NOT IN ( SELECT shippo_shipping_rule_id FROM {$wpdb->yith_shippo_shipping_rulemeta} WHERE meta_key = %s )";
				$query_args = array( $meta_key );
			}

			$rule_ids_per_meta = $wpdb->get_col( $wpdb->prepare( $query, $query_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

			return $rule_ids_per_meta;
		}

		/**
		 * Get property name for a meta
		 *
		 * @param string $meta Meta to search for.
		 *
		 * @return string Property name.
		 */
		protected function get_meta_prop_name( $meta ) {
			$meta_to_props = array_flip( $this->props_to_meta );

			if ( ! isset( $meta_to_props[ $meta ] ) ) {
				return $meta;
			}

			return $meta_to_props[ $meta ];
		}

		/**
		 * Clear rule related caches
		 *
		 * @param \YITH_Shippo_Shipping_Rule|int $rule Rule object or rule id.
		 *
		 * @return void
		 */
		public function clear_cache( $rule ) {
			$rule = yith_shippo_get_shipping_rule( $rule );

			if ( ! $rule || ! $rule->get_id() ) {
				return;
			}

			$this->cache_delete( 'shippo_shipping_rule-' . $rule->get_id() );
			$this->invalidate_versioned_cache();
		}

		/**
		 * Remove the unset value
		 *
		 * @param mixed $value The value.
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public function remove_empty_args( $value ) {
			return false !== $value;
		}
	}
}
