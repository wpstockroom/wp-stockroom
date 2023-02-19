<?php

namespace WP_Stockroom\App\Package;

use WP_Stockroom\App\Shortcode;
use WP_Stockroom\App\Singleton;

/**
 * Class Meta_Box
 * Handles the meta box in the wp-admin
 *
 * @package \WP_Stockroom\App\Package
 */
class Meta_Box {
	use Singleton;

	/**
	 * Register the meta box for the admin.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'package-meta-box',
			__( 'Package Details', 'wp-stockroom' ),
			array( $this, 'render' ),
			'package',
			'normal',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post The current Package.
	 *
	 * @return void Displays the content of the shortcode
	 */
	public function render( $post ) {
		// For now just display the same content as the shortcode, why not?

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Shortcode::instance()->render( array( 'package' => $post->post_name ) );
	}
}
