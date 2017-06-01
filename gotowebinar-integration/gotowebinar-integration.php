<?php
/*
Plugin Name: Gotowebinar Integration
Plugin URI: http://newrainmaker.com/
Description: Integrate Gotowebinar into Rainmaker
Version: 1.0.1
Author: Rainmaker Digital LLC
Author URI: http://www.rainmakerdigital.com
*/

//** Used for labs. Remove when production level
add_filter( 'rm_labs_option_page', 'gtw_rm_labs_option_page' );
function gtw_rm_labs_option_page( $lab_pages ) {

    $lab_pages[] = 'rm_beta_gtw';

    return $lab_pages;
}

add_action( 'rm_labs_after_options', 'gtw_rm_labs_after_options' );
function gtw_rm_labs_after_options() {

    printf( '<h3 class"title">%s</h3>', __( 'GoToWebinar (Beta)', '' ) );

    echo '<table class="form-table"><tbody><tr valign="top">';

    printf(
        '<th scope="row"><label for="%2$s">%1$s</label></th><td><input type="checkbox" name="%2$s" id="%2$s" value="1" %3$s/><p class="description">%4$s</p></td>',
        __( 'Enable GoToWebinar Module' ),
        'rm_beta_gtw',
        checked( '1', get_option( 'rm_beta_gtw' ), false ),
        __( 'Want to get more registrations for your GoToWebinar events? Enabling this feature will allow you to integrate GoToWebinar registration forms with native Rainmaker features like Landing Pages, Product Editor, Conversion Assistant, Forms, and Marketing Automation. To use, activate this Labs feature and then connect your Rainmaker site to your GoToWebinar account in Settings > Content.' )
    );

    echo '</tr></tbody></table>';

} /* end labs code */

if ( ! get_option( 'rm_beta_lab' ) || ! get_option( 'rm_beta_gtw' ) ) {
    return;
}

define("GTW_SYSTEM_DIR" , plugin_dir_path( __FILE__ ) );
define("GTW_URL"        , plugins_url( '', __FILE__ ) );

require_once( GTW_SYSTEM_DIR . "includes/classes/class.Gotowebinar_Integration.php" );

add_action( 'wp_ajax_gtw_get_webinars'   , array( 'Gotowebinar_Integration_Admin', 'ajax_get_webinars' ) );

if ( is_admin() ) {

    add_action( 'admin_enqueue_scripts' , array( 'Gotowebinar_Integration_Admin', 'admin_enqueue_scripts' ) );
    add_action( 'media_buttons'         , array( 'Gotowebinar_Integration_Admin', 'add_my_media_button' ) );
    add_action( 'admin_footer'          , array( 'Gotowebinar_Integration_Admin', 'footer_modal'));

    require_once( GTW_SYSTEM_DIR . "includes/classes/class.Gotowebinar_Integration_Admin.php" );
}