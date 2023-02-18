<?php

namespace WP_Stockroom\App;

/**
 * Class Uploader
 *
 * @package WP_Stockroom\app
 */
class Uploader {

	/**
	 * When uploading, files are saved to a random directory, this is uses to create these random directories.
	 *
	 * @var \WP_Post
	 */
	protected \WP_Post $package_post;

	/**
	 * Handle the uploading of package files
	 *
	 * @param \WP_Post $package_post The package post object.
	 */
	public function __construct( \WP_Post $package_post ) {
		$this->package_post = $package_post;
	}

	/**
	 * When uploading package files we want to upload these files to random directories.
	 * This will make is easier to restrict the uploads.
	 *
	 * @param array $uploads Details for wp_handle_upload.
	 *
	 * @return array
	 */
	public function set_readme_dir( array $uploads ): array {
		$ds              = DIRECTORY_SEPARATOR;
		$uploads['path'] = $uploads['basedir'] . $ds . 'wp-stockroom' . $ds . $this->get_upload_slug();

		return $uploads;
	}

	/**
	 * When uploading package files we want to upload these files to random directories.
	 * This will make is easier to restrict the uploads.
	 *
	 * @param array $uploads Details for wp_handle_upload.
	 *
	 * @return array
	 */
	public function set_zip_dir( array $uploads ): array {
		$ds              = DIRECTORY_SEPARATOR;
		$uploads['path'] = $uploads['basedir'] . $ds . 'wp-stockroom' . $ds . $this->get_upload_slug() . $ds . wp_generate_password( 24, false );

		return $uploads;
	}

	/**
	 * Get the slug of the upload directory for the package.
	 *
	 * @return string
	 */
	protected function get_upload_slug() {
		$slug = get_post_meta( $this->package_post->ID, '_upload_slug', true );
		if ( ! empty( $slug ) ) {
			return $slug;
		}
		$slug = wp_generate_password( 24, false );
		add_post_meta( $this->package_post->ID, '_upload_slug', $slug );

		return $slug;
	}

	/**
	 * Upload the Readme.
	 *
	 * @param array $_file A single file form the $_FILES.
	 *
	 * @return int|\WP_Error
	 */
	public function upload_readme( array $_file ) {

		// Delete the existing readme file.
		$readme_post = Files::instance()->get_readme_post( $this->package_post );
		if ( ! is_wp_error( $readme_post ) ) {
			wp_delete_attachment( $readme_post->ID, true );
		}

		add_filter( 'upload_dir', array( $this, 'set_readme_dir' ) );
		$wp_file = $this->upload_file( $_file );
		remove_filter( 'upload_dir', array( $this, 'set_readme_dir' ) );
		if ( is_wp_error( $wp_file ) ) {
			return $wp_file;
		}

		$post_data = array(
			'post_title'     => "readme.txt for {$this->package_post->post_title}",
			'post_mime_type' => $wp_file['type'],
			'guid'           => $wp_file['url'],
			'post_status'    => 'private', // Make sure the files are always hidden.
		);

		return wp_insert_attachment( wp_slash( $post_data ), $wp_file['file'], $this->package_post->ID, true );
	}

	/**
	 * Upload the package zip file.
	 *
	 * @param array $_file A single file form the $_FILES.
	 *
	 * @return int|\WP_Error
	 */
	public function upload_zip_file( array $_file ) {

		$package_type = get_post_meta( $this->package_post->ID, '_package_type', true );
		$version      = get_post_meta( $this->package_post->ID, '_version', true );

		// If a zip With the same version exists, delete that.
		$zip_same_version = Files::instance()->get_zip_by_version( $this->package_post, $version );
		if ( ! is_wp_error( $zip_same_version ) ) {
			wp_delete_attachment( $zip_same_version->ID, true );
		}

		add_filter( 'upload_dir', array( $this, 'set_zip_dir' ) );
		$wp_file = $this->upload_file( $_file );
		remove_filter( 'upload_dir', array( $this, 'set_zip_dir' ) );
		if ( is_wp_error( $wp_file ) ) {
			return $wp_file;
		}

		$post_data = array(
			'post_title'     => "{$this->package_post->post_title} {$package_type} {$version}",
			'post_mime_type' => $wp_file['type'],
			'guid'           => $wp_file['url'],
			'post_status'    => 'private', // Make sure the files are always hidden.
			'meta_input'     => array(
				'_version' => $version,
			),
		);

		return wp_insert_attachment( wp_slash( $post_data ), $wp_file['file'], $this->package_post->ID, true );
	}

	/**
	 * @param array $_file A single file from $_FILES.
	 *
	 * @return \WP_Error|array {
	 *     On success, returns an associative array of file attributes.
	 *     On failure, returns `$overrides['upload_error_handler']( &$file, $message )`
	 *     or `array( 'error' => $message )`.
	 *
	 * @type string $file  Filename of the newly-uploaded file.
	 * @type string $url   URL of the newly-uploaded file.
	 * @type string $type  Mime type of the newly-uploaded file.
	 *                     }
	 *
	 * @see \WP_REST_Attachments_Controller::upload_from_file for original.
	 */
	protected function upload_file( array $_file ) {
		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'test_form' => false,
		);

		// Bypasses is_uploaded_file() when running unit tests.
		if ( defined( 'DIR_TESTDATA' ) && \DIR_TESTDATA ) {
			$overrides['action'] = 'wp_handle_mock_upload';
		}

		// Include filesystem functions to get access to wp_handle_upload().
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$wp_file = wp_handle_upload( $_file, $overrides );

		if ( isset( $wp_file['error'] ) ) {
			return new \WP_Error(
				'rest_upload_unknown_error',
				$wp_file['error'],
				array( 'status' => 500 )
			);
		}

		return $wp_file;
	}
}
