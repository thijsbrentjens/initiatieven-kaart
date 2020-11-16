<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Initiatieven_Kaart
 * @subpackage Initiatieven_Kaart/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Initiatieven_Kaart
 * @subpackage Initiatieven_Kaart/public
 * @author     Your Name <email@example.com>
 */
class Initiatieven_Kaart_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $initiatieven_kaart    The ID of this plugin.
	 */
	private $initiatieven_kaart;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $initiatieven_kaart       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $initiatieven_kaart, $version ) {

		$this->initiatieven_kaart = $initiatieven_kaart;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Initiatieven_Kaart_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Initiatieven_Kaart_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

			wp_register_style('leaflet-css', plugin_dir_url( __FILE__ ) . 'css/leaflet.css', array(), '1.7.1');
			wp_enqueue_style('leaflet-css');

			wp_register_style('markercluster-css', plugin_dir_url( __FILE__ ) . 'css/MarkerCluster.css', array(), '1.0.0');
			wp_enqueue_style('markercluster-css');

			wp_register_style('markercluster-default-css', plugin_dir_url( __FILE__ ) . 'css/MarkerCluster.Default.css', array(), '1.0.0');
			wp_enqueue_style('markercluster-default-css');

			wp_register_style('leaflet-awesome-markers-css', plugin_dir_url( __FILE__ ) . 'css/leaflet.awesome-markers.css', array(), '1.0.0');
			wp_enqueue_style('leaflet-awesome-markers-css');

			wp_register_style('ion-awesome-markers-css', 'http://code.ionicframework.com/ionicons/1.5.2/css/ionicons.min.css', array(), '1.0.0');
			wp_enqueue_style('ion-awesome-markers-css');
			wp_enqueue_style( $this->initiatieven_kaart, plugin_dir_url( __FILE__ ) . 'css/initiatieven-kaart-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Initiatieven_Kaart_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Initiatieven_Kaart_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

 				wp_enqueue_script('leaflet-js', plugin_dir_url( __FILE__ ) . 'js/leaflet.js', array('jquery'), '1.7.1', true);

 				wp_enqueue_script('leaflet-markercluster-js', plugin_dir_url( __FILE__ ) . 'js/leaflet.markercluster.js', array('jquery', 'leaflet-js'),  $this->version, true);

				wp_enqueue_script('leaflet-awesome-markers-js', plugin_dir_url( __FILE__ ) . 'js/leaflet.awesome-markers.js', array( 'jquery' , 'leaflet-js'), $this->version, true );

				wp_enqueue_script( $this->initiatieven_kaart, plugin_dir_url( __FILE__ ) . 'js/initiatieven-kaart-public.js', array( 'jquery' ), $this->version, false );

	}

}
