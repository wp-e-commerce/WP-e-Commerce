<?php

/**
 * Duplicate Product Class
 *
 * @package     WP eCommerce
 * @subpackage  Duplicate Product Class
 * @since       3.11.0
 */

/**
 * WPSC Duplicate Product Class
 *
 * Used to duplicate products.
 *
 * @since  3.11.0
 */
class WPSC_Duplicate_Product {

	private $post_id       = null;
	private $new_post_id   = null;
	private $new_parent_id = false;

	/**
	 * Create new duplicate product
	 *
	 * @since  3.11.0
	 *
	 * @param  int        $post_id        Post ID.
	 * @param  int|false  $new_parent_id  Optional. New post parent ID.
	 * @param  int|null   $new_post_id    Optional. New post ID if copying to exisiting post.
	 */
	public function __construct( $post_id, $new_parent_id = false, $new_post_id = null ) {

		$this->post_id = absint( $post_id );
		$this->new_parent_id = is_numeric( $new_parent_id ) ? absint( $new_parent_id ) : false;
		$this->new_post_id = is_numeric( $new_post_id ) ? absint( $new_post_id ) : null;
	}

	/**
	 * Duplicates a product
	 *
	 * @since 3.11.0
	 *
	 * @uses  wp_insert_post()  Inserts a new post to the database.
	 *
	 * @return  int|WP_Error  New post ID or error.
	 */
	public function duplicate_product_process() {

		$post = get_post( $this->get_post_id() );

		// If no new post ID yet, duplicate the product post
		if ( ! $this->get_new_post_id() ) {

			$new_parent_id = $this->get_new_parent_id( $post->post_parent );

			$new_post_date     = $post->post_date;
			$new_post_date_gmt = get_gmt_from_date( $new_post_date );

			$new_post_type         = $post->post_type;
			$post_content          = $post->post_content;
			$post_content_filtered = $post->post_content_filtered;
			$post_excerpt          = $post->post_excerpt;
			$post_title            = sprintf( __( '%s (Duplicate)', 'wp-e-commerce' ), $post->post_title );
			$post_name             = $post->post_name;
			$comment_status        = $post->comment_status;
			$ping_status           = $post->ping_status;

			$defaults = array(
				'post_status'           => $post->post_status,
				'post_type'             => $new_post_type,
				'ping_status'           => $ping_status,
				'post_parent'           => $new_parent_id,
				'menu_order'            => $post->menu_order,
				'to_ping'               => $post->to_ping,
				'pinged'                => $post->pinged,
				'post_excerpt'          => $post_excerpt,
				'post_title'            => $post_title,
				'post_content'          => $post_content,
				'post_content_filtered' => $post_content_filtered,
				'post_mime_type'        => $post->post_mime_type,
				'import_id'             => 0
			);

			if ( 'attachment' == $post->post_type ) {
				$defaults['guid'] = $post->guid;
			}

			$defaults = stripslashes_deep( $defaults );

			// Insert the new template in the post table
			$this->new_post_id = wp_insert_post( $defaults );

		}

		// Copy the taxonomies
		$this->duplicate_taxonomies();

		// Copy the meta information
		$this->duplicate_product_meta();

		do_action( 'wpsc_duplicate_product', $post, $this->get_new_post_id() );

		// Finds children (which includes product files AND product images), their meta values, and duplicates them.
		$duplicated_children = $this->duplicate_children();

		// Update product gallery meta (resetting duplicated meta value IDs)
		$this->update_duplicate_product_gallery_meta( $duplicated_children );

		// Copy product thumbnail (resetting duplicated meta value)
		$this->duplicate_product_thumbnail();

		return $this->get_new_post_id();
	}

	/**
	 * Copy the taxonomies of a post to another post
	 *
	 * @since  3.11.0
	 *
	 * @uses  get_object_taxonomies()   Gets taxonomies for the given object.
	 * @uses  wpsc_get_product_terms()  Gets terms for the product taxonomies.
	 * @uses  wp_set_object_terms()     Sets the terms for a post object.
	 */
	public function duplicate_taxonomies() {

		$new_post_id = $this->get_new_post_id();

		if ( $new_post_id ) {

			$id = $this->get_post_id();
			$post_type = get_post_type( $id );
			$taxonomies = get_object_taxonomies( $post_type );

			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = wpsc_get_product_terms( $id, $taxonomy );
				foreach ( $post_terms as $post_term ) {
					wp_set_object_terms( $new_post_id, $post_term->slug, $taxonomy, true );
				}
			}
		}
	}

	/**
	 * Copy the meta information of a post to another post
	 *
	 * @since  3.11.0
	 *
	 * @uses  $wpdb          WordPress database object for queries.
	 * @uses  get_results()  Gets generic multirow results from the database.
	 * @uses  prepare()      Prepares a database query making it safe.
	 * @uses  query()        Runs an SQL query.
	 */
	public function duplicate_product_meta() {

		global $wpdb;

		$new_post_id = $this->get_new_post_id();

		if ( $new_post_id ) {

			$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $this->get_post_id() ) );

			if ( count( $post_meta_infos ) ) {

				$sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
				$values        = array();
				$sql_query_sel = array();

				foreach ( $post_meta_infos as $meta_info ) {
					$meta_key = $meta_info->meta_key;
					$meta_value = addslashes( $meta_info->meta_value );

					$sql_query_sel[] = "( $new_post_id, '$meta_key', '$meta_value' )";
					$values[] = $new_post_id;
					$values[] = $meta_key;
					$values[] = $meta_value;
					$values += array( $new_post_id, $meta_key, $meta_value );
				}

				$sql_query .= implode( ",", $sql_query_sel );
				$wpdb->query( $sql_query );
				clean_post_cache( $new_post_id );
			}
		}
	}

	/**
	 * Update Duplicate Product Gallery Meta
	 *
	 * When a product is duplicated its meta values are copied, too
	 * including the gallery meta array of IDs.
	 *
	 * After the product's children (including attachments) have been
	 * duplicated this function is used to update the gallery meta to
	 * refer to the IDs of any duplicated media.
	 *
	 * @since  3.11.0
	 *
	 * @param  array  $duplicated_children  Associative array mapping original child IDs to duplicated child IDs.
	 */
	private function update_duplicate_product_gallery_meta( $duplicated_children ) {

		$new_post_id = $this->get_new_post_id();

		if ( $new_post_id ) {

			$gallery = get_post_meta( $new_post_id, '_wpsc_product_gallery', true );
			$new_gallery = array();

			// Loop through duplicated gallery IDs.
			if ( is_array( $gallery ) ) {
				foreach ( $gallery as $gallery_id ) {

					// If product image should be duplicated
					if ( apply_filters( 'wpsc_duplicate_product_attachment', true, $gallery_id, $new_post_id ) ) {

						// Update attached image IDs and copy non-attached image IDs
						if ( array_key_exists( $gallery_id, $duplicated_children ) ) {
							$new_gallery[] = $duplicated_children[ $gallery_id ];
						} else {
							$new_gallery[] = $gallery_id;
						}
					}
				}

				update_post_meta( $new_post_id, '_wpsc_product_gallery', $new_gallery );
			}
		}
	}

	/**
	 * Duplicate Featured Image
	 *
	 * When a product is duplicated, the featured image ID is copied when the post
	 * meta is duplicated.
	 *
	 * When the featured image is attached to the duplicated product, if the image
	 * is duplicated the featured image ID is updated to the duplicated image ID
	 * otherwise the featured image ID is removed.
	 *
	 * If the featured image is not attached to the product the featured image ID
	 * remains the same as the original product.
	 *
	 * This function will remove the featured image if the image is not attached to
	 * the duplicated product and offers the opportunity to change the featured image
	 * of the duplicated product via the 'wpsc_duplicate_product_thumbnail' filter.
	 *
	 * @since  3.11.0
	 */
	private function duplicate_product_thumbnail() {

		$new_post_id = $this->get_new_post_id();

		if ( $new_post_id ) {

			$thumbnail_id = $original_thumbnail_id = has_post_thumbnail( $new_post_id ) ? get_post_thumbnail_id( $new_post_id ) : 0;

			// If not duplicating product attachments, ensure featured image ID is zero
			if ( ! apply_filters( 'wpsc_duplicate_product_attachment', true, $thumbnail_id, $new_post_id ) ) {
				$thumbnail_id = 0;
			}

			// Filter featured product image ID
			$thumbnail_id = absint( apply_filters( 'wpsc_duplicate_product_thumbnail', $thumbnail_id, $original_thumbnail_id, $this->get_post_id(), $new_post_id ) );

			if ( $thumbnail_id > 0 ) {
				set_post_thumbnail( $new_post_id, $thumbnail_id );
			} else {
				delete_post_thumbnail( $new_post_id );
			}
		}
	}

	/**
	 * Duplicates product children and meta
	 *
	 * @since  3.11.0
	 *
	 * @uses  get_posts()  Gets an array of posts given array of arguments.
	 *
	 * @return  array  Array mapping old child IDs to duplicated child IDs.
	 */
	private function duplicate_children() {

		$new_parent_id = $this->get_new_post_id();

		// Map duplicate child IDs
		$converted_child_ids = array();

		if ( $new_parent_id ) {

			// Get children products and duplicate them
			$child_posts = get_posts( array(
				'post_parent' => $this->get_post_id(),
				'post_type'   => 'any',
				'post_status' => 'any',
				'numberposts' => -1,
				'order'       => 'ASC'
			) );

			// Duplicate product images and child posts
			foreach ( $child_posts as $child_post ) {

				$duplicate_child = new WPSC_Duplicate_Product( $child_post->ID, $new_parent_id );

				// Duplicate image or post
				if ( 'attachment' == get_post_type( $child_post ) ) {
					$new_child_id = $duplicate_child->duplicate_product_image_process();
				} else {
					$new_child_id = $duplicate_child->duplicate_product_process();
				}

				// Map child ID to new child ID
				if ( $new_child_id && ! is_wp_error( $new_child_id ) ) {
					$converted_child_ids[ $child_post->ID ] = $new_child_id;
				}

				do_action( 'wpsc_duplicate_product_child', $child_post, $new_parent_id, $new_child_id );
			}
		}

		return $converted_child_ids;
	}

	/**
	 * Duplicates a product image.
	 *
	 * Uses a portion of code from media_sideload_image() in `wp-admin/includes/media.php`
	 * to check file before downloading from URL.
	 *
	 * @since  3.11.0
	 *
	 * @uses  get_post_type()          Gets post type.
	 * @uses  wp_get_attachment_url()  Gets attachment URL.
	 * @uses  download_url()           Download file from URl to temp location.
	 * @uses  is_wp_error()            Is WP error?
	 * @uses  media_handle_sideload()  Handle creation of new attachment and attach to post.
	 *
	 * @return  int|bool  Attachment ID or false.
	 */
	public function duplicate_product_image_process() {

		$child_post = get_post( $this->get_post_id() );
		$new_parent_id = $this->get_new_parent_id( $child_post->post_parent );

		if ( 'attachment' == get_post_type( $child_post ) && apply_filters( 'wpsc_duplicate_product_attachment', true, $child_post->ID, $new_parent_id ) ) {

			$file = wp_get_attachment_url( $child_post->ID );

			if ( ! empty( $file ) ) {

				// Set variables for storage, fix file filename for query strings.
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
				$file_array = array();
				$file_array['name'] = basename( $matches[0] );

				// Download file to temp location.
				$file_array['tmp_name'] = download_url( $file );

				// If error storing temporarily, return the error.
				if ( is_wp_error( $file_array['tmp_name'] ) ) {
					return $file_array['tmp_name'];
				}

				// Do the validation and storage stuff.
				$new_post_id = media_handle_sideload( $file_array, $new_parent_id );

				// If error storing permanently, unlink.
				if ( is_wp_error( $new_post_id ) ) {
					@ unlink( $file_array['tmp_name'] );
				}

				// Re-attribute featured image
				if ( has_post_thumbnail( $new_parent_id ) && $child_post->ID == get_post_thumbnail_id( $new_parent_id ) ) {
					set_post_thumbnail( $new_parent_id, $new_post_id );
				}

				// Copy attachment data
				$post_data = array(
					'ID'                    => $new_post_id,
					'post_content'          => $child_post->post_content,
					'post_title'            => $child_post->post_title,
					'post_excerpt'          => $child_post->post_excerpt,
					'post_status'           => $child_post->post_status,
					'comment_status'        => $child_post->comment_status,
					'ping_status'           => $child_post->ping_status,
					'post_password'         => $child_post->post_password,
					'post_content_filtered' => $child_post->post_content_filtered,
					'menu_order'            => $child_post->menu_order
				);

				wp_update_post( $post_data );

				// Copy alt text
				update_post_meta( $new_post_id, '_wp_attachment_image_alt', get_post_meta( $child_post->ID, '_wp_attachment_image_alt', true ) );

				return $new_post_id;
			}

		} elseif ( has_post_thumbnail( $new_parent_id ) && $child_post->ID == get_post_thumbnail_id( $new_parent_id ) ) {

			delete_post_meta( $new_parent_id, '_thumbnail_id' );
		}

		return false;
	}

	/**
	 * Get Post ID
	 *
	 * @since  3.11.0
	 *
	 * @return  int  Post ID.
	 */
	public function get_post_id() {

		return $this->post_id;
	}

	/**
	 * Get New Post ID
	 *
	 * @since  3.11.0
	 *
	 * @return  int  Post ID.
	 */
	public function get_new_post_id() {

		return $this->new_post_id;
	}

	/**
	 * Get New Parent ID
	 *
	 * @since  3.11.0
	 *
	 * @param   int  $default  Default parent ID.
	 * @return  int            Post ID.
	 */
	public function get_new_parent_id( $default = 0 ) {

		return false === $this->new_parent_id ? $default : $this->new_parent_id;
	}
}
