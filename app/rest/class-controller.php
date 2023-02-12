<?php

namespace WP_Stockroom\App\Rest;

use WP_Stockroom\App\Files;
use WP_Stockroom\App\Readme;

/**
 * Class Rest_Controller
 * Handles the rest API parts.
 *
 * @package WP_Stockroom\app
 */
class Controller extends \WP_REST_Controller {

	/**
	 * Setup this controller.
	 *
	 * @return void
	 */
	public static function rest_api_init() {
		$controller = new self();
		$controller->register_routes();
	}

	/**
	 * Constructs the controller.
	 */
	public function __construct() {
		$this->namespace = WP_STOCKROOM_SLUG . '/v1';
		$this->rest_base = 'package';
	}

	/**
	 * Registers the REST API routes.
	 *
	 * @return void
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<name>[a-z0-9-]+)',
			array(
				'args' => array(
					'name' => array(
						'description' => __( 'Slug if the package', 'wp-stockroom' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_package' ),
				),
			)
		);
	}

	/**
	 * Returns the details about the package.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_package( \WP_REST_Request $request ) {
		$package_slug = $request['name'];

		$cache = get_transient( "wp-stockroom/rest/{$package_slug}" );
		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$version = Files::instance()->get_stable_tag( $package_slug );
		if ( is_wp_error( $version ) ) {
			$version->add_data( array( 'status' => 404 ) );

			return $version;
		}

		$zip_file = Files::instance()->get_zip_by_version( $package_slug, $version );
		if ( is_wp_error( $zip_file ) ) {
			$zip_file->add_data( array( 'status' => 404 ) );

			return $zip_file;
		}

		$readme_post = Files::instance()->get_readme_post( $package_slug );
		if ( is_wp_error( $readme_post ) ) {
			$readme_post->add_data( array( 'status' => 404 ) );

			return $readme_post;
		}

		$data = rest_ensure_response(
			array(
				'version'           => $version,
				'last_updated_gmt'  => mysql_to_rfc3339( $readme_post->post_date_gmt ),
				'package_link'      => wp_get_attachment_url( $zip_file->ID ),
				'wp_version'        => '', // TODO.
				'wp_tested_version' => '', // TODO.
				'php_version'       => '',
			)
		);
		set_transient( "wp-stockroom/rest/{$package_slug}", $data );

		return $data;
	}
}
