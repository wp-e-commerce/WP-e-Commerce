<?php

class TestWPSCCountryClass extends WP_UnitTestCase {

	function test_invalid_country_construct() {
		// This actually returns an empty country object. Shouldn't it return false/null or at
		// least populate the passed ID/ISOCODE into the object returned?
		$country = new WPSC_Country( 'XXX' );
		$this->assertFalse( $country );

		// // This should definitely return false/null - no?
		$country = new WPSC_Country( -1 );
		$this->assertFalse( $country );
	}

	function test_valid_country_construct() {
		$country = new WPSC_Country( 223 ); // UK
		$this->assertInstanceOf( 'WPSC_Country', $country );
		$this->assertEquals( 223, $country->get( 'id' ) );
		$this->assertEquals( 'United Kingdom', $country->get( 'name' ) );
	}

	function test_get_name() {
		$country = new WPSC_Country( 223 );
		$this->assertEquals( 'United Kingdom', $country->get_name() );
	}

	function test_get_id() {
		$country = new WPSC_Country( 223 );
		$this->assertEquals( 223, $country->get_id() );
	}

	function test_get_isocode() {
		$country = new WPSC_Country( 223 );
		$this->assertEquals( 'GB', $country->get_isocode() );
	}

	function test_get_currency() {
		$country  = new WPSC_Country( 223 );
		$currency = $country->get_currency();
		$this->assertInstanceOf( 'WPSC_Currency', $currency );
		$this->assertEquals( 'GBP', $currency->code );
	}

	function test_get_currency_name() {
		$country       = new WPSC_Country( 223 );
		$currency_name = $country->get_currency_name();
		$this->assertEquals( 'Pound Sterling', $currency_name );
	}

	function test_get_currency_symbol() {
		$country         = new WPSC_Country( 223 );
		$currency_symbol = $country->get_currency_symbol();
		$this->assertEquals( '£', $currency_symbol );
	}

	function test_get_currency_symbol_html() {
		$country         = new WPSC_Country( 223 );
		$currency_symbol = $country->get_currency_symbol_html();
		$this->assertEquals( '&#163;', $currency_symbol );
	}

	function test_get_currency_code() {
		$country       = new WPSC_Country( 223 );
		$currency_code = $country->get_currency_code();
		$this->assertEquals( 'GBP', $currency_code );
	}

	function test_has_regions() {
		$country       = new WPSC_Country( 223 );
		$has_regions   = $country->has_regions();
		$this->assertFalse( $has_regions );
		$country       = new WPSC_Country( 136 ); // USA
		$has_regions   = $country->has_regions();
		$this->assertTrue( $has_regions );
	}

	function test_has_region() {
		// UK
		$country    = new WPSC_Country( 223 );
		$has_region = $country->has_region( 50 );
		$this->assertFalse( $has_region ); // Oregon is not in the UK
		$has_region = $country->has_region( -1 );
		$this->assertFalse( $has_region ); // Oregon is not in the UK

		// USA
		$country    = new WPSC_Country(  136  );
		$has_region = $country->has_region( 50 );
		$this->assertTrue( $has_region ); // Oregon is in the USA
		$has_region = $country->has_region( 'oregon' );
		$this->assertTrue( $has_region ); // Oregon is in the USA
		$has_region = $country->has_region( 'oregano' );
		$this->assertFalse( $has_region ); // Oregano is not
		$has_region = $country->has_region( -1 );
		$this->assertFalse( $has_region ); // Imaginary state is not in the USA
	}

	function test_get_tax() {
		$country = new WPSC_Country( 223 );
		$tax     = $country->get_tax();
		$this->assertEquals( 20, $tax );
	}

	function test_get_continent() {
		$country   = new WPSC_Country( 223 );
		$continent = $country->get_continent();
		$this->assertEquals( 'europe', $continent );
	}

	function test_is_visible() {
		$country = new WPSC_Country( 223 );
		$visible = $country->is_visible();
		$this->assertTrue( $visible );
	}

	function test_get() {
		$country = new WPSC_Country( 223 );
		$id = $country->get( 'id' );
		$this->assertEquals( 223, $id );
		$name = $country->get( 'name' );
		$this->assertEquals( 'United Kingdom', $name );
		$invalid = $country->get( 'omgwtfbbq' );
		$this->assertNull( $invalid );
	}

	function test_set() {
		$country = new WPSC_Country( 223 );
		$country->set( 'name', 'Great Britain' ); // This should not set the property.
		$name = $country->get( 'name' );
		$this->assertEquals( 'United Kingdom', $name );
		$country->set( 'isocode', 'UK' ); // This should not set the property.
		$isocode = $country->get( 'isocode' );
		$this->assertEquals( 'GB', $isocode );
		$country->set( 'omgwtfbbq_new', 'OMG' ); // This SHOULD set the property.
		$omg = $country->get( 'omgwtfbbq_new' );
		$this->assertEquals( 'OMG', $omg );
		$country->set( 'omgwtfbbq_new', '' ); // Clear it out.
		$omg = $country->get( 'omgwtfbbq_new' );
		$this->assertEquals( '', $omg );
	}

	function test_get_region() {
		$country = new WPSC_Country(  136  );
		$region  = $country->get_region( 50 );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->id );
		$this->assertEquals( 'Oregon', $region->name );
	}

	function test_get_region_count() {
		$country = new WPSC_Country(  136  ); // USA
		$region_count = $country->get_region_count();
		$this->assertEquals( 51, $region_count );
		$country = new WPSC_Country(  223  ); // UK
		$region_count = $country->get_region_count();
		$this->assertEquals( 0, $region_count );
	}

	function test_get_regions() {
		$country = new WPSC_Country(  136  ); // USA
		$regions = $country->get_regions();
		// @TODO - We should assert that we've received an object
		// @TODO - We should also test passing true to get_regions, and that we get an array bacl
		$region_count = count( $regions );
		$this->assertEquals( 51, $region_count );

		$country = new WPSC_Country(  223  ); // UK
		$regions = $country->get_regions();
		$region_count = count( $regions );
		$this->assertEquals( 0, $region_count );
	}

	function test_get_regions_array() {
		// @TODO
	}

	function test_get_region_code_by_region_id() {
		$country = new WPSC_Country(  136  ); // USA
		$region_code = $country->get_region_code_by_region_id( 50 ); // Oregon
		$this->assertEquals( 'OR', $region_code );
		$country = new WPSC_Country(  223  ); // UK
		$region_code = $country->get_region_code_by_region_id( 50 ); // Oregon
		$this->assertEquals( '', $region_code ); // @TODO - What should we assert here?
	}

	function test_get_region_id_by_region_code() {
		$country = new WPSC_Country(  136  ); // USA
		$region_id = $country->get_region_id_by_region_code( 'OR' ); // Oregon
		$this->assertEquals( 50, $region_id );
		$country = new WPSC_Country(  223  ); // UK
		$region_id = $country->get_region_id_by_region_code( 'OR' ); // Oregon
		$this->assertEquals( '', $region_code ); // @TODO - What should we assert here?
	}

	function test_get_region_id_by_region_name() {
		$country = new WPSC_Country(  136  ); // USA
		$region_id = $country->get_region_id_by_region_name( 'Oregon' );
		$this->assertEquals( 50, $region_id );
		$country = new WPSC_Country(  223  ); // UK
		$region_id = $country->get_region_id_by_region_name( 'Oregon' );
		$this->assertEquals( '', $region_code ); // @TODO - What should we assert here?
	}

}

