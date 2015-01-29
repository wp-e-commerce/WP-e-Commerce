<?php

class TestWPSCCouponClass extends WP_UnitTestCase {

	const START_DATE  = '2015-03-06';
	const EXPIRY_DATE = '2015-04-16';

	const PERCENTAGE_COUPON_ID      = 1;
	const PERCENTAGE_COUPON_CODE    = 'TEST_PERCENTAGE';
	const FIXED_COUPON_ID           = 2;
	const FIXED_COUPON_CODE         = 'TEST_FIXED';
	const FREE_SHIPPING_COUPON_ID   = 3;
	const FREE_SHIPPING_COUPON_CODE = 'TEST_FREE_SHIPPING';
	const USE_ONCE_COUPON_ID        = 4;
	const USE_ONCE_COUPON_CODE      = 'TEST_USE_ONCE';
	const EXPIRY_COUPON_ID          = 5;
	const EXPIRY_COUPON_CODE        = 'TEST_EXPIRY';
	const DELETE_COUPON_ID          = 6;
	const DELETE_COUPON_CODE        = 'TEST_DELETE';
	const CONDITIONS_COUPON_ID      = 7;
	const CONDITIONS_COUPON_CODE    = 'TEST_CONDITIONS';
	const ACTIVE_COUPON_ID          = 8;
	const ACTIVE_COUPON_CODE        = 'TEST_ACTIVE';

	function setUp() {
		wpsc_create_or_update_tables();
		$this->setup_test_data();
		parent::setUp();
	}

	function tearDown() {
		parent::tearDown();
	}

	function setup_test_data() {

		global $wpdb;

		$truncate = $wpdb->query( "TRUNCATE TABLE `" . WPSC_TABLE_COUPON_CODES . "`" );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::PERCENTAGE_COUPON_ID,
			'coupon_code'   => self::PERCENTAGE_COUPON_CODE,
			'value'         => '50',
			'is-percentage' => 1,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::FIXED_COUPON_ID,
			'coupon_code'   => self::FIXED_COUPON_CODE,
			'value'         => '5',
			'is-percentage' => 0,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::FREE_SHIPPING_COUPON_ID,
			'coupon_code'   => self::FREE_SHIPPING_COUPON_CODE,
			'value'         => '5',
			'is-percentage' => 2,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::USE_ONCE_COUPON_ID,
			'coupon_code'   => self::USE_ONCE_COUPON_CODE,
			'value'         => '10',
			'is-percentage' => 0,
			'use-once'      => 1,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::EXPIRY_COUPON_ID,
			'coupon_code'   => self::EXPIRY_COUPON_CODE,
			'value'         => '10',
			'is-percentage' => 0,
			'use-once'      => 1,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => self::START_DATE,
			'expiry'        => self::EXPIRY_DATE,
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::DELETE_COUPON_ID,
			'coupon_code'   => self::DELETE_COUPON_CODE,
			'value'         => '10',
			'is-percentage' => 0,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 0,
			'every_product' => 0,
			'start'         => self::START_DATE,
			'expiry'        => self::EXPIRY_DATE,
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::CONDITIONS_COUPON_ID,
			'coupon_code'   => self::CONDITIONS_COUPON_CODE,
			'value'         => '10',
			'is-percentage' => 0,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 0,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( $this->get_test_conditions() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

		$wpdb->insert( WPSC_TABLE_COUPON_CODES, array(
			'id'            => self::ACTIVE_COUPON_ID,
			'coupon_code'   => self::ACTIVE_COUPON_CODE,
			'value'         => '10',
			'is-percentage' => 0,
			'use-once'      => 0,
			'is-used'       => 0,
			'active'        => 1,
			'every_product' => 0,
			'start'         => '0000-00-00',
			'expiry'        => '0000-00-00',
			'condition'     => serialize( array() )
		), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ) );

	}

	function get_test_conditions() {

		return array(

			array(
				'operator' => '',
				'property' => 'item_name',
				'logic'    => 'equal',
				'value'    => 'Test Product'
			),

			array(
				'operator' => 'and',
				'property' => 'item_quantity',
				'logic'    => 'greater',
				'value'    => '1'
			)

		);

	}

	function set_current_user_role( $role ) {

		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );

	}

	function test_invalid_coupon_construct() {

		// Shouldn't allow non-numeric ID.
		$coupon = new WPSC_Coupon( 'XXX' );
		$data = $coupon->get_data();
		$this->assertEmpty( $data );

		// Shouldn't allow negative ID.
		$coupon = new WPSC_Coupon( -1 );
		$data = $coupon->get_data();
		$this->assertEmpty( $data );

	}

	function test_insert_coupon() {

		global $wpdb;

		$this->set_current_user_role( 'administrator' );

		$coupon = new WPSC_Coupon( array(
			'coupon_code'   => self::PERCENTAGE_COUPON_CODE,
			'value'         => '50',
			'is-percentage' => '1',
			'active'        => '1'
		) );

		$result = $coupon->save();
		$result_id = $wpdb->insert_id;

		$this->assertEquals( 1, $result );
		$this->assertTrue( $result_id > 0 );

		$new_coupon = new WPSC_Coupon( $result_id );
		$this->assertEquals( self::PERCENTAGE_COUPON_CODE, $new_coupon->get( 'coupon_code' ) );

	}

	function test_save() {

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$exists = $coupon->exists();
		$this->assertTrue( $exists );

		if ( $exists ) {

			$coupon->set( 'coupon_code', self::PERCENTAGE_COUPON_CODE );
			$coupon->save();
			$this->assertEquals( self::PERCENTAGE_COUPON_CODE, $coupon->get( 'coupon_code' ) );

			$coupon->set( 'coupon_code', 'XXX' );
			$coupon->save();
			$this->assertEquals( 'XXX', $coupon->get( 'coupon_code' ) );

		}

	}

	function test_delete_coupon() {

		$this->set_current_user_role( 'administrator' );

		$coupon = new WPSC_Coupon( self::DELETE_COUPON_ID );
		$this->assertTrue( $coupon->exists() );

		$deleted = $coupon->delete();
		$this->assertEquals( 1, $deleted );

		$this->assertFalse( $coupon->exists() );

	}

	function test_get_code() {

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$exists = $coupon->exists();
		$this->assertTrue( $exists );

		if ( $exists ) {
			$coupon->set( 'coupon_code', self::PERCENTAGE_COUPON_CODE );
			$coupon->save();
			$coupon->delete_cache();

			$new_coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
			$this->assertEquals( self::PERCENTAGE_COUPON_CODE, $new_coupon->get( 'coupon_code' ) );

		}

	}

	function test_is_free_shipping() {

		$coupon = new WPSC_Coupon( self::FREE_SHIPPING_COUPON_ID );
		$free_shipping = $coupon->is_free_shipping();
		$this->assertTrue( $free_shipping );

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$free_shipping = $coupon->is_free_shipping();
		$this->assertFalse( $free_shipping );

	}

	function test_is_coupon_used() {

		$coupon = new WPSC_Coupon( self::USE_ONCE_COUPON_ID );
		$this->assertTrue( $coupon->is_active() );
		$this->assertTrue( $coupon->is_use_once() );
		$this->assertFalse( $coupon->is_used() );

		$coupon->used();
		$this->assertTrue( $coupon->is_used() );
		$this->assertFalse( $coupon->is_active() );

	}

	function test_coupon_data() {

		global $wpdb;

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$exists = $coupon->exists();
		$this->assertTrue( $exists );

		if ( $exists ) {

			$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d", self::PERCENTAGE_COUPON_ID ), ARRAY_A );

			$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );

			// Coupon API unserialises conditions automatically
			$this->assertEquals( $coupon_data['coupon_code'], $coupon->get( 'coupon_code' ) );
			$this->assertEquals( $coupon_data['value'], $coupon->get( 'value' ) );
			$this->assertEquals( $coupon_data['is-percentage'], $coupon->get( 'is-percentage' ) );
			$this->assertEquals( $coupon_data['use-once'], $coupon->get( 'use-once' ) );
			$this->assertEquals( $coupon_data['is-used'], $coupon->get( 'is-used' ) );
			$this->assertEquals( $coupon_data['active'], $coupon->get( 'active' ) );
			$this->assertEquals( $coupon_data['every_product'], $coupon->get( 'every_product' ) );
			$this->assertEquals( $coupon_data['start'], $coupon->get( 'start' ) );
			$this->assertEquals( $coupon_data['expiry'], $coupon->get( 'expiry' ) );
			$this->assertEquals( unserialize( $coupon_data['condition'] ), $coupon->get( 'condition' ) );

		}

	}

	function test_coupon_has_conditions() {

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$this->assertFalse( $coupon->has_conditions() );

		$coupon_cond = new WPSC_Coupon( self::CONDITIONS_COUPON_ID );
		$this->assertTrue( $coupon_cond->has_conditions() );

	}

	function test_coupon_conditions() {

		$coupon = new WPSC_Coupon( self::PERCENTAGE_COUPON_ID );
		$this->assertEmpty( $coupon->get( 'condition' ) );

		$coupon_cond = new WPSC_Coupon( self::CONDITIONS_COUPON_ID );
		$this->assertTrue( is_array( $coupon_cond->get( 'condition' ) ) );
		$this->assertNotEmpty( $coupon_cond->get( 'condition' ) );

	}

	function test_active() {

		$coupon = new WPSC_Coupon( self::ACTIVE_COUPON_ID );

		$this->assertTrue( $coupon->is_active() );

		$coupon->deactivate();
		$this->assertFalse( $coupon->is_active() );

		$coupon->activate();
		$this->assertTrue( $coupon->is_active() );

	}

}
