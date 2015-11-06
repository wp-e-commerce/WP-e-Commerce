<?php

$categorylist = get_terms('wpsc_product_category',array('hide_empty'=> 0));
$allProducts = get_posts('post_type=wpsc-product&nopaging=true');

//Check capabilities
if ( !current_user_can('edit_pages') && !current_user_can('edit_posts') )
	wp_die( __( 'You don\'t have permission to be doing that!', 'wp-e-commerce' ) );

global $wpdb;
?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>WP eCommerce</title>
		<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/jquery/jquery.js"></script>
		<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/tiny_mce_popup.js"></script>
		<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/utils/mctabs.js"></script>
		<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/utils/form_utils.js"></script>
		<script language="javascript" type="text/javascript" src="<?php echo WPSC_URL; ?>/wpsc-core/js/tinymce3/tinymce.js"></script>

		<base target="_self" />
		<style type='text/css'>
			div.current{
				overflow-y: auto !important;
			}

			.description{
				color:grey	!important;
				font-style: italic !important;
			}

			#product_slider_panel a{
				color: blue	!important;
			}
		</style>
	</head>

<body id="link" onload="tinyMCEPopup.executeOnLoad('init();'); document.body.style.display=''; document.getElementById('category').focus();" style="display:none;">
	<form name="WPSC" action="#">
		<div class="tabs">
		<ul>
			<li id="category" class="current"><span><a href="javascript:mcTabs.displayTab('category','wpsc_category_panel');" onmousedown="return false;"><?php _e("Category", 'wp-e-commerce'); ?></a></span></li>
			<li id="add_product"><span><a href="javascript:mcTabs.displayTab('add_product','add_product_panel');" onmousedown="return false;"><?php _e("Products", 'wp-e-commerce'); ?></a></span></li>
			<li id="product_slider"><span><a href="javascript:mcTabs.displayTab('product_slider','product_slider_panel');" onmousedown="return false;"><?php _e("Premium Upgrades", 'wp-e-commerce'); ?></a></span></li>
		</ul>
		</div>

<!-- 	Add a category shortcode options -->
	<div class="panel_wrapper">
		<div id="wpsc_category_panel" class="panel current"><br />
			<table border="0" cellpadding="4" cellspacing="0">

				<tr valign="top">
					<td><strong><label for="wpsc_category"><?php _e("Select Category: ", 'wp-e-commerce'); ?></label></strong></td>
					<td>
						<select id="wpsc_category" name="wpsc_category" style="width: 150px">
							<option value="0"><?php _e("No Category", 'wp-e-commerce'); ?></option>
							<?php
								foreach($categorylist as $category)
									echo "<option value=".$category->term_id." >".$category->name."</option>"."\n";
							?>
						</select><br />
						<span class="description"><?php _e('Select the category you would like to display with a Shortcode.', 'wp-e-commerce') ?></span>
					</td>
				</tr>

				<tr valign="top">
					<td><strong><label for="wpsc_perpage"><?php _e("Number of products per Page: ", 'wp-e-commerce'); ?></label></strong></td>
					<td>
						<input name="number_per_page" id="wpsc_perpage" type="text" value="" style="width: 80px" /><br />
						<span class="description"><?php _e('Select the number of products you would like to display per page.', 'wp-e-commerce') ?></span>
					</td>
				</tr>

				<tr>
					<td><strong><label for="wpsc_saleprod"><?php _e("Sale Products:", 'wp-e-commerce'); ?></label></strong></td>
				</tr>
				<tr>
				<td></td>
					<td>
						<input type="radio" id="wpsc_sale_shortcode" name="wpsc_sale_shortcode" value="1"><?php _e('Add ALL sale products', 'wp-e-commerce');?>
						<br /><span class="description"><?php _e('This will add all your products you have on sale to the page' , 'wp-e-commerce') ?></span>
					</td>
				</tr>
				<tr>
				<td></td>
					<td>
						<input type="radio" id="wpsc_sale_shortcode" name="wpsc_sale_shortcode" value="2"><?php _e('Add sale products by category', 'wp-e-commerce');?>
						<br /><span class="description"><?php _e('This will add all your products you have on sale from the selected category' , 'wp-e-commerce') ?></span>
					</td>
				</tr>

			</table>
		</div>

	<!-- Premium upgrades, check is upgrade exists if so display short code. -->
		<div id="product_slider_panel" class="panel"><br />
			<table border="0" cellpadding="4" cellspacing="0">

			<tr valign="top">
				<strong><label for="wpsc_product_slider"> <?php _e("Product Slider", 'wp-e-commerce'); ?></label></strong>
			</tr>
		<!-- check if product slider installed -->
		<?php if (function_exists('product_slider_preload')){ ?>

				<tr valign="top">
					<td><strong><label for="wpsc_category"> <?php _e("Select Category", 'wp-e-commerce'); ?></label></strong></td>
					<td>
						<select id="wpsc_slider_category" name="wpsc_category" style="width: 200px">
							<option value="0"> <?php _e("No Category", 'wp-e-commerce'); ?></option>
							<option value="all"> <?php _e("All Categories", 'wp-e-commerce'); ?></option>
							<?php
								foreach($categorylist as $category)
								echo "<option value=".$category->term_id." >".$category->name."</option>"."\n";
							?>
						</select><br />
						<span class="description"><?php _e('Select the category you would like to display with a Shortcode.', 'wp-e-commerce') ?></span>
					</td>
				</tr>

				<tr valign="top">
					<td><strong><label for="wpsc_perpage"><?php _e( 'Number of Products', 'wp-e-commerce' ); ?>:</label></strong></td>
					<td>
						<input type='text' id='wpsc_slider_visibles' name='wpsc_slider_visibles'> <br />
						<span class="description"><?php _e('Number of Products to be displayed in the slider.', 'wp-e-commerce') ?></span>
					</td>
				</tr>

		<?php } ?>
	</table>


			<strong><label for="wpsc_members"><?php _e("Members and Capabilities", 'wp-e-commerce'); ?></label></strong>
			<!-- check if members is installed -->
				<?php if (function_exists('wpsc_display_purchasable_capabilities')){ ?>

					<span class="description"> <?php
					_e('<p> To create a preview on your restricted page put this shortcode at the top of your page. you can include html within this short code to display things like images ','wp-e-commerce'); ?></span>
					<code><?php _e('[preview] Preview In Here [/preview]', 'wp-e-commerce'); ?></code>

				<?php }else{ ?>

				<p>	<?php _e(' You don\'t have the Members and Capabilities plugin installed. To start managing your users and creating subscriptions for your site, visit: <a href="https://wpecommerce.org/store/premium-plugins/membership-subscriptions/" target="_blank">Premium Upgrades</a>','wp-e-commerce');
				}?> </p>
		</div>

<!-- 	all these short codes relate to single products, the product id is used to generate all these codes. -->
		<div id="add_product_panel" class="panel"><br />
			<table border="0" cellpadding="4" cellspacing="0">

				<tr valign="top">
					<td><strong><label for="wpsc_product_name"><?php _e("Select a Product", 'wp-e-commerce'); ?></label></strong></td>
					<td>
						<select id="wpsc_product_name" name="wpsc_product_name" style="width: 200px">
							<option value="0"><?php _e("No Product", 'wp-e-commerce'); ?></option>
							<?php
								foreach($allProducts as $product)
									echo "<option value=".$product->ID." >".$product->post_title."</option>"."\n";
							?>
						</select><br />
						<span class="description"><?php _e('Select the product you would like to create a shortcode for.', 'wp-e-commerce') ?></span>
					</td>
				</tr>

			<tr valign="top">
			<?php $selected_gateways = get_option( 'custom_gateway_options' );
					if (in_array( 'wpsc_merchant_paypal_standard', (array)$selected_gateways )) {?>

					<td><strong><label for="add_product_buynow"><?php _e("Shortcode:", 'wp-e-commerce'); ?></label></strong></td>
					<td>
						<input type="radio" id="wpsc_product_shortcode" name="wpsc_product_shortcode" value="1"><?php _e('Add a buy now button', 'wp-e-commerce');?>
						<br /><span class="description"><?php _e('This adds a PayPal Buy Now button for the product selected. Clicking on the button will take your customer straight to PayPal.', 'wp-e-commerce') ?></span>
					</td>
			<?php } ?>

				<tr>
					<td></td>
					<td>
						<input type="radio" id="wpsc_product_shortcode" name="wpsc_product_shortcode" value="2"><?php _e('Add an add to cart button', 'wp-e-commerce');?>
						<br /><span class="description"><?php _e('This adds an add to cart button for the product selected.' , 'wp-e-commerce') ?></span>
					</td>
				</tr>

				<tr>
					<td></td>
					<td>
						<input type="radio" id="wpsc_product_shortcode" name="wpsc_product_shortcode" value="3"><?php _e('Add product', 'wp-e-commerce');?>
						<br /><span class="description"><?php _e('This will add the selected product to your page.' , 'wp-e-commerce') ?></span>
					</td>
				</tr>
			</table>
		</div>
	</div>

			<div class="mceActionPanel">
				<div style="float: left">
					<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'wp-e-commerce'); ?>" onclick="tinyMCEPopup.close();" />
				</div>

				<div style="float: right">
					<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'wp-e-commerce'); ?>" onclick="insertWPSCLink();" />
				</div>
			</div>
		</form>
	</body>
</html>
