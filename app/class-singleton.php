<?php

namespace WP_Stockroom\App;

/**
 * Class Singleton
 *
 * Make a class into a singleton by adding `use Singleton;`
 *
 * phpcs:disable Squiz.Commenting.FunctionComment
 * phpcs:disable Generic.Commenting.DocComment
 *
 * @package WP_Stockroom\App
 */
trait Singleton {

	/**
	 * @var self
	 */
	private static $inst;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( ! static::$inst ) { // @phpstan-ignore-line
			static::$inst = new self(); // @phpstan-ignore-line
		}

		return static::$inst; // @phpstan-ignore-line
	}

	protected function __clone() {
	}

	public function __sleep() {
		// @phpstan-ignore-line
	}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * @return self
	 */
	private function __construct() {
		// @phpstan-ignore-line
		// Possible extend.
		return $this; // @phpstan-ignore-line
	}
}
