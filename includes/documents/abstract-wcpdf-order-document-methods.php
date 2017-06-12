<?php
namespace WPO\WC\PDF_Invoices\Documents;

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( '\\WPO\\WC\\PDF_Invoices\\Documents\\Order_Document_Methods' ) ) :

/**
 * Abstract Order Methods
 *
 * Collection of methods to be used on orders within a Document
 * Created as abstract rather than traits to support PHP versions older than 5.4
 *
 * @class       \WPO\WC\PDF_Invoices\Documents\Order_Document_Methods
 * @version     2.0
 * @category    Class
 * @author      Ewout Fernhout
 */

abstract class Order_Document_Methods extends Order_Document {
	public function is_refund( $order ) {
		if ( method_exists( $order, 'get_type') ) { // WC 3.0+
			$is_refund = $order->get_type() == 'shop_order_refund';
		} else {
			$is_refund = get_post_type( WCX_Order::get_id( $order ) ) == 'shop_order_refund';
		}

		return $is_refund;
	}

	public function get_refund_parent_id( $order ) {
		if ( method_exists( $order, 'get_parent_id') ) { // WC3.0+
			$parent_order_id = $order->get_parent_id();
		} else {
			$parent_order_id = wp_get_post_parent_id( WCX_Order::get_id( $order ) );
		}

		return $parent_order_id;
	}


	public function get_refund_parent( $order ) {
		// only try if this is actually a refund
		if ( ! $this->is_refund( $order ) ) {
			return $order;
		}

		$parent_order_id = $this->get_refund_parent_id( $order );
		$order = WCX::get_order( $parent_order_id );
		return $order;
	}

	/**
	 * Check if billing address and shipping address are equal
	 */
	public function ships_to_different_address() {
		// always prefer parent address for refunds
		if ( $this->is_refund( $this->order ) ) {
			$order = $this->get_refund_parent( $this->order );
		} else {
			$order = $this->order;
		}

		$address_comparison_fields = apply_filters( 'wpo_wcpdf_address_comparison_fields', array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country'
		), $this );
		
		foreach ($address_comparison_fields as $address_field) {
			$billing_field = WCX_Order::get_prop( $order, "billing_{$address_field}", 'view');
			$shipping_field = WCX_Order::get_prop( $order, "shipping_{$address_field}", 'view');
			if ( $shipping_field != $billing_field ) {
				// this address field is different -> ships to different address!
				return true;
			}
		}

		//if we got here, it means the addresses are equal -> doesn't ship to different address!
		return apply_filters( 'wpo_wcpdf_ships_to_different_address', false, $order, $this );
	}
	
	/**
	 * Return/Show billing address
	 */
	public function get_billing_address() {
		// always prefer parent billing address for refunds
		if ( $this->is_refund( $this->order ) ) {
			// temporarily switch order to make all filters / order calls work correctly
			$refund = $this->order;
			$this->order = $this->get_refund_parent( $this->order );
			$address = apply_filters( 'wpo_wcpdf_billing_address', $this->order->get_formatted_billing_address(), $this );
			// switch back & unset
			$this->order = $refund;
			unset($refund);
		} elseif ( $address = $this->order->get_formatted_billing_address() ) {
			// regular shop_order
			$address = apply_filters( 'wpo_wcpdf_billing_address', $address, $this );
		} else {
			// no address
			$address = apply_filters( 'wpo_wcpdf_billing_address', __('N/A', 'woocommerce-pdf-invoices-packing-slips' ), $this );
		}

		return $address;
	}
	public function billing_address() {
		echo $this->get_billing_address();
	}

	/**
	 * Return/Show billing email
	 */
	public function get_billing_email() {
		$billing_email = WCX_Order::get_prop( $this->order, 'billing_email', 'view' );

		if ( !$billing_email && $this->is_refund( $this->order ) ) {
			// try parent
			$parent_order = $this->get_refund_parent( $this->order );
			$billing_email = WCX_Order::get_prop( $parent_order, 'billing_email', 'view' );
		}

		return apply_filters( 'wpo_wcpdf_billing_email', $billing_email, $this );
	}
	public function billing_email() {
		echo $this->get_billing_email();
	}
	
	/**
	 * Return/Show billing phone
	 */
	public function get_billing_phone() {
		$billing_phone = WCX_Order::get_prop( $this->order, 'billing_phone', 'view' );

		if ( !$billing_phone && $this->is_refund( $this->order ) ) {
			// try parent
			$parent_order = $this->get_refund_parent( $this->order );
			$billing_phone = WCX_Order::get_prop( $parent_order, 'billing_phone', 'view' );
		}

		return apply_filters( 'wpo_wcpdf_billing_phone', $billing_phone, $this );
	}
	public function billing_phone() {
		echo $this->get_billing_phone();
	}
	
	/**
	 * Return/Show shipping address
	 */
	public function get_shipping_address() {
		// always prefer parent shipping address for refunds
		if ( $this->is_refund( $this->order ) ) {
			// temporarily switch order to make all filters / order calls work correctly
			$refund = $this->order;
			$this->order = $this->get_refund_parent( $this->order );
			$address = apply_filters( 'wpo_wcpdf_shipping_address', $this->order->get_formatted_shipping_address(), $this );
			// switch back & unset
			$this->order = $refund;
			unset($refund);
		} elseif ( $address = $this->order->get_formatted_shipping_address() ) {
			// regular shop_order
			$address = apply_filters( 'wpo_wcpdf_shipping_address', $address, $this );
		} else {
			// no address
			$address = apply_filters( 'wpo_wcpdf_shipping_address', __('N/A', 'woocommerce-pdf-invoices-packing-slips' ), $this );
		}

		return $address;
	}
	public function shipping_address() {
		echo $this->get_shipping_address();
	}

	/**
	 * Return/Show a custom field
	 */		
	public function get_custom_field( $field_name ) {
		$custom_field = WCX_Order::get_meta( $this->order, $field_name, true );

		if ( !$custom_field && $this->is_refund( $this->order ) ) {
			// try parent
			$parent_order = $this->get_refund_parent( $this->order );
			$custom_field = WCX_Order::get_meta( $parent_order, $field_name, true );
		}

		return apply_filters( 'wpo_wcpdf_billing_custom_field', $custom_field, $this );
	}
	public function custom_field( $field_name, $field_label = '', $display_empty = false ) {
		$custom_field = $this->get_custom_field( $field_name );
		if (!empty($field_label)){
			// add a a trailing space to the label
			$field_label .= ' ';
		}

		if (!empty($custom_field) || $display_empty) {
			echo $field_label . nl2br ($custom_field);
		}
	}

	/**
	 * Return/show product attribute
	 */
	public function get_product_attribute( $attribute_name, $product ) {
		// first, check the text attributes
		$attributes = $product->get_attributes();
		$attribute_key = @wc_attribute_taxonomy_name( $attribute_name );
		if (array_key_exists( sanitize_title( $attribute_name ), $attributes) ) {
			$attribute = $product->get_attribute ( $attribute_name );
			return $attribute;
		} elseif (array_key_exists( sanitize_title( $attribute_key ), $attributes) ) {
			$attribute = $product->get_attribute ( $attribute_key );
			return $attribute;
		}

		// not a text attribute, try attribute taxonomy
		$attribute_key = @wc_attribute_taxonomy_name( $attribute_name );
		$product_id = WCX_Product::get_prop($product, 'id');
		$product_terms = @wc_get_product_terms( $product_id, $attribute_key, array( 'fields' => 'names' ) );
		// check if not empty, then display
		if ( !empty($product_terms) ) {
			$attribute = array_shift( $product_terms );
			return $attribute;
		} else {
			// no attribute under this name
			return false;
		}
	}
	public function product_attribute( $attribute_name, $product ) {
		echo $this->get_product_attribute( $attribute_name, $product );
	}

	/**
	 * Return/Show order notes
	 * could use $order->get_customer_order_notes(), but that filters out private notes already
	 */		
	public function get_order_notes( $filter = 'customer' ) {
		if ( $this->is_refund( $this->order ) ) {
			$post_id = $this->get_refund_parent_id( $this->order );
		} else {
			$post_id = $order_id;
		}

		$args = array(
			'post_id' 	=> $post_id,
			'approve' 	=> 'approve',
			'type' 		=> 'order_note'
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		if ( $notes ) {
			foreach( $notes as $key => $note ) {
				if ( $filter == 'customer' && !get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
					unset($notes[$key]);
				}
				if ( $filter == 'private' && get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
					unset($notes[$key]);
				}					
			}
			return $notes;
		}
	}
	public function order_notes( $filter = 'customer' ) {
		$notes = $this->get_order_notes( $filter );
		if ( $notes ) {
			foreach( $notes as $note ) {
				?>
				<div class="note_content">
					<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Return/Show the current date
	 */
	public function get_current_date() {
		return apply_filters( 'wpo_wcpdf_date', date_i18n( get_option( 'date_format' ) ) );
	}
	public function current_date() {
		echo $this->get_current_date();
	}

	/**
	 * Return/Show payment method  
	 */
	public function get_payment_method() {
		$payment_method_label = __( 'Payment method', 'woocommerce-pdf-invoices-packing-slips' );

		if ( $this->is_refund( $this->order ) ) {
			$parent_order = $this->get_refund_parent( $this->order );
			$payment_method_title = WCX_Order::get_prop( $parent_order, 'payment_method_title', 'view' );
		} else {
			$payment_method_title = WCX_Order::get_prop( $this->order, 'payment_method_title', 'view' );
		}

		$payment_method = __( $payment_method_title, 'woocommerce' );

		return apply_filters( 'wpo_wcpdf_payment_method', $payment_method, $this );
	}
	public function payment_method() {
		echo $this->get_payment_method();
	}

	/**
	 * Return/Show shipping method  
	 */
	public function get_shipping_method() {
		$shipping_method_label = __( 'Shipping method', 'woocommerce-pdf-invoices-packing-slips' );
		$shipping_method = __( $this->order->get_shipping_method(), 'woocommerce' );
		return apply_filters( 'wpo_wcpdf_shipping_method', $shipping_method, $this );
	}
	public function shipping_method() {
		echo $this->get_shipping_method();
	}

	/**
	 * Return/Show order number
	 */
	public function get_order_number() {
		// try parent first
		if ( $this->is_refund( $this->order ) ) {
			$parent_order = $this->get_refund_parent( $this->order );
			$order_number = $parent_order->get_order_number();
		} else {
			$order_number = $this->order->get_order_number();
		}

		// Trim the hash to have a clean number but still 
		// support any filters that were applied before.
		$order_number = ltrim($order_number, '#');
		return apply_filters( 'wpo_wcpdf_order_number', $order_number, $this );
	}
	public function order_number() {
		echo $this->get_order_number();
	}

	/**
	 * Return/Show the order date
	 */
	public function get_order_date() {
		if ( $this->is_refund( $this->order ) ) {
			$parent_order = $this->get_refund_parent( $this->order );
			$order_date = WCX_Order::get_prop( $parent_order, 'date_created' );
		} else {
			$order_date = WCX_Order::get_prop( $this->order, 'date_created' );
		}

		$date = $order_date->date_i18n( get_option( 'date_format' ) );
		$mysql_date = $order_date->date( "Y-m-d H:i:s" );
		return apply_filters( 'wpo_wcpdf_order_date', $date, $mysql_date, $this );
	}
	public function order_date() {
		echo $this->get_order_date();
	}

	/**
	 * Return the order items
	 */
	public function get_order_items() {
		$items = $this->order->get_items();
		$data_list = array();
	
		if( sizeof( $items ) > 0 ) {
			foreach ( $items as $item_id => $item ) {
				// Array with data for the pdf template
				$data = array();

				// Set the item_id
				$data['item_id'] = $item_id;
				
				// Set the id
				$data['product_id'] = $item['product_id'];
				$data['variation_id'] = $item['variation_id'];

				// Set item name
				$data['name'] = $item['name'];
				
				// Set item quantity
				$data['quantity'] = $item['qty'];

				// Set the line total (=after discount)
				$data['line_total'] = $this->format_price( $item['line_total'] );
				$data['single_line_total'] = $this->format_price( $item['line_total'] / max( 1, $item['qty'] ) );
				$data['line_tax'] = $this->format_price( $item['line_tax'] );
				$data['single_line_tax'] = $this->format_price( $item['line_tax'] / max( 1, $item['qty'] ) );
				
				$line_tax_data = maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '' );
				$data['tax_rates'] = $this->get_tax_rate( $item['tax_class'], $item['line_total'], $item['line_tax'], $line_tax_data );
				
				// Set the line subtotal (=before discount)
				$data['line_subtotal'] = $this->format_price( $item['line_subtotal'] );
				$data['line_subtotal_tax'] = $this->format_price( $item['line_subtotal_tax'] );
				$data['ex_price'] = $this->get_formatted_item_price( $item, 'total', 'excl' );
				$data['price'] = $this->get_formatted_item_price( $item, 'total' );
				$data['order_price'] = $this->order->get_formatted_line_subtotal( $item ); // formatted according to WC settings

				// Calculate the single price with the same rules as the formatted line subtotal (!)
				// = before discount
				$data['ex_single_price'] = $this->get_formatted_item_price( $item, 'single', 'excl' );
				$data['single_price'] = $this->get_formatted_item_price( $item, 'single' );

				// Pass complete item array
				$data['item'] = $item;
				
				// Get the product to add more info
				$product = $this->order->get_product_from_item( $item );
				
				// Checking fo existance, thanks to MDesigner0 
				if( !empty( $product ) ) {
					// Thumbnail (full img tag)
					$data['thumbnail'] = $this->get_thumbnail( $product );

					// Set item SKU
					$data['sku'] = $product->get_sku();
	
					// Set item weight
					$data['weight'] = $product->get_weight();
					
					// Set item dimensions
					$data['dimensions'] = WCX_Product::get_dimensions( $product );
				
					// Pass complete product object
					$data['product'] = $product;
				
				} else {
					$data['product'] = null;
				}
				
				// Set item meta
				if (function_exists('wc_display_item_meta')) { // WC3.0+
					$data['meta'] = wc_display_item_meta( $item, array(
						'echo'      => false,
					) );
				} else {
					if ( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) ) {
						$meta = new \WC_Order_Item_Meta( $item['item_meta'], $product );
					} else { // pass complete item for WC2.4+
						$meta = new \WC_Order_Item_Meta( $item, $product );
					}
					$data['meta'] = $meta->display( false, true );
				}

				$data_list[$item_id] = apply_filters( 'wpo_wcpdf_order_item_data', $data, $this->order, $this->get_type() );
			}
		}

		return apply_filters( 'wpo_wcpdf_order_items_data', $data_list, $this->order, $this->get_type() );
	}

	/**
	 * Get the tax rates/percentages for a given tax class
	 * @param  string $tax_class tax class slug
	 * @return string $tax_rates imploded list of tax rates
	 */
	public function get_tax_rate( $tax_class, $line_total, $line_tax, $line_tax_data = '' ) {
		// first try the easy wc2.2+ way, using line_tax_data
		if ( !empty( $line_tax_data ) && isset($line_tax_data['total']) ) {
			$tax_rates = array();

			$line_taxes = $line_tax_data['subtotal'];
			foreach ( $line_taxes as $tax_id => $tax ) {
				if ( !empty($tax) ) {
					$tax_rates[] = $this->get_tax_rate_by_id( $tax_id ) . ' %';
				}
			}

			$tax_rates = implode(' ,', $tax_rates );
			return $tax_rates;
		}

		if ( $line_tax == 0 ) {
			return '-'; // no need to determine tax rate...
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '2.1' ) >= 0 && !apply_filters( 'wpo_wcpdf_calculate_tax_rate', false ) ) {
			// WC 2.1 or newer is used
			$tax = new \WC_Tax();
			$taxes = $tax->get_rates( $tax_class );

			$tax_rates = array();

			foreach ($taxes as $tax) {
				$tax_rates[$tax['label']] = round( $tax['rate'], 2 ).' %';
			}

			if (empty($tax_rates)) {
				// one last try: manually calculate
				if ( $line_total != 0) {
					$tax_rates[] = round( ($line_tax / $line_total)*100, 1 ).' %';
				} else {
					$tax_rates[] = '-';
				}
			}

			$tax_rates = implode(' ,', $tax_rates );
		} else {
			// Backwards compatibility/fallback: calculate tax from line items
			if ( $line_total != 0) {
				$tax_rates = round( ($line_tax / $line_total)*100, 1 ).' %';
			} else {
				$tax_rates = '-';
			}
		}
		
		return $tax_rates;
	}

	/**
	 * Returns the percentage rate (float) for a given tax rate ID.
	 * @param  int    $rate_id  woocommerce tax rate id
	 * @return float  $rate     percentage rate
	 */
	public function get_tax_rate_by_id( $rate_id ) {
		global $wpdb;
		$rate = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $rate_id ) );
		return (float) $rate;
	}

	/**
	 * Returns a an array with rate_id => tax rate data (array) of all tax rates in woocommerce
	 * @return array  $tax_rate_ids  keyed by id
	 */
	public function get_tax_rate_ids() {
		global $wpdb;
		$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates" );

		$tax_rate_ids = array();
		foreach ($rates as $rate) {
			// var_dump($rate->tax_rate_id);
			// die($rate);
			$rate_id = $rate->tax_rate_id;
			unset($rate->tax_rate_id);
			$tax_rate_ids[$rate_id] = (array) $rate;
		}

		return $tax_rate_ids;
	}

	/**
	 * Returns the main product image ID
	 * Adapted from the WC_Product class
	 * (does not support thumbnail sizes)
	 *
	 * @access public
	 * @return string
	 */
	public function get_thumbnail_id ( $product ) {
		global $woocommerce;

		$product_id = WCX_Product::get_id( $product );

		if ( has_post_thumbnail( $product_id ) ) {
			$thumbnail_id = get_post_thumbnail_id ( $product_id );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$thumbnail_id = get_post_thumbnail_id ( $parent_id );
		} else {
			$thumbnail_id = false;
		}

		return $thumbnail_id;
	}

	/**
	 * Returns the thumbnail image tag
	 * 
	 * uses the internal WooCommerce/WP functions and extracts the image url or path
	 * rather than the thumbnail ID, to simplify the code and make it possible to
	 * filter for different thumbnail sizes
	 *
	 * @access public
	 * @return string
	 */
	public function get_thumbnail ( $product ) {
		// Get default WooCommerce img tag (url/http)
		$size = apply_filters( 'wpo_wcpdf_thumbnail_size', 'shop_thumbnail' );
		$thumbnail_img_tag_url = $product->get_image( $size, array( 'title' => '' ) );
		
		// Extract the url from img
		preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $thumbnail_img_tag_url, $thumbnail_url );
		// remove http/https from image tag url to avoid mixed origin conflicts
		$thumbnail_url = array_pop($thumbnail_url);
		$contextless_thumbnail_url = str_replace(array('http://','https://'), '', $thumbnail_url );
		$contextless_site_url = str_replace(array('http://','https://'), '', trailingslashit(get_site_url()));

		// convert url to path
		$thumbnail_path = str_replace( $contextless_site_url, ABSPATH, $contextless_thumbnail_url);
		// fallback if thumbnail file doesn't exist
		if (apply_filters('wpo_wcpdf_use_path', true) && !file_exists($thumbnail_path)) {
			if ($thumbnail_id = $this->get_thumbnail_id( $product ) ) {
				$thumbnail_path = get_attached_file( $thumbnail_id );
			}
		}

		// Thumbnail (full img tag)
		if (apply_filters('wpo_wcpdf_use_path', true) && file_exists($thumbnail_path)) {
			// load img with server path by default
			$thumbnail = sprintf('<img width="90" height="90" src="%s" class="attachment-shop_thumbnail wp-post-image">', $thumbnail_path );
		} else {
			// load img with http url when filtered
			$thumbnail = $thumbnail_img_tag_url;
		}

		// die($thumbnail);
		return $thumbnail;
	}

	/**
	 * Return the order totals listing
	 */
	public function get_woocommerce_totals() {
		// get totals and remove the semicolon
		$totals = apply_filters( 'wpo_wcpdf_raw_order_totals', $this->order->get_order_item_totals(), $this->order );
		
		// remove the colon for every label
		foreach ( $totals as $key => $total ) {
			$label = $total['label'];
			$colon = strrpos( $label, ':' );
			if( $colon !== false ) {
				$label = substr_replace( $label, '', $colon, 1 );
			}		
			$totals[$key]['label'] = $label;
		}

		// WC2.4 fix order_total for refunded orders
		// not if this is the actual refund!
		if ( ! $this->is_refund( $this->order ) ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.4', '>=' ) && isset($totals['order_total']) ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
					$tax_display = get_option( 'woocommerce_tax_display_cart' );
				} else {
					$tax_display = WCX_Order::get_prop( $this->order, 'tax_display_cart' );
				}

				$totals['order_total']['value'] = wc_price( $this->order->get_total(), array( 'currency' => WCX_Order::get_prop( $this->order, 'currency' ) ) );
				$order_total    = $this->order->get_total();
				$tax_string     = '';

				// Tax for inclusive prices
				if ( wc_tax_enabled() && 'incl' == $tax_display ) {
					$tax_string_array = array();
					if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
						foreach ( $this->order->get_tax_totals() as $code => $tax ) {
							$tax_amount         = $tax->formatted_amount;
							$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
						}
					} else {
						$tax_string_array[] = sprintf( '%s %s', wc_price( $this->order->get_total_tax() - $this->order->get_total_tax_refunded(), array( 'currency' => $this->order->get_order_currency() ) ), WC()->countries->tax_or_vat() );
					}
					if ( ! empty( $tax_string_array ) ) {
						if ( version_compare( WOOCOMMERCE_VERSION, '2.6', '>=' ) ) {
							$tax_string = ' ' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
						} else {
							// use old capitalized string
							$tax_string = ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
						}
					}
				}

				$totals['order_total']['value'] .= $tax_string;
			}

			// remove refund lines (shouldn't be in invoice)
			foreach ( $totals as $key => $total ) {
				if ( strpos($key, 'refund_') !== false ) {
					unset( $totals[$key] );
				}
			}

		}

		return apply_filters( 'wpo_wcpdf_woocommerce_totals', $totals, $this->order, $this->get_type() );
	}
	
	/**
	 * Return/show the order subtotal
	 */
	public function get_order_subtotal( $tax = 'excl', $discount = 'incl' ) { // set $tax to 'incl' to include tax, same for $discount
		//$compound = ($discount == 'incl')?true:false;
		$subtotal = $this->order->get_subtotal_to_display( false, $tax );

		$subtotal = ($pos = strpos($subtotal, ' <small')) ? substr($subtotal, 0, $pos) : $subtotal; //removing the 'excluding tax' text			
		
		$subtotal = array (
			'label'	=> __('Subtotal', 'woocommerce-pdf-invoices-packing-slips' ),
			'value'	=> $subtotal, 
		);
		
		return apply_filters( 'wpo_wcpdf_order_subtotal', $subtotal, $tax, $discount, $this );
	}
	public function order_subtotal( $tax = 'excl', $discount = 'incl' ) {
		$subtotal = $this->get_order_subtotal( $tax, $discount );
		echo $subtotal['value'];
	}

	/**
	 * Return/show the order shipping costs
	 */
	public function get_order_shipping( $tax = 'excl' ) { // set $tax to 'incl' to include tax
		$shipping_cost = WCX_Order::get_prop( $this->order, 'shipping_total', 'view' );
		$shipping_tax = WCX_Order::get_prop( $this->order, 'shipping_tax', 'view' );

		if ($tax == 'excl' ) {
			$formatted_shipping_cost = $this->format_price( $shipping_cost );
		} else {
			$formatted_shipping_cost = $this->format_price( $shipping_cost + $shipping_tax );
		}

		$shipping = array (
			'label'	=> __('Shipping', 'woocommerce-pdf-invoices-packing-slips' ),
			'value'	=> $formatted_shipping_cost,
			'tax'	=> $this->format_price( $shipping_tax ),
		);
		return apply_filters( 'wpo_wcpdf_order_shipping', $shipping, $tax, $this );
	}
	public function order_shipping( $tax = 'excl' ) {
		$shipping = $this->get_order_shipping( $tax );
		echo $shipping['value'];
	}

	/**
	 * Return/show the total discount
	 */
	public function get_order_discount( $type = 'total', $tax = 'incl' ) {
		if ( $tax == 'incl' ) {
			switch ($type) {
				case 'cart':
					// Cart Discount - pre-tax discounts. (deprecated in WC2.3)
					$discount_value = $this->order->get_cart_discount();
					break;
				case 'order':
					// Order Discount - post-tax discounts. (deprecated in WC2.3)
					$discount_value = $this->order->get_order_discount();
					break;
				case 'total':
					// Total Discount
					if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) >= 0 ) {
						$discount_value = $this->order->get_total_discount( false ); // $ex_tax = false
					} else {
						// WC2.2 and older: recalculate to include tax
						$discount_value = 0;
						$items = $this->order->get_items();;
						if( sizeof( $items ) > 0 ) {
							foreach( $items as $item ) {
								$discount_value += ($item['line_subtotal'] + $item['line_subtotal_tax']) - ($item['line_total'] + $item['line_tax']);
							}
						}
					}

					break;
				default:
					// Total Discount - Cart & Order Discounts combined
					$discount_value = $this->order->get_total_discount();
					break;
			}
		} else { // calculate discount excluding tax
			if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) >= 0 ) {
				$discount_value = $this->order->get_total_discount( true ); // $ex_tax = true
			} else {
				// WC2.2 and older: recalculate to exclude tax
				$discount_value = 0;

				$items = $this->order->get_items();;
				if( sizeof( $items ) > 0 ) {
					foreach( $items as $item ) {
						$discount_value += ($item['line_subtotal'] - $item['line_total']);
					}
				}
			}
		}

		$discount = array (
			'label'		=> __('Discount', 'woocommerce-pdf-invoices-packing-slips' ),
			'value'		=> $this->format_price( $discount_value ),
			'raw_value'	=> $discount_value,
		);

		if ( round( $discount_value, 3 ) != 0 ) {
			return apply_filters( 'wpo_wcpdf_order_discount', $discount, $type, $tax, $this );
		}
	}
	public function order_discount( $type = 'total', $tax = 'incl' ) {
		$discount = $this->get_order_discount( $type, $tax );
		echo $discount['value'];
	}

	/**
	 * Return the order fees
	 */
	public function get_order_fees( $tax = 'excl' ) {
		if ( $_fees = $this->order->get_fees() ) {
			foreach( $_fees as $id => $fee ) {
				if ($tax == 'excl' ) {
					$fee_price = $this->format_price( $fee['line_total'] );
				} else {
					$fee_price = $this->format_price( $fee['line_total'] + $fee['line_tax'] );
				}

				$fees[ $id ] = array(
					'label' 		=> $fee['name'],
					'value'			=> $fee_price,
					'line_total'	=> $this->format_price( $fee['line_total'] ),
					'line_tax'		=> $this->format_price( $fee['line_tax'] )
				);
			}
			return $fees;
		}
	}
	
	/**
	 * Return the order taxes
	 */
	public function get_order_taxes() {
		$tax_label = __( 'VAT', 'woocommerce-pdf-invoices-packing-slips' ); // register alternate label translation
		$tax_label = __( 'Tax rate', 'woocommerce-pdf-invoices-packing-slips' );
		$tax_rate_ids = $this->get_tax_rate_ids();
		if ( $order_taxes = $this->order->get_taxes() ) {
			foreach ( $order_taxes as $key => $tax ) {
				if ( WCX::is_wc_version_gte_3_0() ) {
					$taxes[ $key ] = array(
						'label'					=> $tax->get_label(),
						'value'					=> $this->format_price( $tax->get_tax_total() + $tax->get_shipping_tax_total() ),
						'rate_id'				=> $tax->get_rate_id(),
						'tax_amount'			=> $tax->get_tax_total(),
						'shipping_tax_amount'	=> $tax->get_shipping_tax_total(),
						'rate'					=> isset( $tax_rate_ids[ $tax->get_rate_id() ] ) ? ( (float) $tax_rate_ids[$tax->get_rate_id()]['tax_rate'] ) . ' %': '',
					);
				} else {
					$taxes[ $key ] = array(
						'label'					=> isset( $tax[ 'label' ] ) ? $tax[ 'label' ] : $tax[ 'name' ],
						'value'					=> $this->format_price( ( $tax[ 'tax_amount' ] + $tax[ 'shipping_tax_amount' ] ) ),
						'rate_id'				=> $tax['rate_id'],
						'tax_amount'			=> $tax['tax_amount'],
						'shipping_tax_amount'	=> $tax['shipping_tax_amount'],
						'rate'					=> isset( $tax_rate_ids[ $tax['rate_id'] ] ) ? ( (float) $tax_rate_ids[$tax['rate_id']]['tax_rate'] ) . ' %': '',
					);
				}

			}
			
			return apply_filters( 'wpo_wcpdf_order_taxes', $taxes, $this );
		}
	}

	/**
	 * Return/show the order grand total
	 */
	public function get_order_grand_total( $tax = 'incl' ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1' ) >= 0 ) {
			// WC 2.1 or newer is used
			$total_unformatted = $this->order->get_total();
		} else {
			// Backwards compatibility
			$total_unformatted = $this->order->get_order_total();
		}

		if ($tax == 'excl' ) {
			$total = $this->format_price( $total_unformatted - $this->order->get_total_tax() );
			$label = __( 'Total ex. VAT', 'woocommerce-pdf-invoices-packing-slips' );
		} else {
			$total = $this->format_price( ( $total_unformatted ) );
			$label = __( 'Total', 'woocommerce-pdf-invoices-packing-slips' );
		}
		
		$grand_total = array(
			'label' => $label,
			'value'	=> $total,
		);			

		return apply_filters( 'wpo_wcpdf_order_grand_total', $grand_total, $tax, $this );
	}
	public function order_grand_total( $tax = 'incl' ) {
		$grand_total = $this->get_order_grand_total( $tax );
		echo $grand_total['value'];
	}


	/**
	 * Return/Show shipping notes
	 */
	public function get_shipping_notes() {
		if ( $this->is_refund( $this->order ) ) {
			// return reason for refund if order is a refund
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
				$shipping_notes = $this->order->get_reason();
			} elseif ( method_exists($this->order, 'get_refund_reason') ) {
				$shipping_notes = $this->order->get_refund_reason();
			} else {
				$shipping_notes = wpautop( wptexturize( WCX_Order::get_prop( $this->order, 'customer_note', 'view' ) ) );
			}
		} else {
			$shipping_notes = wpautop( wptexturize( WCX_Order::get_prop( $this->order, 'customer_note', 'view' ) ) );
		}
		return apply_filters( 'wpo_wcpdf_shipping_notes', $shipping_notes, $this );
	}
	public function shipping_notes() {
		echo $this->get_shipping_notes();
	}

	/**
	 * wrapper for wc_price, ensuring currency is always passed
	 */
	public function format_price( $price, $args = array() ) {
		if ( function_exists( 'wc_price' ) ) { // WC 2.1+
			$args['currency'] = WCX_Order::get_prop( $this->order, 'currency' );
			$formatted_price = wc_price( $price, $args );
		} else {
			$formatted_price = woocommerce_price( $price );
		}

		return $formatted_price;
	}

	/**
	 * Gets price - formatted for display.
	 *
	 * @access public
	 * @param mixed $item
	 * @return string
	 */
	public function get_formatted_item_price ( $item, $type, $tax_display = '' ) {
		if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) {
			return;
		}

		$divide_by = ($type == 'single' && $item['qty'] != 0 )?$item['qty']:1; //divide by 1 if $type is not 'single' (thus 'total')
		if ( $tax_display == 'excl' ) {
			$item_price = $this->format_price( ($this->order->get_line_subtotal( $item )) / $divide_by );
		} else {
			$item_price = $this->format_price( ($this->order->get_line_subtotal( $item, true )) / $divide_by );
		}

		return $item_price;
	}

	public function get_invoice_number() {
		// Call the woocommerce_invoice_number filter and let third-party plugins set a number.
		// Default is null, so we can detect whether a plugin has set the invoice number
		$third_party_invoice_number = apply_filters( 'woocommerce_invoice_number', null, $this->order_id );
		if ($third_party_invoice_number !== null) {
			return $third_party_invoice_number;
		}

		if ( $invoice_number = $this->get_number('invoice') ) {
			return $formatted_invoice_number = $invoice_number->formatted_number;
		} else {
			return '';
		}
	}

	public function invoice_number() {
		echo $this->get_invoice_number();
	}

	public function get_invoice_date() {
		if ( $invoice_date = $this->get_date('invoice') ) {
			return $invoice_date->date_i18n( apply_filters( 'wpo_wcpdf_date_format', wc_date_format(), $this ) );
		} else {
			return '';
		}
	}

	public function invoice_date() {
		echo $this->get_invoice_date();
	}


}

endif; // class_exists