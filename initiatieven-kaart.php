<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Initiatieven_Kaart
 *
 * @wordpress-plugin
 * Plugin Name:       Initiatieven Kaart
 * Plugin URI:        http://example.com/initiatieven-kaart-uri/
 * Description:       Toont LED initiatieven op een
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

// Thijs: setting up things, for dev. TODO: better place?
function get_custom_post_type_template( $archive_template ) {
	global $post;

	if ( is_post_type_archive ( CPT_INITIATIEF ) ) {
		$archive_template = dirname( __FILE__ ) . '/includes/templates/archive-initiatieven.php';
	}
	return $archive_template;
}
add_filter( 'archive_template', 'get_custom_post_type_template' ) ;
