<?php

class TestWPSCRegionClass extends WP_UnitTestCase {

	// Test country / regions
	const COUNTRY_ID_WITH_REGIONS      = 136;
	const COUNTRY_ISOCODE_WITH_REGIONS = 'US';
	const REGION_ID                    = 50;
	const REGION_CODE                  = 'OR';
	const REGION_NAME                  = 'Oregon';
	const INVALID_REGION_NAME          = 'Oregano';
	const REGION_TAX_RATE              = 0.0;

	function test_contructor() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->get_id() );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
		$region = new WPSC_Region( self::COUNTRY_ISOCODE_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->get_id() );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_CODE );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->get_id() );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
		$region = new WPSC_Region( self::COUNTRY_ISOCODE_WITH_REGIONS, self::REGION_CODE );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->get_id() );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
	}

	function test_get_id() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( 50, $region->get_id() );
	}

	function test_get_name() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( self::REGION_NAME, $region->get_name() );
	}

	function test_get_code() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( self::REGION_CODE, $region->get_code() );
	}

	function test_get_tax() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( self::REGION_TAX_RATE, $region->get_tax() );
	}

	function test_get_country_id() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$this->assertEquals( self::COUNTRY_ID_WITH_REGIONS, $region->get_country_id() );
	}


	function test_as_array() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$this->assertInstanceOf( 'WPSC_Region', $region );
		$region_as_array = $region->as_array();
		$this->assertInternalType( 'array', $region_as_array );
	}

	function test_get() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		print_r($region);
		$this->assertEquals( self::REGION_ID, $region->get( 'id' ) );
		$this->assertEquals( self::REGION_NAME, $region->get( 'name' ) );
		$this->assertNull( $region->get( 'omgwtfbbq' ) );
	}

	function test_set() {
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$region->set( 'name', self::INVALID_REGION_NAME ); // This should not set the value
		$this->assertEquals( self::REGION_NAME, $region->get( 'name' ) );
		$region = new WPSC_Region( self::COUNTRY_ID_WITH_REGIONS, self::REGION_ID );
		$region->set( 'omgwtfbbq', 'OMG' ); // This should set the value
		$this->assertEquals( 'OMG', $region->get( 'omgwtfbbq' ) );
		$region->set( 'omgwtfbbq', '' );
	}
}

