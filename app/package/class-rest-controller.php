<?php

namespace WP_Stockroom\App\Package;

use WP_Stockroom\App\Uploader;

/**
 * Class Rest_Controller
 * Handles the rest API parts.
 *
 * @package \WP_Stockroom\App\Package
 */
class Rest_Controller extends \WP_REST_Posts_Controller {

	/**
	 * Both CREATES and UPDATES packages.
	 * But the default method name is "create_item" and this was the easierst.
	 *
	 * @param \WP_REST_Request $request The current request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {
		// update & insert.
		return $this->save_package( $request );
	}

	/**
	 * Update and create pacakges.
	 *
	 * @param \WP_REST_Request $request The current request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	protected function save_package( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error(
				'rest_package_invalid_id',
				__( 'Packages cannot be updated by ID, only slug..', 'wp-stockroom' ),
				array( 'status' => 400 )
			);
		}
		$existing_post = Post_Type::instance()->get_package_by_slug( $request['slug'] );

		if ( ! $existing_post ) {
			/**
			 * Create new Package
			 */
			$package_post    = array(
				'post_title'  => $request['title'] ? $request['title'] : $request['slug'],
				'post_name'   => $request['slug'], // This can't be empty.
				'post_status' => $request['status'] ? $request['status'] : 'draft', // default as draft.
				'post_type'   => 'package',
				'meta_input'  => array(
					'_version'      => $request['version'],
					'_package_type' => $request['package_type'],
				),
			);
			$package_post_id = wp_insert_post( $package_post, true );
		} else {
			/**
			 * Update existing packages.
			 */
			$package_post    = array(
				'ID'          => $existing_post->ID,
				'post_title'  => $request['title'] ? $request['title'] : $existing_post->post_title,
				'post_status' => $request['status'] ? $request['status'] : $existing_post->post_status,
				'meta_input'  => array(
					'_version' => $request['version'],
				),
			);
			$package_post_id = wp_update_post( $package_post, true );
		}

		if ( is_wp_error( $package_post_id ) ) {
			$package_post_id->add_data( array( 'status' => 500 ) );

			return $package_post_id;
		}

		$post = $this->get_post( $package_post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		/**
		 * Upload files if available.
		 */
		$files    = $request->get_file_params();
		$uploader = new Uploader( $post );
		if ( ! empty( $files['readme_file'] ) ) {
			$readme_attachment_id = $uploader->upload_readme( $files['readme_file'] );
			if ( is_wp_error( $readme_attachment_id ) ) {
				$readme_attachment_id->add_data( array( 'status' => 500 ) );

				return $readme_attachment_id;
			}
		}
		if ( ! empty( $files['readme_file'] ) ) {
			$readme_attachment_id = $uploader->upload_zip_file( $files['package_zip_file'] );
			if ( is_wp_error( $readme_attachment_id ) ) {
				$readme_attachment_id->add_data( array( 'status' => 500 ) );

				return $readme_attachment_id;
			}
		}

		/**
		 * All Done
		 */
		$response = $this->prepare_item_for_response( $post, $request );
		/** @var \WP_REST_Response $response */
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		// Todo change package url.
		$response->header( 'Location', rest_url( rest_get_route_for_post( $post ) ) );

		return $response;
	}

	/**
	 * Prepares a single post output for response.
	 *
	 * @param \WP_Post         $item    Post object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$post = $item;
		$item = parent::prepare_item_for_response( $item, $request );

		$item->data['title']            = $post->post_title;
		$item->data['version']          = get_post_meta( $post->ID, '_version', true );
		$item->data['package_type']     = get_post_meta( $post->ID, '_package_type', true );
		$item->data['readme_file']      = '//TODO';
		$item->data['package_zip_file'] = '//TODO';

		return $item;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'package',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the package.', 'wp-stockroom' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'             => array(
					'description' => __( 'An alphanumeric identifier for the Package unique to its type.', 'wp-stockroom' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'version'          => array(
					'description' => __( 'The current version of the Package.', 'wp-stockroom' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'validate_callback' => '__return_true', // TODO actually validate/sanitize version.
					),
					'required'    => true,
				),
				'modified'         => array(
					'description' => __( "The date the Package was last modified, in the site's timezone.", 'wp-stockroom' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'     => array(
					'description' => __( 'The date the Package was last modified, as GMT.', 'wp-stockroom' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'           => array(
					'description' => __( 'A named status for the Package.', 'wp-stockroom' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'validate_callback' => array( $this, 'check_status' ),
					),
				),
				'package_type'     => array(
					'description' => __( 'A named status for the Package.', 'wp-stockroom' ),
					'type'        => 'string',
					'enum'        => Post_Type::instance()->get_types(),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'validate_callback' => function ( $status, $request, $param ) {
							return rest_validate_value_from_schema( $status, $request->get_attributes()['args'][ $param ], $param );
						},
					),
					'required'    => true,
				),
				'title'            => array(
					'description' => __( 'The title of the Package.', 'wp-stockroom' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => null, // TODO Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null, // TODO Note: validation implemented in self::prepare_item_for_database().
					),
				),
				'readme_file'      => array(
					'description' => __( 'Url to the readme file of the Package', 'wp-stockroom' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'package_zip_file' => array(
					'description' => __( 'Url to the Package zip, to the latest version.', 'wp-stockroom' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),

			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
