<?php

/**
 * Commands for working with WP e-Commerce product categories.
 *
 * @since  3.11.5
 *
 * @todo This is fairly generic, and doesn't support WP e-Commerce specific category values such as:
 *   * Category images
 *   * Product display setting
 *   * Thumbnail size
 *   * Target market restrictions
 *   * Checkout settings
 */
class WPSC_WP_CLI_Category_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'stdClass';
	protected $obj_fields = array(
		'term_id',
		'name',
		'slug',
		'parent',
		'count',
	);

	/**
	 * Get a list of product categories.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpsc-category list
	 *
	 *     wp wpsc-category list --format=csv
	 *
	 * @subcommand list
	 * @synopsis
	 */
	function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );
		$args = array(
			'number'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'fields'     => 'all',
			'hide_empty' => false,
		);

		if ( 'ids' == $formatter->format ) {
			$args['fields'] = 'ids';
		}

		$terms = get_terms( 'wpsc_product_category', $args );

		if ( is_wp_error( $terms ) ) {
			WP_CLI::error( __( "Couldn't retrieve categories.", 'wpsc' ) );
		} elseif ( ! count( $terms ) ) {
			WP_CLI::log( __( 'No categories found.', 'wpsc' ) );
		}

		if ( 'ids' == $formatter->format ) {
			echo implode( ' ', $terms );
		} else {
			$formatter->display_items( $terms );
		}
	}

	/**
	 * Get a single category.
	 *
	 * ## OPTIONS
	 *
	 * <category>
	 * : Category ID or slug.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpsc-category get 12
	 *
	 *     wp wpsc-category get example-category --format=json
	 */
	function get( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Work out how we're searching for the term
		$fetch_by = 'id';
		if ( ! is_numeric( $args[0] ) ) {
			$fetch_by = 'slug';
		}
		$fetch = $args[0];

		$term = get_term_by( $fetch_by, $fetch, 'wpsc_product_category' );

		if ( false === $term ) {
			WP_CLI::error( __( "Couldn't get category.", 'wpsc' ) );
		}

		if ( 'ids' == $formatter->format ) {
			echo $term->term_id;
		} else {
			$formatter->display_items( array( $term ) );
		}

	}

	/**
	 * Delete one or more product categories.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The term ID of the category to remove.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete term 7
	 *     wp wpsc-category delete 7
	 */
	public function delete( $args, $assoc_args ) {

		// Validate all term IDs are numeric and valid before doing anything
		foreach ( $args as $term_id ) {
			if ( ! is_numeric( $term_id ) ) {
				WP_CLI::error(
					sprintf( __( 'Invalid category ID provided: %d', 'wpsc' ), $term_id )
				);
			}
			$term = get_term_by( 'id', $term_id, 'wpsc_product_category' );
			if ( ! $term ) {
				WP_CLI::error(
					sprintf( __( 'Invalid category ID provided: %d', 'wpsc' ), $term_id )
				);
			}
		}

		reset( $args );
		foreach ( $args as $term_id ) {
			$result = wp_delete_term( $term_id, 'wpsc_product_category' );
			if ( $result ) {
				WP_CLI::line(
					sprintf( __( 'Category ID %d successfully removed.', 'wpsc' ), $term_id )
				);
			} else {
				WP_CLI::error(
					sprintf( __( 'Category ID %d could not be removed.', 'wpsc' ), $term_id )
				);
			}
		}
		WP_CLI::success( __( 'Categories deleted.', 'wpsc' ) );
	}

	/**
	 * Create a new category.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the category.
	 *
	 * [--description=<description>]
	 * : The description of the category.
	 *
	 * [--parent=<parent_id>]
	 * : The parent category ID to assign to this category.
	 *
	 * [--slug=<slug>]
	 * : The slug to assign to this category.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpsc-category create "My category"
	 *
	 *     wp wpsc-category create "Sub-category" --parent=4 --slug="sub-cat" --description="More specific awesome stuff"
	 */
	public function create( $args, $assoc_args ) {

		$name        = $args[0];
		$description = isset( $assoc_args['description'] ) ? $assoc_args['description'] : '';
		$parent      = isset( $assoc_args['parent'] ) ? $assoc_args['parent'] : 0;
		$slug        = isset( $assoc_args['slug'] ) ? $assoc_args['slug'] : '';

		$args = array(
			'description' => $description,
			'slug'        => $slug,
			'parent'      => $parent,
		);
		if ( ! is_wp_error( wp_insert_term( $name, 'wpsc_product_category', $args ) ) ) {
			WP_CLI::success( __( 'Category successfully created.', 'wpsc' ) );
		} else {
			WP_CLI::error( __( 'Category could not be created.', 'wpsc' ) );
		}
	}

	/**
	 * Generate product categories.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many categories to generate. Default: 10
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 10 product categories
	 *     wp wpsc-category generate
	 *
	 *     # Generate 20 product categories
	 *     wp wpsc-category generate --count=20
	 *
	 */
	function generate( $args, $assoc_args ) {
		$count = isset( $assoc_args['count'] ) ? (int) $assoc_args['count'] : 10;

		$notify = \WP_CLI\Utils\make_progress_bar( __( 'Generating categories', 'wpsc' ), $count );

		for ( $i = 1; $i <= $count; $i++ ) {
			$name = sprintf( __( 'Product category %d', 'wpsc' ), $i );
			if ( ! is_wp_error( wp_insert_term( $name, 'wpsc_product_category', array() ) ) ) {
				$notify->tick();
			} else {
				WP_CLI::error(
					sprintf( __( 'Failed to create %s', 'wpsc' ), $name )
				);
			}
		}
		$notify->finish();
	}
}
