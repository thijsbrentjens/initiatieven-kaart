<?php

if ( function_exists( 'genesis' ) ) {

	// Genesis wordt gebruikt als framework
	// dit geldt voor o.m. het theme voor Digitale Overheid

	// in de breadcrumb zetten we de link naar de algemene kaart
	add_filter( 'genesis_single_crumb', 'led_initiatieven_filter_breadcrumb', 10, 2 );
	add_filter( 'genesis_page_crumb', 'led_initiatieven_filter_breadcrumb', 10, 2 );
	add_filter( 'genesis_archive_crumb', 'led_initiatieven_filter_breadcrumb', 10, 2 );

	// titel toevoegen
	add_action( 'genesis_before_loop', 'led_initiatieven_archive_title', 15 );

	/** standard loop vervangen door custom loop */
	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', 'led_initiatieven_archive_list' );

	// lijstjes toevoegen met de diverse custom taxonomieen
	add_action( 'genesis_loop', 'led_initiatieven_taxonomy_list' );

	// make it so
	genesis();

} else {

	// geen Genesis, maar wel dezelfde content, ongeveer, soort van
	global $post;

	get_header(); ?>

    <div id="primary" class="content-area">
        <div id="content" class="clearfix">

			<?php echo led_initiatieven_archive_title() ?>
			<?php echo led_initiatieven_archive_list() ?>
			<?php echo led_initiatieven_taxonomy_list() ?>

        </div><!-- #content -->
    </div><!-- #primary -->

    <!-- TODO: now initiate the map here -->

	<?php

	get_sidebar();

	get_footer();


}

//========================================================================================================

function led_initiatieven_archive_list( $doreturn = false ) {

	global $post;

	if ( have_posts() ) {

		$initiatieficons = led_get_initiatieficons();
		$return          = '<ul id="map-items">';

		while ( have_posts() ) : the_post();

			$return .= led_get_list_item_archive( $post, $initiatieficons );

		endwhile;

		$return .= '</ul>';

	}

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}
}

//========================================================================================================
