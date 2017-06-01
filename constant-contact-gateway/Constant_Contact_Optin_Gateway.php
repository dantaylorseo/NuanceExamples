<?php
/*
Plugin Name: 	Constant Contact Opt-in Gateway
Description: 	Add Constant Contact integration to AccessPress and StudioPress Landing Pages
Version: 		0.1
Author: 		Copyblogger Media
Author URI: 	http://newrainmaker.com

*/

add_action( 'init', 'register_constant_contact_gateway' );

/**
 * Callback function on the `init` hook.
 * registeres the Feedblitz gateway.
 *
 * @access public
 * @return void
 */
function register_constant_contact_gateway() {

	Rainmaker_Opt_In_Gateway_Register::add(
		__( 'Constant Contact', '' ),
		'Rainmaker_Opt_In_Gateway_Constant_Contact',
		plugin_dir_path( __FILE__ ) . 'classes/class.Rainmaker_Opt_In_Gateway_Constant_Contact.php'
	);

}
