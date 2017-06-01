<?php

class LMS_Quiz_Admin_Menu {

	private static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 */
	public static function get_instance() {
		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_quiz_reports_page' ) );
	}

	/**
	 * Callback for `admin_menu` action
	 * Sets up the reports page
	 */
	public static function add_quiz_reports_page() {
		add_object_page( __( 'Quizzes' ), __( 'Quizzes' ), 'edit_posts', 'lms-quiz', array( 'LMS_Quiz_Reports', 'lms_quiz_dashboard' ) );
		add_submenu_page( 'lms-quiz', __( 'Quiz Reports' ), __( 'Quiz Reports' ), 'edit_posts', 'lms-quiz-reports', array( 'LMS_Quiz_Reports', 'quiz_reports_page' ) );
	}

}

LMS_Quiz_Admin_Menu::get_instance();