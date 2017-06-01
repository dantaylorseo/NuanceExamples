<?php

class Rainmaker_Mail_CPT {

	/**
	 * Callback method on the `init` hook.
	 * Registers the Mail post type.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static public function register_post_type() {

		//echo 'working';

		$labels = array(
			'name'               => _x( 'Broadcast Emails'    , 'post type general name' , '' ),
			'singular_name'      => _x( 'Broadcast Email'     , 'post type singular name', '' ),
			'add_new'            => _x( 'Add New Email' , 'mail'                   , '' ),
			'menu_name'          => __( 'Broadcast Emails'                               , '' ),
			'add_new_item'       => __( 'Add New Email'                            , '' ),
			'edit_item'          => __( 'Edit Email'                               , '' ),
			'new_item'           => __( 'New Email'                                , '' ),
			'all_items'          => __( 'All Emails'                               , '' ),
			'view_item'          => __( 'View Email'                               , '' ),
			'search_items'       => __( 'Search Emails'                            , '' ),
			'not_found'          => __( 'No Emails Found'                          , '' ),
			'not_found_in_trash' => __( 'No Emails Found In Trash'                 , '' ),
			'parent_item_colon'  => '',

		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'revisions' ),
			'menu_position'      => 5,
		);

		register_post_type( 'mail', $args );

	}

}
