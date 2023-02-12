<?php

namespace WP_Stockroom\App\Rest;

use WP_Stockroom\App\Singleton;

/**
 * Class Package
 *
 * @package WP_Stockroom\App
 */
class Meta {
	use Singleton;

	/**
	 * Register meta fields.
	 *
	 * @return void
	 */
	public function rest_fields() {
		register_rest_field(
			'attachment',
			'package',
			array(
				'get_callback'    => array( $this, 'get_package_meta' ),
				'update_callback' => array( $this, 'update_package_meta' ),
				'schema'          => array(
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => '\sanitize_title',
					),
				),
			)
		);

		register_rest_field(
			'attachment',
			'version',
			array(
				'get_callback'    => array( $this, 'get_version_meta' ),
				'update_callback' => array( $this, 'update_version_meta' ),
				'schema'          => array(
					'type'        => 'string',
					'arg_options' => array(
						'validate_callback' => array( $this, 'validate_version' ),
					),
				),
			)
		);
	}

	/**
	 * In the Rest APi get the meta value for the package.
	 *
	 * @param mixed[] $object The entity/post as an array.
	 *
	 * @return string|false
	 */
	public function get_package_meta( array $object ) {
		return get_post_meta( $object['id'], 'package_slug', true );
	}

	/**
	 * Update the value of the package.
	 *
	 * @param string    $value  The value to save.
	 * @param \stdClass $object The entity/post as an object.
	 *
	 * @return bool|int
	 */
	public function update_package_meta( $value, $object ) {
		// Flush Transient on save.
		delete_transient( "wp-stockroom/rest/{$value}" );

		return update_post_meta( $object->ID, 'package_slug', $value );
	}


	/**
	 * In the Rest APi get the meta value for the package.
	 *
	 * @param mixed[] $object The entity/post as an array.
	 *
	 * @return string|false
	 */
	public function get_version_meta( $object ) {
		return get_post_meta( $object['id'], 'version_number', true );
	}

	/**
	 * Update the value of the package.
	 *
	 * @param string    $value  The value to save.
	 * @param \stdClass $object The entity/post as an object.
	 *
	 * @return bool|int
	 */
	public function update_version_meta( $value, $object ) {
		return update_post_meta( $object->ID, 'version_number', $value );
	}

	/**
	 * Validate the given version.
	 *
	 * @param string $version The version to be saved.
	 *
	 * @return bool
	 */
	public function validate_version( $version ) {
		return (bool) preg_match( '@^[0-9.-]*$@', $version );
	}

}
