<?php

class TestWPSCCountryClass extends WP_UnitTestCase {

	// The main country used for tests, has no regions.
	const COUNTRY_ID_WITHOUT_REGIONS                        = 223;
	const COUNTRY_ISOCODE_WITHOUT_REGIONS                   = 'GB';
	const COUNTRY_NAME_WITHOUT_REGIONS                      = 'United Kingdom';
	const COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_CODE        = 'GBP';
	const COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_NAME        = 'Pound Sterling';
	const COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_SYMBOL      = '£';
	const COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_SYMBOL_HTML = '&#163;';
	const COUNTRY_WITHOUT_REGIONS_TAX_RATE                  = 17.5;
	const COUNTRY_WITHOUT_REGIONS_CONTINENT                 = 'europe';

	// A country with regions used for tests that need a region.
	const COUNTRY_ID_WITH_REGIONS = 136;
	const REGION_ID               = 50;
	const NUM_REGIONS             = 51;
	const REGION_CODE             = 'OR';
	const REGION_NAME             = 'Oregon';
	const INVALID_REGION_NAME     = 'Oregano';

	function setUp() {
		wpsc_create_or_update_tables();
		parent::setUp();
	}

	function tearDown() {
		parent::tearDown();
	}

	function test_invalid_country_construct() {

		$country = new WPSC_Country( 'XXX' );
		$this->assertTrue( empty( $country->_id ) && empty( $country->_name ) && empty( $country->_isocode ) );

		// // This should definitely return false/null - no?
		$country = new WPSC_Country( -1 );
		$this->assertTrue( empty( $country->_id ) && empty( $country->_name ) && empty( $country->_isocode ) );
	}

	function test_valid_country_construct() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS ); // UK
		$this->assertInstanceOf( 'WPSC_Country', $country );
		$this->assertEquals( self::COUNTRY_ID_WITHOUT_REGIONS, $country->get( 'id' ) );
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS, $country->get( 'name' ) );
	}

	function test_get_name() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS, $country->get_name() );
	}

	function test_get_id() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$this->assertEquals( self::COUNTRY_ID_WITHOUT_REGIONS, $country->get_id() );
	}

	function test_get_isocode() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$this->assertEquals( self::COUNTRY_ISOCODE_WITHOUT_REGIONS, $country->get_isocode() );
	}

	function test_get_currency() {
		$country  = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$currency = $country->get_currency();
		$this->assertInstanceOf( 'WPSC_Currency', $currency );
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_CODE, $currency->code );
	}

	function test_get_currency_name() {
		$country       = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$currency_name = $country->get_currency_name();
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_NAME, $currency_name );
	}

	function test_get_currency_symbol() {
		$country         = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$currency_symbol = $country->get_currency_symbol();
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_SYMBOL, $currency_symbol );
	}

	function test_get_currency_symbol_html() {
		$country         = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$currency_symbol = $country->get_currency_symbol_html();
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_SYMBOL_HTML, $currency_symbol );
	}

	function test_get_currency_code() {
		$country       = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$currency_code = $country->get_currency_code();
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS_CURRENCY_CODE, $currency_code );
	}

	function test_has_regions() {
		$country       = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$has_regions   = $country->has_regions();
		$this->assertFalse( $has_regions );
		$country       = new WPSC_Country( self::COUNTRY_ID_WITH_REGIONS ); // USA
		$has_regions   = $country->has_regions();
		$this->assertTrue( $has_regions );
	}

	function test_has_region() {
		// UK
		$country    = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$has_region = $country->has_region( REGION_ID );
		$this->assertFalse( $has_region ); // Oregon is not in the UK
		$has_region = $country->has_region( -1 );
		$this->assertFalse( $has_region ); // Non-existent region is not in the UK

		// USA
		$country    = new WPSC_Country(  self::COUNTRY_ID_WITH_REGIONS  );
		$has_region = $country->has_region( self::REGION_ID );
		$this->assertTrue( $has_region ); // Oregon is in the USA
		$has_region = $country->has_region( self::REGION_NAME );
		$this->assertTrue( $has_region ); // Oregon is in the USA
		$has_region = $country->has_region( self::INVALID_REGION_NAME );
		$this->assertFalse( $has_region ); // Oregano is not
		$has_region = $country->has_region( -1 );
		$this->assertFalse( $has_region ); // Imaginary state is not in the USA
	}

	function test_get_tax() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$tax     = $country->get_tax();
		$this->assertEquals( self::COUNTRY_WITHOUT_REGIONS_TAX_RATE, $tax );
	}

	function test_get_continent() {
		$country   = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$continent = $country->get_continent();
		$this->assertEquals( self::COUNTRY_WITHOUT_REGIONS_CONTINENT, $continent );
	}

	function test_is_visible() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$visible = $country->is_visible();
		$this->assertEquals( '1', $visible );
	}

	function test_get() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$id = $country->get( 'id' );
		$this->assertEquals( self::COUNTRY_ID_WITHOUT_REGIONS, $id );
		$name = $country->get( 'name' );
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS, $name );
		$invalid = $country->get( 'omgwtfbbq' );
		$this->assertNull( $invalid );
	}

	function test_set() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$country->set( 'name', 'XXX' ); // This should not set the property.
		$name = $country->get( 'name' );
		$this->assertEquals( self::COUNTRY_NAME_WITHOUT_REGIONS, $name );
		$country->set( 'isocode', 'XX' ); // This should not set the property.
		$isocode = $country->get( 'isocode' );
		$this->assertEquals( self::COUNTRY_ISOCODE_WITHOUT_REGIONS, $isocode );
		$country->set( 'omgwtfbbq_new', 'OMG' ); // This SHOULD set the property.
		$omg = $country->get( 'omgwtfbbq_new' );
		$this->assertEquals( 'OMG', $omg );
		$country->set( 'omgwtfbbq_new', '' ); // Clear it out.
		$omg = $country->get( 'omgwtfbbq_new' );
		$this->assertEquals( '', $omg );
	}

	function test_get_region() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITH_REGIONS );
		$region  = $country->get_region( self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( self::REGION_ID  , $region->get_id() );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
	}

	function test_get_region_count() {
		$country = new WPSC_Country(  self::COUNTRY_ID_WITH_REGIONS  ); // USA
		$region_count = $country->get_region_count();
		$this->assertEquals( self::NUM_REGIONS, $region_count );
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS ); // UK
		$region_count = $country->get_region_count();
		$this->assertEquals( 0, $region_count );
	}

	function test_get_regions() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITH_REGIONS ); // USA
		$regions = array_values( $country->get_regions() );
		$this->assertInstanceOf( 'WPSC_Region', $regions[0] );
		$region_count = count( $regions );
		$this->assertEquals( self::NUM_REGIONS, $region_count );

		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS ); // UK
		$regions = $country->get_regions();
		$this->assertInternalType( 'array', $regions );
		$region_count = count( $regions );
		$this->assertEquals( 0, $region_count );
	}

	function test_get_regions_array() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITH_REGIONS );
		$regions = array_values( $country->get_regions( true ) );
		$this->assertInternalType( 'array', $regions[0] );
		$this->assertEquals( self::NUM_REGIONS, count( $regions ) );
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$regions = $country->get_regions( true );
		$this->assertInternalType( 'array', $regions );
		$this->assertEquals( 0, count( $regions ) );
	}

	function test_get_region_id_by_region_code() {
		$country = new WPSC_Country(  self::COUNTRY_ID_WITH_REGIONS  ); // USA
		$region_id = $country->get_region_id_by_region_code( self::REGION_CODE ); // Oregon
		$this->assertEquals( self::REGION_ID, $region_id );
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS ); // UK
		$region_id = $country->get_region_id_by_region_code( self::REGION_CODE ); // Oregon
	}

	function test_get_region_id_by_region_name() {
		$country = new WPSC_Country(  self::COUNTRY_ID_WITH_REGIONS  ); // USA
		$region_id = $country->get_region_id_by_region_name( 'Oregon' );
		$this->assertEquals( self::REGION_ID, $region_id );
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS ); // UK
		$region_id = $country->get_region_id_by_region_name( 'Oregon' );
	}

	function test_as_array() {
		$country = new WPSC_Country( self::COUNTRY_ID_WITHOUT_REGIONS );
		$regions = $country->as_array();
		$this->assertInternalType( 'array', $regions );
	}

}

