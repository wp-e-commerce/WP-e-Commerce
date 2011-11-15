<?php
/**
 * WP eCommerce edit and add product page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */


require_once(WPSC_FILE_PATH . '/wpsc-admin/includes/products.php');


/**
 * wpsc_additional_column_names function.
 *
 * @access public
 * @param (array) $columns
 * @return (array) $columns
 *
 */
function wpsc_additional_column_names( $columns ){
    $columns = array();

    $columns['cb'] = '<input type="checkbox" />';
    $columns['image'] = '';
    $columns['title'] = __('Name', 'wpsc');
    $columns['weight'] = __('Weight', 'wpsc');
    $columns['stock'] = __('Stock', 'wpsc');
    $columns['price'] = __('Price', 'wpsc');
    $columns['sale_price'] = __('Sale Price', 'wpsc');
    $columns['SKU'] = __('SKU', 'wpsc');
    $columns['cats'] = __('Categories', 'wpsc');
    $columns['featured'] = __('Featured', 'wpsc');
    $columns['hidden_alerts'] = '';
    $columns['date'] = __('Date', 'wpsc');

    return $columns;
}
function wpsc_additional_sortable_column_names( $columns ){

    $columns['stock'] = 'stock';
    $columns['price'] = 'price';
    $columns['sale_price'] = 'sale_price';
    $columns['SKU'] = 'SKU';

    return $columns;
}
function wpsc_additional_column_name_variations( $columns ){
    global $post;

    if(isset($post) && $post->post_parent != '0' )
       remove_meta_box( 'wpsc_product_variation_forms', 'wpsc-product', 'normal' );

    $columns['image'] = '';
    $columns['title'] = __('Name', 'wpsc');
    $columns['weight'] = __('Weight', 'wpsc');
    $columns['stock'] = __('Stock', 'wpsc');
    $columns['price'] = __('Price', 'wpsc');
    $columns['sale_price'] = __('Sale Price', 'wpsc');
    $columns['SKU'] = __('SKU', 'wpsc');
    $columns['hidden_alerts'] = '';

    //For BC for 3.0 (hoping to remove for WPEC 3.9)
    register_column_headers( 'wpsc-product_variants', $columns );
    return apply_filters( 'wpsc_variation_column_headers', $columns);
}

/**
 * wpsc_additional_column_data.
 *
 * @access public
 * @param (array) $column
 * @return void
 * @todo Need to check titles / alt tags ( I don't think thumbnails have any in this code )
 * @desc Switch function to generate columns the right way...no more UI hacking!
 *
 */
function wpsc_additional_column_data( $column ) {
    global $post;

    $is_parent = ( bool )wpsc_product_has_children($post->ID);
        switch ( $column ) :

            case 'image' :

                  $attached_images = get_posts( array(
                      'post_type' => 'attachment',
                      'numberposts' => 1,
                      'post_parent' => $post->ID,
                      'orderby' => 'menu_order',
                      'order' => 'ASC'
		    ) );

                if( isset( $post->ID ) && has_post_thumbnail( $post->ID ) )
                    echo get_the_post_thumbnail( $post->ID, 'admin-product-thumbnails' );
                else if( !empty( $attached_images  ) ) {
                    $attached_image = $attached_images[0];
                    $src = wp_get_attachment_url( $attached_image->ID );
                 ?>
                    <div style='width:38px; height:38px; overflow:hidden;'>
                        <img src='<?php echo $src; ?>' alt='<?php _e( 'Drag to a new position', 'wpsc' ); ?>' width='38' height='38' />
                    </div>
                <?php
		     } else {
		      	$image_url = WPSC_CORE_IMAGES_URL . "/no-image-uploaded.gif";
                ?>
                      <img src='<?php echo $image_url; ?>' alt='<?php _e( 'Drag to a new position', 'wpsc' ); ?>' width='38' height='38' />
                <?php
                     }
                break;
            case 'weight' :

                if( $is_parent ) :
                    _e( 'N/A', 'wpsc' );
				else :
                    $product_data['meta'] = array();
                    $product_data['meta'] = get_post_meta( $post->ID, '' );
                    foreach( $product_data['meta'] as $meta_name => $meta_value )
                        $product_data['meta'][$meta_name] = maybe_unserialize( array_pop( $meta_value ) );

                    $product_data['transformed'] = array();
                    if( !isset( $product_data['meta']['_wpsc_product_metadata']['weight'] ) )
                        $product_data['meta']['_wpsc_product_metadata']['weight'] = "";
                    if( !isset( $product_data['meta']['_wpsc_product_metadata']['weight_unit'] ) )
                        $product_data['meta']['_wpsc_product_metadata']['weight_unit'] = "";

                    $product_data['transformed']['weight'] = wpsc_convert_weight( $product_data['meta']['_wpsc_product_metadata']['weight'], "pound", $product_data['meta']['_wpsc_product_metadata']['weight_unit'] );

                    $weight = $product_data['transformed']['weight'];
                    if( $weight == '' )
                        $weight = '0';

			$unit = $product_data['meta']['_wpsc_product_metadata']['weight_unit'];

			switch( $unit ) {
				case "pound":
					$unit = __(" lbs.", "wpsc");
					break;
				case "ounce":
					$unit = __(" oz.", "wpsc");
					break;
				case "gram":
					$unit = __(" g", "wpsc");
					break;
				case "kilograms":
				case "kilogram":
					$unit = __(" kgs.", "wpsc");
					break;
			}
                        echo $weight.$unit;
                        echo '<div id="inline_' . $post->ID . '_weight" class="hidden">' . $weight . '</div>';

                  endif;
                break;
            case 'stock' :
                $stock = get_post_meta( $post->ID, '_wpsc_stock', true );
                    if( $stock == '' )
                        $stock = __('N/A', 'wpsc');
                    if( !$is_parent ) {
                        echo $stock;
                        echo '<div id="inline_' . $post->ID . '_stock" class="hidden">' . $stock . '</div>';
                    }
                    else
                        echo '~'.wpsc_variations_stock_remaining( $post->ID );
                 break;
            case 'price' :
                $price = get_post_meta( $post->ID, '_wpsc_price', true );
				$has_var = '1';
                if( !$is_parent ) {
                  	echo wpsc_currency_display( $price );
                    echo '<div id="inline_' . $post->ID . '_price" class="hidden">' . trim($price) . '</div>';
	                 $has_var = '0';
                }
                else
                    echo wpsc_product_variation_price_available( $post->ID ).'+';
                 echo '<input type="hidden" value="'.$has_var.'" id="inline_' . $post->ID . '_has_var" />';

                break;
            case 'sale_price' :
                $price = get_post_meta( $post->ID, '_wpsc_special_price', true );
                if( !$is_parent ) {
                    echo wpsc_currency_display( $price );
                    echo '<div id="inline_' . $post->ID . '_sale_price" class="hidden">' . $price  . '</div>';
                } else
                    echo wpsc_product_variation_price_available( $post->ID ).'+';
                break;
            case 'SKU' :
                $sku = get_post_meta( $post->ID, '_wpsc_sku', true );
                    if( $sku == '' )
                        $sku = __('N/A', 'wpsc');

                    echo $sku;
                    echo '<div id="inline_' . $post->ID . '_sku" class="hidden">' . $sku . '</div>';
               break;
            case 'cats' :
                $categories = get_the_product_category( $post->ID );
                    if ( !empty( $categories ) ) {
                        $out = array();
                        foreach ( $categories as $c )
                            $out[] = "<a href='?post_type=wpsc-product&amp;wpsc_product_category={$c->slug}'> " . esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'category', 'display' ) ) . "</a>";
                            echo join( ', ', $out );
			} else {
                            _e('Uncategorized', 'wpsc');
			}
                break;
            case 'featured' :
                $featured_product_url = wp_nonce_url( "index.php?wpsc_admin_action=update_featured_product&amp;product_id=$post->ID", 'feature_product_' . $post->ID);
?>
	<a class="wpsc_featured_product_toggle featured_toggle_<?php echo $post->ID; ?>" href='<?php echo $featured_product_url; ?>' >
            <?php if ( in_array( $post->ID, (array)get_option( 'sticky_products' ) ) ) : ?>
                <img class='gold-star' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/gold-star.gif' alt='<?php _e( 'Unmark as Featured', 'wpsc' ); ?>' title='<?php _e( 'Unmark as Featured', 'wpsc' ); ?>' />
            <?php else: ?>
                <img class='grey-star' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/grey-star.gif' alt='<?php _e( 'Mark as Featured', 'wpsc' ); ?>' title='<?php _e( 'Mark as Featured', 'wpsc' ); ?>' />
            <?php endif; ?>
	</a>
        <?php
                break;
            case 'hidden_alerts' :
                $product_alert = apply_filters( 'wpsc_product_alert', array( false, '' ), $post );
                    if( !empty( $product_alert['messages'] ) )
                        $product_alert['messages'] = implode( "\n",( array )$product_alert['messages'] );

                    if( $product_alert['state'] === true ) {
                        ?>
                            <img alt='<?php echo $product_alert['messages'];?>' title='<?php echo $product_alert['messages'];?>' class='product-alert-image' src='<?php echo  WPSC_CORE_IMAGES_URL;?>/product-alert.jpg' alt='' />
                        <?php
                    }

                    // If a product alert has stuff to display, show it.
                    // Can be used to add extra icons etc
                    if ( !empty( $product_alert['display'] ) )
                        echo $product_alert['display'];
                break;
        endswitch;

}
function wpsc_column_sql_orderby( $vars ) {
    
	if ( ! isset( $vars['post_type'] ) || 'wpsc-product' != $vars['post_type'] || ! isset( $vars['orderby'] ) )
	    return $vars;
	
            switch ( $vars['orderby'] ) :
                case 'stock' :
		    $vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_stock',
					'orderby' => 'meta_value_num'
				)
			);
		    break;
		case 'price' :
		    $vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_price',
					'orderby' => 'meta_value_num'
				)
			);
		    break;
                case 'sale_price' :
		    $vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_special_price',
					'orderby' => 'meta_value_num'
				)
			);
		    
		    break;
		case 'SKU' :
		    $vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_sku',
					'orderby' => 'meta_value'
				)
			);
		    break;
		endswitch;
	    
	return $vars;
}
function wpsc_cats_restrict_manage_posts() {
    global $typenow;

    if ( $typenow == 'wpsc-product' ) {

        $filters = array( 'wpsc_product_category' );

        foreach ( $filters as $tax_slug ) {
            // retrieve the taxonomy object
            $tax_obj = get_taxonomy( $tax_slug );
            $tax_name = $tax_obj->labels->name;
            // retrieve array of term objects per taxonomy
            // output html for taxonomy dropdown filter
            echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
            echo "<option value=''>" . sprintf(_x('Show All %s', 'Show all [category name]', 'wpsc'), $tax_name) . "</option>";
            wpsc_cats_restrict_manage_posts_print_terms($tax_slug);
            echo "</select>";
        }
    }
}

function wpsc_cats_restrict_manage_posts_print_terms($taxonomy, $parent = 0, $level = 0){
	$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $level );
	$terms = get_terms( $taxonomy, array( 'parent' => $parent, 'hide_empty' => false ) );
	if( !($terms instanceof WP_Error) && !empty($terms) )
		foreach ( $terms as $term ){
			echo '<option value="'. $term->slug . '"', ( isset($_GET[$term->taxonomy]) && $_GET[$term->taxonomy] == $term->slug) ? ' selected="selected"' : '','>' . $prefix . $term->name .' (' . $term->count .')</option>';
			wpsc_cats_restrict_manage_posts_print_terms($taxonomy, $term->term_id, $level+1);
		}
}

/**
 * wpsc no minors allowed
 * Restrict the products page to showing only parent products and not variations.
 * @since 3.8
 */

function wpsc_no_minors_allowed( $vars ) {
    global $current_screen;

    if( $current_screen->post_type != 'wpsc-product' )
        return $vars;

    $vars['post_parent'] = 0;

    return $vars;
}

/**
 * wpsc_sortable_column_load
 * 
 * Only sorts columns on edit.php page.
 * @since 3.8.8
 */

function wpsc_sortable_column_load() {
    add_filter( 'request', 'wpsc_no_minors_allowed' );
    add_filter( 'request', 'wpsc_column_sql_orderby', 8 );
}

add_action( 'load-edit.php', 'wpsc_sortable_column_load' );
add_action( 'admin_head', 'wpsc_additional_column_name_variations' );
add_action( 'restrict_manage_posts', 'wpsc_cats_restrict_manage_posts' );
add_action( 'manage_pages_custom_column', 'wpsc_additional_column_data', 10, 2 );
add_filter( 'manage_edit-wpsc-product_sortable_columns', 'wpsc_additional_sortable_column_names' );
add_filter( 'manage_edit-wpsc-product_columns', 'wpsc_additional_column_names' );
add_filter( 'manage_wpsc-product_posts_columns', 'wpsc_additional_column_names' );



/**
 * wpsc_update_featured_products function.
 *
 * @access public
 * @todo Should be refactored to e
 * @return void
 */
function wpsc_update_featured_products() {
	$is_ajax = (int)(bool)$_POST['ajax'];
	$product_id = absint( $_GET['product_id'] );
	check_admin_referer( 'feature_product_' . $product_id );
	$status = get_option( 'sticky_products' );

	$new_status = (in_array( $product_id, $status )) ? false : true;

	if ( $new_status ) {

		$status[] = $product_id;
	} else {
		$status = array_diff( $status, array( $product_id ) );
		$status = array_values( $status );
	}
	update_option( 'sticky_products', $status );

	if ( $is_ajax == true ) {
		if ( $new_status == true ) : ?>
                    jQuery('.featured_toggle_<?php echo $product_id; ?>').html("<img class='gold-star' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/gold-star.gif' alt='<?php _e( 'Unmark as Featured', 'wpsc' ); ?>' title='<?php _e( 'Unmark as Featured', 'wpsc' ); ?>' />");
            <?php else: ?>
                    jQuery('.featured_toggle_<?php echo $product_id; ?>').html("<img class='grey-star' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/grey-star.gif' alt='<?php _e( 'Mark as Featured', 'wpsc' ); ?>' title='<?php _e( 'Mark as Featured', 'wpsc' ); ?>' />");
<?php
		endif;
		exit();
	}
	wp_redirect( wp_get_referer() );
	exit();
}

add_filter( 'page_row_actions','my_action_row', 10, 2 );

function my_action_row( $actions, $post ) {

    if ( $post->post_type != "wpsc-product" )
            return $actions;

    $url = admin_url( 'edit.php' );
    $url = add_query_arg( array( 'wpsc_admin_action' => 'duplicate_product', 'product' => $post->ID ), $url );

    $actions['duplicate'] = '<a href="'.esc_url( $url ).'">'._x( 'Duplicate', 'row-actions', 'wpsc' ).'</a>';

    return $actions;
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'update_featured_product' ) )
    add_action( 'admin_init', 'wpsc_update_featured_products' );

if ( isset( $_GET['wpsc_admin_action'] ) && ( $_GET['wpsc_admin_action'] == 'duplicate_product' ) )
    add_action( 'admin_init', 'wpsc_duplicate_product' );