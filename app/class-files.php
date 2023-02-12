<?php

namespace WP_Stockroom\App;

use League\CommonMark\CommonMarkConverter;

/**
 * Class Files
 *
 * @package WP_Stockroom\app
 */
class Files {
	use Singleton;

	/**
	 * Get the (most recent) readme file post.
	 *
	 * @param string $package_slug The slug of the package.
	 *
	 * @return \WP_Post|\WP_Error
	 */
	public function get_readme_post( $package_slug ) {
		$q_args = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'inherit' ),
			'post_mime_type' => 'text/plain',
			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'package_slug' => array(
					'key'   => 'package_slug',
					'value' => $package_slug,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 1,
		);
		$readme = get_posts( $q_args );
		if ( empty( $readme ) ) {
			return new \WP_Error( 'no-readme-post', 'Cannon find a readme post.' ); // can't find a readme.
		}

		return $readme[0];
	}

	/**
	 * Get the text form the readme file.
	 *
	 * @param string $package_slug The slug of the package.
	 *
	 * @return string|\WP_Error
	 */
	public function get_readme_txt( $package_slug ) {
		$post = $this->get_readme_post( $package_slug );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$file_path = get_attached_file( $post->ID );
		if ( ! $file_path ) {
			return new \WP_Error( 'no-readme-file', 'Cannot find readme file.' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file = file_get_contents( $file_path );
		if ( ! $file ) {
			return new \WP_Error( 'invalid-readme-file', 'Cannot find the file.' );
		}

		return $file;
	}

	/**
	 * Get the stable tag of the plugin.
	 *
	 * @param string $package_slug The slug of the package.
	 *
	 * @return string|\WP_Error Version string on success.
	 */
	public function get_stable_tag( $package_slug ) {
		$text = $this->get_readme_txt( $package_slug );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		preg_match_all( '/stable\s*tag\s*:\s*([0-9.]*)/mi', $text, $matches );

		if ( empty( $matches[1][0] ) ) {
			// Themes have a "version" instead of a stable tag.
			preg_match_all( '/version\s*:\s*([0-9.]*)/mi', $text, $matches );
		}

		if ( empty( $matches[1][0] ) ) {
			return new \WP_Error( 'invalid-stable-tag', __( 'Cannot find a stable tag or version in the readme file.', 'wp-stockroom' ) );
		}

		return $matches[1][0];
	}

	/**
	 * Parse the readme as html.
	 *
	 * @param string $package_slug The slug of the package.
	 *
	 * @return string|\WP_Error HTML on success.
	 */
	public function get_readme_html( $package_slug ) {
		$text = $this->get_readme_txt( $package_slug );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		// Convert the headers.
		$text = preg_replace( '@===\s*(.*)\s*===@m', '## $1', $text );
		if ( null === $text ) {
			return '';
		}
		$text = preg_replace( '@==\s*(.*)\s*==@m', '### $1', $text );
		if ( null === $text ) {
			return '';
		}
		$text = preg_replace( '@=\s*(.*)\s*=@m', '#### $1', $text );
		if ( null === $text ) {
			return '';
		}
		$converter = new CommonMarkConverter();
		$html      = $converter->convert( $text );

		return (string) $html;
	}

	/**
	 * Get all zip files.
	 *
	 * @param string $package_slug The slug of the package.
	 *
	 * @return \WP_Post[]|null
	 */
	public function get_zip_posts( $package_slug ) {
		$q_args = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'inherit' ),
			'post_mime_type' => 'application/zip',
			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'package_slug' => array(
					'key'   => 'package_slug',
					'value' => $package_slug,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'nopaging'       => true,
		);
		$zipfiles = get_posts( $q_args );
		if ( empty( $zipfiles ) ) {
			return null; // can't find zips.
		}

		return $zipfiles;
	}

	/**
	 * Get the zip post for the given version.
	 *
	 * @param string $package_slug The slug of the package.
	 * @param string $version      Defaults to stable tag.
	 *
	 * @return \WP_Error|\WP_Post
	 */
	public function get_zip_by_version( $package_slug, $version = null ) {
		if ( null === $version ) {
			$version = $this->get_stable_tag( $package_slug );
			if ( is_wp_error( $version ) ) {
				return $version;
			}
		}

		$q_args = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'inherit' ),
			'post_mime_type' => 'application/zip',
			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'package_slug' => array(
					'key'   => 'package_slug',
					'value' => $package_slug,
				),
				'version'      => array(
					'key'   => 'version_number',
					'value' => $version,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 1,
		);
		$zipfiles = get_posts( $q_args );
		if ( empty( $zipfiles ) ) {
			// translators: The version string.
			$message = sprintf( __( 'Cannon find a package zip for version %s', 'wp-stockroom' ), $version );

			return new \WP_Error( 'no_zip_for_version', $message );
		}

		return $zipfiles[0];
	}
}
