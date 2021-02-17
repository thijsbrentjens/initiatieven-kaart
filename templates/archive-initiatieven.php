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
/*
 * Deze functie retourneert een of meerder lijstjes met links naar de custom taxonomieen.
 * Als je kijkt naar een single term uit een taxonomie, dan worden alleen links naar
 * de andere terms uit diezelfde taxonomie getoond.
 * Maar anders, dan worden lijstjes getoond met provincies waar
 * initiatieven zijn (wat, waarom is er niks in Zeeland?) of alle initiatieftypes.
 */

function led_initiatieven_taxonomy_list( $doreturn = false ) {

	$return = '';

	if ( is_tax( CT_INITIATIEFTYPE ) ) {
		// toon de lijst van ANDERE initiatieftypes
		$return .= led_initiatieven_show_taxonomy_list( CT_INITIATIEFTYPE, __( 'Andere initiatieftypes', 'taxonomie-lijst', 'initiatieven-kaart' ), $doreturn, get_queried_object_id() );
	} elseif ( is_tax( CT_INITIATIEF_PROVINCIE ) ) {
		// toon de lijst van ANDERE provincies
		$return .= led_initiatieven_show_taxonomy_list( CT_INITIATIEF_PROVINCIE, __( 'Andere provincies', 'taxonomie-lijst', 'initiatieven-kaart' ), $doreturn, get_queried_object_id() );
	} else {
		// toon de lijst van ALLE initiatieftypes
		$return .= led_initiatieven_show_taxonomy_list( CT_INITIATIEFTYPE, __( 'Initiatieftypes', 'taxonomie-lijst', 'initiatieven-kaart' ), $doreturn );
		// en de lijst van ALLE provincies
		$return .= led_initiatieven_show_taxonomy_list( CT_INITIATIEF_PROVINCIE, __( 'Provincies', 'taxonomie-lijst', 'initiatieven-kaart' ), $doreturn );
	}

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}

}

//========================================================================================================
/*
 * Deze functie hoest een titel op boven de lijst met initiatieven.
 * Deze titel komt voor op een overzicht van ALLE initiatieven of een lijst
 * met initiatieven per provincie.
 * Je ziet ook het aantal initiatieven.
 */
function led_initiatieven_archive_title( $doreturn = false ) {

	global $wp_query;

	$archive_title       = _x( 'Initiatieven', 'Archive initiatieven', 'initiatieven-kaart' );
	$archive_description = '';
	$return              = '';
	$count               = $wp_query->post_count;

	if ( is_post_type_archive( CPT_INITIATIEF ) ) {
		// info ophalen voor custom post type CPT_INITIATIEF
		$obj = get_post_type_object( CPT_INITIATIEF );

		if ( $obj->labels->singular_name ) {
			$archive_title = $obj->labels->archives;
		}

		$return = '<h1>' . $archive_title . '</h1>';

	} elseif ( ( is_tax( CT_INITIATIEFTYPE ) ) || ( is_tax( CT_INITIATIEF_PROVINCIE ) ) ) {
		// we kijken naar een lijst van initiatieven per initiatieftype of provincie

		$term_id = get_queried_object_id();
		$term    = get_term( $term_id, ( is_tax( CT_INITIATIEFTYPE ) ? CT_INITIATIEFTYPE : CT_INITIATIEF_PROVINCIE ) );

		if ( $term && ! is_wp_error( $term ) ) {
			$archive_title = $term->name;
			if ( $term->description ) {
				$archive_description = $term->description;
			}
		}

		if ( $count ) {
			if ( is_tax( CT_INITIATIEF_PROVINCIE ) ) {
				// voorzetsels, best belangrijk
				$return = '<h1>' . sprintf( _n( '%s initiatief in %s', "%s initiatieven in %s", $count, 'initiatieven-kaart' ), $count, $archive_title ) . '</h1>';
			} else {
				$return = '<h1>' . sprintf( _n( '%s initiatief voor %s', "%s initiatieven voor %s", $count, 'initiatieven-kaart' ), $count, $archive_title ) . '</h1>';
			}
		} else {
			// Niks geen initiatieven niet. Nul, nada, noppes, nihil.
			// Zeeland en Drenthe, laat van je HO-REN!
			$return              = '<h1>Geen initiatieven gevonden voor ' . $archive_title . '</h1>';
			$archive_description = 'Sorry.';
		}

	}

	if ( $archive_description ) {
		$return .= '<p>' . $archive_description . '</p>';
	}

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}

}

//========================================================================================================

function led_initiatieven_archive_list( $doreturn = false ) {

	// De lijst wordt alfabetisch gesorteerd dankzij 'load_all_initiatieven'
	// zie:
	// add_action('pre_get_posts', array($plugin, 'load_all_initiatieven'), 999);

	global $post;

	if ( have_posts() ) {

		$initiatieficons = get_initiatieficons();
		$return          = '<ul id="map-items">';

		while ( have_posts() ) : the_post();

			// use the location attributes to create data-attributes for the map
			// second term false: current post
			$locationField = get_field( 'openstreet_map' );
			$title         = isset( $post->post_title ) ? $post->post_title : _x( 'Type', 'Label type initiatief', 'initiatieven-kaart' );
			$permalink     = get_post_permalink( $post->id );

			// TODO: use the lon/lat of the first marker instead of map center

			/*
			 * haal de intitieftypes op. Dit kunnen er meerdere zijn, op dit moment.
			 * dit is de taxonomie CT_INITIATIEFTYPE
			 * aan elke waarde hiervan zou een icoontje moeten hangen
			 * dus bijv. type 'Community' krijgt een icoontje 'community'
			 */
			$initatieftypes = get_the_terms( get_the_id(), CT_INITIATIEFTYPE );
			$classes        = array();
			if ( $locationField != false ) {
				// er zijn locatie-gegevens voor dit initiatief

				$initiatieftype = '';

				if ( $initatieftypes && ! is_wp_error( $initatieftypes ) ) :
					// check in welk initiatieftype dit initatieftype zit
					// aan dit type hangt o.m. het icoontje
					// NB op dit moment is het praktisch mogelijk om een initiatief aan
					// MEERDERE initiatieftypes te hangen


					$labels = '';

					foreach ( $initatieftypes as $term ) {
						// het icoontje dat bij dit initatieftype hoort, staat in de array $initiatieficons
						array_push( $classes, $initiatieficons[ $term->slug ] );
						$labels .= '<dd class="' . $term->slug . '">' . $term->name . '</dd>';
					}

					if ( $labels ) {
						// als er iets aanwezig is voor de taxonomy initatietype,
						// dan zetten we alle waarden daarvoor in een <dl>
						$initiatieftype = '<dl class="initiatieftype ' . join( " ", $classes ) . '">';
						$initiatieftype .= '<dt>' . _x( 'Type', 'Label type initiatief', 'initiatieven-kaart' ) . '</dt>';
						$initiatieftype .= $labels;
						$initiatieftype .= '</dl>';
					}

				endif;

				// TB: gebruik de lat/lon van de eerste marker als locatie
				// zie https://github.com/mcguffin/acf-openstreetmap-field/wiki/Usage
				$bestLatitude  = '';
				$bestLongitude = '';
				if ( count( $locationField["markers"] ) >= 1 ):
					$bestLatitude  = $locationField["markers"][0]["lat"];
					$bestLongitude = $locationField["markers"][0]["lng"];
				else:
					// TODO: map center acceptable?
					$bestLatitude  = $locationField["lat"];
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

function led_initiatieven_show_taxonomy_list( $taxonomy = 'category', $title = '', $doreturn = false, $exclude = '' ) {

	$return = '';

	if ( taxonomy_exists( $taxonomy ) ) {

		$args = array(
			'taxonomy'           => $taxonomy,
			'orderby'            => 'name',
			'order'              => 'ASC',
			'hide_empty'         => false,
			'ignore_custom_sort' => true,
			'echo'               => 0,
			'hierarchical'       => true,
			'title_li'           => '',
		);

		if ( $exclude ) {
			$args['exclude']    = $exclude;
			$args['hide_empty'] = true;
		}

		$terms = wp_list_categories( $args );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

			if ( $title ) {
				$return .= '<h2>' . $title . '</h2>';
			}

			$return .= '<ul>';
			$return .= $terms;
			$return .= '</ul>';

		}
	}

	if ( $doreturn ) {
		return $return;
	} else {
		echo $return;
	}

}

//========================================================================================================

function led_initiatieven_filter_breadcrumb( $crumb = '', $args = '' ) {

	if ( $crumb ) {

		$span_before_start  = '<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
		$span_between_start = '<span itemprop="name">';
		$span_before_end    = '</span>';
		$term               = '';

		if ( is_tax( CT_INITIATIEFTYPE ) || is_tax( CT_INITIATIEF_PROVINCIE ) ) {

			$obj = get_post_type_object( CPT_INITIATIEF );

			if ( $obj->labels->singular_name ) {
				$archive_title = $obj->label;
			}

			$term_id = get_queried_object_id();
			$term    = get_term( $term_id, ( is_tax( CT_INITIATIEFTYPE ) ? CT_INITIATIEFTYPE : CT_INITIATIEF_PROVINCIE ) );

			if ( $term && ! is_wp_error( $term ) ) {
				return '<a href="' . get_post_type_archive_link( CPT_INITIATIEF ) . '">' . $obj->label . '</a>' . $args['sep'] . $term->name;
			}

			return $crumb;
		}
	}

	return $crumb;

}

//========================================================================================================
