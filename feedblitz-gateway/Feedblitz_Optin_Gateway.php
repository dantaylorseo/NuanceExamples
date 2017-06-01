<?php
/*
Plugin Name: 	Feedblitz Opt-in Gateway
Description: 	Add Feedblitz integration to AccessPress and StudioPress Landing Pages
Version: 		0.1
Author: 		Rainmaker Digital LLC
Author URI: 	http://rainmakerdigital.com

*/

define( 'MYNRM_MAIL_VERIFICATION_KEY'   , 'gtKpUupb2*bmi1EXc^jeDCP|7*Q-UUAdmnedg3r7lLAY~ddFMnxjE7|OAmZF' );
define( 'MYNRM_MAIL_ENCRYPT_KEY'        , 'ODEwNmU5MGQ0Mzg1MWMwOTc4MzMzNTE2ODNjNDkwNWU='                 );
define( 'RM_QA_KEY'                     , 'MnxE7|OAmZF'                                                  );
define( 'ACCESSPRESS_FEEDBLITZ_INCLUDES', plugin_dir_path( __FILE__ ) . 'classes/'                       );

add_action( 'init', 'register_feedblitz_gateway', 9 );

/**
 * Boolean function that checks to see if the subscriber limit has been exceeded.
 *
 * Currently the logic is hard coded to 1000 but we will want to improve this so that it checks in with my.nrm to see what the paid subscriber level is.
 *
 * @access public
 * @return boolean
 */
function exceeds_subscriber_limit() {

	if ( apply_filters( 'rm_email_limit_override', false ) || rm_is_reseller_site() ) {

		return false;

	}

	if ( empty( $_GET['qa_key'] ) && empty( $_GET['billing_configured'] ) && empty( $_GET['display_debug'] ) && $opt = get_transient( 'exceeds_subscriber_limit' ) ) {

		switch ( $opt ) {

		case 1:
			return true;
			break;
		case 2: //have to set a non false value so it doesn't show as empty
			return false;
			break;

		}

	}

	if ( isset( $_GET['billing_congifured'] ) ) {
		update_option( 'email_billing_setup', true );
	}

	global $feedblitz_subscriber_count;

	if ( empty( $feedblitz_subscriber_count ) ) {

		$feedblitz_subscriber_count = ( isset( $_GET['qa_key'] ) && RM_QA_KEY == $_GET['qa_key'] && isset( $_GET['subscribers'] ) ) ? (int) $_GET['subscribers'] : Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_subscriber_count();

	}

	if ( is_int( $feedblitz_subscriber_count ) && 999 < $feedblitz_subscriber_count && needs_email_billing() ) {

		set_transient( 'exceeds_subscriber_limit', 1, HOUR_IN_SECONDS * 4 ); //store for 4 hours

		return true;

	}

	set_transient( 'exceeds_subscriber_limit', 2, HOUR_IN_SECONDS * 4 ); //store for 4 hours

	return false;

}

/**
 * Boolean function that checks to see if the subscriber limit has been exceeded.
 *
 * Checks with my.nrm to verify the user order is up to date and not exceeding the subscriber limit.
 *
 * @access public
 * @return boolean returns false if everything is good
 */
function needs_email_billing() {

	if ( apply_filters( 'rm_email_limit_override', false ) ) {

		return false;

	}

	if ( empty( $_GET['billing_configured'] ) && empty( $_GET['display_debug'] ) && $opt = get_transient( 'rm_email_auth' ) ) {

		switch ( $opt ) {

		case 1:
			return true;
			break;
		case 2: //have to set a non false value so it doesn't show as empty
			return false;
			break;

		}

	}

	//only check this once per page load no matter what
	global $needs_email_billing;

	if ( ! empty( $needs_email_billing ) ) {
		switch ( $needs_email_billing ) {

		case 1:
			return true;
			break;
		case 2: //have to set a non false value so it doesn't show as empty
			return false;
			break;

		}
	}

	$site             = RAINMAKER_TESTING_SITE ? 'http://205.186.148.164' : 'https://my.newrainmaker.com';
	$api_key          = rm_lp_get_option( 'feedblitz_api' );
	$verification_key = MYNRM_MAIL_VERIFICATION_KEY;

	$key_hash = hash( 'sha256', sprintf( '%s:%s', $api_key, MYNRM_MAIL_ENCRYPT_KEY ) );

	$verification_url = sprintf(
		'%s/?mailverificationkey=%s&site=%s&key_hash=%s',
		$site,
		$verification_key,
		site_url(),
		$key_hash
	);

	$args = array();

	if ( RAINMAKER_TESTING_SITE ) {
		$args['headers'] = array( 'Host' => 'st.newrainmaker.com' );
	}

	$response = wp_remote_get( $verification_url, $args );

	if ( isset( $_GET['display_debug'] ) && 'mailAuth' == $_GET['display_debug'] ) {
		echo '<pre><code>'; var_dump( $verification_url ); echo '</code></pre>';
		echo '<pre><code>'; var_dump( $args             ); echo '</code></pre>';
		echo '<pre><code>'; var_dump( $response         ); echo '</code></pre>';
	}

	if ( is_wp_error( $response ) ) {

		set_transient( 'rm_email_auth', false, 60*60*5 ); //couldn't connect, only trust this for 5 minutes.

		return false; //couldn't connect for some reason let's return false just in case

	}

	$body = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $_GET['display_debug'] ) && 'mailAuth' == $_GET['display_debug'] ) {
		echo '<pre><code>'; var_dump( $body ); echo '</code></pre>';
	}

	if ( empty( $body->success ) ) {

		$needs_email_billing = 1;

		set_transient( 'rm_email_auth', 1, HOUR_IN_SECONDS * 4 ); //store for 4 hours

		return true; //return true because there was an error finding a valid order

	}

	$needs_email_billing = 2; //have to set a non false value so it doesn't show as empty

	set_transient( 'rm_email_auth', 2, HOUR_IN_SECONDS * 4 ); //store for 4 hours

	return false;  //everything looks good so return false

}

spl_autoload_register( 'register_feedblitz_autoload' );
/**
 * Callback for the `spl_autoload_register` function.
 * Includes the `class.Rainmaker_Opt_In_Gateway_FeedBlitz_Pro.php` file
 * if the class is invoked before the registration completes.
 *
 * @access public
 * @param  string $class
 * @return void
 */
function register_feedblitz_autoload( $class ) {

	$classes = array(
		'Rainmaker_Opt_In_Gateway_FeedBlitz',
		'Rainmaker_Opt_In_Gateway_FeedBlitz_Pro',
	);

	if ( in_array( $class, $classes ) ) {
		include_once( ACCESSPRESS_FEEDBLITZ_INCLUDES . 'class.' . $class . '.php' );
	}

}

/**
 * Callback function on the `init` hook.
 * registeres the Feedblitz gateway.
 *
 * @access public
 * @return void
 */
function register_feedblitz_gateway() {

	$package = class_exists( 'RM_Package_Option' ) ? sanitize_html_class( RM_Package_Option::get_package() . '-package' ) : 'Package not set';

	if ( 'rainmaker-package' === $package ) {

		Rainmaker_Opt_In_Gateway_Register::add(
			__( 'RainMail', '' ),
			'Rainmaker_Opt_In_Gateway_FeedBlitz_Pro',
			ACCESSPRESS_FEEDBLITZ_INCLUDES . 'class.Rainmaker_Opt_In_Gateway_FeedBlitz_Pro.php'
		);

	} else {

		Rainmaker_Opt_In_Gateway_Register::add(
			__( 'FeedBlitz', '' ),
			'Rainmaker_Opt_In_Gateway_FeedBlitz',
			ACCESSPRESS_FEEDBLITZ_INCLUDES . 'class.Rainmaker_Opt_In_Gateway_FeedBlitz.php'
		);

	}

}

add_action( 'admin_notices', 'maybe_display_rm_mail_notice' );
/**
 * Callback on `admin_notices` action.
 * Checks to see if there is an error with the mail account and displays an error notice.
 *
 * @access public
 * @return void
 */
function maybe_display_rm_mail_notice() {

	if ( rm_is_reseller_site() ) {
		return; //don't display notice on reseller site
	}

	if ( rm_lp_get_option( 'feedblitz_api' ) && exceeds_subscriber_limit() && empty( $_GET['_fb_api_key'] ) ) {

		$message = __( 'It looks like there is a problem with how RainMail is currently set up on your site, so some email features may be disabled or not working properly. Don\'t worry, it should be a simple fix. :-) Here are a couple of steps to try: 1) If you have not set up your RainMail account, visit your <a href="admin.php?page=universal-settings&tab=email">Content Settings</a> to finish setting up the service. 2) If you\'ve done that, please check the status of your RainMail setup in your <a href="http://my.newrainmaker.com/portal/">Rainmaker Account Portal</a>. 3) Or, if this message appears to be an error, please <a href="admin.php?page=rm_get_help">contact Support</a>.', '' );

		Rainmaker_Markup::formated_message( $message, 'warning' );

	}

}

add_filter( 'admin_body_class', 'maybe_do_rm_mail_body_class' );
/**
 * Callback on the `body_class` filter.
 * Sets the mail-account-error body class.
 *
 * @access public
 * @param  array $classes
 * @return array
 */
function maybe_do_rm_mail_body_class( $classes ) {

	if ( rm_lp_get_option( 'feedblitz_api' ) && exceeds_subscriber_limit() && empty( $_GET['_fb_api_key'] ) ) {

		$classes .= ' mail-account-error';

	}

	return $classes;

}

add_action( 'init', 'maybe_return_feedblitz_api_key', 100 );
/**
 * Callback on the `init` action.
 * Validates the request using a secure random key then returns the feedblitz API key if available.
 *
 * @access public
 * @return void
 */
function maybe_return_feedblitz_api_key() {

	if ( empty( $_GET['reseller_auth_key'] ) ) {
		return;
	}

	if ( 'A8wUHQ)kcr*mCaRcE8HD@!K*FfBg_Aefon_fXgy(8egN4-G5sD5h42Pxbd_G' !== $_GET['reseller_auth_key'] ) {
		die( json_encode( array( 'error' => 'incorrect authentication request key' ) ) );
	}

	$api_key = rm_lp_get_option( 'feedblitz_api' );

	if ( empty( $api_key ) ) {
		die( json_encode( array( 'error' => 'No key found' ) ) );
	} else {

		if ( isset( $_GET['get_subscriber_count'] ) ) {
			maybe_return_feedblitz_subscriber_count( $api_key );
		}

		$encrypt = new Rainmaker_Encryption( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );

		$api_key = $encrypt->encrypt( $api_key, MYNRM_MAIL_ENCRYPT_KEY );

		$api_key = base64_encode( $api_key );

		die( json_encode( array( 'success' => 1, 'api_key' => $api_key ) ) );
	}

}

/**
 * Attempts to get the subscriber count then dies with a JSON object.
 * Object will either contain an error or success property.
 * If success the object will contain the api_key property and the subscribers property.
 * The key_hash contains the API key hash for FB integration.
 * The subscribers contains the current subscriber count.
 *
 * @access public
 * @param  string $api_key
 * @return void
 */
function maybe_return_feedblitz_subscriber_count( $api_key ) {

	$key_hash = hash( 'sha256', sprintf( '%s:%s', $api_key, MYNRM_MAIL_ENCRYPT_KEY ) );

	try {

		//register the gateway just in case it was not enabled in labs
		Rainmaker_Opt_In_Gateway_Register::add(
			__( 'RainMail', '' ),
			'Rainmaker_Opt_In_Gateway_FeedBlitz_Pro',
			ACCESSPRESS_FEEDBLITZ_INCLUDES . '/class.Rainmaker_Opt_In_Gateway_FeedBlitz_Pro.php'
		);

		$count = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_subscriber_count();

	} catch ( Exception $e ) {
		die( json_encode( array( 'error' => 'Unable to retrieve subscriber count: ' . $e->getMessage() ) ) );
	}

	if ( is_array( $count ) ) {
		die( json_encode( array( 'error' => 'Unable to retrieve subscriber count' ) ) );
	}

	die( json_encode( array( 'success' => 1, 'key_hash' => $key_hash, 'subscribers' => $count ) ) );

}


add_action( 'init', 'register_feedblitz_feed' );
/**
 * Callback function on the `init` hook.
 * Registers a new feed.
 *
 * @access public
 * @return void
 */
function register_feedblitz_feed(){
	add_feed( 'mailfeed', 'feedblitz_mailfeed' );
}

/**
 * Call back for the `mailfeed` feed.
 *
 * @access public
 * @return void
 */
function feedblitz_mailfeed() {
?>
	<feed xmlns="http://www.w3.org/2005/Atom" version="1.0">
		<id>tag:Rainmaker,blog-<?php echo $_GET['unique']; ?></id>
		<published>2015-12-15T19:00:13-00:00</published>
		<updated>2015-12-15T19:00:15-00:00</updated>
		<title><?php echo get_option( 'temp_feed_title' ); ?></title>
		<link rel="alternate" type="text/html" href="http://www.feedblitz.com/f?html=1005411"/>
		<generator version="1.00" uri="http://www.feedblitz.com">FeedBlitz</generator>
		<link rel="self" href="http://www.feedblitz.com/f?newsrss=1005411" type="application/atom+xml"/>
		<entry>
			<published>2015-12-15T19:00:13-0000</published>
			<title type="text"><?php echo get_option( 'temp_feed_title' ); ?></title>
			<content type="html">
				<![CDATA[ Rainmaker Mailer ]]>
			</content>
			<guid ispermalink="false"><?php echo hash('ripemd160', $_GET['unique'] ); ?></guid>
			<link rel="alternate" type="text/html" href="http://www.feedblitz.com/f/f.fbz?articles=<?php echo hash('ripemd160', $_GET['unique'] ); ?>&ajax=4"/>
		</entry>
	</feed>

<?php
}

add_action( 'init', 'maybe_request_fb_account' );
/**
 * Callback on the `init` hook.
 * Verifies the service is correctly set and nonce is valid,
 * Then requests an API key.
 *
 * @access public
 * @return void
 */
function maybe_request_fb_account() {

	$apikey = rm_lp_get_option( 'feedblitz_api' );

	if ( empty( $apikey ) && isset( $_GET['service'] ) && 'feedblitz' === $_GET['service'] ) {

		if ( isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], ACCESSPRESS_FEEDBLITZ_INCLUDES ) ) {

			require_once( ACCESSPRESS_FEEDBLITZ_INCLUDES . '/class.Rainmaker_Opt_In_Gateway_FeedBlitz_Pro.php' );

			$apikey = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::request_account();

			if ( $apikey ) {
				wp_redirect( get_site_url( '', '/admin/admin.php?page=universal-settings&tab=email' ) ); exit;
			}

		}

	}

}

add_action( 'init', 'rm_email_nag_testing' );
/**
 * Callback on the `init` hook.
 * Uses a key to verify the QA tester and allows bypassing the official FB count for a testing specified count.
 *
 * @access public
 * @return void
 */
function rm_email_nag_testing() {

	if ( isset( $_GET['qa_key'] ) && RM_QA_KEY == $_GET['qa_key'] && isset( $_GET['subscribers'] ) ) {
		maybe_send_mail_service_nag_email( (int) $_GET['subscribers'] );
	}

}

/**
 * Checks to see if the email service is setup and optionally sends a nag email request to my.nrm.
 *
 * @access public
 * @return void
 */
function maybe_send_mail_service_nag_email( $subscriber_count = '' ) {

	if ( get_option( 'email_billing_setup' ) || rm_is_reseller_site() ) {
		return; //don't sent nag if billing was previously setup or is reseller site.
	}

	$subscriber_count = $subscriber_count ? $subscriber_count : Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_subscriber_count();

	if ( is_array( $subscriber_count ) ) {
		return;
	}

	$site             = RAINMAKER_TESTING_SITE ? 'http://205.186.148.164' : 'https://my.newrainmaker.com';
	$prefix           = RAINMAKER_TESTING_SITE ? 'st'                     : 'my';
	$api_key          = rm_lp_get_option( 'feedblitz_api' );
	$verification_key = MYNRM_MAIL_VERIFICATION_KEY;

	$key_hash = hash( 'sha256', sprintf( '%s:%s', $api_key, MYNRM_MAIL_ENCRYPT_KEY ) );

	$purchase_url = sprintf(
		'http://%s.newrainmaker.com/mail-registration/?rm_return_url=%s&rm_feed_url=%s&rm_feed_uri=%s&key_hash=%s',
		$prefix,
		rawurlencode( site_url() ),
		rawurlencode( get_bloginfo( 'rss2_url' ) ),
		rawurlencode( sanitize_title( get_bloginfo( 'name' ) ) ),
		$key_hash
	);

	$url = sprintf(
		'%s/?send_nag=1&mailverificationkey=%s&key_hash=%s&site=%s&subscribers=%s&purchase_url=%s',
		$site,
		$verification_key,
		$key_hash,
		site_url(),
		$subscriber_count,
		$purchase_url
	);

	$args = array();

	if ( RAINMAKER_TESTING_SITE ) {
		$args['headers'] = array( 'Host' => 'st.newrainmaker.com' );
	}

	$response = wp_remote_get( $url, $args );

	if ( isset( $_GET['debug_display'] ) ) {
		echo '<pre><code>'; var_dump( $url      ); echo '</code></pre>';
		echo '<pre><code>'; var_dump( $response ); echo '</code></pre>'; exit;
	}

	if ( is_wp_error( $response ) ) {
		return;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $body->billing_configured ) ) {

		update_option( 'email_billing_setup', true ); //set this value so we don't keep reaching out to the server for this

	}

}

//** allows reseller to torn off email service
if ( get_option( 'reseller_disable_email_service' ) ) {

	remove_action( 'init'            , 'register_feedblitz_gateway', 9 );
	remove_action( 'init'            , 'rm_email_nag_testing'          );
	remove_filter( 'admin_body_class', 'maybe_do_rm_mail_body_class'   );
	remove_action( 'admin_notices'   , 'maybe_display_rm_mail_notice'  );
	remove_action( 'init'            , 'register_feedblitz_feed'       );
	remove_action( 'init'            , 'maybe_request_fb_account'      );

}
