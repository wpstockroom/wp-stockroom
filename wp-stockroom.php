<?php
/**
 * Plugin Name:       WP Stockroom
 * Description:       Host your own WP themes and plugins.
 * Plugin URI:        https://wpstockroom.com/
 * Author:            janw.oostendorp
 * Author URI:        https://janw.me
 * Text Domain:       wp-stockroom
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           0.9.0
 *
 * @package         WP_Stockroom
 */

namespace WP_Stockroom;

use WP_Stockroom\App\Package\Meta_Box;
use WP_Stockroom\App\Package\Post_Type;
use WP_Stockroom\App\Package\Redirect;
use WP_Stockroom\App\Shortcode;

define( 'WP_STOCKROOM_VERSION', '0.9.0' );
define( 'WP_STOCKROOM_DIR', plugin_dir_path( __FILE__ ) ); // Full path with trailing slash.
define( 'WP_STOCKROOM_URL', plugin_dir_url( __FILE__ ) ); // With trailing slash.
define( 'WP_STOCKROOM_SLUG', basename( __DIR__ ) ); // wp-stockroom.

if ( ! defined( 'ABSPATH' ) ) {
	return; // WP not loaded.
}

/**
 * Autoload internal classes.
 */
spl_autoload_register( function ( $class_name ) { //phpcs:ignore PEAR.Functions.FunctionCallSignature
	if ( strpos( $class_name, __NAMESPACE__ . '\App' ) !== 0 ) {
		return; // Not in the plugin namespace, don't check.
	}
	if ( strpos( $class_name, __NAMESPACE__ . '\App\Vendor' ) === 0 ) {
		return; // 3rd party, prefixed class.
	}
	$transform  = str_replace( __NAMESPACE__ . '\\', '', $class_name );                            // Remove NAMESPACE and it's "/".
	$transform  = str_replace( '_', '-', $transform );                                             // Replace "_" with "-".
	$transform  = (string) preg_replace( '%\\\\((?:.(?!\\\\))+$)%', '\class-$1.php', $transform ); // Set correct classname.
	$transform  = str_replace( '\\', DIRECTORY_SEPARATOR, $transform );                            // Replace NS separator with dir separator.
	$class_path = WP_STOCKROOM_DIR . strtolower( $transform );
	if ( ! file_exists( $class_path ) ) {
		wp_die( "<h1>Can't find class</h1><pre><code>Class: {$class_name}<br/>Path:  {$class_path}</code></pre>" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	require_once $class_path;
} );//phpcs:ignore PEAR.Functions.FunctionCallSignature
require_once WP_STOCKROOM_DIR . 'vendor/autoload.php';

/**
 * Hook everything.
 */

// Plugin (de)activation & uninstall.
register_activation_hook( __FILE__, array( '\WP_Stockroom\App\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\WP_Stockroom\App\Plugin', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( '\WP_Stockroom\App\Plugin', 'uninstall' ) );

// Add translation.
add_action( 'init', array( '\WP_Stockroom\App\Plugin', 'load_textdomain' ), 9 );

// Shortcode.
add_shortcode( Shortcode::TAG, array( Shortcode::instance(), 'render' ) );

// Post type.
add_action( 'init', array( Post_Type::instance(), 'register' ) );
add_filter( 'rest_endpoints', array( \WP_Stockroom\App\Package\Post_Type::instance(), 'remove_routes' ) );
add_filter( 'manage_package_posts_columns', array( Post_Type::instance(), 'admin_list_columns' ) );
add_action( 'manage_package_posts_custom_column', array( Post_Type::instance(), 'admin_list_columns_content' ), 10, 2 );
add_action( 'admin_init', array( Meta_Box::instance(), 'register_meta_box' ) );
// Redirects.
add_action( 'init', array( Redirect::instance(), 'add_rewrite_rule' ) );
add_filter( 'query_vars', array( Redirect::instance(), 'register_query_vars' ) );
add_action( 'template_redirect', array( Redirect::instance(), 'serve_package_files' ) );

