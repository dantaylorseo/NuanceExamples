<?php
/**
 * Plugin Name: LMS Quiz System
 * Plugin URI:
 * Description:
 * Version: 1.0
 * Author: Dan Taylor
 * Author URI:
 * License:  GPLv2 or later
 */


// Extension directory
define("LMS_QUIZ_SYSTEM_DIR", plugin_dir_path( __FILE__ ) );
define("LMS_QUIZ_SYSTEM_URL", plugins_url( '', __FILE__ ) );

require_once( LMS_QUIZ_SYSTEM_DIR . "/includes/custom_functions.php" );
require_once( LMS_QUIZ_SYSTEM_DIR . "/includes/post_types.php" );
require_once( LMS_QUIZ_SYSTEM_DIR . "/includes/shortcodes.php" );

add_action( 'add_meta_boxes', 'lms_quiz_add_meta_boxes' );
function lms_quiz_add_meta_boxes() {
    remove_meta_box( 'slugdiv', 'lms_quiz', 'normal' );
}

add_action( 'wp_enqueue_scripts', 'lms_quiz_enqueue_scripts' );
add_filter( 'rm_lp_keep_scripts', 'lms_quiz_rm_lp_keep_scripts' );
add_filter( 'rm_lp_keep_styles' , 'lms_quiz_rm_lp_keep_styles' );

/**
 *  Callback on `my_rm_lp_keep_scripts`
 *  Keeps scripts on landing pages
 *  @param  $keep array
 *  @return array
 */
function lms_quiz_rm_lp_keep_scripts( $keep ) {
	$keep[] = 'lms-quiz-front-end';

	return $keep;
}

/**
 *  Callback on `my_rm_lp_keep_styles`
 *  Keeps  stylesheets on landing pages
 *  @param  $keep array
 *  @return array
 */
function lms_quiz_rm_lp_keep_styles( $keep ) {
	$keep[] = 'lms-quiz-front-end-style';

	return $keep;
}

function lms_quiz_enqueue_scripts() {
	wp_enqueue_script( 'lms-quiz-front-end', LMS_QUIZ_SYSTEM_URL .'/js/front-end.js', array('jquery') );
	wp_localize_script( 'lms-quiz-front-end', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	wp_enqueue_style( 'lms-quiz-front-end-style', LMS_QUIZ_SYSTEM_URL .'/css/front-end.css' );
}


if ( is_admin() ) {
	require_once( LMS_QUIZ_SYSTEM_DIR . "/classes/menu.class.php" );
	require_once( LMS_QUIZ_SYSTEM_DIR . "/classes/lms-quiz-system.class.php" );
	require_once( LMS_QUIZ_SYSTEM_DIR . "/classes/reports.class.php" );

}