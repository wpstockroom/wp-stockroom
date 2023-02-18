<?php
/**
 * Render the shortcode Template.
 *
 * @package WP_Stockroom\templates
 *
 * @var \WP_Post $package the slug of the current package.
 */

use WP_Stockroom\App\Files;

// Display the readme file.
$readme_html = Files::instance()->get_readme_html( $package );
if ( is_wp_error( $readme_html ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $readme_html->get_error_message();
	return;// stop here.
}
// Display the download zip files.
$zip_files = Files::instance()->get_zip_posts( $package );
if ( ! empty( $zip_files ) ) {
	echo '<ul>';
	foreach ( $zip_files as $zip_file ) {
		$zip_title = get_the_title( $zip_file );
		$zip_url   = wp_get_attachment_url( $zip_file->ID );
		if ( empty( $zip_url ) ) {
			continue; // Don't display if we can't find the url.
		}
		echo '<li><a href="' . esc_attr( $zip_url ) . '">' . esc_html( $zip_title ) . '</a></li>';
	}
	echo '</ul>';
} else {
	echo '<span>' . esc_html__( "Couldn't find any zips yet.", 'wp-stockroom' ) . '</span>';
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $readme_html;
