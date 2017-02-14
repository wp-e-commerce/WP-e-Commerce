<?php
/**
 * The WP eCommerce Base Query Registry Class
 *
 * @package wp-e-commerce
 * @since 3.12.0
 */

abstract class WPSC_Query_Registry extends WPSC_Query_Base {

	/**
	 * Array of all instances.
	 *
	 * @since 3.12.0
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Add a WPSC_Query_Base instance object to the registry.
	 *
	 * @since 3.12.0
	 *
	 * @param WPSC_Query_Base $instance WPSC_Query_Base instance.
	 */
	protected static function add_instance( WPSC_Query_Base $instance ) {
		self::$instances[ get_class( $instance ) ][ $instance->instance_id() ] = $instance;

		return $instance;
	}

	/**
	 * Remove a WPSC_Query_Base instance object from the registry.
	 *
	 * @since 3.12.0
	 *
	 * @param string $instance_id A WPSC_Query_Base instance id.
	 * @param string $class_name  The name of the instance class. Optional if $instance_id is an object.
	 */
	protected static function remove_instance( $instance_id, $class_name = '' ) {
		$class_name = is_object( $instance_id ) ? get_class( $instance_id ) : $class_name;
		if ( empty( $class_name ) ) {
			throw new Exception( sprintf( __( '%s requires a class name be provided', 'wp-e-commerce' ), __METHOD__ ), __LINE__ );
		}

		if ( isset( self::$instances[ $class_name ][ $instance_id ] ) ) {
			unset( self::$instances[ $class_name ][ $instance_id ] );
		}
	}

	/**
	 * Retrieve a WPSC_Query_Base instance by instance id.
	 * a `get_instance` method is required in extended class.
	 * Extended method should call:
	 * `parent::_get_instance( __CLASS__, $id )`,
	 * and if not found, call:
	 * `$instance = parent::add_instance( new self( $log ) );`
	 *
	 * @since 3.12.0
	 *
	 * @param string $class_name  The name of the instance class.
	 * @param string $instance_id A WPSC_Query_Base instance id.
	 *
	 * @return WPSC_Query_Base|bool False or WPSC_Query_Base object instance.
	 */
	protected static function _get_instance( $class_name, $instance_id ) {
		if ( empty( self::$instances[ $class_name ][ $instance_id ] ) ) {
			return false;
		}

		return self::$instances[ $class_name ][ $instance_id ];
	}

	/**
	 * Retrieve all WPSC_Query_Base instances registered.
	 *
	 * @since  3.12.0
	 *
	 * @param  string $class_name  The name of the class for which to fetch all instances.
	 *
	 * @return WPSC_Query_Base[] Array of all registered instance instances.
	 */
	public static function get_all( $class_name = '' ) {
		if ( $class_name ) {
			return isset( self::$instances[ $class_name ] ) ? self::$instances[ $class_name ] : array();
		}

		return self::$instances;
	}

	/**
	 * Retrieves the unique identifier for a WPSC_Query_Base instance.
	 *
	 * @since  3.12.0
	 *
	 * @return mixed
	 */
	abstract public function instance_id();

}
