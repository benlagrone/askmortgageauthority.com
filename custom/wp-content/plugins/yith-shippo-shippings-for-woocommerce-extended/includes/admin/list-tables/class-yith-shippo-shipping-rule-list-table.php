<?php
/**
 * This class manage the shipping rule table
 *
 * @package YITH\Shippo\ListTables
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class that manage the list
 */
class YITH_Shippo_Shipping_Rule_List_Table extends WP_List_Table {

	/**
	 * Store the currier services
	 *
	 * @var array
	 */
	protected $currier_service_opt;
	/**
	 * The shipping zone array
	 *
	 * @var array
	 */
	protected $shipping_zones;
	/**
	 * Boolean that check if the plugin support zones
	 *
	 * @var bool
	 */
	protected $support_zone;

	/**
	 * The construct
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'shipping_rule',
				'plural'   => 'shipping_rules',
				'ajax'     => false,
				'screen'   => 'yith_shippo_shipping_rule_list',
			)
		);
		$this->currier_service_opt = yith_shippo_get_service_options();
		$this->shipping_zones      = yith_shippo()->get_shipping_zones();
		$this->support_zone        = yith_shippo_support_shipping_zones();
	}

	/**
	 * Return all columns
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox"/>',
			'name'         => __( 'Rule', 'yith-shippo-shippings-for-woocommerce' ),
			'service_name' => __( 'Applied to', 'yith-shippo-shippings-for-woocommerce' ),
			'enable'       => __( 'Active', 'yith-shippo-shippings-for-woocommerce' ),
			'actions'      => '',
		);
		if ( $this->support_zone ) {
			$columns = array_merge( array_slice( $columns, 0, 3 ), array( 'shipping_zone' => __( 'Shipping zones', 'yith-shippo-shippings-for-woocommerce' ) ), array_slice( $columns, 3 ) );
		}

		return $columns;
	}

	/**
	 * Show the cn column
	 *
	 * @param YITH_Shippo_Shipping_Rule $item The item.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['plural'],
			$item->get_id()
		);
	}

	/**
	 * Return the right column info
	 *
	 * @param YITH_Shippo_Shipping_Rule $item The item.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function column_name( $item ) {
		$name = $item->get_name();

		if ( empty( $name ) ) {
			$name = _x( 'N/A', '[ADMIN] Empty admin table column', 'yith-shippo-shippings-for-woocommerce' );
		}

		return $name;
	}

	/**
	 * Return the service name
	 *
	 * @param YITH_Shippo_Shipping_Rule $item The item.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function column_service_name( $item ) {
		$service = $item->get_shipping_service();

		return isset( $this->currier_service_opt[ $service ] ) ? $this->currier_service_opt[ $service ] : '';
	}

	/**
	 * Return the shipping zones
	 *
	 * @param YITH_Shippo_Shipping_Rule $item The item.
	 *
	 * @since 1.0.0
	 */
	public function column_shipping_zone( $item ) {
		$zones     = $item->get_shipping_zones();
		$zone_name = array();
		foreach ( $zones as $zone_id ) {
			if ( isset( $this->shipping_zones[ $zone_id ] ) ) {
				$zone_name[] = $this->shipping_zones[ $zone_id ];
			}
		}

		return empty( $zone_name ) ? 'N/A' : implode( ', ', $zone_name );
	}

	/**
	 * Return the onoff button
	 *
	 * @param YITH_Shippo_Shipping_Rule $item The item.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function column_enable( $item ) {
		$column = yith_plugin_fw_get_field(
			array(
				'id'    => 'yith_toggle_rule_' . $item->get_id(),
				'class' => 'yith_toggle_rule',
				'value' => $item->is_enabled() ? 'yes' : 'no',
				'type'  => 'onoff',
			)
		);

		return $column;
	}

	/**
	 * Returns content for column Action
	 *
	 * @param YITH_WCAF_Rate_Rule $item Current item.
	 *
	 * @return string Column content.
	 */
	public function column_actions( $item ) {
		$available_actions = array(
			'edit'   => array(
				'label'  => _x( 'Edit', '[ADMIN] Shipping rules table', 'yith-shippo-shippings-for-woocommerce' ),
				'icon'   => 'edit',
				'action' => 'edit',
				'url'    => esc_url(
					add_query_arg(
						array(
							'action'           => 'edit',
							'shipping_rule_id' => $item->get_id(),
						)
					)
				),
			),
			'delete' => array(
				'label'        => _x( 'Delete', '[ADMIN] Shipping rules table', 'yith-shippo-shippings-for-woocommerce' ),
				'action'       => 'delete',
				'icon'         => 'trash',
				'url'          => esc_url(
					add_query_arg(
						array(
							'action'           => 'delete',
							'shipping_rule_id' => $item->get_id(),
						)
					)
				),
				'confirm_data' => array(
					'title'               => _x( 'Confirm delete', '[ADMIN] Confirmation popup before deleting an item', 'yith-shippo-shippings-for-woocommerce' ),
					'message'             => _x( 'Are you sure you want to delete this item?', '[ADMIN] Confirmation popup before deleting an item', 'yith-shippo-shippings-for-woocommerce' ),
					'confirm-button'      => _x( 'Delete', '[ADMIN] Confirmation popup before deleting an item', 'yith-shippo-shippings-for-woocommerce' ),
					'confirm-button-type' => 'delete',
				),
			),
		);

		$links = '';

		foreach ( $available_actions as $action_id => $action_details ) {
			$links .= yith_plugin_fw_get_component(
				array_merge(
					array(
						'type'  => 'action-button',
						'class' => $action_id,
					),
					$action_details
				),
				false
			);
		}

		return $links;
	}

	/**
	 * Return the bulk actions
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete'  => _x( 'Delete shipping rule', '[ADMIN] Shipping rule bulk actions', 'yith-shippo-shipping-for-woocommerce' ),
			'enable'  => _x( 'Active shipping rule', '[ADMIN] Shipping rule bulk actions', 'yith-shippo-shipping-for-woocommerce' ),
			'disable' => _x( 'Deactivate shipping rule', '[ADMIN] Shipping rule bulk actions', 'yith-shippo-shipping-for-woocommerce' ),
		);

		return $actions;
	}

	/**
	 * Show the filters
	 *
	 * @param string $which Top / Bottom.
	 *
	 * @since 1.0.0
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' === $which && $this->support_zone ) {
			$this->print_shipping_zone_filter();
			$this->print_filter_button();
		}
	}

	/**
	 * Show the filter by zone
	 *
	 * @since 1.0.0
	 */
	protected function print_shipping_zone_filter() {
		$value = isset( $_REQUEST['shipping_zone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['shipping_zone'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args  = array(
			'id'       => '_shipping_zone_id',
			'name'     => 'shipping_zone',
			'type'     => 'select',
			'options'  => array( '' => __( 'Filter by shipping zone', 'yith-shippo-shippings-for-woocommerce' ) ) + $this->shipping_zones,
			'value'    => $value,
			'multiple' => false,

		);
		yith_plugin_fw_get_field( $args, true, false );
	}

	/**
	 * Prints reset button that removes any filter currently applied
	 *
	 * @return void
	 */
	protected function print_filter_button() {
		submit_button(
			_x( 'Filter', '[ADMIN] Filter button label', 'yith-shippo-shippings-for-woocommerce' ),
			'button',
			'filter_action',
			false,
			array(
				'id' => 'post-query-submit',
			)
		);
	}

	/**
	 * Check if the table has items
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function has_items() {
		return count( $this->items ) > 0;
	}

	/**
	 * Prepare the items
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$per_page              = 15;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		try {
			$data_store = WC_Data_Store::load( 'shippo_shipping_rule' );
		} catch ( Exception $e ) {
			$data_store = false;
		}

		$total = 0;
		$items = array();
		if ( $data_store ) {

			$total   = $data_store->count();
			$zone_id = isset( $_REQUEST['shipping_zone'] ) && '' !== $_REQUEST['shipping_zone'] ? sanitize_text_field( wp_unslash( $_REQUEST['shipping_zone'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$items   = $data_store->query(
				array(
					'orderby'       => 'ID',
					'order'         => 'DESC',
					'limit'         => $per_page,
					'offset'        => $current_page - 1,
					'shipping_zone' => $zone_id,
				)
			);
		}

		$this->items = $items;
		$this->set_pagination_args(
			array(
				'total_items' => $total,                  // WE have to calculate the total number of items.
				'per_page'    => $per_page,                     // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total / $per_page ),   // WE have to calculate the total number of pages.
			)
		);
	}

	/**
	 * Display the rows
	 *
	 * @since 1.0.0
	 */
	public function display() {
		?>
		<div class="table-container">
			<?php
			if ( $this->has_items() ) {
				parent::display();
			} else {
				if ( isset( $_REQUEST['shipping_zone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->display_empty_row();
				} else {
					$this->display_empty_message();
				}
			}
			?>
		</div>
		<?php
        echo "<script>jQuery( document ).find( '.yith-add-button' ).addClass( 'button-primary' ).appendTo( 'h1' )</script>";
	}

	/**
	 * Display an empty row if a filter is set
	 *
	 * @since 1.0.0
	 */
	public function display_empty_row() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list"
				<?php
				if ( $singular ) {
					echo " data-wp-lists='list:" . esc_attr( $singular ) . "'";
				}
				?>
			>
			<?php
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
			esc_html_e( 'No rule found for this zone', 'yith-shippo-shippings-for-woocommerce' );
			echo '</td></tr>';
			?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Display the empty message
	 *
	 * @since 1.0.0
	 */
	public function display_empty_message() {

		$url = admin_url( 'admin.php' );

		$url = add_query_arg(
			array(
				'action'  => 'new_shipping_rule',
				'page'    => 'yith_shippo_shipping_for_woocommerce',
				'tab'     => 'shipping',
				'sub_tab' => 'shipping-rules',
			),
			$url
		);
		?>
		<p class="no-items-found">
			<i class="yith-icon yith-icon-shipping-rule"></i>
			<span class="no-items-message"><?php esc_html_e( 'You have no shipping rules created yet.', 'yith-shippo-shipping-for-woocommerce' ); ?></span>
			<span class="no-items-message"><?php esc_html_e( 'Create now your first one!', 'yith-shippo-shipping-for-woocommerce' ); ?></span>
			<a href="<?php echo esc_url_raw( $url ); ?>" class="button yith-add-button yith-plugin-fw__button--xxl"><?php esc_html_e( 'Create rule', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
		</p>
		<?php
	}
}
