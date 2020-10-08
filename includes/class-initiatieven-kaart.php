<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Initiatieven_Kaart
 * @subpackage Initiatieven_Kaart/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Initiatieven_Kaart
 * @subpackage Initiatieven_Kaart/includes
 * @author     Your Name <email@example.com>
 */
class Initiatieven_Kaart {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Initiatieven_Kaart_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $initiatieven_kaart    The string used to uniquely identify this plugin.
	 */
	protected $initiatieven_kaart;

	// Thijs: TODO: document
	private $types;
	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'INITIATIEVEN_KAART_VERSION' ) ) {
			$this->version = INITIATIEVEN_KAART_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		if ( ! defined( 'CPT_INITIATIEF' ) ) {
			define( 'CPT_INITIATIEF', 'initiatief' );
		}

		$this->initiatieven_kaart = 'initiatieven-kaart';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// register type:
		$this->registerPostType();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Initiatieven_Kaart_Loader. Orchestrates the hooks of the plugin.
	 * - Initiatieven_Kaart_i18n. Defines internationalization functionality.
	 * - Initiatieven_Kaart_Admin. Defines all hooks for the admin area.
	 * - Initiatieven_Kaart_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-initiatieven-kaart-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-initiatieven-kaart-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-initiatieven-kaart-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-initiatieven-kaart-public.php';

		$this->loader = new Initiatieven_Kaart_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Initiatieven_Kaart_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Initiatieven_Kaart_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Initiatieven_Kaart_Admin( $this->get_initiatieven_kaart(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Initiatieven_Kaart_Public( $this->get_initiatieven_kaart(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_initiatieven_kaart() {
		return $this->initiatieven_kaart;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Initiatieven_Kaart_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function registerPostType() {

		$this->types = array(

			// Custom post types
			CPT_INITIATIEF => array(
				'label'                 => esc_html__( CPT_INITIATIEF, 'waymark' ),
				'description'           => '',
				'labels'                => array(
					'name'                  => esc_html__('Initiatieven', 'waymark' ),
					'singular_name'         => esc_html__('Initiatief', 'waymark' ),
					'menu_name'             => esc_html__('Initiatieven', 'waymark' ),
					'name_admin_bar'        => esc_html__('Initiatief', 'waymark' ),
					'archives'              => esc_html__('Overzicht initiatieven', 'waymark' ),
					'attributes'            => esc_html__('Eigenschappen initiatief', 'waymark' ),
					'parent_item_colon'     => esc_html__('Parent Map:', 'waymark' ),
					'all_items'             => esc_html__('Alle initiatieven', 'waymark' ),
					'add_new_item'          => esc_html__('Initiatief toevoegen', 'waymark' ),
					'add_new'               => esc_html__('Toevoegen', 'waymark' ),
					'new_item'              => esc_html__('Nieuw initiatief', 'waymark' ),
					'edit_item'             => esc_html__('Bewerk initiatief', 'waymark' ),
					'update_item'           => esc_html__('Update initiatief', 'waymark' ),
					'view_item'             => esc_html__('Bekijk initiatief', 'waymark' ),
					'view_items'            => esc_html__('Bekijk initiatieven', 'waymark' ),
					'search_items'          => esc_html__('Zoek initiatief', 'waymark' ),
					'not_found'             => esc_html__('Not found', 'waymark' ),
					'not_found_in_trash'    => esc_html__('Not found in Trash', 'waymark' ),
					'featured_image'        => esc_html__('Featured Image', 'waymark' ),
					'set_featured_image'    => esc_html__('Set featured image', 'waymark' ),
					'remove_featured_image' => esc_html__('Remove featured image', 'waymark' ),
					'use_featured_image'    => esc_html__('Use as featured image', 'waymark' ),
					'insert_into_item'      => esc_html__('Insert into Map', 'waymark' ),
					'uploaded_to_this_item' => esc_html__('Uploaded to this initiatief', 'waymark' ),
					'items_list'            => esc_html__('Map list', 'waymark' ),
					'items_list_navigation' => esc_html__('Maps list navigation', 'waymark' ),
					'filter_items_list'     => esc_html__('Filter initiatief list', 'waymark' ),
				),
				'supports'              => array( 'title', 'author', 'excerpt', 'editor' ),
				'hierarchical'          => false,
				'public'                => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'menu_position'         => 6,
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => true,
				'can_export'            => true,
				'has_archive'           => true,
				'exclude_from_search'   => false,
				'publicly_queryable'    => true,
				// TODO: 'rewrite' is not working locally?
				// 'rewrite'               => array('slug' => CPT_INITIATIEF ),
				'rewrite'               => false,
				'capability_type'       => 'post'
			));

			$types = array();
			foreach($this->types as $type_id => $type_data) {
				$types[] = $type_id;

				register_post_type($type_id, $type_data);
			}
		return True;
	}

	// TODO: version? $plugin->version?
	public function enqueue_scripts() {

	}
}
