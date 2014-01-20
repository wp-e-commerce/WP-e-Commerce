<?php

if ( class_exists( 'WPSC_Product' ) )
	return;

class WPSC_Product {
	private static $instances = array();
	public static function get_instance( $post ) {
		$id = $post;
		if ( is_object( $post ) )
			$id = $post->ID;

		if ( ! isset( self::$instances[$id] ) )
			self::$instances[$id] = new WPSC_Product( $id );

		return self::$instances[$id];
	}

	private $post;
	private $id;
	private $variation_sets;
	private $variation_terms;
	private $variations;
	private $has_variations;
	private $has_various_prices;
	private $has_various_sale_prices;
	private $has_various_savings;
	private $is_on_sale;
	private $sale_price;
	private $price;
	private $saving;
	private $saving_percent;

	public function __get( $name ) {
		if ( ! isset( $this->$name ) ) {
			// lazy load variations
			if ( in_array( $name, array(
				'has_variations',
				'variations',
				'variation_sets',
				'variation_terms',
			) ) )
				$this->fetch_variations();

			// lazy load prices
			if ( in_array( $name, array(
				'is_on_sale',
				'has_various_prices',
				'has_various_savings',
				'has_various_sale_prices',
				'sale_price',
				'price',
				'saving',
				'saving_percent',
			) ) )
				$this->process_prices();

			if ( in_array( $name, array(
				'has_stock',
				'stock',
				'all_stock',
				'claimed_stock',
				'has_limited_stock'
			) ) )
				$this->process_stocks();

		}

		if ( isset( $this->$name ) )
			return $this->$name;

		return null;
	}

	public function __construct( $post ) {
		if ( is_object( $post ) ) {
			$this->id = $post->ID;
			$this->post = $post;
			return;
		}
		$this->id = $post;
		$this->post = WP_Post::get_instance( $post );
	}

	private function process_stocks() {
		$this->fetch_variations();

		if ( $this->has_variations )
			$this->process_variation_stocks();
		else
			$this->process_normal_stocks();
	}

	private function process_variation_stocks() {
		foreach ( $this->variations as $variation ) {
			if ( ! $variation->has_stock )
				return false;
		}

		return true;
	}

	private function process_normal_stocks() {
		global $wpdb;

		$this->has_limited_stock = is_numeric( $this->post->_wpsc_stock );

		if ( ! $this->has_limited_stock ) {
			$this->all_stock = 0;
			$this->claimed_stock = 0;
			$this->stock = 0;
			$this->has_stock = true;
			return;
		}

		$this->all_stock = (int) $this->post->_wpsc_stock;
		$claimed_stock_sql = $wpdb->prepare( 'SELECT SUM(stock_claimed) FROM '.WPSC_TABLE_CLAIMED_STOCK.' WHERE product_id=%d', $this->id );
		$this->claimed_stock = $wpdb->get_var( $claimed_stock_sql );
		$this->stock = $this->all_stock - $this->claimed_stock;
		$this->has_stock = $this->stock > 0;
	}

	private function process_normal_prices() {
		$this->price = (float) $this->post->_wpsc_price;
		$this->sale_price = (float) $this->post->_wpsc_special_price;
		$this->is_on_sale =    $this->sale_price
		                    && $this->sale_price < $this->price;

		if ( $this->is_on_sale ) {
			$this->saving = (float) $this->price - $this->sale_price;
			$this->saving_percent = round( $this->saving / $this->price * 100 );
		}
	}

	private function process_variation_prices() {
		// populate arrays of sale prices, original prices, and saving amounts
		$sales = array();
		$originals = array();
		$diffs = array();
		$diff_percents = array();

		foreach ( $this->variations as $variation ) {
			if ( ! in_array( $variation->post->post_status, array( 'publish', 'inherit' ) ) )
				continue;

			$variation->process_prices();
			$sale_price = $variation->sale_price;
			$price = (float) $variation->price;

			$is_variation_on_sale =
				   $sale_price
				&& $sale_price < $price;

			$sale_price = (float) $sale_price;

			if ( $is_variation_on_sale ) {
				$sales[] = $sale_price;

				if (    is_null( $this->sale_price )
				     || $sale_price < $this->sale_price )
					$this->sale_price = $sale_price;

				$diff = $price - $sale_price;

				if ( ! $diff )
					continue;

				$diff_percent = round( $diff / $price * 100 );
				$diffs[] = $diff;
				$diff_percents[] = $diff_percent;

				if (    is_null( $this->saving )
					 || $diff <= $this->saving ) {

					if (     $diff != $this->saving
						  || $diff_percent < $this->saving_percent )
						$this->saving_percent = $diff_percent;

					$this->saving = $diff;
				}
			}

			$originals[] = $price;

			if (    is_null( $this->price )
			     || $price < $this->price )
				$this->price = $price;
		}

		// see if we can use these min/max values with
		// "from $xx.xx" or "up to $xx.xx"
		$sales = array_unique( $sales );
		$originals = array_unique( $originals );
		$diffs = array_unique( $diffs );
		$this->is_on_sale = ! is_null( $this->sale_price );
		$this->has_various_sale_prices = count( $sales ) > 1;
		$this->has_various_prices = count( $originals ) > 1;
		$this->has_various_savings = count( $diffs ) > 1;
	}

	private function process_prices() {
		$this->fetch_variations();

		if ( ! is_null( $this->price ) )
			return;

		if ( ! $this->has_variations )
			$this->process_normal_prices();
		else
			$this->process_variation_prices();
	}

	private function fetch_variation_terms() {
		if ( ! is_null( $this->variation_sets ) && ! is_null( $this->variation_terms ) )
			return;

		$this->variation_terms = array();
		$this->variation_sets = array();

		$terms = wpsc_get_product_terms( $this->id, 'wpsc-variation' );

		foreach ( $terms as $term ) {
			if ( ! $term->parent ) {
				$this->variation_sets[$term->term_id] = $term->name;
			} else {
				if ( ! array_key_exists( $term->parent, $this->variation_terms ) )
					$this->variation_terms[$term->parent] = array();

				$this->variation_terms[$term->parent][$term->term_id] = $term->name;
			}
		}
	}

	private function fetch_variations() {
		if ( isset( $this->has_variations ) )
			return;

		if ( $this->post->post_parent ) {
			$this->has_variations = false;
			$this->variations = array();
			$this->variation_sets = array();
			return;
		}

		if ( ! is_null( $this->variations ) )
			return;

		$variation_posts = get_posts( array(
			'post_type'   => 'wpsc-product',
			'post_parent' => $this->id,
			'post_status' => array( 'publish', 'inherit' ),
			'nopaging'    => true,
		) );

		$this->variations = array_map(
			array( 'WPSC_Product', 'get_instance' ),
			wp_list_filter(
				$variation_posts,
				array( 'post_status' => 'trash' ),
				'NOT'
			)
		);

		$this->fetch_variation_terms();

		$this->has_variations =
			   count( $this->variations ) > 0
			&& count( $this->variation_sets ) > 0;
	}
}