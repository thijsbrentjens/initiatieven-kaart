<?php

if ( function_exists( 'genesis' ) ) {

	echo 'Genesis custom loop! initiatieven_archive_custom_loop';

	/** Replace the standard loop with our custom loop */
	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', 'initiatieven_archive_custom_loop' );

	genesis();

} else {
	global $post;


	get_header(); ?>

    <div id="primary" class="content-area">
        <div id="content" class="clearfix">
            <h3>Data initiatieven</h3>
            <ul id="map-items">
				<?php while ( have_posts() ) : the_post();

					$contenttype = get_post_type();
					// TODO: check for contenttype being the customtype?
					echo 'ahem ' . $contenttype;

					// use the location attributes to create data-attributes for the map
					// second term false: current post
					// TODO: configurable name for the location field?
					$locationField = get_field( 'openstreet_map' );
					echo '<pre>';
					var_dump( $locationField );
					echo '</pre>';

					/*
					 * <div class="leaflet-map" data-height="400" data-map="leaflet" data-map-lng="5.1103163" data-map-lat="52.0925858" data-map-zoom="14" data-map-layers="[&quot;OpenStreetMap.Mapnik&quot;]" data-map-markers="[{&quot;label&quot;:&quot;Stationshal 12B, 3511CE Utrecht, Utrecht The Netherlands&quot;,&quot;default_label&quot;:&quot;Stationshal 12B, 3511CE Utrecht, Utrecht The Netherlands&quot;,&quot;lat&quot;:52.0892107,&quot;lng&quot;:5.10993}]"></div>
					 */

					// TODO: use the lon/lat of the first marker instead of map center
					$map_item_type = "onbekend";

					// haal de intitieftypes op. Dit kunnen er meerdere zijn, op dit moment.
					$initatieftypes = get_the_terms( get_the_id(), CT_INITIATIEFTYPE );
					$title          = isset( $post->post_title ) ? $post->post_title : '';
					$permalink      = get_post_permalink( $post->id );
					if ( $map_item_typeField ) {
						$map_item_type = $map_item_typeField;
					} else {
						// category --> unknown
					}

					if ( $locationField != false ) {
						// TODO: is the location the center of the map, or better the first marker?
						// preferably the first marker, need to decide with Paul
						printf( '<li class="map-item" data-latitude="%s" data-longitude="%s" data-map-item-type="%s">', $locationField["lat"], $locationField["lng"], $map_item_type );
						printf( '<h2><a href="%s">%s</a></h2>', $permalink, $title );

						// iets van een samenvatting, beschrijving tonen hier
						printf( '<p>%s</p>', wp_strip_all_tags( get_the_excerpt() ) );

						// als er iets aanwezig is voor de taxonomy initatietype,
                        // dan zetten we alle waarden daarvoor in een <dl>
						if ( $initatieftypes && ! is_wp_error( $initatieftypes ) ) :

							$classes = array();
							$labels = array();

							foreach ( $initatieftypes as $term ) {
								$classes[] = $term->slug;
								$labels[]  = $term->name;
							}

							?>

                            <dl class="<?php echo join( " ", $classes ) ?>">
                                <dt><?php _e( 'Type', 'Label type initiatief', 'initiatieven-kaart' ) ?></dt>
                                <dd><?php echo join( ", ", $labels ) ?></dd>
                            </dl>
						<?php
						endif;

						echo '</li>';
					}
					?>


				<?php endwhile; ?>
            </ul>
            <!-- <div id="initiatieven-kaart-map" class="archives-map"></div> -->
        </div><!-- #content -->
    </div><!-- #primary -->

    <!-- TODO: now initiate the map here -->

	<?php

	get_sidebar();

	get_footer();


}


/** Code for custom loop */
function initiatieven_archive_custom_loop() {

	// code for a completely custom loop
	global $post;

	if ( have_posts() ) {

		$postcounter = 0;

		while ( have_posts() ) : the_post();

			$postcounter ++;

			$permalink       = get_permalink();
			$excerpt         = wp_strip_all_tags( get_the_excerpt( $post ) );
			$postdate        = get_the_date();
			$classattr       = genesis_attr( 'entry' );
			$contenttype     = get_post_type();
			$current_post_id = isset( $post->ID ) ? $post->ID : 0;
			$cssid           = 'image_featured_image_post_' . $current_post_id;

			$labelledbytitleid = sanitize_title( 'title_' . $contenttype . '_' . $current_post_id );
			$labelledby        = ' aria-labelledby="' . $labelledbytitleid . '"';

			printf( '<article %s %s>', $classattr, $labelledby );
			printf( '<a href="%s">Yoyoyohy <h2 id="%s">%s</h2><p class="meta">%s</p><p>%s</p></a>', get_permalink(), $labelledbytitleid, $thetitle, $postdate, $excerpt );
			echo '</article>';

		endwhile;

		wp_reset_query();

	}

}
