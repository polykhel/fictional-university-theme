<?php

add_action( 'rest_api_init', 'universityRegisterSearch' );

function universityRegisterSearch(): void {
	register_rest_route( 'university/v1', 'search', array(
		'methods'  => WP_REST_SERVER::READABLE,
		'callback' => 'universitySearchResults',
	) );
}

function universitySearchResults( $data ) {
	$mainQuery = new WP_Query( array(
		'post_type' => array( 'post', 'page', 'professor', 'program', 'campus', 'event' ),
		's'         => sanitize_text_field( $data['term'] ),
	) );

	$results = array(
		'generalInfo' => array(),
		'professors'  => array(),
		'programs'    => array(),
		'events'      => array(),
		'campuses'    => array(),
	);

	while ( $mainQuery->have_posts() ) {
		$mainQuery->the_post();

		$post_type = get_post_type();
		if ( $post_type == 'post' or $post_type == 'page' ) {
			$results['generalInfo'][] = array(
				'title'      => get_the_title(),
				'permalink'  => get_the_permalink(),
				'postType'   => get_post_type(),
				'authorName' => get_the_author()
			);
		}
		if ( $post_type == 'professor' ) {
			$results['professors'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink(),
				'image'     => get_the_post_thumbnail_url( 0, 'professorLandscape' )
			);
		}
		if ( $post_type == 'program' ) {
			$relatedCampuses = get_field( 'related_campus' );

			if ( $relatedCampuses ) {
				foreach ( $relatedCampuses as $campus ) {
					$results['campuses'][] = array(
						'title' => get_the_title($campus),
						'permalink' => get_the_permalink($campus)
					);
				}
			}

			$results['programs'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink(),
				'id'        => get_the_ID()
			);
		}
		if ( $post_type == 'event' ) {
			$eventDate = new DateTime( get_field( 'event_date' ) );
			if ( has_excerpt() ) {
				$description = get_the_excerpt();
			} else {
				$description = wp_trim_words( get_the_content(), 18 );
			}

			$results['events'][] = array(
				'title'       => get_the_title(),
				'permalink'   => get_the_permalink(),
				'month'       => $eventDate->format( 'M' ),
				'day'         => $eventDate->format( 'd' ),
				'description' => $description
			);
		}
		if ( $post_type == 'campus' ) {
			$results['campuses'][] = array(
				'title'     => get_the_title(),
				'permalink' => get_the_permalink()
			);
		}
	}

	if ( $results['programs'] ) {
		$programsMetaQuery = array( 'relation' => 'OR' );

		foreach ( $results['programs'] as $item ) {
			$programsMetaQuery[] = array(
				'key'     => 'related_programs',
				'compare' => 'LIKE',
				'value'   => '"' . $item['id'] . '"'
			);
		}

		$programRelationshipQuery = new WP_Query( array(
			'post_type'  => array( 'professor', 'event' ),
			'meta_query' => $programsMetaQuery
		) );

		while ( $programRelationshipQuery->have_posts() ) {
			$programRelationshipQuery->the_post();
			$post_type = get_post_type();

			if ( $post_type == 'professor' ) {
				$results['professors'][] = array(
					'title'     => get_the_title(),
					'permalink' => get_the_permalink(),
					'image'     => get_the_post_thumbnail_url( 0, 'professorLandscape' )
				);
			}
			if ( $post_type == 'event' ) {
				$eventDate = new DateTime( get_field( 'event_date' ) );
				if ( has_excerpt() ) {
					$description = get_the_excerpt();
				} else {
					$description = wp_trim_words( get_the_content(), 18 );
				}

				$results['events'][] = array(
					'title'       => get_the_title(),
					'permalink'   => get_the_permalink(),
					'month'       => $eventDate->format( 'M' ),
					'day'         => $eventDate->format( 'd' ),
					'description' => $description
				);
			}
		}
		$results['professors'] = array_unique( $results['professors'], SORT_REGULAR );
		$results['events'] = array_unique( $results['events'], SORT_REGULAR );
	}


	return $results;
}