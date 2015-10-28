<?php

class WPSC_Product_Variations {

	private $variation_sets     = array();
	private $variation_terms    = array();
	private $variations         = null;
	private $product_id;
	private $prices             = null;
	private $sale_from_prices   = null;
	private static $instances   = array();

	public static function &get_instance( $product_id ) {
		if ( ! array_key_exists( $product_id, self::$instances ) ) {
			self::$instances[ $product_id ] = new WPSC_Product_Variations( $product_id );
		}

		return self::$instances[ $product_id ];
	}


	private function __construct( $product_id ) {
		$this->product_id = $product_id;
		$terms = wpsc_get_product_terms( $product_id, 'wpsc-variation' );
		foreach ( $terms as $term ) {
			if ( $term->parent == 0 ) {
				$this->variation_sets[ $term->term_id ] = $term->name;
			}
			else {
				if ( ! array_key_exists( $term->parent, $this->variation_terms ) ) {
					$this->variation_terms[ $term->parent ] = array();
				}

				$this->variation_terms[ $term->parent ] [$term->term_id ] = $term->name;
			}
		}
	}

	public function get_variation_sets() {
		return $this->variation_sets;
	}

	public function get_variation_terms( $variation_set_id = 0 ) {
		if ( empty( $variation_set_id ) ) {
			return $this->variation_terms;
		}

		return $this->variation_terms[ $variation_set_id ];
	}

	public function get_variation_set_dropdown( $variation_set_id ) {
		if ( ! array_key_exists( $variation_set_id, $this->variation_terms ) ) {
			return '';
		}

		$product_id = esc_attr( $this->product_id );
		$classes    = apply_filters( 'wpsc_get_product_variation_set_dropdown_classes', array( 'wpsc-product-variation-dropdown' ), $variation_set_id, $this->product_id );
		$classes    = implode( ' ', $classes );
		$output     = "<select name='wpsc_product_variations[{$variation_set_id}]' id='wpsc-product-{$product_id}-{$variation_set_id}' class='{$classes}'>";

		foreach ( $this->variation_terms[ $variation_set_id ] as $variation_term_id => $variation_term_title ) {
			$label = esc_attr( $variation_term_title );
			$output .= "<option value='{$variation_term_id}'>{$label}</option>";
		}

		$output .= "</select>";

		return apply_filters( 'wpsc_get_product_variation_set_dropdown', $output, $variation_set_id, $this->product_id );
	}

	public function has_variations() {
		$product = get_post( $this->product_id );

		if ( $product->post_parent ) {
			$this->variations = array();
			return false;
		}

		if ( is_null( $this->variations ) ) {
			$this->variations = get_posts( array(
				'post_type'   => 'wpsc-product',
				'post_parent' => $this->product_id,
				'post_status' => 'inherit',
			) );
		}

		return count( $this->variations ) > 0 && count( $this->variation_sets ) > 0;
	}

	private function fetch_variation_prices() {

		if ( is_array( $this->prices ) ) {
			return;
		}

		$this->sale_from_prices = array();

		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT pm.meta_value AS price, pm2.meta_value AS sale_price
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_price'
			LEFT JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_special_price'
			WHERE
				p.post_type = 'wpsc-product'
				AND
				p.post_parent = %d
		", $this->product_id );

		$this->prices = $wpdb->get_results( $sql );
	}

	private function sort_prices( $price_type ) {
		$prices = wp_list_pluck( $this->prices, $price_type );
		$prices = array_filter( $prices );
		$prices = array_map( 'floatval', $prices );
		sort( $prices );
		return $prices;
	}

	public function get_sale_from_price( $return_type = 'string' ) {
		$this->fetch_variation_prices();

		$sale_prices = $this->sort_prices( 'sale_price' );
		$first       = $sale_prices[0];
		$count       = count( $sale_prices );
		$last        = $sale_prices[$count - 1];

		if ( 'string' == $return_type ) {
			$return = wpsc_format_currency( $first );
			if ( $count > 1 && $first != $last ) {
				$return = sprintf( esc_html__( 'from %s', 'wp-e-commerce' ), $return );
			}
		} else {
			$return = $first;
		}

		return $return;
	}

	public function get_original_from_price( $return_type = 'string' ) {
		$this->fetch_variation_prices();

		$prices = $this->sort_prices( 'price' );
		$first  = $prices[0];
		$count  = count( $prices );
		$last   = $prices[ count( $prices ) - 1 ];

		if ( $return_type == 'string' ) {
			$return = wpsc_format_currency( $first );

			if ( $count  > 1 && $first != $last ) {
				$return = sprintf( esc_html__( 'from %s', 'wp-e-commerce' ), $return );
			}
		} else {
			$return = (float) $first;
		}

		return $return;
	}

	/**
	 * This seems...unfinished.  Need to investigate usage.
	 *
	 * @todo  Investigate intended usage, as this appears unfinished.
	 * @param  string $format [description]
	 * @return [type]         [description]
	 */
	public function get_you_save( $format = '%1$d (%2$d)' ) {
		$diffs = array();
		$diff_percents = array();

		foreach ( $this->prices as $item ) {
			if ( $item->sale_price ) {
				$diff = (float) $item->price - (float) $item->sale_price;
				$diffs[] = $diff;
				$diff_percent[] = $diff / $item->price * 100;
			}
		}

		sort( $diffs );
		sort( $diff_percents );

		switch ( $format ) {
			case 'number':
				$output = (float) $diffs[0];
				break;

			case 'percentage':
			case 'percent':
				$output = (float) round( $diff_percents[0] );
				break;

			default:
				$output = sprintf(
					$format,
					wpsc_format_currency( $diffs[0] ),
					$diff_percents[0]
				);
		}

		$first = $diff[0];
		$count = count( $diff );
		$last  = $diff[$count - 1];

	}

	public function is_on_sale() {
		$this->fetch_variation_prices();

		if ( count( $this->sale_from_prices ) > 0 ) {
			return true;
		}

		return false;
	}

	public function is_out_of_stock() {

		if ( ! $this->has_variations() ) {
			return false;
		}

		foreach ( $this->variations as $variation ) {
			if ( ! wpsc_is_product_out_of_stock( $variation->ID ) ) {
				return false;
			}
		}

		return true;
	}
}