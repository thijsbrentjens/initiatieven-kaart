<?php


if ( function_exists( 'genesis' ) ) {

	// Genesis wordt gebruikt als framework
	// dit geldt voor o.m. het theme voor Digitale Overheid

	// geen meuk over publicatiedatums etc tonen
	remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );

	// extra informatie toevoegen
	add_action( 'genesis_after_entry_content', 'led_initiatief_single_info' );

	// make it so
	genesis();

} else {

	// geen Genesis
	global $post;

	get_header(); ?>

    <div id="primary" class="content-area">
        <div id="content" class="clearfix">

            <h1><?php the_title() ?></h1>
			<?php the_content() ?>
			<?php echo led_initiatief_single_info() ?>

        </div><!-- #content -->
    </div><!-- #primary -->

    <!-- TODO: now initiate the map here -->

	<?php

	get_sidebar();

	get_footer();


}

//========================================================================================================

function led_initiatief_single_info( $doreturn = false ) {
	$bla = '';
	global $post;

	$initiatieftype  = '';
	$return          = '';
	$provincie       = array();
	$initiatieficons = get_initiatieficons();
	$initatieftypes  = get_the_terms( get_the_id(), CT_INITIATIEFTYPE );

	// haal de waarden op uit de ACF-velden
	$locationField         = get_field( 'openstreet_map' );
	$straatnaam_huisnummer = get_field( 'locatie_straatnaam_huisnummer' );
	$locatie_postcode      = get_field( 'locatie_postcode' );
	$website               = get_field( 'locatie_website' );
	$contactpersoon        = get_field( 'locatie_contactpersoon' );
	$contactgegevens       = get_field( 'locatie_contactgegevens' );
	$plaatsnaam            = get_field( 'locatie_plaatsnaam' );


	// bepalen aan welke provincie(s) dit initiatief hangt
	$terms = get_the_terms( get_the_id(), CT_INITIATIEF_PROVINCIE );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$provincie[] = $term->name;
		}
	}

	if ( $initatieftypes && ! is_wp_error( $initatieftypes ) ) :
		// check in welk initiatieftype dit initatieftype zit
		// aan dit type hangt o.m. het icoontje
		// NB op dit moment is het praktisch mogelijk om een initiatief aan
		// MEERDERE initiatieftypes te hangen

		$classes = array();
		$labels  = '';

		foreach ( $initatieftypes as $term ) {

			// het icoontje dat bij dit initatieftype hoort, staat in de array $initiatieficons
			$classes[ $term->slug ] = $initiatieficons[ $term->slug ];
			$labels                 .= '<dd class="' . $term->slug . '">' . $term->name . '</dd>';
		}

		if ( $labels ) {
			// als er iets aanwezig is voor de taxonomy initatietype,
			// dan zetten we alle waarden daarvoor in een <dl>
			$initiatieftype = '<dl class="initiatieftype ' . join( " ", $classes ) . '">';
			$initiatieftype .= '<dt>' . _x( 'Type initiatief', 'Label type initiatief', 'initiatieven-kaart' ) . '</dt>';
			$initiatieftype .= $labels;
			$initiatieftype .= '</dl>';

		}

	endif;


	if ( $locationField != false ) {
		// er zijn locatie-gegevens voor dit initiatief
		// TODO: misschien willen we bij een single initiatief ook een kaart tonen?
		//		$return .= sprintf( '<li class="map-item" data-latitude="%s" data-longitude="%s" data-map-item-type="%s">', $locationField["lat"], $locationField["lng"], join( " ", $classes ) );
		//		$return .= '</li>';
	}

	// Initiatieftype
	if ( $initiatieftype ) {
		$return .= sprintf( '%s', $initiatieftype );
	}

	// Adresgegevens
	if ( $provincie || $straatnaam_huisnummer || $plaatsnaam || $locatie_postcode ) {
		// TODO: schema markup voor locatie
		$return .= '<h2>' . _x( 'Adres', 'Single initiatief', 'initiatieven-kaart' ) . '</h2>';
		$adres  = '';
		if ( $straatnaam_huisnummer ) {
			$adres .= wp_strip_all_tags( $straatnaam_huisnummer ) . '<br>';
		}
		if ( $locatie_postcode ) {
			$adres .= ( ( $adres ) ? wp_strip_all_tags( $locatie_postcode ) . '<br>' : wp_strip_all_tags( $locatie_postcode ) );
		}
		if ( $plaatsnaam ) {
			$adres .= ( ( $adres ) ? wp_strip_all_tags( $plaatsnaam ) . '<br>' : wp_strip_all_tags( $plaatsnaam ) );
		}
		if ( $provincie ) {
			$adres .= ( ( $adres ) ? join( ", ", $provincie ) . '<br>' : join( ", ", $provincie ) );
		}
		$return .= '<p>' . $adres . '</p>';
	}

	// Contactgegevens
	if ( $website || $contactpersoon || $contactgegevens ) {
		// TODO: schema markup voor contactinformatie
		$return .= '<h2>' . _x( 'Contact', 'Single initiatief', 'initiatieven-kaart' ) . '</h2>';
		if ( $website ) {
			$linktext = $website;
			$linktext = preg_replace( '|https://|i', '', $linktext );
			$linktext = preg_replace( '|http://|i', '', $linktext );
			$linktext = rtrim( $linktext, '/' );;

			$return .= '<p><a href="' . esc_url( $website ) . '">' . $linktext . '</a></p>';
		}
		if ( $contactpersoon ) {
			$return .= '<p>' . wp_strip_all_tags( $contactpersoon ) . '</p>';
		}
		if ( $contactgegevens ) {
			$return .= '<p>' . wp_strip_all_tags( $contactgegevens ) . '</p>';
		}
	}

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}
}

//========================================================================================================
