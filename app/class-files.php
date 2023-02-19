<?php

namespace WP_Stockroom\App;

use League\CommonMark\CommonMarkConverter;
use WP_Stockroom\App\Package\Post_Type;

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
	 * @param \WP_Post $package_post The post of the package.
	 *
	 * @return \WP_Post|\WP_Error
	 */
	public function get_readme_post( \WP_Post $package_post ) {
		$q_args = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'private' ),
			'post_mime_type' => 'text/plain',
			'post_parent'    => $package_post->ID,
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
	 * @param \WP_Post $package_post The post of the package.
	 *
	 * @return string|\WP_Error
	 */
	public function get_readme_txt( \WP_Post $package_post ) {
		$post = $this->get_readme_post( $package_post );
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
	 * Get the stable tag of the plugin/theme.
	 *
	 * @param \WP_Post $package_post The post of the package.
	 *
	 * @return string|\WP_Error Version string on success.
	 */
	public function get_stable_tag( \WP_Post $package_post ) {
		$text = $this->get_readme_txt( $package_post );
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
	 * @param \WP_Post $package_post The post of the package.
	 *
	 * @return string|\WP_Error HTML on success.
	 */
	public function get_readme_html( \WP_Post $package_post ) {
		$text = $this->get_readme_txt( $package_post );
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
	 * @param \WP_Post $package_post The post of the package.
	 *
	 * @return \WP_Post[]|null
	 */
	public function get_zip_posts( \WP_Post $package_post ) {
		$q_args   = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'private' ),
			'post_mime_type' => 'application/zip',
			'post_parent'    => $package_post->ID,
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
	 * @param \WP_Post $package_post The post of the package.
	 * @param string   $version      Defaults to latest.
	 *
	 * @return \WP_Error|\WP_Post
	 */
	public function get_zip_by_version( \WP_Post $package_post, $version = null ) {
		if ( null === $version ) {
			$version = Post_Type::instance()->get_latest_version( $package_post, true );
		}
		$q_args = array(
			'post_type'      => array( 'attachment' ),
			'post_status'    => array( 'private' ),
			'post_mime_type' => 'application/zip',
			'post_parent'    => $package_post->ID,
			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'version' => array(
					'key'   => '_version',
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

	/**
	 * Get the readme link.
	 *
	 * @param \WP_Post $package_post The package post.
	 *
	 * @return string
	 */
	public function get_readme_link( \WP_Post $package_post ) {
		return get_home_url( null, "wp-stockroom/{$package_post->post_name}/readme.txt" );
	}

	/**
	 * Get the readme link.
	 *
	 * @param \WP_Post    $package_post The package post.
	 * @param string|null $version Leave empty for the link to the latest/stable version.
	 *
	 * @return string
	 */
	public function get_zip_link( \WP_Post $package_post, string $version = null ) {
		if ( empty( $version ) ) {
			return get_home_url( null, "wp-stockroom/{$package_post->post_name}/latest.zip" );
		}
		return get_home_url( null, "wp-stockroom/{$package_post->post_name}/{$version}.zip" );
	}
}
