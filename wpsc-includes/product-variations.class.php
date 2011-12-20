<?php

class WPSC_Product_Variations
{
	private static $instances = array();
	public static function &get_instance( $product_id ) {
		if ( ! array_key_exists( $product_id, self::$instances ) )
			self::$instances[$product_id] = new WPSC_Product_Variations( $product_id );

		return self::$instances[$product_id];
	}

	private $variation_sets = array();
	private $variation_terms = array();
	private $variations = null;
	private $product_id;
	private $sale_from_prices = null;
	private $original_from_prices = null;

	private function __construct( $product_id ) {
		$this->product_id = $product_id;
		$terms = wp_get_object_terms( $product_id, 'wpsc-variation' );
		foreach ( $terms as $term ) {
			if ( $term->parent == 0 ) {
				$this->variation_sets[$term->term_id] = $term->name;
			}
			else {
				if ( ! array_key_exists( $term->parent, $this->variation_terms ) )
					$this->variation_terms[$term->parent] = array();

				$this->variation_terms[$term->parent][$term->term_id] = $term->name;
			}
		}
	}

	public function get_variation_sets() {
		return $this->variation_sets;
	}

	public function get_variation_terms( $variation_set_id = 0 ) {
		if ( empty( $variation_set_id ) )
			return $this->variation_terms;

		return $this->variation_terms[$variation_set_id];
	}

	public function get_variation_set_dropdown( $variation_set_id ) {
		if ( ! array_key_exists( $variation_set_id, $this->variation_terms ) )
			return '';

		$product_id = esc_attr( $this->product_id );
		$classes = apply_filters( 'wpsc_get_product_variation_set_dropdown_classes', array( 'wpsc-product-variation-dropdown' ), $variation_set_id, $this->product_id );
		$classes = implode( ' ', $classes );
		$output = "<select name='wpsc_product_variation' id='wpsc-product-{$product_id}-{$variation_set_id}' class='{$classes}'>";
		foreach ( $this->variation_terms[$variation_set_id] as $variation_term_id => $variation_term_title ) {
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
		if ( is_array( $this->sale_from_prices ) && is_array( $this->original_from_prices ) )
			return;

		$this->sale_from_prices = array();
		$this->original_from_prices = array();

		global $wpdb;
		$joins = array(
			"INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_price'",
		);

		$selects = array(
			'pm.meta_value AS price',
		);

		$joins[] = "INNER JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_special_price'";
		$selects[] = 'pm2.meta_value AS special_price';

		$joins = implode( ' ', $joins );
		$selects = implode( ', ', $selects );

		$sql = $wpdb->prepare( "
			SELECT pm.meta_value AS price, pm2.meta_value AS sale_price
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_price'
			INNER JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_special_price'
			WHERE
				p.post_type = 'wpsc-product'
				AND
				p.post_parent = %d
		", $this->product_id );

		$results = $wpdb->get_results( $sql );

		foreach ( $results as $row ) {
			$original_price = (float) $row->price;
			$sale_price = (float) $row->sale_price;

			if ( $sale_price > 0 && $sale_price < $original_price )
				$this->sale_from_prices[] = $sale_price;
			else
				$this->original_from_prices[] = $original_price;
		}

		sort( $this->sale_from_prices );
		sort( $this->original_from_prices );
	}

	public function get_sale_from_price( $return_type = 'string' ) {
		$this->fetch_variation_prices();
		$prices_count = count( $this->sale_from_prices );
		$sale_price = count( $this->sale_from_prices ) > 0 ? $this->sale_from_prices[0] : 0;

		if ( $return_type == 'string' ) {
			$return = wpsc_format_price( $sale_price );
			if ( $prices_count > 1 && $sale_price != $this->sale_from_prices[$prices_count - 1] )
				$return = sprintf( esc_html__( 'from %s', 'wpsc' ), $return );
		}
		else {
			$return = (float) $sale_price;
		}
		return $return;
	}

	public function get_original_from_price( $return_type = 'string' ) {
		$this->fetch_variation_prices();
		$prices_count = count( $this->original_from_prices );
		$original_price = $prices_count > 0 ? $this->original_from_prices[0] : 0;

		if ( $return_type == 'string' ) {
			$return = wpsc_format_price( $original_price );
			if ( $prices_count  > 1 && $original_price != $this->original_from_prices[$prices_count - 1] )
				$return = sprintf( esc_html__( 'from %s', 'wpsc' ), $return );
		}
		else {
			$return = (float) $original_price;
		}

		return $return;
	}

	public function is_on_sale() {
		$this->fetch_variation_prices();
		if ( count( $this->sale_from_prices ) > 0 )
			return true;

		return false;
	}

	public function is_out_of_stock() {
		global $wpdb;

		if ( ! $this->has_variations() )
			return false;

		foreach ( $this->variations as $variation ) {
			if ( ! wpsc_is_product_out_of_stock( $variation->ID ) )
				return false;
		}

		return true;
	}
}