<?php

add_action( 'init', 'register_lms_quiz_post_types' );

/** Registers the post types used for the LMS Quiz system */
function register_lms_quiz_post_types() {

	$quiz = array(
		'labels'              => array(
			'name'               => _x( 'Quizzes', 'post type general name' ),
			'singular_name'      => _x( 'Quiz', 'post type singular name' ),
			'menu_name'          => _x( 'Quizzes', 'admin menu' ),
			'name_admin_bar'     => _x( 'Quizzes', 'add new on admin bar' ),
			'add_new'            => _x( 'Add New Quiz', 'quiz' ),
			'add_new_item'       => __( 'Add New Quiz' ),
			'new_item'           => __( 'New Quiz' ),
			'edit_item'          => __( 'Edit Quiz' ),
			'view_item'          => __( 'View Quiz' ),
			'all_items'          => __( 'All Quizzes' ),
			'search_items'       => __( 'Search Quizzes' ),
			'parent_item_colon'  => __( 'Parent Quiz:' ),
			'not_found'          => __( 'No quizzes found.' ),
			'not_found_in_trash' => __( 'No quizzes found in Trash.' )
		),
		'public'             => false,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'exclude_from_search'=> true,
		'menu_position'      => 27,
		'menu_icon'          => 'dashicons-clipboard',
		'supports'           => array( 'title' ),
	);

	$submission = array(
		'labels'              => array(
			'name'               => _x( 'Quiz Reports', 'post type general name' ),
			'singular_name'      => _x( 'Quiz Report', 'post type singular name' ),
			'menu_name'          => _x( 'Quiz Reports', 'admin menu' ),
			'name_admin_bar'     => _x( 'Quiz Reports', 'add new on admin bar' ),
			'add_new'            => _x( 'Add New Quiz Report', 'quiz' ),
			'add_new_item'       => __( 'Add New Quiz Report' ),
			'new_item'           => __( 'New Quiz Report' ),
			'edit_item'          => __( 'View Quiz Report' ),
			'view_item'          => __( 'View Quiz Report' ),
			'all_items'          => __( 'All Quiz Reports' ),
			'search_items'       => __( 'Search Quiz Reports' ),
			'parent_item_colon'  => __( 'Parent Quiz Report:' ),
			'not_found'          => __( 'No submissions found.' ),
			'not_found_in_trash' => __( 'No submissions found in Trash.' )
		),
		'public'             => false,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'query_var'          => true,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'exclude_from_search'=> true,
		'menu_position'      => 27,
		'menu_icon'          => 'dashicons-clipboard',
		'supports'           => array( 'title' ),
	);

	register_post_type( 'lms_quiz', $quiz );
	register_post_type( 'lms_quiz_submission', $submission );
}

/**
 * Sets the custom 'post updated' messages for our custom post types
 * @param  array $messages An array of the messages used by WordPress
 * @return array $messages Return the array back to WordPress with amended values.
 */
function lms_quiz_updated_messages( $messages ) {
	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$messages['lms_quiz'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Quiz updated.' ),
		2  => __( 'Custom field updated.' ),
		3  => __( 'Custom field deleted.' ),
		4  => __( 'Quiz updated.' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Quiz restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Quiz published.' ),
		7  => __( 'Quiz saved.' ),
		8  => __( 'Quiz submitted.' ),
		9  => sprintf(
			__( 'Quiz scheduled for: <strong>%1$s</strong>.' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Quiz draft updated.' )
	);

	$messages['lms_quiz_submission'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Quiz Report updated.' ),
		2  => __( 'Custom field updated.' ),
		3  => __( 'Custom field deleted.' ),
		4  => __( 'Quiz Report updated.' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Quiz Report restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Quiz Report published.' ),
		7  => __( 'Quiz Report saved.' ),
		8  => __( 'Quiz Report submitted.' ),
		9  => sprintf(
			__( 'Quiz Report scheduled for: <strong>%1$s</strong>.' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Quiz Report draft updated.' )
	);

	if ( $post_type_object->publicly_queryable ) {
		$permalink = get_permalink( $post->ID );

		$view_link = '';
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = '';
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}

	return $messages;
}
