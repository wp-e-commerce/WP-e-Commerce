<?php

/**
 * A new way of using the WordPress API
 *
 * @package Sputnik
 * @subpackage Public Utilities
 */
class Sputnik_Library_Plugin extends Sputnik_Library_Plugin_Base {
	/**
	 * Register hooks automatically
	 *
	 * Ensure you call this from your child class
	 *
	 * @param boolean $enable_prefixes Whether to enable prefixed methods (i.e. `action_init` or `filter_the_title`)
	 */
	protected static function register_hooks($enable_prefixes = false) {
		if (function_exists('get_called_class') && false) {
			$class = get_called_class();
		}
		else {
			$trace = debug_backtrace();
			$class = $trace[1]['class'];
		}
		self::_register_hooks($enable_prefixes, $class);
	}

	/**
	 * Add a method as a filter
	 *
	 * This is exactly the same as {@see add_filter()} but instead of passing
	 * a full callback, only the method needs to be passed in.
	 *
	 * @param string $hook Filter name
	 * @param string $method Method name on current class, or priority (as an int)
	 * @param int $priority Specify the order in which the functions associated with a particular action are executed (default: 10)
	 * @param int $accepted_args Number of parameters which callback accepts (default: corresponds to method prototype)
	 * @param string $class Internal use only
	 */
	protected static function add_filter($hook, $method = null, $priority = 10, $params = null, $class = null) {
		if ($method === null) {
			$method = $hook;
		}
		elseif (is_int($method)) {
			$priority = $method;
			$method = $hook;
		}

		if ($class === null) {
			if (function_exists('get_called_class') && false) {
				$class = get_called_class();
			}
			else {
				$trace = debug_backtrace();
				do {
					array_shift($trace);
				}
				while (empty($trace[0]['class']) && !empty($trace));
				if (empty($trace)) {
					throw new BadMethodCallException('Must be called from a class');
				}
				$class = $trace[0]['class'];
			}
		}

		if (!method_exists($class, $method)) {
			throw new InvalidArgumentException('Method does not exist');
		}

		if ($params === null) {
			$ref = new ReflectionMethod($class, $method);
			$params = $ref->getNumberOfParameters();
		}

		return add_filter($hook, array($class, $method), $priority, $params);
	}

	/**
	 * Add a method as a action
	 *
	 * This is exactly the same as {@see add_action()} but instead of passing
	 * a full callback, only the method needs to be passed in.
	 *
	 * @internal This is duplication, but ensures consistency with WordPress API
	 * @param string $hook Action name
	 * @param string $method Method name on current class, or priority (as an int)
	 * @param int $priority Specify the order in which the functions associated with a particular action are executed (default: 10)
	 * @param int $accepted_args Number of parameters which callback accepts (default: corresponds to method prototype)
	 * @param string $class Internal use only
	 */
	protected static function add_action($hook, $method = null, $priority = 10, $params = null, $class = null) {
		if ($method === null) {
			$method = $hook;
		}
		elseif (is_int($method)) {
			$priority = $method;
			$method = $hook;
		}

		if ($class === null) {
			if (function_exists('get_called_class') && false) {
				$class = get_called_class();
			}
			else {
				$trace = debug_backtrace();
				do {
					array_shift($trace);
				}
				while (empty($trace[0]['class']) && !empty($trace));
				if (empty($trace)) {
					throw new BadMethodCallException('Must be called from a class');
				}
				$class = $trace[0]['class'];
			}
		}

		if (!method_exists($class, $method)) {
			throw new InvalidArgumentException('Method does not exist');
		}

		if ($params === null) {
			$ref = new ReflectionMethod($class, $method);
			$params = $ref->getNumberOfParameters();
		}

		return add_action($hook, array($class, $method), $priority, $params);
	}
}
