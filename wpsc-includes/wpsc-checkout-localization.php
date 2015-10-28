<?php

_wpsc_deprecated_file(
	__FILE__,
	'3.9',
	'/wpsc-components/theme-engine-v1/classes/checkout-localization.php',
	__( 'This file has been scoped specifically to the 1.0 theme engine component.', 'wp-e-commerce' )
);

if ( ! function_exists( '_wpsc_countries_localizations' ) ) {
	// make the countries and regions information available to our javascript
	add_filter( 'wpsc_javascript_localizations', '_wpsc_countries_localizations', 10, 1 );

	/**
	 * add countries data to the wpec javascript localizations
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param array 	localizations  other localizations that can be added to
	 *
	 * @return array	localizations array with countries information added
	 */
	function _wpsc_countries_localizations( $localizations_array ) {

		$localizations_array['no_country_selected']       = __( 'Please select a country', 'wp-e-commerce' );
		$localizations_array['no_region_selected_format'] = __( 'Please select a %s', 'wp-e-commerce' );
		$localizations_array['no_region_label']           = __( 'State/Province', 'wp-e-commerce' );
		$localizations_array['base_country']              = get_option( 'base_country' );

		$country_list = array();

		foreach ( WPSC_Countries::get_countries() as $country_id => $wpsc_country ) {
			if ( $wpsc_country->is_visible() ) {
				$country_list[$wpsc_country->get_isocode()] = $wpsc_country->get_name();

				if ( $wpsc_country->has_regions() ) {
					$regions = $wpsc_country->get_regions();

					$region_list = array();
					foreach ( $regions as $region_id => $wpsc_region ) {
						$region_list[$region_id] = $wpsc_region->get_name();
					}

					if ( ! empty ( $region_list ) ) {
						$localizations_array[ 'wpsc_country_'.$wpsc_country->get_isocode() . '_regions' ] = $region_list;
					}
				}

				$region_label = $wpsc_country->get( 'region_label' );
				if ( ! empty( $region_label ) ) {
					$localizations_array['wpsc_country_' . $wpsc_country->get_isocode() . '_region_label' ] = $region_label;
				}
			}
		}

		if ( ! empty( $country_list ) ) {
			$localizations_array['wpsc_countries'] = $country_list;
		}

		return $localizations_array;
	}

	/**
	 * add checkout unique name to form id map to user javascript localizations
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param array[string]     $localizations    other localizations that can be added to
	 *
	 * @return array                      localizations array with checkout information added
	 */
	function _wpsc_localize_checkout_item_name_to_from_id( $localizations ) {
		$localizations['wpsc_checkout_unique_name_to_form_id_map'] = _wpsc_create_checkout_unique_name_to_form_id_map();
		$localizations['wpsc_checkout_item_active']                = _wpsc_create_checkout_item_active_map();
		$localizations['wpsc_checkout_item_required']              = _wpsc_create_checkout_item_required_map();
		return $localizations;
	}

	add_filter( 'wpsc_javascript_localizations', '_wpsc_localize_checkout_item_name_to_from_id', 10, 1 );


	/**
	 * @param array $localizations
	 *
	 * @since 3.8.14.1
	 *
	 * @return array   localizations array with checkout information added
	 */
	function _wpsc_localize_checkout_related_options( $localizations ) {
		$localizations['store_uses_shipping'] = wpsc_is_shipping_enabled();
		return $localizations;
	}

	add_filter( 'wpsc_javascript_localizations', '_wpsc_localize_checkout_related_options', 10, 1 );

	/**
	 * Creates an array mapping from checkout item id to the item name in the field.  array is
	 * localized into the user javascript so that the javascript can find items using the well known names.
	 *
	 * In release 3.8.14 the unique key for a field is available in an element attribute
	 * called 'data-wpsc-meta-key'. Just in case someone out there has a highly customized
	 * checkout experience that doesn't use the WPeC core functions to create the controls
	 * using this map will maintain backwards compatibility.
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 * @return (boolean)
	 */
	function _wpsc_create_checkout_unique_name_to_form_id_map() {
		global $wpsc_checkout;

		if ( empty( $wpsc_checkout ) ) {
			$wpsc_checkout = new wpsc_checkout();
		} else {
			$wpsc_checkout->rewind_checkout_items();
		}

		$checkout_item_map = array();
		while ( wpsc_have_checkout_items() ) {
			$checkout_item = wpsc_the_checkout_item();

			if ( ! empty( $checkout_item->unique_name ) ) {
				$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_element_id();
			}
		}

		$wpsc_checkout->rewind_checkout_items();

		return $checkout_item_map;
	}

	/**
	 *	Create an array of item name and active status
	 * @access public
	 *
	 * @since 3.8.14
	 * @return (boolean)
	 */
	function _wpsc_create_checkout_item_active_map() {
		global $wpsc_checkout;

		if ( empty( $wpsc_checkout ) ) {
			$wpsc_checkout = new wpsc_checkout();
		} else {
			$wpsc_checkout->rewind_checkout_items();
		}

		$checkout_item_map = array();
		while ( wpsc_have_checkout_items() ) {
			$checkout_item = wpsc_the_checkout_item();

			if ( ! empty( $checkout_item->unique_name ) ) {
				$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_element_active();
			}
		}

		$wpsc_checkout->rewind_checkout_items();

		return $checkout_item_map;

	}

	/**
	 *	Create an array of item name and active status
	 * @access public
	 *
	 * @since 3.8.14
	 * @return (boolean)
	 */
	function _wpsc_create_checkout_item_required_map() {
		global $wpsc_checkout;

		if ( empty( $wpsc_checkout ) ) {
			$wpsc_checkout = new wpsc_checkout();
		}

		$checkout_item_map = array();
		while ( wpsc_have_checkout_items() ) {
			$checkout_item = wpsc_the_checkout_item();

			if ( ! empty( $checkout_item->unique_name ) ) {
				$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_name_is_required();
			}
		}

		$wpsc_checkout->rewind_checkout_items();

		return $checkout_item_map;

	}


	/**
	 * On the checkout page create a hidden elements holding current customer meta values
	 *
	 * This let's the wp-e-commerce javascript process any dependency rules even if the store has configured
	 * the checkout forms so that some fields are hidden.  The most important of these fields are the
	 * country, region and state fields. But it's just as easy to include all of them and not worry about
	 * what various parts of WPeC, themes or plugs may be doing.
	 *
	 * @since 3.8.14
	 *
	 * @access private
	 */
	function _wpsc_customer_meta_into_checkout_page() {

		$checkout_metas = _wpsc_get_checkout_meta();

		foreach ( $checkout_metas as $key => $value ) {
			?>
			<input class="wpsc-meta-value" type="hidden" value="<?php echo esc_attr( $value );?>" data-wpsc-meta-key="<?php echo esc_attr( $key );?>" />
			<?php
		}

	}

	add_action( 'wpsc_before_shopping_cart_page', '_wpsc_customer_meta_into_checkout_page' );



	/**
	 * On the checkout page create a hidden element holding the acceptable shipping countries
	 *
	 * This let's the wp-e-commerce javascript process any dependency rules even if the store has configured
	 * the checkout forms so that some fields are hidden.  The most important of these fields are the
	 * country, region and state fields. But it's just as easy to include all of them and not worry about
	 * what various parts of WPeC, themes or plugs may be doing.
	 *
	 * @since 3.8.14
	 *
	 * @access private
	 */
	function _wpsc_acceptable_shipping_countries_into_checkout_page() {

		$acceptable_countries = wpsc_get_acceptable_countries();

		// if the acceptable countries is true all available countries can be shipped to,
		// otherwise we are going to restrict the countries list
		if ( $acceptable_countries !== true ) {

			$country_code_list = array();
			foreach ( $acceptable_countries as $key => $country_id ) {
				$wpsc_country = new WPSC_Country( $country_id );
				$country_code_list[$wpsc_country->get_isocode()] = $wpsc_country->get_name();
			}
			?>
			<script type="text/javascript">
			/* <![CDATA[ */
				var wpsc_acceptable_shipping_countries = <?php echo json_encode( $country_code_list ); ?>;
			/* ]]> */
			</script>
			<?php
		}
	}

	add_action( 'wpsc_before_shopping_cart_page', '_wpsc_acceptable_shipping_countries_into_checkout_page', 10, 0 );
}