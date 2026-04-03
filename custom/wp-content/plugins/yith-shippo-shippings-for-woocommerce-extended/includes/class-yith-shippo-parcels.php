<?php
/**
 * This class manage the parcel boxes
 *
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

require_once YITH_SHIPPO_DIR . 'vendor/autoload.php';

use Latuconsinafr\BinPackager\BinPackager3D\Bin;
use Latuconsinafr\BinPackager\BinPackager3D\Item;
use Latuconsinafr\BinPackager\BinPackager3D\Packager;
use Latuconsinafr\BinPackager\BinPackager3D\Types\SortType;

/**
 * The class
 */
class YITH_Shippo_Parcels {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Helper array to connect each Bin id to the parcel set on settings
	 *
	 * @var array
	 */
	protected $parcel_list;

	/**
	 * Helper array to connect the cart contents to the Item object
	 *
	 * @var array
	 */
	protected $items_list;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->logger = YITH_Shippo_Logger::get_instance();
		add_action( 'admin_init', array( $this, 'bulk_actions' ), 10 );
	}

	/**
	 * Get the amount of parcel in the rate ( is a rate for parcel )
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return int
	 * @since  1.0.0
	 * @author YITH <plugins@yithemes.com>
	 */
	public static function count_item_in_parcel( $parcel ) {
		$total = 0;
		if ( isset( $parcel['filled'] ) ) {
			foreach ( $parcel['filled'] as $item ) {
				$total += $item['quantity'];
			}
		}

		return $total;
	}

	/**
	 * Return the parcel id
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return false|string
	 * @since  1.0.0
	 */
	public static function get_parcel_id( $parcel ) {
		$id = $parcel['id'] ?? '';
		if ( ! empty( $id ) && false === strpos( $parcel['id'], 'item-key-' ) ) {
			$id = explode( '-', $id );
			$id = $id[1] ?? false;
		}

		return $id;
	}

	/**
	 * Return the parcel type
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return false|string
	 * @since  1.0.0
	 */
	public static function get_parcel_type( $parcel ) {
		$id = $parcel['id'] ?? '';
		if ( ! empty( $id ) && false === strpos( $parcel['id'], 'single_product' ) ) {
			$id = explode( '-', $id );
			$id = $id[0] ?? false;
		} else {
			$id = 'parcel';
		}

		return $id;
	}

	/**
	 * Get an array with all information about product in parcel
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_product_info_in_parcel( $parcel ) {
		$results = array();
		if ( isset( $parcel['filled'] ) ) {
			foreach ( $parcel['filled'] as $item ) {
				$info                           = array(
					'qty'            => $item['quantity'],
					'value'          => $item['amount'],
					'name'           => $item['product_name'],
					'tariff_number'  => $item['tariff_number'] ?? '',
					'country_origin' => $item['country_origin'] ?? '',
				);
				$results[ $item['product_id'] ] = $info;
			}
		}

		return $results;
	}

	/**
	 * Save the parcel boxes from admin
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function bulk_actions() {
		if ( ! isset( $_REQUEST['_yith_shippo_nonce'], $_REQUEST['yith_shippo_parcel'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_yith_shippo_nonce'] ) ), 'yith_shippo_edit_parcel_boxes' ) ) {
			return;
		}

		$posted = $_REQUEST;

		// looking for bulk actions.
		if ( isset( $posted['yith_shippo_bulk_parcels_triggered'], $posted['yith_shippo_parcel_bulk_action'] ) && $posted['yith_shippo_bulk_parcels_triggered'] && '' !== sanitize_text_field( wp_unslash( $posted['yith_shippo_parcel_bulk_action'] ) ) ) {
			$action    = sanitize_text_field( wp_unslash( $posted['yith_shippo_parcel_bulk_action'] ) );
			$bulk_list = $posted['yith-shippo-bulk'];
			if ( ! empty( $bulk_list ) ) {
				$this->$action( array_keys( $bulk_list ) );
			}
		}
	}

	/**
	 * Activate parcel boxes
	 *
	 * @param array $parcel_boxes List of array toi activate.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected function activate_parcel_boxes( $parcel_boxes ) {
		if ( $parcel_boxes ) {
			foreach ( $parcel_boxes as $parcel_box ) {
				$parcel = yith_shippo_get_parcel_box( $parcel_box );
				if ( $parcel ) {
					$parcel->set_enabled( 1 );
					$parcel->save();
				}
			}
		}
	}

	/**
	 * Deactivate parcel boxes
	 *
	 * @param array $parcel_boxes List of array toi activate.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected function deactivate_parcel_boxes( $parcel_boxes ) {
		if ( $parcel_boxes ) {
			foreach ( $parcel_boxes as $parcel_box ) {
				$parcel = yith_shippo_get_parcel_box( $parcel_box );
				if ( $parcel ) {
					$parcel->set_enabled( 0 );
					$parcel->save();
				}
			}
		}
	}

	/**
	 * Delete parcel boxes
	 *
	 * @param array $parcel_boxes List of array toi activate.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected function delete_parcel_boxes( $parcel_boxes ) {
		if ( $parcel_boxes ) {
			foreach ( $parcel_boxes as $parcel_box ) {
				$parcel = yith_shippo_get_parcel_box( $parcel_box );
				if ( $parcel ) {
					$parcel->delete();
				}
			}
		}
	}

	/**
	 * Get parcel boxes from database
	 *
	 * @param array $args Arguments to the query.
	 *
	 * @retun array
	 * @since 1.0.0
	 */
	public function get_parcel_boxes_as_array( $args = array() ) {
		try {
			/**
			 * Parcel Box Data Store
			 *
			 * @var YITH_Shippo_Parcel_Box_Data_Store
			 */
			$data_store = WC_Data_Store::load( 'shippo_parcel_box' );
		} catch ( Exception $e ) {
			$data_store = false;
		}
		$items = array();
		if ( $data_store ) {
			$items = $data_store->get_parcel_boxes_as_array( $args );
		}

		return $items;
	}

	/**
	 * Calculate parcels on order
	 *
	 * @param WC_Order $order Order.
	 * @param int|bool $shipping_zone Shipping zone.
	 *
	 * @return array List of boxes.
	 * @since 1.0.0
	 */
	public function calculate_parcels_on_order( $order, $shipping_zone = false ) {
		$products_to_pack = $this->get_products_to_pack_by_order( $order );

		$this->logger->shipments( 'Start to calculate parcel boxes on order ' . $order->get_id() );

		return $this->calculate_parcels( $products_to_pack, $shipping_zone );
	}

	/**
	 * Calculate parcels
	 *
	 * @param int|bool $shipping_zone Shipping zone.
	 *
	 * @return array List of boxes.
	 *
	 * @since 1.0.0
	 */
	public function calculate_parcels_on_cart( $contents, $shipping_zone = false ) {
		$products_to_pack = $this->get_cart_products_to_pack( $contents );
		$this->logger->shipments( 'Start to calculate parcel boxes on cart' );

		return $this->calculate_parcels( $products_to_pack, $shipping_zone );
	}

	/**
	 * Calculate if items fit on parcel
	 * - args is an array with:
	 * - 'products_in_shipping' list of products to fit inside the parcel
	 * - 'parcel' name and dimensions of parcel
	 * - 'order_id' id of the order
	 * - 'package' id of package
	 *
	 * @param array $args Arguments to calculcate.
	 *
	 * @return array|string List of boxes.
	 * @since 1.0.0
	 */
	public function calculate_items_in_parcel( array $args ) {
		$parcel_value            = 0;
		$products_to_pack        = array();
		$parcels                 = array();
		$calculate_product_value = false;
		if ( ! defined( 'YITH_SHIPPO_CALCULATING_FIT_ITEMS' ) ) {
			define( 'YITH_SHIPPO_CALCULATING_FIT_ITEMS', true );
		}
		if ( isset( $args['products_in_shipping'] ) ) {
			foreach ( $args['products_in_shipping'] as $key => $item ) {
				$item['value'] = wc_format_decimal( $item['value'] );
				if ( 'all' === $item['product'] ) {
					$order                   = wc_get_order( $args['order_id'] );
					$products_to_pack        = $this->get_products_to_pack_by_order( $order );
					$parcel_value            = (float) $item['value'];
					$calculate_product_value = true;
				} else {
					$product                  = wc_get_product( $item['product'] );
					$parcel_value             += (float) $item['value'];
					$products_to_pack[ $key ] = array(
						'product'        => wc_get_product( $product ),
						'quantity'       => $item['qty'],
						'item_key'       => $key,
						'tariff_number'  => $item['tariff_number'],
						'country_origin' => $item['country_origin'],
					);
				}
			}
		}
		if ( isset( $args['parcel'], $args['package'] ) ) {
			$parcel    = $args['parcel'];
			$parcels[] = array(
				'ID'            => $args['package'],
				'name'          => _x( 'Custom parcel', 'generic name of a parcel created inside the order', 'yith-shippo-shippings-for-woocommerce' ),
				'enabled'       => 1,
				'item_key'      => uniqid(),
				'type'          => 'parcel',
				'weight'        => ! empty( $parcel['weight'] ) ? $this->round_number( $parcel['weight'] ) : 0,
				'height'        => ! empty( $parcel['height'] ) ? $this->round_number( $parcel['height'] ) : 0,
				'length'        => ! empty( $parcel['length'] ) ? $this->round_number( $parcel['length'] ) : 0,
				'width'         => ! empty( $parcel['width'] ) ? $this->round_number( $parcel['width'] ) : 0,
				'distance_unit' => get_option( 'woocommerce_dimension_unit' ),
				'mass_unit'     => get_option( 'woocommerce_weight_unit' ),
				'max_weight'    => '',
				'inner_padding' => 0,
			);
		}

		$parcels     = $this->get_best_parcels( $products_to_pack, $parcels );
		$tot_parcels = count( $parcels );
		if ( 1 === $tot_parcels ) {
			$parcel_found = current( $parcels );

			if ( (float) $parcel['height'] === (float) $parcel_found['height'] && (float) $parcel['length'] === (float) $parcel_found['length'] && (float) $parcel['width'] === (float) $parcel_found['width'] ) {
				$parcel_found['amount'] = $parcel_value;
				$filled                 = $parcel_found['filled'];

				if ( $calculate_product_value ) {
					$total_fitted = 0;
					foreach ( $filled as $item_fit ) {
						$total_fitted += $item_fit['quantity'];
					}

					if ( $total_fitted > 0 ) {
						foreach ( $filled as $item_fit_key => $item_fit ) {
							$parcel_found['filled'][ $item_fit_key ]['amount'] = ( $parcel_value / $total_fitted ) * (int) $item_fit['quantity'];
						}
					}
				} else {
					foreach ( $filled as $item_fit_key => $item_fit ) {
						if ( isset( $args['products_in_shipping'][ $item_fit_key ]['value'] ) ) {
							$parcel_found['filled'][ $item_fit_key ]['amount'] = $args['products_in_shipping'][ $item_fit_key ]['value'];
						}
					}
				}
			} else {
				$parcel_found = _x( 'Please, check the products and parcel chosen.', 'Error message showed when the items do not fit inside the parcel', 'yith-shippo-shippings-for-woocommerce' );

			}
		} else { // Or no parcels are found or the product can't be added in a unique parcel.
			$parcel_found = _x( 'Please, check the products and parcel chosen.', 'Error message showed when the items do not fit inside the parcel', 'yith-shippo-shippings-for-woocommerce' );

		}

		return apply_filters( 'yith_shippo_parcel_found', $parcel_found );
	}


	/**
	 * Calculate parcels
	 *
	 * @param array    $products_to_pack Products to put inside the parcels.
	 * @param int|bool $shipping_zone Shipping zone.
	 *
	 * @return array List of boxes.
	 * @since 1.0.0
	 */
	public function calculate_parcels( $products_to_pack, $shipping_zone ) {

		$parcel_boxes = array();

		if ( ! $products_to_pack ) {
			// no products to process.
			$this->logger->shipments( 'No products to process' );

			return $parcel_boxes;
		}

		$owner_parcels = $this->get_parcel_boxes_as_array(
			array(
				'enabled'       => true,
				'shipping_zone' => $shipping_zone,
			)
		);
		$this->logger->shipments( 'Enabled parcels defined on settings: ' . print_r( $owner_parcels, 1 ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		if ( ! $owner_parcels ) {
			$this->logger->shipments( 'No parcels found. Starting to create parcels from single products.' );
			foreach ( $products_to_pack as $product_to_pack ) {
				$parcel_from_product = $this->create_a_parcel_from_product( 'item-key-' . $product_to_pack['item_key'], $product_to_pack );
				if ( $parcel_from_product ) {
					$owner_parcels[] = $parcel_from_product;
				}
			}
			$this->logger->shipments( 'Parcels created from single products: ' . print_r( $owner_parcels, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		$this->logger->shipments( 'Started to fill the packages' );

		return $this->get_best_parcels( $products_to_pack, $owner_parcels );

	}

	/**
	 * Return the list of packages filled with products
	 *
	 * @param array $products_to_pack List of products to pack.
	 * @param array $owner_parcels List of parcels.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_best_parcels( $products_to_pack, $owner_parcels ) {

		$parcels = $this->fit_parcels( $products_to_pack, $owner_parcels );
		$this->logger->shipments( 'Created the boxes:' . print_r( $parcels, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		if ( 'yes' === get_option( 'yith_shippo_combine_boxes', 'no' ) && ! defined( 'YITH_SHIPPO_CALCULATING_FIT_ITEMS' ) ) {
			$parcels = $this->combine_boxes( $parcels );
			$this->logger->shipments( 'Combined box:' . print_r( $parcels, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			if ( 'yes' === get_option( 'yith_shippo_cube_dimensions', 'no' ) ) {
				$parcels = array_map( array( $this, 'cube_box' ), $parcels );
			}
		}

		$this->logger->shipments( 'Parcel filled:' . print_r( $parcels, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		$parcels = array_map( array( $this, 'adjust_parcel' ), $parcels );
		$this->logger->shipments( 'Parcel Adjusted:' . print_r( $parcels, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		$this->logger->shipments( 'Parcel process ended' );

		return $parcels;
	}


	/**
	 * Fit the products inside the parcels
	 *
	 * @param array $products_to_pack List of product to pack.
	 * @param array $owner_parcels List of parcels set on store or created by single product.
	 *
	 * @return array();
	 * @since 1.0.0
	 */
	public function fit_parcels( $products_to_pack, $owner_parcels ) {
		$parcel_boxes = array();

		$packager     = $this->fill_new_packager( $products_to_pack, $owner_parcels );
		$sorted_bins  = $packager->getBins();
		$sorted_items = $packager->getItems();

		if ( iterator_count( $sorted_bins ) === 0 || iterator_count( $sorted_items ) === 0 ) {
			return $parcel_boxes;
		}

		$main_packager = new Packager( 2, SortType::ASCENDING );
		$main_packager->addItems( $sorted_items );
		$counter = 0;
		while ( iterator_count( $sorted_items ) > 0 ) {
			$unfitted_items                   = $sorted_items;
			$best_box                         = $this->find_better_box( $sorted_bins, $sorted_items );
			$new_box_id                       = $best_box->getId() . '-' . $counter ++;
			$new_bin                          = new Bin( $new_box_id, $best_box->getLength(), $best_box->getHeight(), $best_box->getBreadth(), $best_box->getWeight() );
			$this->parcel_list[ $new_box_id ] = $this->parcel_list[ $best_box->getId() ];
			$main_packager->addBin( $new_bin );

			foreach ( $sorted_items as $item ) {
				$main_packager->packItemToBin( $new_bin, $item );
			}

			$sorted_items = $new_bin->getIterableUnfittedItems();

			if ( count( $unfitted_items ) === count( $sorted_items ) ) {
				break;
			}
		}

		$bins = $main_packager->getBins();
		foreach ( $bins as $bin ) {
			$fitted_items = $bin->getIterableFittedItems();

			if ( iterator_count( $fitted_items ) === 0 ) {
				// avoid empty parcels.
				continue;
			}

			$formatted_items = array_map( array( $this, 'format_item' ), $bin->getFittedItems() );
			$parcel_settings = $this->parcel_list[ $bin->getID() ];
			// adjustment to the products parcels.
			$weight = true === strpos( $bin->getID(), 'item-key' ) ? (float) $bin->getTotalFittedWeight() : (float) $bin->getTotalFittedWeight() + (float) $parcel_settings['weight'];
			// don't use the bin dimension because that had been reduced by inner padding.

			$parcel_boxes[] = array(
				'id'            => $bin->getID(),
				'name'          => $parcel_settings['name'],
				'length'        => $parcel_settings['length'],
				'width'         => $parcel_settings['width'],
				'height'        => $parcel_settings['height'],
				'distance_unit' => get_option( 'woocommerce_dimension_unit' ),
				'mass_unit'     => get_option( 'woocommerce_weight_unit' ),
				'weight'        => $this->round_number( $weight ),
				'volume'        => $bin->getVolume(),
				'insurance'     => get_option( 'yith-shippo-require-insurance', 'no' ),
				'signature'     => get_option( 'yith-shippo-require-signature', 'no' ),
				'amount'        => array_sum( array_column( $formatted_items, 'amount' ) ),
				'filled'        => $this->merge_items( $formatted_items ),
			);
		}

		// Check if there are unboxed items and create a parcel for each product.
		if ( ! empty( $sorted_items ) ) {

			foreach ( $sorted_items as $item ) {
				$formatted_item = $this->format_item( $item );

				$parcel_boxes[] = array(
					'id'            => 'single_product_' . $item->getId(),
					'length'        => $item->getLength(),
					'width'         => $item->getBreadth(),
					'height'        => $item->getHeight(),
					'volume'        => $item->getVolume(),
					'amount'        => $formatted_item['amount'],
					'distance_unit' => get_option( 'woocommerce_dimension_unit' ),
					'mass_unit'     => get_option( 'woocommerce_weight_unit' ),
					'weight'        => $formatted_item['weight'],
					'insurance'     => get_option( 'yith-shippo-require-insurance', 'no' ),
					'signature'     => get_option( 'yith-shippo-require-signature', 'no' ),
					'filled'        => array( $formatted_item ),
				);
			}
		}

		return $parcel_boxes;
	}

	/**
	 * Return the list of items inside the parcel in a readable format
	 *
	 * @param Item $item Item to format.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function format_item( $item ) {
		$product_info      = $this->items_list[ $item->getId() ];
		$product           = $product_info['product'];
		$tarif             = yith_shippo_get_product_tariff_number( $product );
		$country_of_origin = yith_shippo_get_product_country_origin( $product );

		return array(
			'item_key'       => $product_info['item_key'],
			'product_id'     => $product->get_id(),
			'product_name'   => $product->get_name(),
			'height'         => $item->getHeight(),
			'weight'         => $item->getWeight(),
			'width'          => $item->getBreadth(),
			'length'         => $item->getLength(),
			'volume'         => $item->getVolume(),
			'amount'         => $product->get_price(),
			'tariff_number'  => ! empty( $product_info['tariff_number'] ) ? $product_info['tariff_number'] : $tarif,
			'country_origin' => ! empty( $product_info['country_origin'] ) ? $product_info['country_origin'] : $country_of_origin,
			'quantity'       => 1,
		);
	}

	/**
	 * Merge the items with the same cart item key
	 *
	 * @param array $items List of items to merge.
	 *
	 * @return  array
	 * @since 1.0.0
	 */
	private function merge_items( $items ) {
		$merged_items = array();
		foreach ( $items as $item ) {
			if ( isset( $merged_items[ $item['item_key'] ] ) ) {
				$merged_items[ $item['item_key'] ]['quantity'] ++;
			} else {
				$merged_items[ $item['item_key'] ] = $item;
			}
		}

		return $merged_items;
	}

	/**
	 * Combine all boxes in only one that is the sum of each dimensions.
	 *
	 * @param array $parcels Parcels to combine.
	 *
	 * @return $array
	 * @since  1.0.0
	 */
	public function combine_boxes( $parcels ) {

		if ( ! $parcels ) {
			return $parcels;
		}

		$combined_box = array(
			'id'            => 'combined_box',
			'length'        => 0,
			'width'         => 0,
			'height'        => 0,
			'volume'        => 0,
			'amount'        => 0,
			'distance_unit' => get_option( 'woocommerce_dimension_unit' ),
			'mass_unit'     => get_option( 'woocommerce_weight_unit' ),
			'insurance'     => get_option( 'yith-shippo-require-insurance', 'no' ),
			'signature'     => get_option( 'yith-shippo-require-signature', 'no' ),
			'weight'        => 0,
			'filled'        => array(),
		);

		foreach ( $parcels as $parcel ) {
			if ( ( $parcel['width'] + $parcel['length'] ) > ( $combined_box['width'] + $combined_box['length'] ) ) {
				$combined_box['width']  = $parcel['width'];
				$combined_box['length'] = $parcel['length'];
			}
			$combined_box['height'] += $parcel['height'];
			$combined_box['weight'] += $parcel['weight'];
			$combined_box['volume'] += $parcel['volume'];
			$combined_box['amount'] += $parcel['amount'];
			$combined_box['filled'] = array_merge( $combined_box['filled'], (array) $parcel['filled'] );
		}

		return array( $combined_box );

	}

	/**
	 * Create a parcel from product
	 *
	 * @param string $prefix Prefix for the id.
	 * @param array  $product_to_pack Product to pack.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function create_a_parcel_from_product( $prefix, $product_to_pack ) {

		$dimension_standard = yith_shippo_get_default_parcel_dimension();
		if ( ! isset( $product_to_pack['product'] ) || ! $product_to_pack['product'] ) {
			return false;
		}

		$product = $product_to_pack['product'];
		$width   = empty( $product->get_width() ) ? $dimension_standard['width'] : $product->get_width();
		$heigth  = empty( $product->get_height() ) ? $dimension_standard['height'] : $product->get_height();
		$length  = empty( $product->get_length() ) ? $dimension_standard['length'] : $product->get_length();

		return array(
			'ID'            => $prefix . '-' . $product->get_id(),
			'name'          => $product->get_name() . ' parcel',
			'length'        => $length,
			'width'         => $width,
			'height'        => $heigth,
			'inner_padding' => 0,
			'volume'        => $heigth * $length * $width,
			'distance_unit' => get_option( 'woocommerce_dimension_unit' ),
			'mass_unit'     => get_option( 'woocommerce_weight_unit' ),
			'weight'        => empty( $product->get_weight() ) ? $dimension_standard['weight'] : $product->get_weight(),
			'enabled'       => 1,
			'type'          => 'parcel',
			'from_product'  => true,
		);
	}

	/**
	 * Fill the new packager
	 *
	 * @param array $products_to_pack Array with product to pack information.
	 * @param array $parcels List of parcel boxes.
	 *
	 * @return mixed
	 */
	private function fill_new_packager( $products_to_pack, $parcels ) {
		$packager = new Packager( 2, SortType::DESCENDING );
		foreach ( $products_to_pack as $product_to_pack ) {
			$product = $product_to_pack['product'];
			$length  = empty( $product->get_length() ) ? 0 : $this->round_number( $product->get_length() );
			$height  = empty( $product->get_height() ) ? 0 : $this->round_number( $product->get_height() );
			$width   = empty( $product->get_height() ) ? 0 : $this->round_number( $product->get_width() );
			$weight  = empty( $product->get_weight() ) ? 0 : $this->round_number( $product->get_weight() );
			for ( $i = 1; $i <= $product_to_pack['quantity']; $i ++ ) {
				$item                                                                                   = new Item( $product_to_pack['item_key'] . '-' . $product->get_id() . '-' . $i, (float) $length, (float) $height, (float) $width, (float) $weight );
				$this->items_list[ $product_to_pack['item_key'] . '-' . $product->get_id() . '-' . $i ] = $product_to_pack;
				$packager->addItem( $item );
			}
		}

		foreach ( $parcels as $parcel ) {
			if ( $parcel['enabled'] ) {
				$dimension_unit   = get_option( 'woocommerce_dimension_unit' );
				$inner_padding    = (float) $parcel['inner_padding'];
				$parcel['length'] = (float) yith_shippo_convert_from_to( $parcel['length'], $parcel['distance_unit'], $dimension_unit ) - 2 * $inner_padding;
				$parcel['height'] = (float) yith_shippo_convert_from_to( $parcel['height'], $parcel['distance_unit'], $dimension_unit ) - 2 * $inner_padding;
				$parcel['width']  = (float) yith_shippo_convert_from_to( $parcel['width'], $parcel['distance_unit'], $dimension_unit ) - 2 * $inner_padding;
				$weight           = empty( $parcel['max_weight'] ) ? 100 : $parcel['max_weight'];
				$bin              = new Bin( $parcel['type'] . '-' . $parcel['ID'], $parcel['length'], $parcel['height'], $parcel['width'], $weight );

				$this->parcel_list[ $parcel['type'] . '-' . $parcel['ID'] ] = $parcel;
				$packager->addBin( $bin );
			}
		}
		$packager->withFirstFit();

		return $packager;
	}

	/**
	 * Get products to pack of a specific order
	 *
	 * @param WC_Order $order Order id.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_products_to_pack_by_order( $order ) {
		$product_to_pack = array();
		$order_items     = $order->get_items();

		if ( ! $order_items ) {
			return $product_to_pack;
		}

		foreach ( $order_items as $item ) {
			/**
			 * Product
			 *
			 * @var WC_Product $product
			 */
			$product = $item->get_product();

			if ( $product && $product->exists() && $product->needs_shipping() ) {
				$product_to_pack[] = array(
					'product'  => $product,
					'quantity' => $item->get_quantity(),
					'item_key' => $item->get_id(),
				);
			}
		}

		return $product_to_pack;
	}


	/**
	 * Return the products in cart that will be shipped
	 *
	 * @return array|void
	 */
	public function get_cart_products_to_pack( $contents ) {

		$product_to_pack = array();


		foreach ( $contents as $cart_item_key => $cart_item ) {
			/**
			 * Product
			 *
			 * @var WC_Product $product
			 */
			$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $product->needs_shipping() ) {

				$product_to_pack[] = array(
					'product'  => $product,
					'quantity' => $cart_item['quantity'],
					'item_key' => $cart_item_key,
				);
			}
		}


		return $product_to_pack;
	}

	/**
	 * Calculate the better box that fits with product
	 *
	 * @param ArrayIterator $sorted_bins List of bins.
	 * @param ArrayIterator $sorted_items List of items.
	 *
	 * @return Bin
	 * @since 1.0.0
	 */
	public function find_better_box( $sorted_bins, $sorted_items ) {
		$box_ok   = array();
		$packager = new Packager( 2, SortType::DESCENDING );
		$packager->addItems( $sorted_items );
		$first_box = false;
		foreach ( $sorted_bins as $bin ) {
			if ( ! $first_box ) {
				$first_box = new Bin( $bin->getId(), $bin->getLength(), $bin->getHeight(), $bin->getBreadth(), $bin->getWeight() );
				$packager->addBin( $first_box );
			} else {
				$packager->addBin( new Bin( $bin->getId(), $bin->getLength(), $bin->getHeight(), $bin->getBreadth(), $bin->getWeight() ) );
			}
		}
		$descending_sorted_bins = $packager->getBins();
		foreach ( $descending_sorted_bins as $bin ) {
			// No item left.
			if ( iterator_count( $packager->getIterableItems() ) === 0 ) {
				break;
			}

			// Pack item(s) to current open bin.
			foreach ( $sorted_items as $item ) {
				$packager->packItemToBin( $bin, $item );
			}

			if ( iterator_count( $bin->getIterableUnfittedItems() ) === 0 ) {
				array_push( $box_ok, $bin );
			} else {
				break;
			}
		}

		return ! empty( $box_ok ) ? array_pop( $box_ok ) : $first_box;
	}


	/**
	 * Cube the box
	 *
	 * @param array $parcel Parcel to cube.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function cube_box( $parcel ) {
		$cube_side        = round( pow( $parcel['volume'], 1 / 3 ), 2 );
		$parcel['length'] = $cube_side;
		$parcel['width']  = $cube_side;
		$parcel['height'] = $cube_side;

		return $parcel;
	}

	/**
	 * Adjust parcels to minimum dimension
	 *
	 * @param array $parcel Parcel to adjust.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function adjust_parcel( $parcel ) {
		if ( 'yes' === get_option( 'yith_shippo_enable_additional_weight', 'no' ) ) {
			$parcel = $this->adjust_weight( $parcel );
			$this->logger->shipments( 'Parcel Weight Adjusted:' . print_r( $parcel, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		$parcel = $this->adjust_minimum_dimensions( $parcel );
		$this->logger->shipments( 'Parcel Minimum Dimension Adjusted:' . print_r( $parcel, 1 ) ); //phpcs:ignore

		return $parcel;
	}

	/**
	 * Adjust parcels weight
	 *
	 * @param array $parcel Parcel to adjust.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function adjust_weight( $parcel ) {

		$additional_weight = get_option( 'yith_shippo_additional_weight' );

		if ( $additional_weight && isset( $parcel['weight'] ) ) {

			if ( 'fixed' === $additional_weight['type'] ) {
				$parcel['weight'] = (float) $parcel['weight'] + $additional_weight['amount'];
			} else {
				$parcel['weight'] = (float) $parcel['weight'] + ( (float) $parcel['weight'] * $additional_weight['amount'] / 100 );
			}

			$parcel['weight'] = $this->round_number( $parcel['weight'] );
		}

		return $parcel;
	}

	/**
	 * Adjust parcels to minimum dimensions
	 *
	 * @param array $parcel Parcel to adjust.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function adjust_minimum_dimensions( $parcel ) {
		$default            = yith_shippo_get_default_parcel_dimension();
		$minimum_settings   = get_option(
			'yith_shippo_min_parcel_dimension',
			array(
				'min-width'  => $default['width'],
				'min-height' => $default['height'],
				'min-length' => $default['length'],
				'min-weight' => $default['weight'],
			)
		);
		$dimension_to_check = array( 'width', 'height', 'length', 'weight' );
		foreach ( $dimension_to_check as $dimension ) {

			if ( isset( $parcel[ $dimension ] ) ) {
				$parcel[ $dimension ] = max( (float) $parcel[ $dimension ], (float) $minimum_settings[ 'min-' . $dimension ] );
			}
		}

		return $parcel;
	}

	/**
	 * Round the number to  2 decimals
	 *
	 * @param string|float $number The number.
	 *
	 * @return float
	 */
	public function round_number( $number ) {
		$number = round( $number, 2 );

		return wc_format_decimal( $number );
	}
}
