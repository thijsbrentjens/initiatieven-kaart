<?php


if ( function_exists( 'genesis' ) ) {

	// Genesis wordt gebruikt als framework
	// dit geldt voor o.m. het theme voor Digitale Overheid

	// titel toevoegen
	add_action( 'genesis_before_loop', 'led_initiatieven_archive_title', 15 );

	/** standard loop vervangen door custom loop */
	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', 'led_initiatieven_archive_list' );

	// make it so
	genesis();

} else {

	// geen Genesis
	global $post;

	get_header(); ?>

    <div id="primary" class="content-area">
        <div id="content" class="clearfix">

			<?php echo led_initiatieven_archive_title() ?>
			<?php echo led_initiatieven_archive_list() ?>

        </div><!-- #content -->
    </div><!-- #primary -->

    <!-- TODO: now initiate the map here -->

	<?php

	get_sidebar();

	get_footer();


}

//========================================================================================================

function led_initiatieven_archive_title( $doreturn = false ) {

    $archive_title = _x( 'Initiatieven', 'Archive initiatieven', 'initiatieven-kaart' );

    // info ophalen voor custom post type CPT_INITIATIEF
    $obj = get_post_type_object( CPT_INITIATIEF );

    if ( $obj->labels->singular_name ) {
	    $archive_title = $obj->labels->archives;
    }
	$return = '<h1>' . $archive_title . '</h1>';

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}

}

//========================================================================================================

function led_initiatieven_archive_list( $doreturn = false ) {

    // TODO: misschien de lijst gewoon op alfabetisch volgorde sorteren

	global $post;

	if ( have_posts() ) {

		$initiatieficons = get_initiatieficons();
		$return          = '<ul id="map-items">';

		while ( have_posts() ) : the_post();

			// use the location attributes to create data-attributes for the map
			// second term false: current post
			$locationField = get_field( 'openstreet_map' );
			$title          = isset( $post->post_title ) ? $post->post_title : _x( 'Type', 'Label type initiatief', 'initiatieven-kaart' );
			$permalink      = get_post_permalink( $post->id );

			// TODO: use the lon/lat of the first marker instead of map center

			/*
			 * haal de intitieftypes op. Dit kunnen er meerdere zijn, op dit moment.
			 * dit is de taxonomie CT_INITIATIEFTYPE
			 * aan elke waarde hiervan zou een icoontje moeten hangen
			 * dus bijv. type 'Community' krijgt een icoontje 'community'
			 */
			$initatieftypes = get_the_terms( get_the_id(), CT_INITIATIEFTYPE );
			$classes = array();
			if ( $locationField != false ) {
				// er zijn locatie-gegevens voor dit initiatief

				$initiatieftype = '';

				if ( $initatieftypes && ! is_wp_error( $initatieftypes ) ) :
					// check in welk initiatieftype dit initatieftype zit
					// aan dit type hangt o.m. het icoontje
					// NB op dit moment is het praktisch mogelijk om een initiatief aan
					// MEERDERE initiatieftypes te hangen


					$labels  = '';

					foreach ( $initatieftypes as $term ) {
						// het icoontje dat bij dit initatieftype hoort, staat in de array $initiatieficons
						array_push($classes, $initiatieficons[ $term->slug ]);
						$labels    .= '<dd class="' . $term->slug . '">' . $term->name . '</dd>';
					}

					if ( $labels ) {
						// als er iets aanwezig is voor de taxonomy initatietype,
						// dan zetten we alle waarden daarvoor in een <dl>
						$initiatieftype = '<dl class="' . join( " ", $classes ) . '">';
						$initiatieftype .= '<dt class="visuallyhidden">' . _x( 'Type', 'Label type initiatief', 'initiatieven-kaart' ) . '</dt>';
						$initiatieftype .= $labels;
						$initiatieftype .= '</dl>';
					}

				endif;

				// TB: gebruik de lat/lon van de eerste marker als locatie
				// zie https://github.com/mcguffin/acf-openstreetmap-field/wiki/Usage
				$bestLatitude = '';
				$bestLongitude = '';
				if (count($locationField["markers"]) >= 1):
					$bestLatitude = $locationField["markers"][0]["lat"];
					$bestLongitude = $locationField["markers"][0]["lng"];
				else:
					// TODO: map center acceptable?
					$bestLatitude = $locationField["lat"];
					$bestLongitude = $locationField["lng"];
				endif;

				$return .= sprintf( '<li class="map-item" data-latitude="%s" data-longitude="%s" data-map-item-type="%s">', $bestLatitude, $bestLongitude, join( " ", $classes ) );
				$return .= sprintf( '<h2><a href="%s">%s</a></h2>', $permalink, $title );

				// iets van een samenvatting, beschrijving tonen hier
				$return .= sprintf( '<p>%s</p>', wp_strip_all_tags( get_the_excerpt() ) );
				$return .= sprintf( '%s', $initiatieftype );
				$return .= '</li>';
			} else {
				// geen locationField , wel een list item toevoegen, maar zonder de data attributen voor locatie?
				// nog bepalen wat te doen, obv daravan evt refactoren met code hierboven
				$return .= $locationField;
				$return .= sprintf( '<li class="map-item no-location" data-map-item-type="%s">', join( " ", $classes ) );
				$return .= sprintf( '<h2><a href="%s">%s</a></h2>', $permalink, $title );
				$return .= sprintf( '<p>%s</p>', wp_strip_all_tags( get_the_excerpt() ) );
				$return .= '</li>';
			}

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
