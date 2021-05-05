<?php

namespace Mytory\MainSlider;

/**
 * Class MainSlider
 */
class MainSlider {
	public $version = '0.1.0';

	public function __construct() {
		add_action( 'init', array( $this, 'registerPostType' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
		add_shortcode( 'mytory_slider', array( $this, 'shortcode' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 3 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );

			add_action( 'current_screen', array( $this, 'removeUselessYoast' ) );
		}
	}

	function registerPostType() {
		$labels = array(
			'name'               => _x( 'Sliders', 'post type general name', 'wp-main-slider' ),
			'singular_name'      => _x( 'Slider', 'post type singular name', 'wp-main-slider' ),
			'menu_name'          => _x( 'Mytory Slider', 'admin menu', 'wp-main-slider' ),
			'name_admin_bar'     => _x( 'Slider', 'add new on admin bar', 'wp-main-slider' ),
			'add_new'            => _x( 'Add New', 'wp-main-slider', 'wp-main-slider' ),
			'add_new_item'       => __( 'Add New Slider', 'wp-main-slider' ),
			'new_item'           => __( 'New Slider', 'wp-main-slider' ),
			'edit_item'          => __( 'Edit Slider', 'wp-main-slider' ),
			'view_item'          => __( 'View Slider', 'wp-main-slider' ),
			'all_items'          => __( 'All Sliders', 'wp-main-slider' ),
			'search_items'       => __( 'Search Sliders', 'wp-main-slider' ),
			'parent_item_colon'  => __( 'Parent Sliders:', 'wp-main-slider' ),
			'not_found'          => __( 'No Sliders found.', 'wp-main-slider' ),
			'not_found_in_trash' => __( 'No Sliders found in Trash.', 'wp-main-slider' )
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Mytory Slider', 'wp-main-slider' ),
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title' )
		);
		register_post_type( 'mytory_slider', $args );
	}

	function adminEnqueueScripts() {
		wp_enqueue_media();
		$src = get_theme_file_uri( str_replace( get_template_directory(), '',
			realpath( __DIR__ . '/../dist/media.js' ) ) );
		wp_enqueue_script( 'wp-main-slider-media', $src, array( 'jquery' ), $this->version, true );
	}

	function enqueueScripts() {
		global $post;
		if ( has_shortcode( $post->post_content, 'mytory_slider' ) ) {
			$src = get_theme_file_uri( str_replace( get_template_directory(), '',
				realpath( __DIR__ . '/../dist/swiper.js' ) ) );
			wp_enqueue_script( 'swiper-slider', $src, filemtime( realpath( __DIR__ . '/../js/swiper.js' ) ), true );
		}
	}

	function addMetaBox() {
		add_meta_box(
			'wp-main-slider-images',
			__( 'Images', 'wp-main-slider' ),
			function () {
				include 'metabox/images.php';
			},
			'mytory_slider'
		);

		add_meta_box(
			'wp-main-slider-configuration',
			__( 'Configuration', 'wp-main-slider' ),
			function () {
				include 'include/checked-helper.php';
				include 'metabox/configuration.php';
			},
			'mytory_slider'
		);
	}


	function save( $post_id, $post, $is_update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $post->post_type == 'mytory_slider' ) {
			if ( isset( $_POST['_mytory_slider_image_ids'] ) ) {
				update_post_meta( $post_id, '_mytory_slider_image_ids',
					sanitize_text_field( $_POST['_mytory_slider_image_ids'] ) );
			}
			if ( ! empty( $_POST['mytory_slider'] ) ) {
				foreach ( $_POST['mytory_slider'] as $k => $v ) {
					update_post_meta( $post_id, $k, $v );
				}
			}
		}
	}

	function shortcode( $attributes ) {
		$attributes = shortcode_atts( array(
			'id' => '',
		), $attributes );

		$image_ids = get_post_meta( $attributes['id'], '_mytory_slider_image_ids', true );
		ob_start();
		include 'templates/basic-slider.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	function removeYoastMetabox() {
		remove_meta_box( 'wpseo_meta', 'mytory_slider', 'normal' );
	}

	function removeYoastCustomColumns( $columns ) {
		$yoast_columns = array(
			'wpseo-score',
			'wpseo-score-readability',
			'wpseo-title',
			'wpseo-metadesc',
			'wpseo-focuskw',
		);
		foreach ( $yoast_columns as $yoast_column ) {
			unset( $columns[ $yoast_column ] );
		}

		return $columns;
	}

	function removeYoastAction() {
		include 'include/wp-filters-extras.php';
		remove_filters_for_anonymous_class( 'restrict_manage_posts', 'WPSEO_Meta_Columns', 'posts_filter_dropdown',
			10 );
	}

	/**
	 * Useless Yoast Plugin.
	 * Do action after current screen is set.
	 */
	function removeUselessYoast() {
		$current_screen = get_current_screen();
		if ( $current_screen->post_type == 'mytory_slider' ) {
			add_filter( 'manage_mytory_slider_posts_columns', array( $this, 'removeYoastCustomColumns' ), 100 );
			add_action( 'restrict_manage_posts', array( $this, 'removeYoastAction' ), 1 );
			add_action( 'add_meta_boxes', array( $this, 'removeYoastMetabox' ), 100 );
		}
	}
}
