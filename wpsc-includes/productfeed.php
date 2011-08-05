<?php


function wpsc_feed_publisher() {

	// If the user wants a product feed, then hook-in the product feed function
	if ( isset($_GET["rss"]) && ($_GET["rss"] == "true") &&
	     ($_GET["action"] == "product_list") ) {

    		add_action( 'wp', 'wpsc_generate_product_feed' );

  	}

}

add_action('init', 'wpsc_feed_publisher');


function wpsc_generate_product_feed() {

	global $wpdb, $wp_query, $post;
	
	// Don't cache feed under WP Super-Cache
	define('DONOTCACHEPAGE',TRUE);

	$siteurl = get_option('siteurl');

	// Allow limiting
	if (isset($_GET['limit']) && (is_numeric($_GET['limit']))) {
		$limit = "LIMIT ".$_GET['limit']."";
	} else {
		$limit = '';
	}

	$selected_category = '';
	$selected_product = '';

	if (isset($_GET['product_id']) && (is_numeric($_GET['product_id']))) {

		$args[] = array ( 'post__in' => $_GET['product_id'] );

	} elseif (isset($_GET['category_id']) && (is_numeric($_GET['category_id']))) {

		$args[] = array ( 'cat' => $_GET['category_id'] );

	}

	$args['post_type'] = 'wpsc-product';
	$args['posts_per_page'] = 999;

	$products = query_posts ($args); 

	$self = get_option('siteurl')."/index.php?rss=true&amp;action=product_list$selected_category$selected_product";

	header("Content-Type: application/xml; charset=UTF-8");
	header('Content-Disposition: inline; filename="E-Commerce_Product_List.rss"');

	$output = "<?xml version='1.0' encoding='UTF-8' ?>\n\r";
	$output .= "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'";

	$google_checkout_note = FALSE;

	if ($_GET['xmlformat'] == 'google') {
		$output .= ' xmlns:g="http://base.google.com/ns/1.0"';
		// Is Google Checkout available as a payment gateway
        	$selected_gateways = get_option('custom_gateway_options');
		if (in_array('google',$selected_gateways)) {
			$google_checkout_note = TRUE;
		}
	} else {
		$output .= ' xmlns:product="http://www.buy.com/rss/module/productV2/"';
	}

	$output .= ">\n\r";
	$output .= "  <channel>\n\r";
	$output .= "    <title><![CDATA[".get_option('blogname')." Products]]></title>\n\r";
	$output .= "    <link>".get_option('siteurl')."/wp-admin/admin.php?page=".WPSC_DIR_NAME."/display-log.php</link>\n\r";
	$output .= "    <description>This is the WP e-Commerce Product List RSS feed</description>\n\r";
	$output .= "    <generator>WP e-Commerce Plugin</generator>\n\r";
	$output .= "    <atom:link href='$self' rel='self' type='application/rss+xml' />\n\r";

	foreach ($products as $post) {

		setup_postdata($post);

		$purchase_link = wpsc_product_url($post->ID);

		$output .= "    <item>\n\r";
		if ($google_checkout_note) {
			$output .= "      <g:payment_notes>Google Checkout</g:payment_notes>\n\r";
		}
		$output .= "      <title><![CDATA[".get_the_title()."]]></title>\n\r";
		$output .= "      <link>$purchase_link</link>\n\r";
		$output .= "      <description><![CDATA[".get_the_content()."]]></description>\n\r";
		$output .= "      <pubDate>".$post->post_modified_gmt."</pubDate>\n\r";
		$output .= "      <guid>$purchase_link</guid>\n\r";

		$image_link = wpsc_the_product_thumbnail() ;

		if ($image_link !== FALSE) {

			if ($_GET['xmlformat'] == 'google') {
				$output .= "      <g:image_link>$image_link</g:image_link>\n\r";
			} else {
				$output .= "      <enclosure url='$image_link' />\n\r";
			}

		}

		$price = wpsc_calculate_price($post->ID);
		$args = array(
			'display_currency_symbol' => false,
			'display_decimal_point'   => true,
			'display_currency_code'   => false,
			'display_as_html'         => false
		);
		$price = wpsc_currency_display($price, $args);
		$children = get_children(array('post_parent'=> $post->ID,
					                   'post_type'=>'wpsc-product'));
		foreach ($children as $child) {
			$child_price = wpsc_calculate_price($child->ID);

			if (($price == 0) && ($child_price > 0)) {
				$price = $child_price;
			} else if ( ($child_price > 0) && ($child_price < $price) ) {
				$price = $child_price;
			}
		}

		if ($_GET['xmlformat'] == 'google') {

			$output .= "      <g:price>".$price."</g:price>\n\r";

			$google_elements = Array ();

			$product_meta = get_post_custom ( $post->ID );

			foreach ( $product_meta as $meta_key => $meta_value ) {
				if ( stripos($meta_key,'g:') === 0 )
					$google_elements[$meta_key] = $meta_value;
			}

			$google_elements = apply_filters( 'wpsc_google_elements', array ( 'product_id' => $post->ID, 'elements' => $google_elements ) );
			$google_elements = $google_elements['elements'];

            $done_condition = FALSE;
            $done_weight = FALSE;

            if ( count ( $google_elements ) ) {

				foreach ( $google_elements as $element_name => $element_values ) {

					foreach ( $element_values as $element_value ) {

						$output .= "      <".$element_name.">";
						$output .= "<![CDATA[".$element_value."]]>";
						$output .= "</".$element_name.">\n\r";

					}
 
					if ($element_name == 'g:shipping_weight')
						$done_weight = TRUE;

					if ($element_name == 'g:condition')
						$done_condition = TRUE;

				}

			}

            if (!$done_condition)
				$output .= "      <g:condition>new</g:condition>\n\r";

			if ( ! $done_weight ) {
				$wpsc_product_meta = get_product_meta( $post->ID, 'product_metadata',true );
				$weight = apply_filters ( 'wpsc_google_shipping_weight', $wpsc_product_meta['weight'], $post->ID );
				if ( $weight && is_numeric ( $weight ) && $weight > 0 ) {
					$output .= "<g:shipping_weight>$weight pounds</g:shipping_weight>";
				}
			}

		} else {

			$output .= "      <product:price>".$price."</product:price>\n\r";

		}

		$output .= "    </item>\n\r";

	}

	$output .= "  </channel>\n\r";
	$output .= "</rss>";
	echo $output;
	exit();
}
?>
