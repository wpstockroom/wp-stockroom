<?php

namespace WP_Stockroom\App\Package;

use WP_Stockroom\App\Singleton;

/**
 * Class Post_Type
 *
 * @package WP_Stockroom\App\Package
 */
class Post_Type {
	use Singleton;


	/**
	 * The allowed package types.
	 *
	 * @var string[]
	 */
	protected $types = array(
		'plugin',
		'theme',
	);

	/**
	 * Get the allowed package types.
	 *
	 * @return string[]
	 */
	public function get_types(): array {
		return $this->types;
	}

	/**
	 * Find a package by slug.
	 *
	 * @param string $slug The slug of the package post.
	 *
	 * @return \WP_Post|null
	 */
	public function get_package_by_slug( string $slug ) {
		$args    = array(
			'name'           => $slug,
			'post_type'      => 'package',
			'post_status'    => array( 'any' ),
			'posts_per_page' => 1,
		);
		$package = get_posts( $args );

		if ( empty( $package ) ) {
			return null;
		}

		return $package[0];
	}

	/**
	 * Register the package post type.
	 *
	 * @return void
	 */
	public function register() {
		$post_type_single = _x( 'Package', 'posttype single name global used', 'wp-stockroom' );
		$post_type_plural = _x( 'Packages', 'posttype plural name global used', 'wp-stockroom' );

		$labels = array(
			'name'               => $post_type_single,
			'singular_name'      => $post_type_single,
			'add_new'            => _x( 'Add New', 'POSTTYPE', 'wp-stockroom' ),
			/* Translators: The post type. */
			'add_new_item'       => sprintf( __( 'Add New %s', 'wp-stockroom' ), $post_type_single ),
			/* Translators: The post type. */
			'edit_item'          => sprintf( __( 'Edit %s', 'wp-stockroom' ), $post_type_single ),
			/* Translators: The post type. */
			'new_item'           => sprintf( __( 'New %s', 'wp-stockroom' ), $post_type_single ),
			/* Translators: The post type. */
			'all_items'          => sprintf( __( 'All %s', 'wp-stockroom' ), $post_type_plural ),
			/* Translators: The post type. */
			'view_item'          => sprintf( __( 'View %s', 'wp-stockroom' ), $post_type_single ),
			/* Translators: The post type. */
			'search_items'       => sprintf( __( 'Search %s', 'wp-stockroom' ), $post_type_plural ),
			/* Translators: The post type. */
			'not_found'          => sprintf( __( 'No %s found', 'wp-stockroom' ), $post_type_plural ),
			/* Translators: The post type. */
			'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'wp-stockroom' ), $post_type_plural ),
			'parent_item_colon'  => '',
			'menu_name'          => _x( 'Packages', 'menu items', 'wp-stockroom' ),
		);
		$args   = array(
			'labels'                => $labels,
			'description'           => __( 'The Stockroom Packages', 'wp-stockroom' ),
			'public'                => false, // can the posttype be seen on front and backend?
			'publicly_queryable'    => false, // can be called via url params.
			'show_in_rest'          => true,
			'rest_namespace'        => WP_STOCKROOM_SLUG . '/v1',
			'rest_controller_class' => '\WP_Stockroom\App\Package\Rest_Controller',
			'show_ui'               => true, // show a default WP-admin UI.
			'show_in_menu'          => true,
			'show_in_admin_bar'     => false, // show in admin bar.
			'query_var'             => false, // true or string to replace the value in url's.
			'has_archive'           => false,
			'hierarchical'          => false,
			'can_export'            => false,
			'taxonomies'            => array(),
			'supports'              => array( 'title' ),
			'menu_icon'             => 'dashicons-editor-customchar',
			'menu_position'         => 59,
		);
		register_post_type( 'package', $args );
	}

	/**
	 * Add custom columns to the admin so the most important data is visible.
	 *
	 * @param array $columns The current list of columns.
	 *
	 * @return array
	 */
	public function admin_list_columns( $columns ) {
		$columns['version'] = __( 'Version', 'wp-stockroom' );
		$columns['type']    = _x( 'Type', 'The Type of package', 'wp-stockroom' );

		return $columns;
	}

	/**
	 * Display the custom column content.
	 *
	 * @param string $column  The current colum.
	 * @param int    $post_id The current post_id.
	 *
	 * @return void
	 */
	public function admin_list_columns_content( $column, $post_id ) {
		switch ( $column ) {
			case 'version':
				echo esc_html( get_post_meta( $post_id, '_version', true ) );
				break;
			case 'type':
				echo esc_html( get_post_meta( $post_id, '_package_type', true ) );
				break;
		}
	}
}
