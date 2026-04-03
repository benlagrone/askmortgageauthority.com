<?php
/**
 * Class that install the plugin tables
 *
 * @package YITH\Shippo
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class that init the db
 */
class YITH_Shippo_Install {
	/**
	 * The function that init the configuration
	 *
	 * @since  1.0.0
	 */
	public static function init() {
		self::install_tables();
		// install data stores.
		self::install_data_stores();
		self::install_carriers_folder();
	}

	/**
	 * Create the plugin tables
	 *
	 * @since  1.0.0
	 */
	public static function install_tables() {
		global $wpdb;

		$wpdb->yith_shippo_shipping_rules        = $wpdb->prefix . 'yith_shippo_shipping_rules';
		$wpdb->yith_shippo_shipping_rulemeta     = $wpdb->prefix . 'yith_shippo_shipping_rulemeta';
		$wpdb->yith_shippo_shipping_parcel_boxes = $wpdb->prefix . 'yith_shippo_shipping_parcel_boxes';
		$wpdb->yith_shippo_shipping_tracking     = $wpdb->prefix . 'yith_shippo_shipping_tracking';

		// un-prefixed tables (required for WP automatic meta handling).
		$wpdb->shippo_shipping_rulemeta = $wpdb->prefix . 'yith_shippo_shipping_rulemeta';

		$current_db_version = get_option( 'yith_shippo_db_version' );

		if (  version_compare( $current_db_version, YITH_SHIPPO_DB_VERSION, '>=' ) ) {
			return;
		}

		// assure dbDelta function is defined.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// retrieve table charset.
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $wpdb->yith_shippo_shipping_rules (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
    			name varchar(255) NOT NULL DEFAULT '',
                enabled tinyint(1) NOT NULL DEFAULT 0,
                shipping_service varchar(255) NOT NULL DEFAULT 'all',
                condition_enabled tinyint(1) NOT NULL DEFAULT 0,
                condition_type varchar(4) NOT NULL DEFAULT 'show',
                condition_mode varchar(3) NOT NULL DEFAULT  'and',
                fee_enabled tinyint(1) NOT NULL DEFAULT 0,
                fee_type varchar(20) NOT NULL DEFAULT 'fixed',
                fee_value decimal(19,4) NOT NULL DEFAULT 0.00,
                label_enabled tinyint(1) NOT NULL DEFAULT  0,
                custom_label varchar(255) NOT NULL  DEFAULT '',
                priority tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (ID),
                KEY shipping_service (shipping_service),
                KEY condition_type (condition_type)
                ) $charset_collate;";


		dbDelta( $sql );
		$sql = "CREATE TABLE $wpdb->yith_shippo_shipping_rulemeta (
				meta_id bigint(20) NOT NULL AUTO_INCREMENT,
    			shippo_shipping_rule_id bigint(20) NOT NULL,
    			meta_key varchar(255) NOT NULL DEFAULT '',
    			meta_value longtext NOT NULL DEFAULT '',
                PRIMARY KEY (meta_id),
                KEY external_rule (shippo_shipping_rule_id),
                KEY object_type (meta_key)
                ) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $wpdb->yith_shippo_shipping_parcel_boxes (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
    			name varchar(255) NOT NULL DEFAULT '',
                enabled tinyint(1) NOT NULL DEFAULT 1,
                type varchar(255) NOT NULL DEFAULT 'parcel',
                weight varchar(25),
                width varchar(25),
                height varchar(25),
                length varchar(25),
                inner_padding varchar(25),
                max_weight varchar(25),
                distance_unit varchar(25),
                weight_unit varchar(25),
                shipping_zones longtext DEFAULT '',
                PRIMARY KEY (ID)
                ) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $wpdb->yith_shippo_shipping_tracking (
				id bigint(20) NOT NULL AUTO_INCREMENT,
    			tracking_number varchar(255) NOT NULL,
    			carrier varchar(255) DEFAULT '',
    			order_id bigint(255) NOT NULL DEFAULT 0,
    			rate_key varchar(20)  NOT NULL DEFAULT '',
    			status varchar(100)  NOT NULL DEFAULT 'UNKNOWN',
    			status_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    			info longtext DEFAULT '',
                PRIMARY KEY (id)
                ) $charset_collate;";

		dbDelta( $sql );

		update_option( 'yith_shippo_db_version', YITH_SHIPPO_DB_VERSION );
	}

	/**
	 * Install data stores for the plugin
	 *
	 * @return void.
	 */
	protected static function install_data_stores() {
		add_filter( 'woocommerce_data_stores', array( self::class, 'add_data_stores' ) );
	}

	/**
	 * Add plugin's data stores to list of available ones
	 *
	 * @param array $data_stores Available Data Stores.
	 *
	 * @return array Filtered array of Data Stores
	 */
	public static function add_data_stores( $data_stores ) {
		$data_stores = array_merge(
			$data_stores,
			array(
				'shippo_shipping_rule'  => 'YITH_Shippo_Shipping_Rule_Data_Store',
				'shippo_parcel_box'     => 'YITH_Shippo_Parcel_Box_Data_Store',
				'shippo_order_shipping' => 'YITH_Shippo_Order_Shipping_Data_Store',
				'shippo_tracking'       => 'YITH_Shippo_Tracking_Data_Store',
			)
		);

		return $data_stores;
	}

	/**
	 * Create the carriers folder
	 *
	 * @since  1.0.0
	 */
	protected static function install_carriers_folder() {
		$folder_name = 'carriers';

		if ( ! file_exists( YITH_SHIPPO_DOCUMENT_SAVE_DIR . $folder_name ) ) {
			wp_mkdir_p( YITH_SHIPPO_DOCUMENT_SAVE_DIR . $folder_name );
		}
	}

}
