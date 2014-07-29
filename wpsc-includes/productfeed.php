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

    set_time_limit(0);

    $xmlformat = '';
    if ( isset( $_GET['xmlformat'] ) ) {
    	$xmlformat = $_GET['xmlformat'];
    }

	// Don't build up a huge posts cache for the whole store - http://code.google.com/p/wp-e-commerce/issues/detail?id=885
	wp_suspend_cache_addition(true);

    $chunk_size = apply_filters ( 'wpsc_productfeed_chunk_size', 50 );

    // Don't cache feed under WP Super-Cache
    define( 'DONOTCACHEPAGE', true );

	$selected_category = '';
	$selected_product = '';

	$args = array(
			'post_type'     => 'wpsc-product',
			'numberposts'   => $chunk_size,
			'offset'        => 0,
			'cache_results' => false,
		);

	$args = apply_filters( 'wpsc_productfeed_query_args', $args );

	$self = home_url( "/index.php?rss=true&amp;action=product_list$selected_category$selected_product" );

	header("Content-Type: application/xml; charset=UTF-8");
	header('Content-Disposition: inline; filename="E-Commerce_Product_List.rss"');

	echo "<?xml version='1.0' encoding='UTF-8' ?>\n\r";
	echo "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'";

	$google_checkout_note = false;

	if ( $xmlformat == 'google' ) {
		echo ' xmlns:g="http://base.google.com/ns/1.0"';
		// Is Google Checkout available as a payment gateway
        	$selected_gateways = get_option('custom_gateway_options');
		if (in_array('google',$selected_gateways)) {
			$google_checkout_note = true;
		}
	} else {
		echo ' xmlns:product="http://www.buy.com/rss/module/productV2/"';
	}

	echo ">\n\r";
	echo "  <channel>\n\r";
	echo "    <title><![CDATA[" . sprintf( _x( '%s Products', 'XML Feed Title', 'wpsc' ), get_option( 'blogname' ) ) . "]]></title>\n\r";
	echo "    <link>" . admin_url( 'admin.php?page=' . WPSC_DIR_NAME . '/display-log.php' ) . "</link>\n\r";
	echo "    <description>" . _x( 'This is the WP e-Commerce Product List RSS feed', 'XML Feed Description', 'wpsc' ) . "</description>\n\r";
	echo "    <generator>" . _x( 'WP e-Commerce Plugin', 'XML Feed Generator', 'wpsc' ) . "</generator>\n\r";
	echo "    <atom:link href='$self' rel='self' type='application/rss+xml' />\n\r";

	$products = get_posts( $args );

	while ( count ( $products ) ) {

		foreach ($products as $post) {

			setup_postdata($post);

			$purchase_link = get_permalink($post->ID);

			echo "    <item>\n\r";
			if ($google_checkout_note) {
				echo "      <g:payment_notes>" . _x( 'Google Wallet', 'Google Checkout Payment Notes in XML Feed', 'wpsc' ) . "</g:payment_notes>\n\r";
			}
			echo "      <title><![CDATA[".get_the_title()."]]></title>\n\r";
			echo "      <link>$purchase_link</link>\n\r";
			echo "      <description><![CDATA[".apply_filters ('the_content', get_the_content())."]]></description>\n\r";
			echo "      <guid>$purchase_link</guid>\n\r";

			$image_link = wpsc_the_product_thumbnail() ;

			if ($image_link !== FALSE) {

				if ( $xmlformat == 'google' ) {
					echo "      <g:image_link><![CDATA[$image_link]]></g:image_link>\n\r";
				} else {
					echo "      <enclosure url='" . esc_url( $image_link ) . "' />\n\r";
				}

			}

			$price = wpsc_calculate_price($post->ID);
			$currargs = array(
				'display_currency_symbol' => false,
				'display_decimal_point'   => true,
				'display_currency_code'   => false,
				'display_as_html'         => false
			);
			$price = wpsc_currency_display($price, $currargs);

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

			if ( $xmlformat == 'google' ) {

				echo "      <g:price>".$price."</g:price>\n\r";

				$google_elements = array();

				$product_meta = get_post_custom ( $post->ID );

                if ( is_array ( $product_meta ) ) {
				    foreach ( $product_meta as $meta_key => $meta_value ) {
					    if ( stripos( $meta_key, 'g:' ) === 0 )
						    $google_elements[ $meta_key ] = $meta_value;
				    }
                }

				$google_elements = apply_filters( 'wpsc_google_elements', array( 'product_id' => $post->ID, 'elements' => $google_elements ) );
				$google_elements = $google_elements['elements'];

	            $done_condition = FALSE;
	            $done_availability = FALSE;
	            $done_weight = FALSE;

	            if ( count ( $google_elements ) ) {

					foreach ( $google_elements as $element_name => $element_values ) {

						foreach ( $element_values as $element_value ) {

							echo "      <".$element_name.">";
							echo "<![CDATA[".$element_value."]]>";
							echo "</".$element_name.">\n\r";

						}

						if ($element_name == 'g:shipping_weight')
							$done_weight = TRUE;

						if ($element_name == 'g:condition')
							$done_condition = TRUE;

	                    if ($element_name == 'g:availability')
	                        $done_availability = true;
					}

				}

	            if (!$done_condition)
					echo "      <g:condition>new</g:condition>\n\r";

	            if (!$done_availability) {

	                if(wpsc_product_has_stock()) :
	                    $product_availability = "in stock";
	                else :
	                    $product_availability = "out of stock";
	                endif ;

	                echo " <g:availability>$product_availability</g:availability>";

	            }

				if ( ! $done_weight ) {
					$wpsc_product_meta = get_product_meta( $post->ID, 'product_metadata',true );
					$weight = apply_filters ( 'wpsc_google_shipping_weight', $wpsc_product_meta['weight'], $post->ID );
					if ( $weight && is_numeric ( $weight ) && $weight > 0 ) {
						echo "<g:shipping_weight>$weight pounds</g:shipping_weight>";
					}
				}

			} else {

				echo "      <product:price>".$price."</product:price>\n\r";

			}

			echo "    </item>\n\r";

		}

		$args['offset'] += $chunk_size;
		$products = get_posts ( $args );

	}

	echo "  </channel>\n\r";
	echo "</rss>";
	exit();
}
?>