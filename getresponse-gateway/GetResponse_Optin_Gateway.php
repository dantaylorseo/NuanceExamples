<?php
/*
Plugin Name: 	GetResponse Opt-in Gateway
Description: 	Add GetResponse integration to AccessPress and StudioPress Landing Pages
Version: 		0.1
Author: 		Copyblogger Media
Author URI: 	http://newrainmaker.com

*/

//** Used for labs. Remove when production level
add_filter( 'rm_labs_option_page', 'getresponse_rm_labs_option_page' );
function getresponse_rm_labs_option_page( $lab_pages ) {

	$lab_pages[] = 'rm_beta_getresponse';

	return $lab_pages;
}

add_action( 'rm_labs_after_options', 'getresponse_rm_labs_after_options' );
function getresponse_rm_labs_after_options() {

	printf( '<h3 class"title">%s</h3>', __( 'GetResponse (Beta)', '' ) );

	echo '<table class="form-table"><tbody><tr valign="top">';

	printf(
		'<th scope="row"><label for="%2$s">%1$s</label></th><td><input type="checkbox" name="%2$s" id="%2$s" value="1" %3$s/><p class="description">%4$s</p></td>',
		__( 'Enable GetResponse Module' ),
		'rm_beta_getresponse',
		checked( '1', get_option( 'rm_beta_getresponse' ), false ),
		__( 'Enabling this feature will allow you to integrate GetResponse with native Rainmaker features. Examples include: Landing Pages, Product Editor,
		Conversion Assistant, Forms, and Marketing Automation. To use, you must connect your Rainmaker website with your GetResponse account in "Email Settings."' )
	);

	echo '</tr></tbody></table>';

} /* end labs code */


add_action( 'init', 'register_getresponse_gateway' );

//** Used for labs. Remove when production level
if ( ! get_option( 'rm_beta_lab' ) || ! get_option( 'rm_beta_getresponse' ) ) {

	remove_action( 'init', 'register_getresponse_gateway' );

} /* end labs code */

/**
 * Callback function on the `init` hook.
 * registeres the Feedblitz gateway.
 *
 * @access public
 * @return void
 */
function register_getresponse_gateway() {

	Rainmaker_Opt_In_Gateway_Register::add(
		__( 'GetResponse', '' ),
		'Rainmaker_Opt_In_Gateway_GetResponse',
		plugin_dir_path( __FILE__ ) . 'classes/class.Rainmaker_Opt_In_Gateway_GetResponse.php'
	);

}
