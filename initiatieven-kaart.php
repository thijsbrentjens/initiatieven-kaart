<?php

/**
 * LED initiatieven-kaart
 *
 * @link              https://www.digitaleoverheid.nl/
 * @since             1.0.0
 * @package           Initiatieven_Kaart
 *
 * @wordpress-plugin
 * Plugin Name:       Initiatieven Kaart voor LED (digitaleoverheid.nl)
 * Plugin URI:        http://example.com/initiatieven-kaart-uri/
 * Description:       Toont LED initiatieven op een kaart
 * Version:           1.0.0
 * Author:            Thijs Brentjens
 * Author URI:        https://brentjensgeoict.nl
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       initiatieven-kaart
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'INITIATIEVEN_KAART_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-initiatieven-kaart.php';

//========================================================================================================

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_initiatieven_kaart() {

	$plugin = new Initiatieven_Kaart();
	$plugin->run();

	add_action('wp_enqueue_scripts', array($plugin, 'enqueue_scripts'));


}

run_initiatieven_kaart();

//========================================================================================================

function get_custom_post_type_template( $archive_template ) {
	global $post;

	if ( is_post_type_archive ( CPT_INITIATIEF ) ) {
		// het is een archive voor CPT = CPT_INITIATIEF
		$archive_template = dirname( __FILE__ ) . '/includes/templates/archive-initiatieven.php';
	}

	return $archive_template;

}

add_filter( 'archive_template', 'get_custom_post_type_template' ) ;

//========================================================================================================

function wporg_custom_post_type() {


	$args = array(
		'label'               => esc_html__( CPT_INITIATIEF, 'waymark' ),
		'description'         => '',
		'labels'              => array(
			'name'                  => esc_html_x( 'Initiatieven', 'post type', 'initiatieven-kaart' ),
			'singular_name'         => esc_html_x( 'Initiatief', 'post type', 'initiatieven-kaart' ),
			'menu_name'             => esc_html_x( 'Initiatieven', 'post type', 'initiatieven-kaart' ),
			'name_admin_bar'        => esc_html_x( 'Initiatief', 'post type', 'initiatieven-kaart' ),
			'archives'              => esc_html_x( 'Overzicht initiatieven', 'post type', 'initiatieven-kaart' ),
			'attributes'            => esc_html_x( 'Eigenschappen initiatief', 'post type', 'initiatieven-kaart' ),
			'parent_item_colon'     => esc_html_x( 'Parent Map:', 'post type', 'initiatieven-kaart' ),
			'all_items'             => esc_html_x( 'Alle initiatieven', 'post type', 'initiatieven-kaart' ),
			'add_new_item'          => esc_html_x( 'Initiatief toevoegen', 'post type', 'initiatieven-kaart' ),
			'add_new'               => esc_html_x( 'Toevoegen', 'post type', 'initiatieven-kaart' ),
			'new_item'              => esc_html_x( 'Nieuw initiatief', 'post type', 'initiatieven-kaart' ),
			'edit_item'             => esc_html_x( 'Bewerk initiatief', 'post type', 'initiatieven-kaart' ),
			'update_item'           => esc_html_x( 'Update initiatief', 'post type', 'initiatieven-kaart' ),
			'view_item'             => esc_html_x( 'Bekijk initiatief', 'post type', 'initiatieven-kaart' ),
			'view_items'            => esc_html_x( 'Bekijk initiatieven', 'post type', 'initiatieven-kaart' ),
			'search_items'          => esc_html_x( 'Zoek initiatief', 'post type', 'initiatieven-kaart' ),
			'not_found'             => esc_html_x( 'Not found', 'post type', 'initiatieven-kaart' ),
			'not_found_in_trash'    => esc_html_x( 'Not found in Trash', 'post type', 'initiatieven-kaart' ),
			'featured_image'        => esc_html_x( 'Featured Image', 'post type', 'initiatieven-kaart' ),
			'set_featured_image'    => esc_html_x( 'Set featured image', 'post type', 'initiatieven-kaart' ),
			'remove_featured_image' => esc_html_x( 'Remove featured image', 'post type', 'initiatieven-kaart' ),
			'use_featured_image'    => esc_html_x( 'Use as featured image', 'post type', 'initiatieven-kaart' ),
			'insert_into_item'      => esc_html_x( 'Insert into Map', 'post type', 'initiatieven-kaart' ),
			'uploaded_to_this_item' => esc_html_x( 'Uploaded to this initiatief', 'post type', 'initiatieven-kaart' ),
			'items_list'            => esc_html_x( 'Map list', 'post type', 'initiatieven-kaart' ),
			'items_list_navigation' => esc_html_x( 'Maps list navigation', 'post type', 'initiatieven-kaart' ),
			'filter_items_list'     => esc_html_x( 'Filter initiatief list', 'post type', 'initiatieven-kaart' ),
		),
		'supports'            => array( 'title', 'author', 'excerpt', 'editor' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => array( 'slug' => CPT_INITIATIEF ),
		'capability_type'     => 'post'
	);

	register_post_type( CPT_INITIATIEF, $args );

	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => esc_html_x( 'Type initatief', 'taxonomy', 'initiatieven-kaart' ),
		'singular_name'     => esc_html_x( 'Initatieftype', 'taxonomy singular name', 'initiatieven-kaart' ),
		'search_items'      => esc_html_x( 'Search initatieftype', 'taxonomy', 'initiatieven-kaart' ),
		'all_items'         => esc_html_x( 'All initatieftypes', 'taxonomy', 'initiatieven-kaart' ),
		'parent_item'       => esc_html_x( 'Parent initatieftype', 'taxonomy', 'initiatieven-kaart' ),
		'parent_item_colon' => esc_html_x( 'Parent initatieftype:', 'taxonomy', 'initiatieven-kaart' ),
		'edit_item'         => esc_html_x( 'Edit initatieftype', 'taxonomy', 'initiatieven-kaart' ),
		'update_item'       => esc_html_x( 'Update initatieftype', 'taxonomy', 'initiatieven-kaart' ),
		'add_new_item'      => esc_html_x( 'Add New initatieftype', 'taxonomy', 'initiatieven-kaart' ),
		'new_item_name'     => esc_html_x( 'New initatieftype Name', 'taxonomy', 'initiatieven-kaart' ),
		'menu_name'         => esc_html_x( 'Initatieftype', 'taxonomy', 'initiatieven-kaart' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => CT_INITIATIEFTYPE ),
	);

	register_taxonomy( CT_INITIATIEFTYPE, array( CPT_INITIATIEF ), $args );
	
	
}

add_action('init', 'wporg_custom_post_type');

//========================================================================================================

if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array(
		'key' => 'group_5f589cb3955cc',
		'title' => 'Velden voor initiatief',
		'fields' => array(
			array(
				'center_lat' => 51.9179617,
				'center_lng' => 4.5007038,
				'zoom' => 14,
				'key' => 'field_5f58c08e264c0',
				'label' => 'OpenStreet Map',
				'name' => 'openstreet_map',
				'type' => 'open_street_map',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'return_format' => 'raw',
				'layers' => array(
					0 => 'OpenStreetMap.Mapnik',
				),
				'allow_map_layers' => 1,
				'height' => 400,
				'max_markers' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'initiatief',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'acf_after_title',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
	));

endif;

//========================================================================================================

