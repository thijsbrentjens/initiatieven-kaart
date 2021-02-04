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
 * Version:           1.0.4
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
define( 'INITIATIEVEN_KAART_VERSION', '1.0.4' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-initiatieven-kaart.php';

// in dit bestand staan de veld-definities voor de ACF-velden
require plugin_dir_path( __FILE__ ) . 'includes/acf-definitions.php';

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

	// zorg ervoor dat een lijst met initiatieven ALLE initiatieven
	// in 1 keer toont, zonder paginering, alfabetisch gesorteerd
	add_action('pre_get_posts', array($plugin, 'load_all_initiatieven'), 999);


}

run_initiatieven_kaart();

//========================================================================================================
/*
 * filter voor overzicht (archive + taxonomy) van de initiatieven
 */
function led_template_archive_initiatieven( $archive_template ) {
	global $post;

	if ( is_post_type_archive ( CPT_INITIATIEF ) ) {
		// het is een archive voor CPT = CPT_INITIATIEF
		$archive_template = dirname( __FILE__ ) . '/templates/archive-initiatieven.php';
	}
	elseif ( ( is_tax( CT_INITIATIEFTYPE ) ) || ( is_tax( CT_INITIATIEF_PROVINCIE ) ) ) {
		// het is een overzicht van initiatieven per type of per provincie
		$archive_template = dirname( __FILE__ ) . '/templates/archive-initiatieven.php';
	}

	return $archive_template;

}

add_filter( 'taxonomy_template', 'led_template_archive_initiatieven' ) ;
add_filter( 'archive_template', 'led_template_archive_initiatieven' ) ;

//========================================================================================================
/*
 * filter voor overzicht (archive) van de initiatieven
 */
function led_template_single_initiatief( $archive_template ) {
	global $post;

	if ( is_singular ( CPT_INITIATIEF ) ) {
		// het is een single voor CPT = CPT_INITIATIEF
		$archive_template = dirname( __FILE__ ) . '/templates/single-initiatief.php';
	}

	return $archive_template;

}

add_filter( 'single_template', 'led_template_single_initiatief' ) ;

//========================================================================================================

/*
 * Deze functie zorgt voor het custom post type 'initiatief' en voor
 * twee custom taxonomies: initiatieftype en provincie; deze
 * taxonomieen zijn alleen geldig voor CPT 'initiatief'.
 */
function led_custom_tax_and_types() {


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

	// Initiatieftype
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

	// Gemeente; dit is een taxonomy zodat we initiatieven kunnen groeperen.
	$labels = array(
		'name'              => esc_html_x( 'Provincie', 'taxonomy', 'initiatieven-kaart' ),
		'singular_name'     => esc_html_x( 'Provincie', 'taxonomy singular name', 'initiatieven-kaart' ),
		'search_items'      => esc_html_x( 'Search provincie', 'taxonomy', 'initiatieven-kaart' ),
		'all_items'         => esc_html_x( 'All provincies', 'taxonomy', 'initiatieven-kaart' ),
		'parent_item'       => esc_html_x( 'Parent provincie', 'taxonomy', 'initiatieven-kaart' ),
		'parent_item_colon' => esc_html_x( 'Parent provincie:', 'taxonomy', 'initiatieven-kaart' ),
		'edit_item'         => esc_html_x( 'Edit provincie', 'taxonomy', 'initiatieven-kaart' ),
		'update_item'       => esc_html_x( 'Update provincie', 'taxonomy', 'initiatieven-kaart' ),
		'add_new_item'      => esc_html_x( 'Add New provincie', 'taxonomy', 'initiatieven-kaart' ),
		'new_item_name'     => esc_html_x( 'New provincie Name', 'taxonomy', 'initiatieven-kaart' ),
		'menu_name'         => esc_html_x( 'Provincie', 'taxonomy', 'initiatieven-kaart' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => CT_INITIATIEF_PROVINCIE ),
	);

	register_taxonomy( CT_INITIATIEF_PROVINCIE, array( CPT_INITIATIEF ), $args );



}

// Trigger registering the post type as soon as possible
add_action('init', 'led_custom_tax_and_types');

//========================================================================================================
/*
 * deze functie haalt voor alle inititieftypen de bijbehorende icoontjes op
 * TODO: EIGENLIJK hoort deze functie thuis in de class Initiatieven_Kaart
 * maar voor nu heb ik 'm effe hier gefrut
 * SORRY MENSHEID!!
 */
function get_initiatieficons() {

	$arr_initiatief_type_icon = [];

	// alle types langs om voor elk het bijbehorende icoontje op te halen
	$args  = [
		'taxonomy'   => CT_INITIATIEFTYPE,
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	];
	$terms = get_terms( $args );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$count = count( $terms );

		foreach ( $terms as $term ) {

			$initiatief_type_icon = get_field( 'initiatief_type_icon', CT_INITIATIEFTYPE . '_' . $term->term_id );

			if ( $initiatief_type_icon ) {
				$arr_initiatief_type_icon[ $term->slug ] = $initiatief_type_icon;
			}
			else {
				$arr_initiatief_type_icon[ $term->slug ] = 'onbekend';
			}
		}
	}

	return $arr_initiatief_type_icon;

}

//========================================================================================================


