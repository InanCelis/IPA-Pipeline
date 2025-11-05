<?php
/**
 * Houzez functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Houzez
 * @since Houzez 1.0
 * @author Waqas Riaz
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
global $wp_version;

/**
*	---------------------------------------------------------------------------------------
*	Define constants
*	---------------------------------------------------------------------------------------
*/
define( 'HOUZEZ_THEME_NAME', 'Houzez' );
define( 'HOUZEZ_THEME_SLUG', 'houzez' );
define( 'HOUZEZ_THEME_VERSION', '3.4.9.1' );
define( 'HOUZEZ_FRAMEWORK', get_template_directory() . '/framework/' );
define( 'HOUZEZ_WIDGETS', get_template_directory() . '/inc/widgets/' );
define( 'HOUZEZ_INC', get_template_directory() . '/inc/' );
define( 'HOUZEZ_TEMPLATE_PARTS', get_template_directory() . '/template-parts/' );
define( 'HOUZEZ_IMAGE', get_template_directory_uri() . '/img/' );
define( 'HOUZEZ_CSS_DIR_URI', get_template_directory_uri() . '/css/' );
define( 'HOUZEZ_JS_DIR_URI', get_template_directory_uri() . '/js/' );
/**
*	----------------------------------------------------------------------------------------
*	Set up theme default and register various supported features.
*	----------------------------------------------------------------------------------------
*/

if ( ! function_exists( 'houzez_setup' ) ) {
	
	function houzez_setup() {

		/* add title tag support */
		add_theme_support( 'title-tag' );

		/* Load child theme languages */
		load_theme_textdomain( 'houzez', get_stylesheet_directory() . '/languages' );

		/* load theme languages */
		load_theme_textdomain( 'houzez', get_template_directory() . '/languages' );

		/* Add default posts and comments RSS feed links to head */
		add_theme_support( 'automatic-feed-links' );

		//Add support for post thumbnails.
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'houzez-gallery', 1170, 785, true);	
		add_image_size( 'houzez-item-image-1', 592, 444, true );
		add_image_size( 'houzez-top-v7', 780, 780, true );
		add_image_size( 'houzez-item-image-4', 758, 564, true );
		add_image_size( 'houzez-item-image-6', 584, 438, true );
		add_image_size( 'houzez-variable-gallery', 0, 600, false );
		add_image_size( 'houzez-map-info', 120, 90, true );
		add_image_size( 'houzez-image_masonry', 496, 9999, false ); // blog-masonry.php

		/**
		*	Register nav menus. 
		*/
		register_nav_menus(
			array(
				'top-menu' => esc_html__( 'Top Menu', 'houzez' ),
				'main-menu' => esc_html__( 'Main Menu', 'houzez' ),
				'main-menu-left' => esc_html__( 'Menu Left', 'houzez' ),
				'main-menu-right' => esc_html__( 'Menu Right', 'houzez' ),
				'mobile-menu-hed6' => esc_html__( 'Mobile Menu Header 6', 'houzez' ),
				'footer-menu' => esc_html__( 'Footer Menu', 'houzez' )
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		/*
		 * Enable support for Post Formats.
		 * See https://developer.wordpress.org/themes/functionality/post-formats/
		 */
		add_theme_support( 'post-formats', array(

		) );

		//remove gallery style css
		add_filter( 'use_default_gallery_style', '__return_false' );

		update_option( 'redux-framework_extendify_plugin_notice', 'hide' );
	
		/*
		 * Adds `async` and `defer` support for scripts registered or enqueued by the theme.
		 */
		$loader = new Houzez_Script_Loader();
		add_filter( 'script_loader_tag', array( $loader, 'filter_script_loader_tag' ), 10, 2 );
	}
}
add_action( 'after_setup_theme', 'houzez_setup' );


remove_filter( 'pre_user_description', 'wp_filter_kses' );
// Add sanitization for WordPress posts.
add_filter( 'pre_user_description', 'wp_filter_post_kses' );

/**
 *	---------------------------------------------------------------------
 *	Classes
 *	---------------------------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'classes/Houzez_Query.php' );
require_once( HOUZEZ_FRAMEWORK . 'classes/houzez_data_source.php' );
require_once( HOUZEZ_FRAMEWORK . 'classes/upgrade20.php');
require_once( HOUZEZ_FRAMEWORK . 'classes/script-loader.php');
require_once( HOUZEZ_FRAMEWORK . 'classes/houzez-lazy-load.php');
require_once( HOUZEZ_FRAMEWORK . 'admin/class-admin.php');

//require_once( HOUZEZ_FRAMEWORK . 'classes/class-houzez-submit-property.php');

/**
 *	---------------------------------------------------------------------
 *	Hooks
 *	---------------------------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'template-hooks.php' );


/**
 *	---------------------------------------------------------------------
 *	Functions
 *	---------------------------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'functions/template-functions.php' );
//require_once( HOUZEZ_FRAMEWORK . 'functions/header-functions.php' );
//require_once( HOUZEZ_FRAMEWORK . 'functions/footer-functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/price_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/helper_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/search_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/google_map_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/open_street_map_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/profile_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/property_functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/emails-functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/blog-functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/membership-functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/cron-functions.php' );
require_once( HOUZEZ_FRAMEWORK . 'functions/property-expirator.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/messages_functions.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/property_rating.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/menu-walker.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/mobile-menu-walker.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/review.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/stats.php');
require_once( HOUZEZ_FRAMEWORK . 'functions/agency_agents.php');
require_once( HOUZEZ_FRAMEWORK . 'admin/menu/menu.php');


if ( class_exists( 'WooCommerce', false ) ) {
	require_once( HOUZEZ_FRAMEWORK . 'functions/woocommerce.php' );
}

require_once( get_template_directory() . '/template-parts/header/partials/favicon.php' );

require_once(get_theme_file_path('localization.php'));

/**
 *	---------------------------------------------------------------------------------------
 *	Yelp
 *	---------------------------------------------------------------------------------------
 */
require_once( get_template_directory() . '/inc/yelpauth/yelpoauth.php' );

/**
 *	---------------------------------------------------------------------------------------
 *	include metaboxes
 *	---------------------------------------------------------------------------------------
 */
if( houzez_theme_verified() ) {

	if( is_admin() ) {
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/property-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/property-additional-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/agency-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/agent-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/partner-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/testimonials-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/posts-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/packages-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/reviews-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/project-metaboxes.php' );

		if( houzez_check_classic_editor () ) {
			require_once( get_theme_file_path('/framework/metaboxes/listings-templates-metaboxes-classic-editor.php') );
			require_once( get_theme_file_path('/framework/metaboxes/page-header-metaboxes-classic-editor.php') );
		} else {
			require_once( get_theme_file_path('/framework/metaboxes/listings-templates-metaboxes.php') );
			require_once( get_theme_file_path('/framework/metaboxes/page-header-metaboxes.php') );
		}

		
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/header-search-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/page-template-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/transparent-menu-metaboxes.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/taxonomies-metaboxes.php' );

		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/status-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/type-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/label-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/cities-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/state-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/area-meta.php' );
		require_once( HOUZEZ_FRAMEWORK . 'metaboxes/metaboxes.php' );
	}
	
}


/**
 *	---------------------------------------------------------------------------------------
 *	Options Admin Panel
 *	---------------------------------------------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'options/remove-tracking-class.php' ); // Remove tracking
require_once( HOUZEZ_FRAMEWORK . 'options/houzez-option.php' );

if( ! function_exists( 'houzez_load_redux_config' ) ) {
	function houzez_load_redux_config() {
		if ( class_exists( 'ReduxFramework' ) ) {
			require_once(get_theme_file_path('/framework/options/houzez-options.php'));
			require_once(get_theme_file_path('/framework/options/main.php'));
		}
	}
}
add_action('after_setup_theme', 'houzez_load_redux_config', 20);


/**
 *	----------------------------------------------------------------
 *	Enqueue scripts and styles.
 *	----------------------------------------------------------------
 */
require_once( HOUZEZ_INC . 'register-scripts.php' );

/**
 *	----------------------------------------------------
 *	TMG plugin activation
 *	----------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'class-tgm-plugin-activation.php' );
require_once( HOUZEZ_FRAMEWORK . 'register-plugins.php' );

/**
 *	----------------------------------------------------------------
 *	Better JPG and SSL 
 *	----------------------------------------------------------------
 */
require_once( HOUZEZ_FRAMEWORK . 'thumbnails/better-jpgs.php');
require_once( HOUZEZ_FRAMEWORK . 'thumbnails/honor-ssl-for-attachments.php');

/**
 *	-----------------------------------------------------------------------------------------
 *	Styling
 *	-----------------------------------------------------------------------------------------
 */
if ( class_exists( 'ReduxFramework' ) ) {
	require_once( get_template_directory() . '/inc/styling-options.php' );
}

if ( houzez_check_elementor_installed() ) {
	require get_template_directory() . '/inc/blocks/blocks.php';
}

/**
 *	---------------------------------------------------------------------------------------
 *	Widgets
 *	---------------------------------------------------------------------------------------
 */
require_once(get_theme_file_path('/framework/widgets/about.php'));
require_once(get_theme_file_path('/framework/widgets/code-banner.php'));
require_once(get_theme_file_path('/framework/widgets/mortgage-calculator.php'));
require_once(get_theme_file_path('/framework/widgets/image-banner-300-250.php'));
require_once(get_theme_file_path('/framework/widgets/contact.php'));
require_once(get_theme_file_path('/framework/widgets/properties.php'));
require_once(get_theme_file_path('/framework/widgets/featured-properties.php'));
require_once(get_theme_file_path('/framework/widgets/properties-viewed.php'));
require_once(get_theme_file_path('/framework/widgets/property-taxonomies.php'));
require_once(get_theme_file_path('/framework/widgets/latest-posts.php'));
require_once(get_theme_file_path('/framework/widgets/agents-search.php'));
require_once(get_theme_file_path('/framework/widgets/agency-search.php'));
require_once(get_theme_file_path('/framework/widgets/advanced-search.php'));


 /**
 *	---------------------------------------------------------------------------------------
 *	Set up the content width value based on the theme's design.
 *	---------------------------------------------------------------------------------------
 */
if( !function_exists('houzez_content_width') ) {
	function houzez_content_width()
	{
		$GLOBALS['content_width'] = apply_filters('houzez_content_width', 1170);
	}

	add_action('after_setup_theme', 'houzez_content_width', 0);
}

/**
 *	------------------------------------------------------------------
 *	Visual Composer
 *	------------------------------------------------------------------
 */
if (is_plugin_active('js_composer/js_composer.php') && is_plugin_active('houzez-theme-functionality/houzez-theme-functionality.php') ) {

	if( !function_exists('houzez_include_composer') ) {
		function houzez_include_composer()
		{
			require_once(get_template_directory() . '/framework/vc_extend.php');
		}

		add_action('init', 'houzez_include_composer', 9999);
	}

	// Filter to replace default css class names for vc_row shortcode and vc_column
	if( !function_exists('houzez_custom_css_classes_for_vc_row_and_vc_column') ) {
		//add_filter('vc_shortcodes_css_class', 'houzez_custom_css_classes_for_vc_row_and_vc_column', 10, 2);
		function houzez_custom_css_classes_for_vc_row_and_vc_column($class_string, $tag)
		{
			if ($tag == 'vc_row' || $tag == 'vc_row_inner') {
				$class_string = str_replace('vc_row-fluid', 'row-fluid', $class_string);
				$class_string = str_replace('vc_row', 'row', $class_string);
				$class_string = str_replace('wpb_row', '', $class_string);
			}
			if ($tag == 'vc_column' || $tag == 'vc_column_inner') {
				$class_string = preg_replace('/vc_col-sm-(\d{1,2})/', 'col-sm-$1', $class_string);
				$class_string = str_replace('wpb_column', '', $class_string);
				$class_string = str_replace('vc_column_container', '', $class_string);
			}
			return $class_string;
		}
	}

}

/*-----------------------------------------------------------------------------------*/
/*	Register blog sidebar, footer and custom sidebar
/*-----------------------------------------------------------------------------------*/
if( !function_exists('houzez_widgets_init') ) {
	add_action('widgets_init', 'houzez_widgets_init');
	function houzez_widgets_init()
	{
		register_sidebar(array(
			'name' => esc_html__('Default Sidebar', 'houzez'),
			'id' => 'default-sidebar',
			'description' => esc_html__('Widgets in this area will be shown in the blog sidebar.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Property Listings', 'houzez'),
			'id' => 'property-listing',
			'description' => esc_html__('Widgets in this area will be shown in property listings sidebar.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Search Sidebar', 'houzez'),
			'id' => 'search-sidebar',
			'description' => esc_html__('Widgets in this area will be shown in search result page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Single Property', 'houzez'),
			'id' => 'single-property',
			'description' => esc_html__('Widgets in this area will be shown in single property sidebar.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Page Sidebar', 'houzez'),
			'id' => 'page-sidebar',
			'description' => esc_html__('Widgets in this area will be shown in page sidebar.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Agency Sidebar', 'houzez'),
			'id' => 'agency-sidebar',
			'description' => esc_html__('Widgets in this area will be shown in agencies template and agency detail page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Agent Sidebar', 'houzez'),
			'id' => 'agent-sidebar',
			'description' => esc_html__('Widgets in this area will be shown in agents template and angent detail page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Mobile Menu', 'houzez'),
			'id' => 'hz-mobile-menu',
			'description' => esc_html__('Widgets in this area will be shown in the mobile menu', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Custom Widget Area 1', 'houzez'),
			'id' => 'hz-custom-widget-area-1',
			'description' => esc_html__('You can assign this widget are to any page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Custom Widget Area 2', 'houzez'),
			'id' => 'hz-custom-widget-area-2',
			'description' => esc_html__('You can assign this widget are to any page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Custom Widget Area 3', 'houzez'),
			'id' => 'hz-custom-widget-area-3',
			'description' => esc_html__('You can assign this widget are to any page.', 'houzez'),
			'before_widget' => '<div id="%1$s" class="widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Footer Area 1', 'houzez'),
			'id' => 'footer-sidebar-1',
			'description' => esc_html__('Widgets in this area will be show in footer column one', 'houzez'),
			'before_widget' => '<div id="%1$s" class="footer-widget widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Footer Area 2', 'houzez'),
			'id' => 'footer-sidebar-2',
			'description' => esc_html__('Widgets in this area will be show in footer column two', 'houzez'),
			'before_widget' => '<div id="%1$s" class="footer-widget widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Footer Area 3', 'houzez'),
			'id' => 'footer-sidebar-3',
			'description' => esc_html__('Widgets in this area will be show in footer column three', 'houzez'),
			'before_widget' => '<div id="%1$s" class="footer-widget widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
		register_sidebar(array(
			'name' => esc_html__('Footer Area 4', 'houzez'),
			'id' => 'footer-sidebar-4',
			'description' => esc_html__('Widgets in this area will be show in footer column four', 'houzez'),
			'before_widget' => '<div id="%1$s" class="footer-widget widget widget-wrap %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="widget-header"><h3 class="widget-title">',
			'after_title' => '</h3></div>',
		));
	}
}

/**
 *	---------------------------------------------------------------------
 *	Disable emoji scripts
 *	---------------------------------------------------------------------
 */
if( !function_exists('houzez_disable_emoji') ) {
	function houzez_disable_emoji() {
		if ( ! is_admin() && houzez_option( 'disable_emoji', 0 ) ) {
			remove_action('wp_head', 'print_emoji_detection_script', 7);
			remove_action('wp_print_styles', 'print_emoji_styles');
		}
	}
	houzez_disable_emoji();
}


/**
 *	---------------------------------------------------------------------
 *	Remove jQuery migrate.
 *	---------------------------------------------------------------------
 */
if( !function_exists('houzez_remove_jquery_migrate') ) {
	function houzez_remove_jquery_migrate( $scripts ) {
		if ( ! houzez_option( 'disable_jquery_migrate', 0 ) ) return;
		if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
			$script = $scripts->registered['jquery'];

			if ( $script->deps ) { // Check whether the script has any dependencies.
				$script->deps = array_diff( $script->deps, array(
					'jquery-migrate',
				) );
			}
		}
	}
	//add_action( 'wp_default_scripts', 'houzez_remove_jquery_migrate' );
}


if( !function_exists('houzez_js_async_attr')) {
	function houzez_js_async_attr($url){
	 
		# Do not add defer or async attribute to these scripts
		$scripts_to_exclude = array('jquery.js');
		
		//if ( is_user_logged_in() ) return $url;
		if ( is_admin() || houzez_is_dashboard() || is_preview() || houzez_option('defer_async_enabled', 0 ) == 0 ) return $url;
		
		foreach($scripts_to_exclude as $exclude_script){
		    if(true == strpos($url, $exclude_script ) )
		    return $url;    
		}
		 
		# Defer or async all remaining scripts not excluded above
		return str_replace( ' src', ' defer src', $url );
	}
	//add_filter( 'script_loader_tag', 'houzez_js_async_attr', 10 );
}

if( !function_exists('houzez_instantpage_script_loader_tag')) {
	function houzez_instantpage_script_loader_tag( $tag, $handle ) {
	  if ( 'houzez-instant-page' === $handle && houzez_option('preload_pages', 1) ) {
	    $tag = str_replace( 'text/javascript', 'module', $tag );
	  }
	  return $tag;
	}
	add_filter( 'script_loader_tag', 'houzez_instantpage_script_loader_tag', 10, 2 );
}

if(!function_exists('houzez_hide_admin_bar')) {
	function houzez_hide_admin_bar($bool) {
	  
	  if ( !current_user_can('administrator') && !is_admin() ) {
	  		return false;

	  } else if ( houzez_is_dashboard() ) :
	    return false;

	  else :
	    return $bool;
	  endif;
	}
	add_filter('show_admin_bar', 'houzez_hide_admin_bar');
}

if ( !function_exists( 'houzez_block_users' ) ) {

	add_action( 'admin_init', 'houzez_block_users' );

	function houzez_block_users() {
		$users_admin_access = houzez_option('users_admin_access');

		if( is_user_logged_in() && $users_admin_access && !houzez_is_demo() ) {
			
			if (is_admin() && !current_user_can('administrator') && isset( $_GET['action'] ) != 'delete' && !(defined('DOING_AJAX') && DOING_AJAX)) {
				wp_die(esc_html("You don't have permission to access this page.", "Houzez"));
				exit;
			}
		}
	}

}

if( !function_exists('houzez_unset_default_templates') ) {
	function houzez_unset_default_templates( $templates ) {
		if( !is_admin() ) {
			return $templates;
		}
		$houzez_templates = houzez_option('houzez_templates');

		if( !empty($houzez_templates) ) {
			foreach ($houzez_templates as $template) {
				unset( $templates[$template] );
			}
		}
	    
	    return $templates;
	}
	add_filter( 'theme_page_templates', 'houzez_unset_default_templates' );
}

if(!function_exists('houzez_author_pre_get')) {
	function houzez_author_pre_get( $query ) {
	    if ( $query->is_author() && $query->is_main_query() && !is_admin() ) :
	        $query->set( 'posts_per_page', houzez_option('num_of_agent_listings', 10) );
	        $query->set( 'post_type', array('property') );
	    endif;
	}
	add_action( 'pre_get_posts', 'houzez_author_pre_get' );
}

add_action ('redux/options/houzez_options/saved', 'houzez_save_custom_options_for_cron');
if( ! function_exists('houzez_save_custom_options_for_cron') ) {
    function houzez_save_custom_options_for_cron() {

    	
        $insights_removal = houzez_option('insights_removal', '60');
        $custom_days = houzez_option('custom_days', '90');
        
        update_option('houzez_insights_removal', $insights_removal);
        update_option('houzez_custom_days', $custom_days);

    }
}

if( ! function_exists( 'houzez_is_mobile_filter' ) ) {
	function houzez_is_mobile_filter( $is_mobile ) {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$is_mobile = false;
		} elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // Many mobile devices (all iPhone, iPad, etc.)
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) {
				$is_mobile = true;
		} else {
			$is_mobile = false;
		}
		return $is_mobile ;
	}
	//add_filter( 'wp_is_mobile', 'houzez_is_mobile_filter' );
}

if( ! function_exists('houzez_update_existing_users_with_manager_role_once') ) {
	function houzez_update_existing_users_with_manager_role_once() {
	    // Check if the update has already been done
	    if (get_option('houzez_manager_role_updated')) {
	        return; // Exit if already run
	    }

	    // Fetch all users with the houzez_manager role
	    $args = [
	        'role' => 'houzez_manager'
	    ];
	    $users = get_users($args);

	    foreach ($users as $user) {
	        // Ensure each user has the houzez_manager role, which now has updated capabilities
	        $user->add_role('houzez_manager');
	    }

	    // Set an option to indicate the update has been run
	    update_option('houzez_manager_role_updated', true);
	}

	// Run the function to update users
	houzez_update_existing_users_with_manager_role_once();
}

// ======================================

// Rewrite clean URL without .php
function add_clean_custom_feed_rewrite_rule() {
    add_rewrite_rule(
        '^xml/property/feeds?$',
        'index.php?custom_feed=1',
        'top'
    );
    add_rewrite_rule(
        '^xml/property/thinkspain?$',
        'index.php?thinkspain=1',
        'top'
    );
	add_rewrite_rule(
        '^xml/property/james-edition?$',
        'index.php?james-edition=1',
        'top'
    );
}
add_action('init', 'add_clean_custom_feed_rewrite_rule');

// Register both query vars
function add_custom_feed_query_var($vars) {
    $vars[] = 'custom_feed';
    $vars[] = 'thinkspain'; 
    $vars[] = 'james-edition'; 
    return $vars;
}
add_filter('query_vars', 'add_custom_feed_query_var');

// Route to the appropriate template
function load_custom_feed_template() {
    if (get_query_var('custom_feed') == 1) {
        include get_template_directory() . '/xml/property/custom-feed.php';
        exit;
    }
    if (get_query_var('thinkspain') == 1) {
        include get_template_directory() . '/xml/property/thinkspain.php';
        exit;
    }
	if (get_query_var('james-edition') == 1) {
        include get_template_directory() . '/xml/property/james-edition.php';
        exit;
    }
}
add_action('template_redirect', 'load_custom_feed_template');


add_action('wp_ajax_houzez_get_all_cities', 'houzez_get_all_cities');
add_action('wp_ajax_nopriv_houzez_get_all_cities', 'houzez_get_all_cities');

function houzez_get_all_cities() {
    $cities = get_terms([
        'taxonomy'   => 'property_city',
        'hide_empty' => false
    ]);

    $results = [];

    if (!is_wp_error($cities)) {
        foreach ($cities as $city) {
            $results[] = [
                'name' => $city->name,
                'slug' => $city->slug,
                'count' => $city->count // Include count for debugging
            ];
        }
    }

    wp_send_json($results);
}

// Add a new AJAX function for country-specific city filtering
add_action('wp_ajax_houzez_get_cities_by_country', 'houzez_get_cities_by_country');
add_action('wp_ajax_nopriv_houzez_get_cities_by_country', 'houzez_get_cities_by_country');

function houzez_get_cities_by_country() {
    $country_name = sanitize_text_field($_POST['country']);
    $api_cities = json_decode(stripslashes($_POST['api_cities']), true);
    
    if (empty($country_name) || empty($api_cities)) {
        wp_send_json_error('Invalid parameters');
        return;
    }
    
    // Get all local cities
    $local_cities = get_terms([
        'taxonomy'   => 'property_city',
        'hide_empty' => false
    ]);

    // Get all local states
    $local_states = get_terms([
        'taxonomy'   => 'property_state',
        'hide_empty' => false
    ]);

    $matched_items = [];
    
    // Match cities
    if (!is_wp_error($local_cities)) {
        $local_city_lookup = [];
        foreach ($local_cities as $city) {
            $normalized_name = strtolower(trim($city->name));
            $local_city_lookup[$normalized_name] = $city;
            
            $no_spaces = str_replace(' ', '', $normalized_name);
            $no_special = preg_replace('/[^\w\s]/', '', $normalized_name);
            $slug_version = str_replace(' ', '-', $no_special);
            
            $local_city_lookup[$no_spaces] = $city;
            $local_city_lookup[$no_special] = $city;
            $local_city_lookup[$slug_version] = $city;
            $local_city_lookup[$city->slug] = $city;
        }
        
        foreach ($api_cities as $api_city) {
            $api_city_normalized = strtolower(trim($api_city));
            $api_city_no_spaces = str_replace(' ', '', $api_city_normalized);
            $api_city_no_special = preg_replace('/[^\w\s]/', '', $api_city_normalized);
            $api_city_slug = str_replace(' ', '-', preg_replace('/[^\w\s]/', '', $api_city_normalized));
            
            $matched_city = null;
            
            if (isset($local_city_lookup[$api_city_normalized])) {
                $matched_city = $local_city_lookup[$api_city_normalized];
            } elseif (isset($local_city_lookup[$api_city_no_spaces])) {
                $matched_city = $local_city_lookup[$api_city_no_spaces];
            } elseif (isset($local_city_lookup[$api_city_no_special])) {
                $matched_city = $local_city_lookup[$api_city_no_special];
            } elseif (isset($local_city_lookup[$api_city_slug])) {
                $matched_city = $local_city_lookup[$api_city_slug];
            }
            
            if ($matched_city) {
                $matched_items[] = [
                    'name' => $matched_city->name,
                    'slug' => $matched_city->slug,
                    'count' => $matched_city->count
                ];
            }
        }
    }
    
    // Match states
    if (!is_wp_error($local_states)) {
        $local_state_lookup = [];
        foreach ($local_states as $state) {
            $normalized_name = strtolower(trim($state->name));
            $local_state_lookup[$normalized_name] = $state;
            
            $no_spaces = str_replace(' ', '', $normalized_name);
            $no_special = preg_replace('/[^\w\s]/', '', $normalized_name);
            $slug_version = str_replace(' ', '-', $no_special);
            
            $local_state_lookup[$no_spaces] = $state;
            $local_state_lookup[$no_special] = $state;
            $local_state_lookup[$slug_version] = $state;
            $local_state_lookup[$state->slug] = $state;
        }
        
        foreach ($api_cities as $api_city) {
            $api_city_normalized = strtolower(trim($api_city));
            $api_city_no_spaces = str_replace(' ', '', $api_city_normalized);
            $api_city_no_special = preg_replace('/[^\w\s]/', '', $api_city_normalized);
            $api_city_slug = str_replace(' ', '-', preg_replace('/[^\w\s]/', '', $api_city_normalized));
            
            $matched_state = null;
            
            if (isset($local_state_lookup[$api_city_normalized])) {
                $matched_state = $local_state_lookup[$api_city_normalized];
            } elseif (isset($local_state_lookup[$api_city_no_spaces])) {
                $matched_state = $local_state_lookup[$api_city_no_spaces];
            } elseif (isset($local_state_lookup[$api_city_no_special])) {
                $matched_state = $local_state_lookup[$api_city_no_special];
            } elseif (isset($local_state_lookup[$api_city_slug])) {
                $matched_state = $local_state_lookup[$api_city_slug];
            }
            
            if ($matched_state) {
                $matched_items[] = [
                    'name' => $matched_state->name,
                    'slug' => $matched_state->slug,
                    'count' => $matched_state->count
                ];
            }
        }
    }
    
    // Remove duplicates
    $unique_items = [];
    $seen_slugs = [];
    
    foreach ($matched_items as $item) {
        if (!in_array($item['slug'], $seen_slugs)) {
            $unique_items[] = $item;
            $seen_slugs[] = $item['slug'];
        }
    }
    
    wp_send_json_success($unique_items);
}

add_action('wp_enqueue_scripts', 'houzez_enqueue_custom_scripts');
function houzez_enqueue_custom_scripts() {
    wp_enqueue_script('custom-houzez-js', get_template_directory_uri() . '/js/custom-houzez.js', ['jquery'], '1.1', true);

    wp_localize_script('custom-houzez-js', 'houzez_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'all_cities_text' => 'All Cities',
        'nonce' => wp_create_nonce('houzez_nonce')
    ]);
}


require_once get_template_directory() . '/functions-partnership-install.php'; 

// Include Pipeline Database and AJAX Handlers
require_once get_template_directory() . '/inc/pipeline-database.php';
require_once get_template_directory() . '/inc/pipeline-ajax-handlers.php'; 

// Include Partnership Invoice AJAX Handlers
require_once get_template_directory() . '/inc/partnership-invoice-ajax-handlers.php'; 


// =====================================================
// PARTNERSHIP COMMENTS AJAX HANDLER
// =====================================================
add_action('wp_ajax_get_partnership_comments', 'get_partnership_comments_ajax_handler');
function get_partnership_comments_ajax_handler() {
	error_log('AJAX Handler Called');
    global $wpdb;
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in'));
        return;
    }
    
    if (!isset($_GET['partner_id'])) {
        wp_send_json_error(array('message' => 'Partner ID required'));
        return;
    }
    
    $partner_id = intval($_GET['partner_id']);
    $comments_table = $wpdb->prefix . 'partnership_comments';
    
    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT c.id, c.comment, c.user_id,
         u.display_name as user_name, 
         DATE_FORMAT(c.created_at, '%%b %%d, %%Y at %%h:%%i %%p') as created_at
         FROM {$comments_table} c 
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
         WHERE c.partnership_id = %d 
         ORDER BY c.created_at DESC",
        $partner_id
    ));
    
    $formatted = array();
    if ($comments) {
        foreach ($comments as $c) {
            $formatted[] = array(
                'id' => $c->id,
                'comment' => $c->comment,
                'user_name' => $c->user_name ?: 'Unknown User',
                'created_at' => $c->created_at,
				'user_id' => (int)$c->user_id,
            );
        }
    }
    
    wp_send_json_success(array('comments' => $formatted));
}



// AJAX Handler: Delete Partnership Comment
add_action('wp_ajax_delete_partnership_comment', 'delete_partnership_comment_ajax_handler');
function delete_partnership_comment_ajax_handler() {
    global $wpdb;
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in'));
        return;
    }
    
    if (!isset($_POST['comment_id'])) {
        wp_send_json_error(array('message' => 'Comment ID required'));
        return;
    }
    
    $comment_id = intval($_POST['comment_id']);
    $current_user_id = get_current_user_id();
    $comments_table = $wpdb->prefix . 'partnership_comments';
    
    // Get comment to check ownership
    $comment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$comments_table} WHERE id = %d",
        $comment_id
    ));
    
    if (!$comment) {
        wp_send_json_error(array('message' => 'Comment not found'));
        return;
    }
    
    // Check if user owns the comment or is admin
    if ($comment->user_id != $current_user_id && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'You can only delete your own comments'));
        return;
    }
    
    // Delete the comment
    $result = $wpdb->delete($comments_table, array('id' => $comment_id));

    if ($result) {
        wp_send_json_success(array('message' => 'Comment deleted successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete comment'));
    }
}

// ============================================
// INVOICE COMMENTS AJAX HANDLERS
// ============================================

// Get invoice comments
add_action('wp_ajax_get_invoice_comments', 'get_invoice_comments_ajax_handler');
function get_invoice_comments_ajax_handler() {
    global $wpdb;

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to view comments'));
        return;
    }

    $invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
    $invoice_type = isset($_GET['invoice_type']) ? sanitize_text_field($_GET['invoice_type']) : 'partnership';

    if (!$invoice_id) {
        wp_send_json_error(array('message' => 'Invalid invoice ID'));
        return;
    }

    $comments_table = $wpdb->prefix . 'invoice_comments';

    // Get all comments for this invoice
    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT c.id, c.comment, c.user_id,
         u.display_name as user_name,
         DATE_FORMAT(c.created_at, '%%b %%d, %%Y at %%h:%%i %%p') as created_at
         FROM {$comments_table} c
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.invoice_id = %d AND c.invoice_type = %s
         ORDER BY c.created_at DESC",
        $invoice_id,
        $invoice_type
    ));

    // Format the response
    $formatted_comments = array();
    foreach ($comments as $comment) {
        $formatted_comments[] = array(
            'id' => (int)$comment->id,
            'comment' => stripslashes($comment->comment),
            'user_name' => $comment->user_name ?: 'Unknown User',
            'created_at' => $comment->created_at,
            'user_id' => (int)$comment->user_id,
        );
    }

    wp_send_json_success(array('comments' => $formatted_comments));
}

// Add invoice comment
add_action('wp_ajax_add_invoice_comment', 'add_invoice_comment_ajax_handler');
function add_invoice_comment_ajax_handler() {
    global $wpdb;

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to add comments'));
        return;
    }

    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $invoice_type = isset($_POST['invoice_type']) ? sanitize_text_field($_POST['invoice_type']) : 'partnership';
    $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

    if (!$invoice_id || empty($comment)) {
        wp_send_json_error(array('message' => 'Invalid data provided'));
        return;
    }

    $comments_table = $wpdb->prefix . 'invoice_comments';

    // Insert the comment
    $result = $wpdb->insert(
        $comments_table,
        array(
            'invoice_id' => $invoice_id,
            'invoice_type' => $invoice_type,
            'user_id' => get_current_user_id(),
            'comment' => $comment,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%d', '%s', '%s')
    );

    if ($result) {
        wp_send_json_success(array('message' => 'Comment added successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to add comment'));
    }
}

// Delete invoice comment
add_action('wp_ajax_delete_invoice_comment', 'delete_invoice_comment_ajax_handler');
function delete_invoice_comment_ajax_handler() {
    global $wpdb;

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to delete comments'));
        return;
    }

    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Invalid comment ID'));
        return;
    }

    $current_user_id = get_current_user_id();
    $comments_table = $wpdb->prefix . 'invoice_comments';

    // Get the comment to check ownership
    $comment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$comments_table} WHERE id = %d",
        $comment_id
    ));

    if (!$comment) {
        wp_send_json_error(array('message' => 'Comment not found'));
        return;
    }

    // Check if user owns the comment or is admin
    if ($comment->user_id != $current_user_id && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'You can only delete your own comments'));
        return;
    }

    // Delete the comment
    $result = $wpdb->delete($comments_table, array('id' => $comment_id), array('%d'));

    if ($result) {
        wp_send_json_success(array('message' => 'Comment deleted successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete comment'));
    }
}

// AJAX handler for searching partnerships
add_action('wp_ajax_search_partnerships', 'search_partnerships_callback');
add_action('wp_ajax_nopriv_search_partnerships', 'search_partnerships_callback');

function search_partnerships_callback() {
    // Verify nonce
    check_ajax_referer('search_partnerships_nonce', 'nonce');
    
    global $wpdb;
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    if (empty($search)) {
        wp_send_json_error('Search term is required');
    }
    
    // Query the partnerships table
    $table_name = $wpdb->prefix . 'partnerships';
    
    $query = $wpdb->prepare(
        "SELECT company_name, contact_person, mobile, email, website 
         FROM $table_name 
         WHERE company_name LIKE %s 
         ORDER BY company_name ASC 
         LIMIT 10",
        '%' . $wpdb->esc_like($search) . '%'
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    if ($results) {
        wp_send_json_success($results);
    } else {
        wp_send_json_success(array());
    }
}

// CODE THAT RUNS ONCE (on first activation)
// add_role(
//     'sales_role',           // Role slug
//     'Sales',                // Display name
//     array(
//         'read' => true,
//         'edit_posts' => true,
//         'publish_posts' => true,
//         'delete_posts' => true,
//     )
// );

// CODE THAT RUNS EVERY TIME (on every page load)
add_filter( 'user_has_cap', function( $caps, $cap, $args ) {
    // Check if user has sales_role
    $user = wp_get_current_user();
    if ( ! in_array( 'sales_role', $user->roles ) ) {
        return $caps;
    }

    // Allowed post IDs for this role
    $allowed_post_ids = array( 5, 12, 23 ); // Change these to your post IDs

    if ( 'edit_post' === $cap || 'delete_post' === $cap ) {
        $post_id = isset( $args[2] ) ? $args[2] : null;
        
        if ( $post_id && ! in_array( $post_id, $allowed_post_ids ) ) {
            $caps['edit_posts'] = false;
        }
    }
    
    return $caps;
}, 10, 3 );

// CODE THAT RUNS EVERY TIME (on every admin page load)
// BUT allow AJAX requests to go through!
add_action( 'admin_init', function() {
    // Allow AJAX requests - don't redirect if it's an AJAX call
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    $user = wp_get_current_user();

    if ( in_array( 'sales_role', $user->roles ) ) {
        wp_safe_remote_get( home_url() );
        wp_redirect( home_url() );
        exit;
    }
});