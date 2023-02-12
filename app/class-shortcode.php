<?php

namespace WP_Stockroom\App;

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

		$atts    = shortcode_atts(
			array( 'package' => $default_package ),
			$atts,
			self::TAG
		);
		$package = $atts['package'];

		ob_start();
		require WP_STOCKROOM_DIR . 'templates' . DIRECTORY_SEPARATOR . 'shortcode-render.php';
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

}
