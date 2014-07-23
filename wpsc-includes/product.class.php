<?php

/**
 * Product class.
 *
 * This is basically a wrapper for WP_Post, but with additional methods that are
 * specific for wpsc-product post type.
 *
 * @since 3.8.14
 *
 * @property-read array   $variation_sets          Variation sets assigned to this product
 * @property-read array   $variation_terms         Variation terms assigned to this product
 * @property-read array   $variations              This product's children (variations)
 * @property-read boolean $has_variations          Whether this product has variations
 * @property-read boolean $has_various_prices      Whether this product's variations have different prices
 * @property-read boolean $has_various_sale_prices Whether this product's variations have different sale prices.
 * @property-read boolean $has_various_savings     Whether the saving percentage of this product's variations are different.
 * @property-read boolean $is_on_sale              Whether this product is currently on sale
 * @property-read float   $sale_price              This product's sale price
 * @property-read float   $price                   This product's price
 * @property-read float   $saving                  This product's saving amount
 * @property-read float   $saving_percent          This product's saving percentage
 * @property-read float   $has_limited_stock       Whether this product has limited stock
 * @property-read float   $has_stock               Whether this product has stock
 * @property-read int     $all_stock               Total inventory of this product
 * @property-read int     $claimed_stock           Total claimed stock of this product
 * @property-read int     $stock                   Total available stock of this product
 * @property-read float   $sales                   Sales stats for this product
 * @property-read float   $earnings                Earnings stats for this product
 */
class WPSC_Product {

	/**
	 * Cache for instances of this class
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Get an instance of this class, using a post object or a post ID
	 *
	 * @since  0.1
	 * @param  int|WP_Post $post Post ID or WP_Post instance
	 * @return WPSC_Product
	 */
	public static function get_instance( $post ) {
		$id = $post;

		if ( is_object( $post ) ) {
			$id = $post->ID;
		}

		if ( ! isset( self::$instances[ $id ] ) ) {
			self::$instances[ $id ] = new WPSC_Product( $id );
		}

		return self::$instances[ $id ];
	}

	/**
	 * The post object (an instance of WP_Post)
	 *
	 * @since 3.8.14
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Variation sets assigned to this product
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $variation_sets;

	/**
	 * Variation terms assigned to this product
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $variation_terms;

	/**
	 * This product's children (variations)
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $variations;

	/**
	 * Whether this product has variations
	 *
	 * @since 3.8.14
	 * @var boolean
	 */
	private $has_variations;

	/**
	 * Whether this product's variations have different prices
	 *
	 * This is useful when displaying the price to the front-end. E.g. whether to
	 * display the prices as "From: $xx.xx" or just "$xx.xx" because all variations
	 * have the same price.
	 *
	 * @since 3.8.14
	 * @var boolean
	 */
	private $has_various_prices;

	/**
	 * Whether this product's variations have different sale prices.
	 *
	 * See {@link WPSC_Post::$has_various_prices} for more info.
	 *
	 * @since 3.8.14
	 * @var boolean
	 */
	private $has_various_sale_prices;

	/**
	 * Whether the saving percentage of this product's variations are different.
	 *
	 * See {@link WPSC_Post::$has_various_prices} for more info.
	 *
	 * @since 3.8.14
	 * @var boolean
	 */
	private $has_various_savings;

	/**
	 * Whether this product is current on sale.
	 *
	 * @since 3.8.14
	 * @var boolean
	 */
	private $is_on_sale;

	/**
	 * This product's sale price.
	 *
	 * Can also be the minimum sale price of all this product's variations.
	 *
	 * @since 3.8.14
	 * @var float
	 */
	private $sale_price;

	/**
	 * This product's price.
	 *
	 * Can also be the minimum price of all this product's variations.
	 *
	 * @since 3.8.14
	 * @var float
	 */
	private $price;

	/**
	 * This product's saving amount.
	 *
	 * Can also be the minimum saving amount of all this product's variations.
	 *
	 * @since 3.8.14
	 * @var  float
	 */
	private $saving;

	/**
	 * This product's saving percentage.
	 *
	 * Can also be the minimum saving percentage of all this product's variations.
	 *
	 * @since 3.8.14
	 * @var float
	 */
	private $saving_percent;

	/**
	 * Whether this product has limited stock.
	 *
	 * If this product has variations, is true when one of the variations has
	 * limited stock.
	 *
	 * @since 3.8.14
	 * @var bool
	 */
	private $has_limited_stock;

	/**
	 * Whether this product has stock.
	 *
	 * If this product has variations, is true when one of the variations has stock.
	 *
	 * @since 3.8.14
	 * @var bool
	 */
	private $has_stock;

	/**
	 * All stocks currently in inventory for this product.
	 *
	 * If this product has variations, this is the total inventory of all variations.
	 *
	 * @since 3.8.14
	 * @var int
	 */
	private $all_stock;

	/**
	 * Claimed stock for this product.
	 *
	 * If this product has variations, this is the total claimed stock of all variations.
	 *
	 * @since 3.8.14
	 * @var int
	 */
	private $claimed_stock;

	/**
	 * Available stock for this product (All stock - Claimed stock).
	 *
	 * If this product has variations, this is the total available stock of all variations.
	 *
	 * @since 3.8.14
	 * @var int
	 */
	private $stock;

	/**
	 * Sales and earnings for this product.
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $stats;

	/**
	 * Earnings stats for this product.
	 *
	 * @since 3.8.14
	 * @var float
	 */
	private $earnings;

	/**
	 * Sales stats for this product.
	 *
	 * @since 3.8.14
	 * @var float
	 */
	private $sales;

	/**
	 * Magic properties
	 *
	 * @since 3.8.14
	 * @param  string $name Name of property
	 * @return mixed        Value
	 */
	public function __get( $name ) {

		// Properties are not initialized by default, instead, they are lazy-loaded on demand
		if ( ! isset( $this->$name ) ) {

			// lazy load variations
			if ( in_array( $name, array(
				'has_variations',
				'variations',
				'variation_sets',
				'variation_terms',
			) ) ) {
				$this->fetch_variations();
			}

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
			) ) ) {
				$this->process_prices();
			}

			// lazy load stocks
			if ( in_array( $name, array(
				'has_stock',
				'stock',
				'all_stock',
				'claimed_stock',
				'has_limited_stock'
			) ) ) {
				$this->process_stocks();
			}

			// lazy load stats
			if ( in_array( $name, array(
				'sales',
				'earnings',
			) ) ) {
				$this->process_stats();
			}
		}

		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		return null;
	}

	/**
	 * Magic setters for 'sales' and 'earnings' properties
	 *
	 * @since 3.8.14
	 * @param string $name  Name of property
	 * @param mixed  $value Value of property
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, array(
			'sales',
			'earnings',
		) ) ) {
			$this->stats[ $name ] = $value;
			update_post_meta( $this->post->ID, '_wpsc_stats', $this->stats );
		}
	}

	/**
	 * Constructor
	 *
	 * @since 3.8.14
	 * @param int|WP_Post $post ID or Post object
	 */
	public function __construct( $post ) {
		if ( is_object( $post ) ) {
			$this->post = $post;
			return;
		}
		$this->post = WP_Post::get_instance( $post );
	}

	/**
	 * Does product exists
	 *
	 * @since 3.8.14.2
	 */
	public function exists() {
		return ! empty( $this->post );
	}

	/**
	 * Initialize stock properties
	 *
	 * @since 3.8.14
	 */
	private function process_stocks() {
		// Load all variations first
		$this->fetch_variations();

		if ( $this->has_variations ) {
			$this->process_variation_stocks();
		} else {
			$this->process_normal_stocks();
		}
	}

	/**
	 * Initialize stock properties in case this product has variations
	 *
	 * @since 3.8.14
	 */
	private function process_variation_stocks() {
		$this->has_limited_stock = false;
		$this->has_stock         = false;
		$this->all_stock         = 0;
		$this->claimed_stock     = 0;
		$this->stock             = 0;

		foreach ( $this->variations as $variation ) {
			if ( $variation->has_limited_stock ) {
				$this->has_limited_stock = true;
			}

			if ( $variation->has_stock ) {
				$this->has_stock = true;
			}

			$this->all_stock     += $variation->all_stock;
			$this->claimed_stock += $variation->claimed_stock;
			$this->stock         += $variation->stock;
		}
	}

	/**
	 * Initialize stock properties in case this product has no variations
	 *
	 * @since 3.8.14
	 */
	private function process_normal_stocks() {
		global $wpdb;

		$this->has_limited_stock = is_numeric( $this->post->_wpsc_stock );

		if ( ! $this->has_limited_stock ) {
			$this->all_stock     = 0;
			$this->claimed_stock = 0;
			$this->stock         = 0;
			$this->has_stock     = true;
			return;
		}

		$this->all_stock = (int) $this->post->_wpsc_stock;

		// TODO: implement caching for this query
		$claimed_stock_sql   = $wpdb->prepare( 'SELECT SUM(stock_claimed) FROM '.WPSC_TABLE_CLAIMED_STOCK.' WHERE product_id=%d', $this->post->ID );
		$this->claimed_stock = $wpdb->get_var( $claimed_stock_sql );
		$this->stock         = $this->all_stock - $this->claimed_stock;
		$this->has_stock     = $this->stock > 0;
	}

	/**
	 * Initialize price properties in case this product has no variations
	 *
	 * @since 3.8.14
	 */
	private function process_normal_prices() {
		$this->price      = (float) $this->post->_wpsc_price;
		$this->sale_price = (float) $this->post->_wpsc_special_price;
		$this->is_on_sale = $this->sale_price && ( $this->sale_price < $this->price );

		if ( $this->is_on_sale ) {
			$this->saving         = (float) $this->price - $this->sale_price;
			$this->saving_percent = round( $this->saving / $this->price * 100 );
		}
	}

	/**
	 * Initialize price properties in case this product has variations
	 *
	 * @since 3.8.14
	 */
	private function process_variation_prices() {
		// populate arrays of sale prices, original prices, and saving amounts
		$sales         = array();
		$originals     = array();
		$diffs         = array();
		$diff_percents = array();

		foreach ( $this->variations as $variation ) {
			// ignore variations that are not published
			if ( ! in_array( $variation->post->post_status, array( 'publish', 'inherit' ) ) ) {
				continue;
			}

			// initialize price properties for variation
			// note that we can't rely on lazy loading here because $variation->sale_price
			// is accessible from within this class, thus lazy loading is not
			// triggered automatically
			$variation->process_prices();

			$sale_price = (float) $variation->sale_price;
			$price      = (float) $variation->price;

			$is_variation_on_sale = $sale_price && ( $sale_price < $price );

			if ( $is_variation_on_sale ) {

				// store this sale price in an array so that later we can figure
				// out whether there are different sale prices across variations
				$sales[] = $sale_price;

				// only save the minimum sale price to $this->sale_price
				if ( is_null( $this->sale_price ) || $sale_price < $this->sale_price ) {
					$this->sale_price = $sale_price;
				}

				// saving amount
				$diff = $price - $sale_price;

				// saving percentage
				$diff_percent = round( $diff / $price * 100 );

				// store the saving and percentage into an array so that later
				// we'll see whether there are different amounts / percentages
				// across variations
				$diffs[]         = $diff;
				$diff_percents[] = $diff_percent;

				// only use the minimum saving and percentage for the parent object
				if ( is_null( $this->saving ) || $diff <= $this->saving ) {

					if ( $diff != $this->saving || ( $diff_percent < $this->saving_percent ) ) {
						$this->saving_percent = $diff_percent;
					}

					$this->saving = $diff;
				}
			}

			// well, you know the drill
			$originals[] = $price;

			// only use the minimum price for the parent object
			if ( is_null( $this->price ) || ( $price < $this->price ) ) {
				$this->price = $price;
			}
		}

		// see if we can use these min/max values with
		// "from $xx.xx" or "up to $xx.xx"
		$sales     = array_unique( $sales );
		$originals = array_unique( $originals );
		$diffs     = array_unique( $diffs );

		$this->is_on_sale              = ! is_null( $this->sale_price );
		$this->has_various_sale_prices = count( $sales ) > 1;
		$this->has_various_prices      = count( $originals ) > 1;
		$this->has_various_savings     = count( $diffs ) > 1;
	}

	/**
	 * Initialize price properties for this product
	 *
	 * @since 3.8.14
	 */
	private function process_prices() {
		$this->fetch_variations();

		if ( ! is_null( $this->price ) ) {
			return;
		}

		if ( ! $this->has_variations ) {
			$this->process_normal_prices();
		} else {
			$this->process_variation_prices();
		}
	}

	/**
	 * Initialize variation terms for this product
	 *
	 * @since 3.8.14
	 */
	private function fetch_variation_terms() {
		// don't do anything if this is already initialized
		if ( ! is_null( $this->variation_sets ) && ! is_null( $this->variation_terms ) ) {
			return;
		}

		$this->variation_terms = array();
		$this->variation_sets  = array();

		// get all the attached variation terms
		$terms = wpsc_get_product_terms( $this->post->ID, 'wpsc-variation' );

		foreach ( $terms as $term ) {
			// Terms with no parents are variation sets (e.g. Color, Size)
			if ( ! $term->parent ) {
				$this->variation_sets[ $term->term_id ] = $term->name;
			} else {
				// Terms with parents are called "variation terms" (e.g. Blue, Red or Large, Medium)
				if ( ! array_key_exists( $term->parent, $this->variation_terms ) ) {
					$this->variation_terms[ $term->parent ] = array();
				}

				$this->variation_terms[ $term->parent ][ $term->term_id ] = $term->name;
			}
		}
	}

	/**
	 * Initialize children products (variations)
	 *
	 * @since 3.8.14
	 */
	private function fetch_variations() {
		// don't do anything if this is already initialized
		if ( isset( $this->has_variations ) ) {
			return;
		}

		// If this object itself is a variation, it's "sterile"
		if ( $this->post->post_parent ) {
			$this->has_variations = false;
			$this->variations     = array();
			$this->variation_sets = array();
			return;
		}

		// get the posts
		$variation_posts = get_posts( array(
			'post_type'   => 'wpsc-product',
			'post_parent' => $this->post->ID,
			'post_status' => array( 'publish', 'inherit' ),
			'nopaging'    => true,
		) );

		// wrap these posts with WPSC_Product
		$this->variations = array_map(
			array( 'WPSC_Product', 'get_instance' ),
			// filter out trashed variations
			wp_list_filter(
				$variation_posts,
				array( 'post_status' => 'trash' ),
				'NOT'
			)
		);

		// Initialize the terms
		$this->fetch_variation_terms();

		$this->has_variations =
			   count( $this->variations ) > 0
			&& count( $this->variation_sets ) > 0;
	}

	/**
	 * Lazy load stats
	 *
	 * @since 3.8.14
	 */
	private function process_stats() {
		if ( $this->post ) {
			if ( ! property_exists( $this, '_wpsc_stats' ) || empty( $this->post->_wpsc_stats ) ) {
				$this->stats = WPSC_Purchase_Log::get_stats_for_product( $this->post->ID );
				update_post_meta( $this->post->ID, '_wpsc_stats', $this->stats );
			}
		}
	}

	/**
	 * Get more specific stats by providing an array of arguments
	 *
	 * @since 3.8.14
	 * @param  array|string $args Arguments. See {@link WPSC_Purchase_Log::fetch_stats()}
	 * @return array       'sales' and 'earnings' stats
	 */
	public function get_stats( $args = '' ) {
		return WPSC_Purchase_Log::get_stats_for_product( $this->post->ID, $args );
	}
}

/**
 * Collection of WPSC_Product objects
 *
 * Pass in the constructor an array containing arguments similar to WP_Query.
 *
 * @since 3.8.14
 * @property-read array $products An array containing the queried products
 * @property-read WP_Query $query The WP_Query object associated with this collection
 * @property-read int $sales The total sales of the products in this collection
 * @property-read float $earnings The total earnings of the products in this collection
 */
class WPSC_Products {
	/**
	 * An array containing the queried products
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $products;

	/**
	 * The WP_Query object associated with this collection
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $query;

	/**
	 * The arguments that are passed into $this->query
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $args;

	/**
	 * An associative array containing 'sales' and 'earnings' keys.
	 *
	 * @since 3.8.14
	 * @var array
	 */
	private $stats;

	/**
	 * Magic getters for the following properties:
	 *
	 * 	- $query
	 * 	- $products
	 * 	- $sales
	 * 	- $earnings
	 *
	 * @since 3.8.14
	 * @param  string $name Name of variable
	 * @return mixed        Value
	 */
	public function __get( $name ) {
		// Lazy load the query and products
		if ( in_array( $name, array(
			'query',
			'products',
		) ) ) {
			$this->fetch_products();
			return $this->products;
		}

		// Lazy load the sales and earnings
		if ( in_array( $name, array(
			'sales',
			'earnings',
		) ) ) {
			$this->process_stats();
			return $this->stats[ $name ];
		}

		return null;
	}

	/**
	 * Constructor of the WPSC_Products instance
	 *
	 * @since 3.8.14
	 * @param string $args Arguments. See {@link WP_Query::__construct()}.
	 */
	public function __construct( $args = '' ) {
		$defaults = array(
			'post_type' => 'wpsc-product',
			'nopaging'  => true,
		);

		$this->args  = wp_parse_args( $args, $defaults );
		$this->query = new WP_Query();
	}

	/**
	 * Fetch stats from the database
	 *
	 * @since 3.8.14
	 */
	private function process_stats() {
		// bail if this is already set
		if ( isset( $this->stats ) ) {
			return;
		}

		// get posts from the database
		$this->fetch_products();

		$args['products'] = $this->products;

		$this->stats = WPSC_Purchase_Log::fetch_stats( $args );
	}

	/**
	 * Get stats of the products, specifying some more arguments
	 *
	 * @since 3.8.14
	 * @param  array $args Arguments. See {@link WPSC_Purchase_Log::fetch_stats()}.
	 * @return array       'earnings' and 'sales' stats
	 */
	public function get_stats( $args ) {
		$this->fetch_products();

		$args['products'] = $this->products;

		return WPSC_Purchase_Log::fetch_stats( $args );
	}

	/**
	 * Fetch products from the database
	 *
	 * @since 3.8.14
	 */
	private function fetch_products() {
		if ( isset( $this->products ) ) {
			return;
		}

		// query the DB
		$this->query->query( $this->args );

		// wrap the WP_Post object into WPSC_Product objects
		$this->products = array_map( array( 'WPSC_Product', 'get_instance' ), $this->query->posts );
	}
}