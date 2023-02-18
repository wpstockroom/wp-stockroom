<?php

namespace WP_Stockroom\App;

use WP_Stockroom\App\Package\Post_Type;

/**
 * Class Shortcode
 *
 * @package WP_Stockroom\app
 */
class Shortcode {
	use Singleton;

	const TAG = 'wp-stockroom';

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode Attributes.
	 *
	 * @return false|string
	 */
	public function render( $atts ) {
		$default_package = get_post();
		$default_package = ( $default_package ) ? $default_package->post_name : null;

		$atts = shortcode_atts(
			array( 'package' => $default_package ),
			$atts,
			self::TAG
		);
		if ( empty( $atts['package'] ) ) {
			return __( 'Please provide a package in shortcode.', 'wp-stockroom' );
		}
		$package = Post_Type::instance()->get_package_by_slug( $atts['package'] );
		if ( null === $package ) {
			// translators: The not found package slug.
			return sprintf( __( 'Cannot find package with %1$s', 'wp-stockroom' ), $atts['package'] );
		}

		ob_start();
		require WP_STOCKROOM_DIR . 'templates' . DIRECTORY_SEPARATOR . 'shortcode-render.php';
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

}
