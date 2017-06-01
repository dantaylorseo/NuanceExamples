<?php
/*
Plugin Name: Rainmaker Mail
Plugin URI: http://newrainmaker.com/
Description: Feedblitz integrated email handler
Version: 1.0.1
Author: Rainmaker Digital LLC
Author URI: http://www.rainmakerdigital.com
*/

if ( !defined( 'ABSPATH' ) ) {
	die( "Sorry, you are not allowed to access this page directly." );
}


class Rainmaker_Mail_Init {

	/**
	 * __construct function.
	 * Registers the autoloader and adds required actions/filters to allow this tool to work.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		//** Allows reseller to turn off email service
		if ( get_option( 'reseller_disable_email_service' ) ) {

			return;

		}

		//do not load if FeedBlitz isn't setup
		if ( ! rm_lp_get_option( 'feedblitz_api' ) ) {
			return;
		}

		if ( is_admin() ) {
			add_filter( 'gettext', array( $this, 'gettext' ) );
		}

		define( 'RM_MAIL_DIR'    , plugin_dir_path( __FILE__ )                       );
		define( 'RM_MAIL_CLASSES', RM_MAIL_DIR                 . 'includes/classes/' );
		define( 'RM_MAIL_ASSETS' , plugin_dir_url( __FILE__ )  . 'assets/'           );

		spl_autoload_register( array( 'Rainmaker_Mail_Init', 'autoloader' ) );

		if ( isset( $_GET['page'] ) && 'universal-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'email' === $_GET['tab'] ) {

			add_action( 'admin_enqueue_scripts', array( 'Rainmaker_Mail_Settings', 'enqueue_scripts' ) );

		}

		if ( isset( $_GET['page'] ) && ( 'rainmail-manage-lists' === $_GET['page'] || 'rainmail-edit-list' === $_GET['page'] ) ) {

			add_action( 'admin_enqueue_scripts', array( 'Rainmaker_Mail_Manage_Lists', 'enqueue_scripts' ) );

		}

		add_action( 'init'                                       , array( 'Rainmaker_Mail_CPT'             , 'register_post_type' )        );
		add_action( 'rm_universal_settings_content_email_service', array( 'Rainmaker_Mail_Settings'        , 'display'            ), 20    );
		add_action( 'load-post.php'                              , array( 'Rainmaker_Mail_Init'            , 'maybe_do_post_meta' )        );
		add_action( 'load-post-new.php'                          , array( 'Rainmaker_Mail_Init'            , 'maybe_do_post_meta' )        );
		add_action( 'save_post'                                  , array( 'Rainmaker_Mail_Init'            , 'maybe_do_save'      ), 10, 2 );
		add_action( 'wp_ajax_mail_preview'                       , array( 'Rainmaker_Mail_AJAX'            , 'preview'            )        );
		add_action( 'save_post'                                  , array( 'Rainmaker_Mail_Init'            , 'maybe_publish'      ), 15, 2 );
		add_filter( 'wp_mail'                                    , array( 'Rainmaker_Mail_Build_Content'   , 'wp_mail'            )        );
		add_action( 'premise_copy_post_meta'                     , array( 'Rainmaker_Mail_Duplicate'       , 'copy_post_meta'     ), 10, 3 );
		add_filter( 'premise_copy_draft_status'                  , array( 'Rainmaker_Mail_Duplicate'       , 'draft_status'       )        );
		add_filter( 'manage_edit-mail_columns'                   , array( 'Rainmaker_Mail_Edit_Table'      , 'posts_columns'      )        );
		add_action( 'manage_mail_posts_custom_column'            , array( 'Rainmaker_Mail_Edit_Table'      , 'custom_column'      ), 10, 2 );
		add_filter( 'post_row_actions'                           , array( 'Rainmaker_Mail_Init'            , 'page_row_actions'   ), 10, 2 );
		add_action( 'load-edit.php'                              , array( 'Rainmaker_Mail_Init'            , 'maybe_do_table'     )        );
		add_action( 'wp_ajax_rm_broadcast_metrics'               , array( 'Rainmaker_Mail_Edit_Table'      , 'get_metrics'        )        );
		add_action( 'wp_ajax_rm_broadcast_preview'               , array( 'Rainmaker_Mail_Edit_Table'      , 'preview'            )        );
		add_action( 'wp_ajax_rm_rainmail_template_preview'       , array( 'Rainmaker_Mail_Template_Preview', 'preview'            )        );
		add_action( 'wp_ajax_edit_optin_list'                    , array( 'Rainmaker_Mail_AJAX_Lists'      , 'edit_optin_list'    )        );
		add_action( 'admin_menu'                                 , array( 'Rainmaker_Mail_Init'            , 'admin_menu_lists'   )        );

	}

	/**
	 * Registered autoload function.
	 * Used to load class files automatically if they are in the provided array.
	 *
	 * @access public
	 * @param mixed $class
	 * @return void
	 */
	static function autoloader( $class ) {

		$classes = array(

			'Rainmaker_Mail_AJAX'               => 'class.Rainmaker_Mail_AJAX.php',
			'Rainmaker_Mail_AJAX_Lists'         => 'class.Rainmaker_Mail_AJAX_Lists.php',
			'Rainmaker_Mail_Build_Content'      => 'class.Rainmaker_Mail_Build_Content.php',
			'Rainmaker_Mail_Broadcast_Meta'     => 'class.Rainmaker_Mail_Broadcast_Meta.php',
			'Rainmaker_Mail_Broadcast_Save'     => 'class.Rainmaker_Mail_Broadcast_Save.php',
			'Rainmaker_Mail_CPT'                => 'class.Rainmaker_Mail_CPT.php',
			'Rainmaker_Mail_Duplicate'          => 'class.Rainmaker_Mail_Duplicate.php',
			'Rainmaker_Mail_Edit_Table'         => 'class.Rainmaker_Mail_Edit_Table.php',
			'Rainmaker_Mail_Mailer'             => 'class.Rainmaker_Mail_Mailer.php',
			'Rainmaker_Mail_Settings'           => 'class.Rainmaker_Mail_Settings.php',
			'Rainmaker_Mail_Template_Option'    => 'class.Rainmaker_Mail_Template_Option.php',
			'Rainmaker_Mail_Template_Preview'   => 'class.Rainmaker_Mail_Template_Preview.php',
			'Rainmaker_Mail_Manage_Lists'       => 'class.Rainmaker_Mail_Manage_Lists.php',

		);

		if ( ! isset( $classes[ $class ] ) ) {
			return;
		}

		include( RM_MAIL_CLASSES . $classes[ $class ] );

	}

	/**
	 * Action on the load-post.php and load-post-new.php hooks.
	 * Checks to make sure the current post type is mail
	 * then instantiates the Rainmaker_Mail_Campaign_Meta object.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function maybe_do_post_meta() {

		global $typenow;

		if ( 'mail' == $typenow ) {

			new Rainmaker_Mail_Broadcast_Meta;

		}

	}

	/**
	 * Action on the save_post hook.
	 * Checks to make sure the _broadcast_editor_nonce is set and correctly verified
	 * then instantiates the Rainmaker_Mail_Broadcast_Save object.
	 *
	 * @access public
	 * @static
	 * @param mixed $post_id
	 * @param mixed $post
	 * @return void
	 */
	static public function maybe_do_save( $post_id, $post ) {

		if ( isset( $_POST['_broadcast_editor_nonce'] ) && wp_verify_nonce( $_POST['_broadcast_editor_nonce'], RM_MAIL_DIR ) ) {

			/* Get the post type object. */
			$post_type = get_post_type_object( $post->post_type );

			/* Check if the current user has permission to edit the post. */
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ){
				return $post_id;
			}

			new Rainmaker_Mail_Broadcast_Save( $post_id, $post );

		}

		return $post_id;

	}

	/**
	 * Callback on `transition_post_status` action.
	 * Conditionally calls `Rainmaker_Mail_Mailer::publish`
	 * if the post status is `publish` and post_type is `mail`.
	 *
	 * @access public
	 * @static
	 * @param string $new_status
	 * @param string $old_status
	 * @param WP_Post $post
	 * @return void
	 */
	static function maybe_publish( $post_id, $post ) {

		if ( 'publish' === $post->post_status && 'mail' === $post->post_type ) {
			Rainmaker_Mail_Mailer::publish( $post_id, $post );
		}

	}

	/**
	 * Callback for WordPress 'page_row_actions' action.
	 *
	 * Add the 'Duplicate' ability to the mail post type.
	 *
	 * @since 2.4.2
	 *
	 * @param  array  $actions An associative array of actions.
	 * @param  object $post    The current post object.
	 * @return array           An associative array of actions.
	 */
	static public function page_row_actions( $actions, $post ) {

		if ( 'mail' === get_post_type( $post ) ) {

			return Rainmaker_Mail_Edit_Table::page_row_actions( $actions, $post );

		}

		return $actions;

	}

	/**
	 * Action on the load-post.php and load-post-new.php hooks.
	 * Checks to make sure the current post type is mail
	 * then instantiates the Rainmaker_Subscriber_Meta object.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function maybe_do_table() {

		global $typenow;

		if ( 'mail' == $typenow ) {

			add_action( 'admin_enqueue_scripts', array( 'Rainmaker_Mail_Edit_Table', 'enqueue_scripts' ) );

		}

	}

	/**
	 * Callback on the `gettext` filter.
	 * Changes the translated strings for the mail editor.
	 *
	 * @access public
	 * @param  string $text
	 * @return string
	 */
	function gettext( $text ) {

		if ( ( isset( $_GET['post_type'] ) && 'mail' == $_GET['post_type'] ) || ( isset( $_GET['post'] ) && 'mail' == get_post_type( $_GET['post'] ) ) ) {

			switch ( $text ) {
			case 'Schedule' :
				$text = __( 'Schedule Broadcast', '' );
				break;
			case 'Publish' :
				$text = __( 'Send Broadcast Immediately', '' );
				break;
			}

		}

		return $text;

	}

	/**
	 *
	 * Creates the pages for the manage mail lists
	 *
	 * @return Void
	 */
	public static function admin_menu_lists() {

		add_menu_page( __( 'Manage Email Lists', '' ), __( 'Manage Email Lists', '' ), 'edit_posts', 'rainmail-manage-lists', array( 'Rainmaker_Mail_Manage_Lists', 'manage_lists_page' ) );
		add_menu_page( __( 'Edit Email List'   , '' ), __( 'Edit Email List'   , '' ), 'edit_posts', 'rainmail-edit-list'   , array( 'Rainmaker_Mail_Manage_Lists', 'edit_list_page'    ) );

	}

}

new Rainmaker_Mail_Init;
