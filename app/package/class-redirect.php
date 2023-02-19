<?php

namespace WP_Stockroom\App\Package;

use WP_Stockroom\App\Files;
use WP_Stockroom\App\Singleton;

/**
 * Class Redirect
 *
 * @package WP_Stockroom\App\Package
 */
class Redirect {

	use Singleton;

	/**
	 * Register the rewrite rule.
	 *
	 * @return void
	 */
	public function add_rewrite_rule() {
		add_rewrite_rule( WP_STOCKROOM_SLUG . '/([^/]*)/([^/]*)?$', 'index.php?package_slug=$matches[1]&package_file_name=$matches[2]', 'top' );
	}

	/**
	 * Register the query vars we need for the rewrite rules.
	 *
	 * @param array $query_vars The existing Query vars.
	 *
	 * @return array
	 */
	public function register_query_vars( array $query_vars ) {
		$query_vars[] = 'package_slug';
		$query_vars[] = 'package_file_name';

		return $query_vars;
	}

	/**
	 * Serve the package files if needed.
	 *
	 * @return void|never
	 */
	public function serve_package_files() {
		if ( empty( get_query_var( 'package_slug' ) ) || empty( get_query_var( 'package_file_name' ) ) ) {
			// Nothing to do here, that's fine.
			return;
		}
		$package = Post_Type::instance()->get_package_by_slug( get_query_var( 'package_slug' ) );
		if ( empty( $package ) ) {
			wp_die(
				esc_html__( 'Cannot find given package.', 'wp-stockroom' ),
				esc_html__( 'Cannot find given package.', 'wp-stockroom' ),
				array(
					'response' => 404,
					'code'     => 'stockroom_cant_find_package',
				)
			);
		}
		if ( 'readme.txt' === get_query_var( 'package_file_name' ) ) {
			$readme_post = Files::instance()->get_readme_post( $package );
			$this->render_file( $readme_post );
		}
		$version   = str_replace( '.zip', '', get_query_var( 'package_file_name' ) );
		$file_name = "{$package->post_name}.{$version}.zip";
		if ( 'latest' === $version ) {
			$version = Post_Type::instance()->get_latest_version( $package, true );
		}
		$zip_file = Files::instance()->get_zip_by_version( $package, $version );
		$this->render_file( $zip_file );
	}

	/**
	 * Render the given file.
	 *
	 * @param \WP_Error|\WP_Post $file_post The post that should contain the file.
	 * @param string|null        $filename Optional filename, defaults to the filename of the file.
	 *
	 * @return never|void
	 */
	public function render_file( $file_post, string $filename = null ) {
		if ( is_wp_error( $file_post ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( $file_post );
		}
		$file = get_attached_file( $file_post->ID );
		if ( empty( $file ) ) {
			wp_die(
				esc_html__( 'Cannot find file on disk.', 'wp-stockroom' ),
				esc_html__( 'Cannot find file on disk.', 'wp-stockroom' ),
				array(
					'response' => 404,
					'code'     => 'stockroom_cant_find_file',
				)
			);
		}
		if ( empty( $filename ) ) {
			$filename = basename( $file );
		}
		header( 'Content-Type: ' . $file_post->post_mime_type );
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		//phpcs:ignore
		echo file_get_contents( $file );
		die;
	}
}
