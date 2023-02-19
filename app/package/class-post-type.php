<?php

namespace WP_Stockroom\App\Package;

use WP_Stockroom\App\Files;
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
	 * Check if the given post is a plugin.
	 *
	 * @param int|\WP_Post|null $post Optional. Post ID or post object.
	 *
	 * @return bool True on plugin
	 */
	public function is_plugin( $post = null ) {
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return false;
		}

		return ( get_post_meta( $post->ID, '_package_type', true ) === 'plugin' );
	}

	/**
	 * Check if the given post is a theme.
	 *
	 * @param int|\WP_Post|null $post Optional. Post ID or post object.
	 *
	 * @return bool True on theme
	 */
	public function is_theme( $post = null ) {
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return false;
		}

		return ( get_post_meta( $post->ID, '_package_type', true ) === 'theme' );
	}

	/**
	 * Get the latest version of the package.
	 *
	 * @param int|\WP_Post|null $post Optional. Post ID or post object.
	 * @param bool              $stable Check for the stable tag instead of latest.
	 *
	 * @return false|mixed|string|\WP_Error|\WP_Post
	 */
	public function get_latest_version( $post = null, bool $stable = false ) {
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return false;
		}
		if ( false === $stable ) {
			return get_post_meta( $post->ID, '_version', true );
		}
		return Files::instance()->get_stable_tag( $post );
	}

	/**
	 * Remove the api routes we don't want.
	 *
	 * @param array $endpoints Currently registerd route.
	 *
	 * @return array
	 */
	public function remove_routes( array $endpoints ): array {
		// Unset auto saves.
		unset( $endpoints['/wp-stockroom/v1/package/(?P<id>[\d]+)/autosaves'] );
		unset( $endpoints['/wp-stockroom/v1/package/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)'] );

		// Unset delete and update.
		foreach ( $endpoints['/wp-stockroom/v1/package/(?P<id>[\d]+)'] as $key => $route ) {
			if ( ! empty( $route['methods'] ) && in_array( $route['methods'], array( \WP_REST_Server::DELETABLE, \WP_REST_Server::EDITABLE ), true ) ) {
				unset( $endpoints['/wp-stockroom/v1/package/(?P<id>[\d]+)'][ $key ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Add custom columns to the admin so the most important data is visible.
	 *
	 * @param array $columns The current list of columns.
	 *
	 * @return array
	 */
	public function admin_list_columns( $columns ) {
		$columns['type']    = _x( 'Type', 'The Type of package', 'wp-stockroom' );
		$columns['version'] = __( 'Version', 'wp-stockroom' );

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
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		switch ( $column ) {
			case 'type':
				if ( $this->is_plugin( $post_id ) ) {
					echo '<span class="dashicons dashicons-admin-plugins"></span>' . __( 'Plugin' ); //phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				} else {
					echo '<span class="dashicons dashicons-admin-appearance"></span>' . __( 'Theme' ); //phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				}
				break;
			case 'version':
				/** @var \WP_Post $post */
				$post    = get_post( $post_id );
				$version = $this->get_latest_version( $post );

				if ( $this->is_plugin( $post_id ) ) {
					$stable = $this->get_latest_version( $post, true );
					if ( ! is_wp_error( $stable ) ) {
						$zip_link = esc_attr( Files::instance()->get_zip_link( $post, $stable ) );
						// translators: The version number.
						echo sprintf( __( 'Stable version %1$s', 'wp-stockroom' ), "<a href='{$zip_link}'>{$stable}</a>" ) . "<br/>\n";
					}
					$zip_link = esc_attr( Files::instance()->get_zip_link( $post, $version ) );
					// translators: The version number.
					echo sprintf( __( 'Latest version %1$s', 'wp-stockroom' ), "<a href='{$zip_link}'>{$version}</a>" );
				} else {
					echo "<strong>{$version}</strong>";
				}

				break;
		}
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
