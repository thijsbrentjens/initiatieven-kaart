<?php

if ( function_exists( 'genesis' ) ) {

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

					$contenttype     = get_post_type();
					// TODO: check for contenttype being the customtype?
					// echo $contenttype;

				// use the location attributes to create data-attributes for the map
				// second term false: current post
				// TODO: configurable name for the location field?
				$locationField = get_field('location', false, false);
				// TODO: use the lon/lat of the first marker instead of map center
				$map_item_type = "onbekend";
				$map_item_typeField = get_field('map-item-type', false, false);
				$title = isset( $post->post_title ) ? $post->post_title : '';
				$permalink = get_post_permalink($post->id);
				if ($map_item_typeField) {
					$map_item_type = $map_item_typeField;
				} else {
					// category --> unknown
				}

				if ($locationField != false) {
					// TODO: is the location the center of the map, or better the first marker?
					// preferably the first marker, need to decide with Paul
					printf( '<li class="map-item" data-latitude="%s" data-longitude="%s" data-map-item-type="%s">', $locationField["lat"], $locationField["lng"], $map_item_type );
					printf ('<h4>%s</h4>', $title);
					printf ('<p class="map-item-type %s">Soort initiatief: %s</p>', $map_item_type, $map_item_type);
					printf ('<p><a href="%s" class="postdetails">Meer informatie over dit initiatief</a></p>', $permalink);
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
