<?php

if ( ! class_exists( 'Rainmaker_Opt_In_Gateway_FeedBlitz' ) ) {
	require_once( ACCESSPRESS_FEEDBLITZ_INCLUDES . '/class.Rainmaker_Opt_In_Gateway_FeedBlitz.php' );
}

final class Rainmaker_Opt_In_Gateway_FeedBlitz_Pro extends Rainmaker_Opt_In_Gateway_FeedBlitz {

	/**
	 * Stores the API Key
	 *
	 * @var    string
	 * @access protected
	 */
	protected $_api_key;

	/**
	 * Stores the OEM Key
	 *
	 * @var    string
	 * @access protected
	 */
	protected $_oem_key = 'ODEwNmU5MGQ0Mzg1MWMwOTc4MzMzNTE2ODNjNDkwNWU=';

	/**
	 * The constructor should be extended.
	 * When extended it must set the $_api_uri_base property.
	 *
	 * @access protected
	 * @return void
	 */
	public function __construct() {

		$this->_api_uri_base = 'https://app.feedblitz.com/';

		if ( preg_match( '#(?:cbmdev0(1|2))\.rmkr\.net#', basename( ABSPATH ) ) ) {
			$this->_api_uri_base = 'https://dev.feedblitz.com/';
		}

	}

	/**
	 * Outputs the settings.
	 * We will be using the Rainmaker Landing Pages settings to handle this.
	 *
	 * @uses static::_setting_fields() to get the list of options to build
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function settings() {

		$apikey = rm_lp_get_option( 'feedblitz_api' );

		if ( isset( $_GET['_fb_api_key'] ) ) {

			//if ( isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], ACCESSPRESS_FEEDBLITZ_INCLUDES ) ) {

			$apikey = static::get_instance()->_request_account();

			//}

		}

		$prefix = RAINMAKER_TESTING_SITE ? 'st'   : 'my';
		$https  = RAINMAKER_TESTING_SITE ? 'http' : 'https';

		if ( empty( $apikey ) ) {

			$link = sprintf(
				'<a href="%s" class="button button-primary button-feedblitz-email"><span class="dashicons dashicons-email" style="margin: 6px 6px 0 0;"></span>%s</a>',
				add_query_arg(
					array(
						'nonce'   => wp_create_nonce( ACCESSPRESS_FEEDBLITZ_INCLUDES ),
						'service' => 'feedblitz',
					),
					get_site_url()
				),
				__( 'Set Up RainMail', '' )
			);

			printf(
				'<table class="form-table">
					<tbody>
						<tr>
							<th>%s</th>
							<td>%s</td>
						</tr>
					</tbody>
				</table>',
				__( 'Activate RainMail', '' ),
				$link
			);

		} elseif ( rm_is_reseller_site() ) { //for reseller sites

			$disconnect = sprintf( '<a class="deactivate-feedblitz" href="#feedblitz_api_key">%s</a>', __( 'Remove RainMail Authentication', '' ) );

			printf(
				'<table class="form-table">
					<tbody>
						<tr>
							<th>%s</th><td><input type="hidden" id="feedblitz_api_key" name="%s[feedblitz_api]" value="%s" />%s</td>
						</tr>',
				__( 'RainMail is Configured', '' ),
				RAINMAKER_LP_SETTINGS_FIELD,
				$apikey,
				$disconnect
			);

			static::get_instance()->list_editor();

			echo '
					</tbody>
				</table>';
		} else {

			if ( needs_email_billing() ) {
				$page  = 'mail-registration';
				$class = 'primary';
				$copy  = __( 'Complete RainMail Billing', '' );
			} else {
				$page  = 'portal';
				$class = 'secondary';
				$copy  = __( 'Manage RainMail Billing', '' );
			}

			$key_hash = hash( 'sha256', sprintf( '%s:%s', $apikey, MYNRM_MAIL_ENCRYPT_KEY ) );

			$link = sprintf(
				'<a href="%s://%s.newrainmaker.com/%s/?rm_return_url=%s&rm_feed_url=%s&rm_feed_uri=%s&key_hash=%s" class="button button-%s button-feedblitz-email"><span class="dashicons dashicons-email" style="margin: 6px 6px 0 0;"></span>%s</a>',
				$https,
				$prefix,
				$page,
				rawurlencode( site_url() ),
				rawurlencode( get_bloginfo( 'rss2_url' ) ),
				rawurlencode( sanitize_title( get_bloginfo( 'name' ) ) ),
				$key_hash,
				$class,
				$copy
			);

			$disconnect = sprintf( '<a class="deactivate-feedblitz" href="#feedblitz_api_key">%s</a>', __( 'Remove RainMail Authentication', '' ) );

			printf(
				'<table class="form-table">
					<tbody>
						<tr>
							<th>%s</th><td>%s<input type="hidden" id="feedblitz_api_key" name="%s[feedblitz_api]" value="%s" /> %s</td>
						</tr>',
				__( 'RainMail is Configured', '' ),
				$link,
				RAINMAKER_LP_SETTINGS_FIELD,
				$apikey,
				$disconnect
			);

			static::get_instance()->list_editor();

			echo '
					</tbody>
				</table>';

		}

?>
			<script type="text/javascript">
				(function($) {
					$( '.deactivate-feedblitz' ).click(function(){

						var deleteButton = $(this),
							Input        = $(this).attr('href'),
							confirmTipped;

						confirmTipped = Tipped.create( deleteButton, '<p class="tool-tip-title"><?php echo rm_is_reseller_site() ? __( 'Are you sure? If so, click "Yes" to continue, then save the settings to confirm your decision. This will remove RainMail from your site.', '' ) : __( 'Are you sure? If so, click "Yes" to continue, then save the settings to confirm your decision. This will remove RainMail from your site. (If you wish to cancel any associated billing you have set up, please log in to your <a href="http://my.newrainmaker.com/portal/">Rainmaker Account Portal</a>.)', '' ); ?></p><a href="#yes" class="button confirm-delete confirm-delete-positive deactivate-feedblitz"><?php _e( 'Yes', '' ); ?></a> <a href="#no" class="button confirm-delete confirm-delete-negative"><?php _e( 'No', '' ); ?></a>', {
							skin: 'light',
							size: 'small',
							close: true,
							hideOn: false,
			//				title: confirmTitle,
							hideOnClickOutside: true,
							hideOthers: true,
							showOn: false,
						});

						setTimeout(function(){
							confirmTipped.show();
						}, 100 );

						$('body').on('click', '.confirm-delete', function(){

							confirmTipped.hide();

							if ( $(this).hasClass('deactivate-feedblitz') ) {

								$( Input ).attr('value', '');

								if ( $('.button-feedblitz-email').length > 0 ) {
									$('.button-feedblitz-email').removeClass('button-secondary').addClass('button-primary').text('<?php _e( 'Set Up RainMail', '' ); ?>');
								} else {
									$( '<p><?php _e( "RainMail Authentication Removed. Save settings to confirm.", "" ); ?></p>' ).insertBefore( deleteButton );
								}

								deleteButton.hide();

							}

							return false;

						});

						return false;
					});
				})(jQuery);
			</script>
			<?php

	}

	/**
	 * Returns the formatted postal address
	 *
	 * @return String  HTML to output
	 */
	public function _rainmail_ajax_get_address() {

		$this->_get_http_instance()->set_method( 'GET' );

		$api_url = $this->_get_api_url( 'f.api/profile?key={apikey}' );

		$output = '';

		$response = $this->_get_response( $api_url, 'xml' );
		if ( empty( $reponse['error'] ) ) {

			if ( ! preg_match( '#Email subscription powered by the Rainmaker Platform#iUs', $response['profile']['data']['footer'], $matches ) ) {
				if ( preg_match( '#<span id="custom_footer">([^<]*?)</span>#iUs', $response['profile']['data']['footer'], $matches ) ) {
					$tagline = $matches[1];
				}
			}

			$response = $response['profile']['contact'];

			$output .= ( ! empty( $response['Company'] ) ? '<strong>'.$response['Company'].'</strong><br>' : '' );
			$output .= ( ! empty( $response['Name'] ) ? $response['Name'].'<br>' : '' );
			$output .= ( ! empty( $response['Street1'] ) ? $response['Street1'].'<br>' : '' );
			$output .= ( ! empty( $response['Street2'] ) ? $response['Street2'].'<br>' : '' );
			$output .= ( ! empty( $response['City'] ) ? $response['City'].', ' : '' );
			$output .= ( ! empty( $response['State'] ) ? $response['State'].' ' : '' );
			$output .= ( ! empty( $response['ZipCode'] ) ? $response['ZipCode'].'<br>' : '' );
			$output .= ( ! empty( $response['Country'] ) ? $response['Country'].'<br><br>' : '' );
			$output .= ( ! empty( $response['Email'] ) ? $response['Email'].'<br>' : '' );
			$output .= ( ! empty( $response['Phone'] ) ? $response['Phone'].'<br><br>' : '' );
			$output .= ( ! empty( $tagline ) ? '<i>'.$tagline.'</i><br>' : '<i>Email subscription powered by the Rainmaker Platform</i>' );

			return $output;
		}

		return 'There was an error retrieving the address. <a href="#" class="rainmail_reload_address">Retry?</a>';

	}

	/**
	 * Retrieves the postal address
	 *
	 * @return String  JSON encoded string
	 */
	public function _get_rainmail_address() {

		$this->_get_http_instance()->set_method( 'GET' );

		$api_url = $this->_get_api_url( 'f.api/profile?key={apikey}' );

		$response = $this->_get_response( $api_url, 'xml' );
		$response['tagline'] = "";
		if ( ! preg_match( '#Email subscription powered by the Rainmaker Platform#iUs', $response['profile']['data']['footer'], $matches ) ) {
			if ( preg_match( '#<span id="custom_footer">([^<]*?)</span>#iUs', $response['profile']['data']['footer'], $matches ) ) {
				$response['tagline'] = $matches[1];
			}
		}

		return json_encode( $response );
	}
	/**
	 * Returns JSON formatted data for the lists opt in template.
	 *
	 * @param  Int     $id The ID of the list to be updates
	 * @return JSON    An json string of the response
	 */
	public function _get_optin_email_template( $id ) {

		$output = array();

		$args = array(
			'list_id' => $id,
		);
		$api_url = $this->_get_api_url( 'f.api/customreg/{list_id}?key={apikey}', $args );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( ! empty ( $response['customreg'] ) ) {

			$subject = ! is_array( $response['customreg']['title'] ) ? $response['customreg']['title'] : '';
			$message = str_replace( '&lt;$BlogRecipient$&gt;', '[recipient_email]', $response['customreg']['message'] );

			$match = array(
				'/<a[^<"]*href="[^"]*(BlogSubLink)[^"]*"[^<]*>([^<]*)<\/a>/i',
				'/(?:<\$\s?if\s?firstname\s?!=\s?""\s?\$>)?<\$\s?firstname\s?\$>(?:<\$\s?else\s?\$>\s?([^<\$]+))?(?:<\$\s?endif\s?\$>)?/',
				'/(?:<\$\s?if\s?lastname\s?!=\s?""\s?\$>)?<\$\s?lastname\s?\$>(?:<\$\s?else\s?\$>\s?([^<\$]+))?(?:<\$\s?endif\s?\$>)?/',
				'/(?:<\$\s?if\s?_subscriber_hash\s?!=\s?""\s?\$>)?<\$\s?_subscriber_hash\s?\$>(?:<\$\s?else\s?\$>\s?([^<\$]+))?(?:<\$\s?endif\s?\$>)?/',
				'/(?:<\$\s?if\s?([^!]+)!=\s?""\s?\$>)?<\$\s?([^\$]+)\s?\$>(?:<\$\s?else\s?\$>\s?([^<\$]+))?(?:<\$\s?endif\s?\$>)?/',
			);

			$replace = array(
				'[confirm_link text="$2"]',
				'[firstname default="$1"]',
				'[lastname default="$1"]',
				'[unique_id default="$1"]',
				'[custom_field tag="$1" default="$3"]',
			);

			$message = preg_replace( $match, $replace, $message );
			$subject = preg_replace( $match, $replace, $subject );

			$output = array(
				'status'  => 'ok',
				'title'   => ( !is_array( $subject ) ? stripslashes( $subject ) : '' ),
				'message' => ( !is_array( $message ) ? stripslashes( $message ) : '' ),
			);
		} else {
			$output = array(
				'status' => 'error'
			);
		}

		return json_encode( $output );


	}

	/**
	 * Handles preparing the unsubscribe data from accesspress.
	 * If extended this can either process the unsubscribe directly or return the email for processing.
	 * If the unsubscribe is directly processed the subscriber_id needs to be returned in the array.
	 * If there is an error this must return an array( 'error' => 'Error Description' ).
	 *
	 * return value like:
	 * array(
	 *  'email'         => 'example@domain.com',
	 *  'subscriber_id' => '1234', //optional. Provide only if subscription processed directly
	 * )
	 *
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  array  $component_details
	 * @return mixed false by default unless the final class replaces this method
	 */
	protected function _accesspress_unsubscribe_handler( $list_id, $email, $component_details ) {

		if ( isset( $component_details['product_id'] ) ) {

			$product = get_post( $component_details['product_id'] );

			if ( is_object( $product ) && ! is_wp_error( $product ) && isset( $product->post_name ) ) {
				$product_slug = rawurlencode( sprintf( '%s-%s', basename( ABSPATH ), $product->post_name ) );

				$this->_tag_subscriber( $email, array( $product_slug => 0 ) );
			}

		}

		return false;

	}

	/**
	 * Updates the postal address
	 *
	 * @param  Array   $data  the $_POST array
	 * @return String  JSON encoded string
	 */
	public function _save_rainmail_address( $data ) {

		$api_url = $this->_get_api_url( 'f.api/profile?key={apikey}' );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		//print_r( $data );

		$tag = ( ! empty( $data['tag'] ) ? $data['tag'] : __( 'Email subscription powered by the Rainmaker Platform', '' ) );

		$footer = htmlentities( sprintf( '
			<!--<![endif]-->
			<hr style=\'clear:both;\'>
			<div align=\'center\' style=\'background-color:white;color:black;padding-bottom:0.7em\'>
				<br>
				<a href="<$BlogUnsubLink$>">%s</a>
				<br>
				<span id="custom_footer">%s</span>
				<br>
				<$BlogPublisherCompany$> | <$BlogPublisherStreet1$> | <$BlogPublisherCity$>, <$BlogPublisherState$> <$BlogPublisherZip$>
				<br>
			</div>',
				__( 'Safely unsubscribe from <$BlogTitle$>', '' ),
				$tag
			) );

		$body = sprintf('<?xml version="1.0" encoding="utf-8"?>
						<feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0">
							<profile>
								<status>ok</status>
								<contact>
									<Company>%s</Company>
									<Name>%s</Name>
									<Street1>%s</Street1>
									<Street2>%s</Street2>
									<ZipCode>%s</ZipCode>
									<City>%s</City>
									<Country>%s</Country>
									<State>%s</State>
									<Email>%s</Email>
									<Phone>%s</Phone>
								</contact>
								<data>
									<footer>%s</footer>
									<pra>rainmaker@mail.feedblitz.com</pra>
									<cname>email.rainmakerplatform.com:8443</cname>
								</data>
							</profile>
						</feedblitzapi>',
			$data['company'],
			$data['name'],
			$data['street'],
			$data['street2'],
			$data['zip'],
			$data['city'],
			$data['country'],
			$data['state'],
			$data['email'],
			$data['tel'],
			$footer
		);

		//echo $body;

		$this->_get_http_instance()->set_headers( $this->_http_header );
		$this->_get_http_instance()->set_body( $body, '' );
		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 120 );
		$this->_get_http_instance()->send_request( $api_url );
		$this->_log_request( $api_url, 'xml', $body );

		return json_encode( $this->_get_http_instance()->get_response_body( 'xml' ) );
	}

	/**
	 * Updates the lists confirmation email
	 *
	 * @param  Int     $id The ID of the list to be updates
	 * @return JSON    An json string of the response
	 */
	static function update_optin_email_template( $data ) {
		return static::get_instance()->_update_optin_email_template( $data );
	}

	/**
	 * Updates the lists confirmation email
	 *
	 * @param  Int     $id The ID of the list to be updates
	 * @return JSON    An json string of the response
	 */
	public function _update_optin_email_template( $data ) {

		$subject = ! empty ( $data['subject'] ) ? $data['subject'] : '';
		$message = ! empty ( $data['message'] ) ? $data['message'] : '';

		$message = stripslashes( $message );
		$subject = stripslashes( $subject );

		$message = preg_replace('/\[confirm_link text="([^"]*)"\]/i', '<a href="&lt;$BlogSubLink$&gt;">$1</a>', $message );
		$message = str_replace( '[recipient_email]', '&lt;$BlogRecipient$&gt;', $message );

		Rainmaker_Mail_Build_Content::get_instance()->unset_shortcodes();

		Rainmaker_Mail_Build_Content::get_instance()->add_shortcodes();

		$message = do_shortcode( $message );
		$subject = do_shortcode( $subject );

		$message = wpautop( $message );

		Rainmaker_Mail_Build_Content::get_instance()->reset_shortcodes();

		$args = array(
			'list_id' => $data['id'],
		);

		$api_url = $this->_get_api_url( 'f.api/customreg/{list_id}?key={apikey}', $args );

		if ( empty ( $subject ) && empty ( $message ) ) {
			$this->_get_http_instance()->set_method( 'DELETE' );
			$this->_get_http_instance()->set_timeout( 120 );
			$response = $this->_get_response( $api_url, 'xml' );
			return json_encode( $response );
		}

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'customreg' => array(
				'title'   => $subject,
				'message' => $message,
			)
		);

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 120 );
		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $reponse['error'] ) ) {

		}

		return json_encode( $response );

		die();

	}

	/**
	 * Updates the lists template by editing the current OEM template
	 *
	 * @uses   get_oem_template()
	 * @param  Int     $id The ID of the list to be updates
	 * @return Array   An array of the response
	 */
	public function _update_list_template( $id ) {

		$name       = accesspress_get_option( 'email_receipt_name' );
		$name       = empty( $name ) ? get_bloginfo( 'name' ) : $name;

		$from_email = accesspress_get_option( 'email_receipt_address' );
		$from_email = empty( $from_email ) ? sprintf( 'support@%s', preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] ) ) : $from_email;

		$logo    = rm_lp_get_option( 'email_template_logo' );

		$baseTemplate = $this->get_oem_template();

		if ( $logo = rm_lp_get_option( 'email_template_logo' ) ) {

			$endTemplate = str_replace(
				'<a href="<$BlogURL$>"><img border="0" align="center" src="http://rainmakerplatform.com/wp-content/themes/rm-platform/images/logo.png" style="height:auto !important;max-width:100% !important;"/></a>',
				'<a href="'.get_home_url().'"><img border="0" align="center" src="'.$logo.'" style="height:auto !important;max-width:100% !important;"/></a>',
				$baseTemplate
			);

		} else {

			$endTemplate = str_replace(
				'<a href="<$BlogURL$>"><img border="0" align="center" src="http://rainmakerplatform.com/wp-content/themes/rm-platform/images/logo.png" style="height:auto !important;max-width:100% !important;"/></a>',
				'<h1><a href="'.get_home_url().'">'.get_bloginfo( 'name' ).'</a></h1>',
				$baseTemplate
			);

		}

		$args = array(
			'list_id' => $id,
		);

		$api_url = $this->_get_api_url( 'f.api/template/{list_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'template' => array(
				'fromname'       => $name,
				'fromaddress'    => $from_email,
				'html'           => $endTemplate,
				'active'         => 1,
			)
		);

		$this->_get_http_instance()->set_method( 'PUT' );
		$this->_get_http_instance()->set_timeout( 120 );

		return $response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		die();

	}

	/**
	 * Gets the OEM template
	 *
	 * @return String The HTML of the OEM tamplate.
	 */
	private function get_oem_template() {

		$api_url = $this->_get_api_url( 'f.api/template/1010306?key={oemkey}' );

		$this->_get_http_instance()->set_method( 'GET' );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $reponse['error'] ) ) {
			return $response['template']['html'];
		}

	}

	/**
	 * Gets the OEM Opt In Email template
	 *
	 * @return String The HTML of the OEM tamplate.
	 */
	private function get_oem_optin_email_template() {
		$api_url = $this->_get_api_url( 'f.api/customreg/1010306?key={oemkey}' );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $reponse['error'] ) ) {
			return $response;
		}
	}

	/**
	 * Public function to initiate account request.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function request_account() {

		$apikey = static::get_instance()->_request_account();

		return $apikey;
	}

	/**
	 * Create Feedblitz Account.
	 *
	 * @access private
	 * @return string
	 */
	private function _request_account() {

		$user = $this->_get_user();

		if ( ! $user ) {
			return;
		}

		$args = array(
			'email' => rawurlencode( $user->user_email ),
			'url'   => defined( 'RAINMAKER_LOCAL_SITE' ) && RAINMAKER_LOCAL_SITE ? 'http://www.copyblogger.com/feed' : get_bloginfo( 'rss2_url' ),
			'uri'   => sanitize_title( get_bloginfo( 'name' ) ),
			'name'  => sanitize_title( $user->user_firstname  ),
		);

		$api_url = $this->_get_api_url( 'f?OemAddSite&key={oemkey}&email={email}&url={url}&uri={uri}&username={name}&oemprogram=Newsletter%20plus', $args );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['apikey'] ) ) {
			return;
		}

		$opts = get_option( RAINMAKER_LP_SETTINGS_FIELD );

		$opts['feedblitz_api'] = $response['apikey'];

		$this->_api_key = $opts['feedblitz_api'];

		update_option( RAINMAKER_LP_SETTINGS_FIELD, $opts );

		update_option( '_feedblitz_oem_site_key', $opts['feedblitz_api'] );

		delete_transient( 'rm_email_auth' ); //delete the transient if it exists

		wp_cache_add ( RAINMAKER_LP_SETTINGS_FIELD, $opts, 'options' );

		//added in 1.8 to indicate new accounts since 1.8
		update_option( 'new_rainmail_1_8', 1 );

		return $opts['feedblitz_api'];

	}

	/**
	 * Get the user data from my.nrm if possible.
	 * Or loads the current WP user if not.
	 *
	 * @access private
	 * @return array
	 */
	private function _get_user() {

		$site             = RAINMAKER_TESTING_SITE ? 'http://205.186.148.164' : 'https://my.newrainmaker.com';
		$verification_key = MYNRM_MAIL_VERIFICATION_KEY;

		//http://st.newrainmaker.com/?mailverificationkey=gtKpUupb2*bmi1EXc^jeDCP|7*Q-UUAdmnedg3r7lLAY~ddFMnxjE7|OAmZF&get_user_data=true&user_site=http://nick-croft.preview02.rmkr.net/
		$verification_url = sprintf(
			'%s/?mailverificationkey=%s&user_site=%s&get_user_data=true',
			$site,
			$verification_key,
			rawurlencode( get_site_url() )
		);

		$args = array();

		if ( RAINMAKER_TESTING_SITE ) {
			$args['headers'] = array( 'Host' => 'st.newrainmaker.com' );
		}

		$response = wp_remote_get( $verification_url, $args );

		$user = is_wp_error( $response ) ? array( 'error' => 'wp error' ) : json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $user ) && is_object( $user ) && empty( $user->error ) ) {

			return $user;

		}

		$user = wp_get_current_user();

		if ( empty( $user ) || ! is_object( $user ) || is_wp_error( $user ) ) {
			return;
		}

		return $user;

	}

	/**
	 * Return an array of arguments that are used for validating the API request.
	 * This will replace key values in the request URI.
	 *
	 * @access protected
	 * @return array
	 */
	protected function _get_api_validation() {

		if ( empty( $this->_api_key ) ) {

			$this->_api_key = rm_lp_get_option( 'feedblitz_api' );

		}

		return array(
			'apikey' => $this->_api_key,
			'oemkey' => $this->_oem_key,
		);

	}


	/**
	 * Verifies the API key is attached to our OEM account
	 * Calls the private _validate_oem_account method via get_instance();
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function validate_oem_account() {
		$sequence = static::get_instance()->_validate_oem_account();

		return $sequence;
	}

	/**
	 * Verifies the API key is attached to our OEM account
	 *
	 * @access protected
	 * @return mixed
	 */
	protected function _validate_oem_account() {

		$args = array(
		);

		$api_url = $this->_get_api_url( 'f/?OemVerify&oemkey={oemkey}&key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['subscriberid'] ) ) {
			return array( 'error' => __( 'The API key is not attached to our OEM', '' ) );
		}

		return true;

	}

	/**
	 * Handles preparing the subscribe data from accesspress.
	 * If extended this can either process the submission directly or return the email, first name, and last name for processing.
	 * If the submission is directly processed the subscriber_id needs to be returned in the array.
	 * If there is an error this must return an array( 'error' => 'Error Description' ).
	 *
	 * return value like:
	 * array(
	 * 'email'         => 'example@domain.com',
	 *  'first_name'    => 'First',
	 *  'last_name'     => 'Last',
	 *  'subscriber_id' => '1234', //optional. Provide only if subscription processed directly
	 * )
	 *
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @param  array  $component_details
	 * @return mixed false by default unless the final class replaces this method
	 */
	protected function _accesspress_subscribe_handler( $list_id, $email, $first_name, $last_name, $component_details ) {

		$product = get_post( $component_details['product_id'] );

		$product_slug = rawurlencode( sprintf( '%s-%s', basename( ABSPATH ), $product->post_name ) );

		$tags = array(
			'firstname'   => rawurlencode( $first_name                 ),
			'lastname'    => rawurlencode( $last_name                  ),
			$product_slug => rawurlencode( date( 'Y-m-d H:i', time() ) ),
		);

		if ( $this->_is_first_order( $component_details['order_id'], $component_details['member'] ) ) {
			$tags[rawurlencode( sprintf( '%sSubscribeDate', basename( ABSPATH ) ) )] = rawurlencode( date( 'Y-m-d H:i', time() ) );
		}

		$subscriber = $this->_subscribe( $list_id, $email, $first_name, $last_name, $tags );

		if ( isset( $subscriber['error'] ) ) {
			return $subscriber;
		}

		return array( 'subscriber_id' => $subscriber );

	}

	/**
	 * checks to see if this is the first order.
	 *
	 * @access protected
	 * @param  int $order_id
	 * @param  int $member_id
	 * @return void
	 */
	protected function _is_first_order( $order_id, $member_id ) {

		$order_args = array(
			'post_type'  => 'acp-orders',
			'meta_key'   => '_acp_order_member_id',
			'meta_value' => $member_id,
		);

		$orders = new WP_Query( $args );

		$first_order = true;

		while ( $orders->have_posts() ) {
			$orders->the_post();

			if ( $order_id != get_the_ID() ) {
				$first_order = false;
			}

		}

		wp_reset_postdata();

		return $first_order;

	}

	/**
	 * Add subscriber to list.
	 * Checks to see if the user has opted in previously
	 * and then use a single opt-in method for the new list, tags, etc if possible.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @return mixed
	 */
	protected function _subscribe( $list_id, $email, $first_name, $last_name, $tags = array() ) {

		$tags = $this->_santitize_tags( $tags );

		if ( $first_name ) {
			$tags['firstname'] = rawurlencode( $first_name );
		}

		if ( $last_name ) {
			$tags['lastname']  = rawurlencode( $last_name  );
		}

		if ( $subscriber = $this->_get_user_by_email( $email ) ) {

			if ( ! empty( $tags ) && isset( $subscriber['subscription'] ) ) {

				foreach ( $subscriber['subscription'] as $subscription ) {

					if ( $list_id == $subscription['id'] ) {
						return $this->_tag_subscriber( $email, $tags );
					}

				}

			}

			return $this->_single_opt_in( $list_id, $email, $first_name, $last_name, $tags );

		}

		$args = array(
			'email'   => rawurlencode( $email ),
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f?SimpleApiSubscribe&key={apikey}&email={email}&listid={list_id}', $args );

		if ( ! empty( $tags ) ) {
			$api_url = add_query_arg( $tags, $api_url );
		}

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['subscriberid'] ) ) {
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
		}

		/**
		 * Runs after verifying a successful subscription for rm_email service.
		 *
		 * @param  int    $subscriberid the remote ID from Feedblitz
		 * @param  int    $list_id      list the user was subscribed to
		 * @param  string $email        user email address
		 * @param  array  $tags         any tags applied to the user in array( 'tag' => 'value' ) format
		 */
		do_action( 'rm_email_subscribed', $response['subscriberid'], $list_id, $email, $tags );

		return $response['subscriberid'];

	}

	/**
	 * Add subscriber to list via single opt-in.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @return mixed
	 */
	static function single_opt_in( $list_id, $email, $first_name, $last_name, $tags = array() ) {
		return static::get_instance()->_single_opt_in( $list_id, $email, $first_name, $last_name, $tags );
	}

	/**
	 * Add subscriber to list via single opt-in.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @return mixed
	 */
	protected function _single_opt_in( $list_id, $email, $first_name, $last_name, $tags = array() ) {

		$tags = $this->_santitize_tags( $tags );

		$args = array(
			'email'   => $email,
			'list_id' => $list_id,
			'tags'    => '',
		);

		$api_url = $this->_get_api_url( 'f?OemRegister&oemkey={oemkey}&key={apikey}&email={email}&Listid={list_id}&tags={tags}', $args );

		if ( ! empty( $tags ) ) {
			$api_url = add_query_arg( $tags, $api_url );
		}

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['subscriberid'] ) ) {
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
		}

		/**
		 * Runs after verifying a successful subscription for rm_email service.
		 *
		 * @param  int    $subscriberid the remote ID from Feedblitz
		 * @param  int    $list_id      list the user was subscribed to
		 * @param  string $email        user email address
		 * @param  array  $tags         any tags applied to the user in array( 'tag' => 'value' ) format
		 */
		do_action( 'rm_email_subscribed', $response['subscriberid'], $list_id, $email, $tags );

		return $response['subscriberid'];

	}

	/**
	 * Tags the subscriber.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $email
	 * @return mixed
	 */
	static function tag_subscriber( $email, $tags = array() ) {
		return $sequence = static::get_instance()->_tag_subscriber( $email, $tags );
	}

	/**
	 * Tags the subscriber.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $email
	 * @return mixed
	 */
	protected function _tag_subscriber( $email, $tags = array() ) {

		$tags = $this->_santitize_tags( $tags );

		$args = array(
			'email' => $email,
		);

		$api_url = $this->_get_api_url( 'f?SimpleApiSetFields&key={apikey}&email={email}', $args );

		if ( ! empty( $tags ) ) {
			$api_url = add_query_arg( $tags, $api_url );

			$api_url = str_replace( ' ', '+', $api_url );
		}

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['subscriberid'] ) ) {
			return array( 'error' => __( 'It seems there was an error tagging at this time. Please try again.', '' ) );
		}

		do_action( 'rm_tagged_subscriber', $response['subscriberid'], $email, $tags );

		return $response['subscriberid'];

	}

	/**
	 * Cleans up provided tags to ensure nothing hinky is going on.
	 *
	 * @access protected
	 * @param array $tags (default: array())
	 * @return void
	 */
	protected function _santitize_tags( $tags = array() ) {
		$sanitized_tags = array();
		foreach ( (array) $tags as $tag => $value ) {
			$sanitized_tags[$this->_sanitize_tag( $tag )] = $this->_sanitize_value( $value );
		}

		return $sanitized_tags;
	}

	/**
	 * Sanitizes the tag name.
	 * Different from tag value in case we ever need to do something different.
	 *
	 * @access protected
	 * @param  string $tag
	 * @return string
	 */
	protected function _sanitize_tag( $tag ) {
		return $this->_sanitize_string( $tag );
	}
	/**
	 * Sanitize the tag value.
	 *
	 * @access protected
	 * @param  string $value
	 * @return string
	 */
	protected function _sanitize_value( $value ) {
		return $this->_sanitize_string( $value );
	}

	/**
	 * Sanitizes a string based on provided arguments.
	 *
	 * @access protected
	 * @param  string $string
	 * @param  array $args (default: array())
	 * @return string
	 */
	protected function _sanitize_string( $string, $args = array() ) {

		$defaults = array(
			'strip_tags' => 1,
			'one_zero'   => 0,
			'url_encode' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['strip_tags'] ) {
			$string = strip_tags( $string );
		}

		if ( $args['one_zero'] ) {
			$string = empty( $string ) ? 0 : 1;
		}

		if ( $args['url_encode'] ) {
			$string = urlencode( $string );
		}

		return $string;

	}

	/**
	 * Calls the private _delete_list( $list_id ) method.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @return void
	 */
	static function delete_list( $list_id ) {
		return static::get_instance()->_delete_list( $list_id );
	}

	private function _delete_list( $list_id ) {

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'DELETE' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml' );

		delete_transient( get_called_class() . '_lists' );

		return $response;

	}

	/**
	 * Calls the private _restore_list( $list_id ) method.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @return void
	 */
	static function restore_list( $list_id ) {
		return static::get_instance()->_restore_list( $list_id );
	}

	/**
	 * Restores a previously deleted list by setting the status and liststatus to "ok".
	 *
	 * @access private
	 * @param int $list_id
	 * @return void
	 */
	private function _restore_list( $list_id ) {

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}/?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'syndication' => array(
				'id'         => $list_id,
				'status'     => 'ok',
				'liststatus' => 'ok',
			),
		);

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		delete_transient( get_called_class() . '_lists' );

		return $response;

	}

	private function _process_list( $data ) {

		$api_url = $this->_get_api_url( 'f.api/syndications?key={apikey}' );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$url_base = defined( 'RAINMAKER_LOCAL_SITE' ) && RAINMAKER_LOCAL_SITE ? 'http://www.copyblogger.com/' : home_url( '/' );

		$link  = $url_base . '/feed/mailfeed/' . '?unique=' . uniqid();
		$turbo = -3;

		if ( ! empty( $data['rss'] ) && $data['rss'] == 1 ) {
			$turbo = -10;
			$link  = $url_base . '/feed/' . '?unique=' . uniqid();
			if( ! empty( $data['rss-url'] ) && !filter_var( $data['rss-url'], FILTER_VALIDATE_URL ) === false ) {
				$link = $data['rss-url'];
			}
		}

		$body = array(
			'syndication' => array(
				'name'                   => stripslashes( $data['name'] ),
				'description'            => stripslashes( $data['description'] ),
				'link'                   => $link,
				'status'                 => 'ok',
				'liststatus'             => 'ok',
				'turbo'                  => $turbo,
				'autosite'               => 1,
				'notifyowner'            => 0,
				'autoresponderid'        => isset( $data['new-responder']   ) ? $data['new-responder']   : '',
				'landingpage'            => isset( $data['sub-landingpage'] ) ? $data['sub-landingpage'] : '',
				'unsubscriberedirecturl' => isset( $data['un-redirect']     ) ? $data['un-redirect']     : '',
			)
		);

		if ( ! empty( $data['id'] ) ) {

			$this->_get_http_instance()->set_method( 'POST' );

			$body['syndication']['id'] = $data['id'];

		} else {

			$this->_get_http_instance()->set_method( 'PUT' );

		}

		$this->_get_http_instance()->set_timeout( 120 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $data['id'] ) && isset( $data['json'] ) ) {

			if ( ! empty( $response['syndications']['syndication']['id'] ) ) {
				$list_id = $response['syndications']['syndication']['id'];
			} elseif ( ! empty( $response['syndications']['id'] ) ) {
				$list_id = $response['syndications']['id'];
			}

			if ( ! empty( $list_id ) ) {
				$array = array( 'id' => $list_id );
				return json_encode( $array );
			}

		}

		$list_transients = array(
			str_replace( 'Rainmaker_Opt_In_Gateway', '', get_called_class() . '_lists'        ),
			str_replace( 'Rainmaker_Opt_In_Gateway', '', get_called_class() . '_lists_detail' )
		);

		foreach ( $list_transients as $transient ) {
			delete_transient( $transient );
		}

		return $response;

	}

	private function _modify_list( $data ) {

		$args = array(
			'list_id' => $data['id'],
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$url_base = defined( 'RAINMAKER_LOCAL_SITE' ) && RAINMAKER_LOCAL_SITE ? 'http://www.copyblogger.com/' : home_url( '/' );

		$link = $url_base . '/feed/mailfeed/' . '?unique=' . uniqid();

		if ( ! empty( $data['rss'] ) && $data['rss'] == 1 ) {
			$link = $url_base . '/feed/' . '?unique=' . uniqid();
			if( ! empty( $data['rss-url'] ) && !filter_var( $data['rss-url'], FILTER_VALIDATE_URL ) === false ) {
				$link = $data['rss-url'];
			}
		}

		$body = array(
			'syndication' => array(
				'name'                   => stripslashes( $data['name'] ),
				'description'            => stripslashes( $data['description'] ),
				'link'                   => $link,
				'status'                 => 'ok',
				'liststatus'             => 'ok',
				'notifyowner'            => 0,
				'autoresponderid'        => isset( $data['new-responder']   ) ? $data['new-responder']   : '',
				'landingpage'            => isset( $data['sub-landingpage'] ) ? $data['sub-landingpage'] : '',
				'unsubscriberedirecturl' => isset( $data['un-redirect']     ) ? $data['un-redirect']     : '',
			)
		);

		if ( ! empty( $data['no_link'] ) ) {
			unset( $body['syndication']['link'] );
		}

		$this->_get_http_instance()->set_method( 'POST' );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		return $response;

	}


	protected function _edit_list( $data, $action ) {

		switch ( $action ) {

		case 'delete' :
			$response = static::get_instance()->_delete_list( $data );
			break;

		case 'edit' :
			$response = static::get_instance()->_process_list( $data );
			break;

		case 'new' :
			//return print_r( $data, true);
			//$data['name']        = $data[0];
			//$data['description'] = $data[1];
			$response            = static::get_instance()->_process_list( $data );

			if ( ! empty( $response['syndications'] ) && ! empty( $response['syndications']['id'] ) ) {
				static::new_list_template( $list_id );
			}

			break;

		}

		return $response;

	}

	static function update_ar_sequence( $syndication ) {
		$sequence = static::get_instance()->_update_ar_sequence( $syndication );

		return $sequence;
	}

	protected function _update_ar_sequence( $syndication ) {

		if ( empty( $syndication['id'] ) ) {

			$list = $this->_process_list( $syndication );

			if ( ! empty( $list['syndications']['syndication']['id'] ) ) {
				$list_id = $list['syndications']['syndication']['id'];
			} elseif ( ! empty( $list['syndications']['id'] ) ) {
				$list_id = $list['syndications']['id'];
			} else {
				return $list;
			}

			$args = array(
				'list_id' => $list_id,
			);

			$api_url = $this->_get_api_url( 'f?SimpleApiConvertToAr&key={apikey}&listid={list_id}', $args );

			$this->_get_http_instance()->set_method( 'GET' );

			$response = $this->_get_response( $api_url );

		} else {

			$syndication['no_link'] = true;

			$list = $this->_modify_list( $syndication );

		}

		return $list;

	}

	static function update_ar_message( $sequence_id, $message ) {
		$message = static::get_instance()->_update_ar_message( $sequence_id, $message );

		return $message;
	}

	protected function _update_ar_message( $sequence_id, $message ) {

		$delay = '';

		if ( $message['immediate'] ) {
			$delay = 0;
		} else {
			switch ( $message['interval'] ) {
			case 'days':
				$delay = (int) $message['delay'] * 24 * 60;
				break;
			case 'weeks':
				$delay = (int) $message['delay'] * 7 * 24 * 60;
				break;
			default: //defaults to hours
				$delay = (int) $message['delay'] * 60;
			}
		}

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$body = array(
			'autoresponder' => array(
				'id'                   => $sequence_id,
				'autoresponderentries' => array(
					'autoresponderentry' => array(
						'id'          => $message['id'],
						'status'      => empty( $message['status'] ) ? 'draft' : ( 'deleted' === $message['status'] ? 'deleted' : 'ok' ),
						'subject'     => stripslashes( $message['subject'] ),
						'html'        => $message['content'],
						'delay'       => $delay,
						'usetemplate' => $message['usetemplate'],
						'actions'     => array(
							'action' => array(
								'type'         => 'mail',
								'anycondition' => $message['anycondition'],
								'conditions'   => $message['conditions'],
							),
						),
					),
				),
			),
		);

		if ( empty( $body['autoresponder']['autoresponderentries']['autoresponderentry']['id'] ) ) {
			unset( $body['autoresponder']['autoresponderentries']['autoresponderentry']['id'] );
		}

		if ( empty( $message['conditions'] ) ) {
			unset( $body['autoresponder']['autoresponderentries']['autoresponderentry']['actions']['action']['conditions'] );
		}

		$args = array(
			'list_id' => $sequence_id,
		);

		if( empty( $message['id'] ) ) {
			$this->_get_http_instance()->set_method( 'PUT' );
			$api_url = $this->_get_api_url( 'f.api/autoresponder/{list_id}/entries?key={apikey}', $args );
		} else {
			$this->_get_http_instance()->set_method( 'POST' );

			$args['entry_id'] = $message['id'];
			$api_url = $this->_get_api_url( 'f.api/autoresponder/{list_id}/entries/{entry_id}?key={apikey}', $args );
		}

		$this->_get_http_instance()->set_timeout( 120 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if( empty( $response['autoresponder'] ) && ! empty( $response['entries'] ) ) {
			$response['autoresponder'] = $response['entries'];
		}

		if ( 'deleted' === $message['status'] && isset( $response['rsp']['success'] ) ) {
			return array( 'success' => $response['rsp']['success'] );
		}

		if ( empty( $response['autoresponder'] ) || empty( $response['autoresponder']['autoresponderentries'] ) || empty( $response['autoresponder']['autoresponderentries']['autoresponderentry'] ) ) {

			var_dump( $body );

			var_dump( $response );

			return array( 'error' => __( 'There was a problem sending the newsflash.', '' ) );

		}

		return $response;

	}

	/**
	 * Gets the messages for provided AR sequence.
	 *
	 * @access public
	 * @static
	 * @param int    $sequence_id
	 * @return array
	 */
	static function get_ar_messages( $sequence_id ) {
		return static::get_instance()->_get_ar_messages( $sequence_id );
	}

	/**
	 * Gets the messages for the provided AR sequence.
	 *
	 * @access private
	 * @param int    $sequence_id
	 * @return array
	 */
	private function _get_ar_messages( $sequence_id, $start_date, $end_date ) {

		$args = array(
			'sequence_id' => $sequence_id,
		);

		$api_url = $this->_get_api_url( 'f.api/autoresponder/{sequence_id}/entries/?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['autoresponder']['autoresponderentries']['autoresponderentry'] ) ) {
			return false; //error
		}

		return $response['autoresponder']['autoresponderentries']['autoresponderentry'];

	}

	/**
	 * Updates the list to use the default from name/address or the current users name/address.
	 *
	 * @access private
	 * @param  int $list_id
	 * @return void
	 */
	static function new_list_template( $list_id ) {

		$from_name  = accesspress_get_option( 'email_receipt_name'    );
		$from_email = accesspress_get_option( 'email_receipt_address' );

		if ( empty( $from_name ) || empty( $from_email ) ) {

			$user = wp_get_current_user();

			$email        = $user->user_email;
			$fname        = $user->user_firstname;
			$lname        = $user->user_lastname;
			$display_name = $user->display_name;

			$name = empty( $display_name ) ? sprintf( '%s %s', $fname, $lname ) : $display_name;

			$from_email = empty( $from_email ) ? $email : $from_email;
			$from_name  = empty( $from_name  ) ? $name  : $from_name;

		}

		static::get_instance()->_update_ar_template( $list_id, $from_name, $from_email );

	}

	/**
	 * Sets the template for the list so that the from name and email are used correctly.
	 * Calls the private method using the get_instance() method as a proxy.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @param string $from_name
	 * @param string $from_email
	 * @return void
	 */
	static function update_ar_template( $list_id, $from_name, $from_email ) {

		$template = static::get_instance()->_update_ar_template( $list_id, $from_name, $from_email );

		return $template;

	}

	/**
	 * Sets the template for the list so that the from name and email are used correctly.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @param string $from_name
	 * @param string $from_email
	 * @return void
	 */
	private function _update_ar_template( $list_id, $from_name, $from_email ) {

		$name       = accesspress_get_option( 'email_receipt_name' );
		$name       = empty( $name ) ? get_bloginfo( 'name' ) : $name;

		$from_email = accesspress_get_option( 'email_receipt_address' );
		$from_email = empty( $from_email ) ? sprintf( 'support@%s', preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] ) ) : $from_email;

		$baseTemplate = $this->get_oem_template();

		if ( $logo = rm_lp_get_option( 'email_template_logo' ) ) {

			$endTemplate = str_replace( 'http://rainmakerplatform.com/wp-content/themes/rm-platform/images/logo.png', $logo, $baseTemplate );

		} else {

			$endTemplate = str_replace(
				'<a href="<$BlogURL$>"><img border="0" align="center" src="http://rainmakerplatform.com/wp-content/themes/rm-platform/images/logo.png" style="height:auto !important;max-width:100% !important;"/></a>',
				'<h1><a href="<$BlogURL$>">'.get_bloginfo( 'name' ).'</a></h1>',
				$baseTemplate
			);

		}

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/template/{list_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'template' => array(
				'fromname'       => $name,
				'fromaddress'    => $from_email,
				'html'           => $endTemplate,
				'active'         => 1,
			)
		);

		$this->_get_http_instance()->set_method( 'PUT' );
		$this->_get_http_instance()->set_timeout( 120 );

		return $response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

	}

	static function update_triggers( $args ) {
		return static::get_instance()->_update_triggers( $args );
	}

	/**
	 * Sets the template for the list so that the from name and email are used correctly.
	 *
	 * @access public
	 * @static
	 * @param  array $data
	 * @return void
	 */
	private function _update_triggers( $data ) {

		$args = array(
			'list_id'    => isset( $data['list_id']    ) ? $data['list_id']    : '',
			'trigger_id' => isset( $data['trigger_id'] ) ? $data['trigger_id'] : '',
		);

		$api_url = $this->_get_api_url( 'f.api/triggers/{list_id}/{trigger_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'triggers' => array(
				'id'      => isset( $data['list_id']    ) ? $data['list_id']    : '',
				'trigger' => array(
					'id'     => isset( $data['trigger_id'] ) ? $data['trigger_id'] : '',
					'event'  => isset( $data['event']      ) ? $data['event']      : '',
					'action' => isset( $data['action']     ) ? $data['action']     : '',
					'listid' => isset( $data['action_id']  ) ? $data['action_id']  : '',
				),
			)
		);

		$http_api = $this->_get_http_instance();

		if ( empty( $data['trigger_id'] ) ) {
			$this->_get_http_instance()->set_method( 'PUT' );
		} else {
			$this->_get_http_instance()->set_method( 'POST' );
		}

		$this->_get_http_instance()->set_timeout( 120 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['triggers'] ) || empty( $response['triggers']['trigger'] ) || empty( $response['triggers']['trigger']['id'] ) ) {
			return false;
		}

		return $response['triggers']['trigger']['id'];

	}

	/**
	 * Gets the metrics for provided mailing and list.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @param int $mailing_id
	 * @return void
	 */
	static function get_metrics( $list_id, $mailing_id ) {
		return static::get_instance()->_get_metrics( $list_id, $mailing_id );
	}

	/**
	 * Gets the metrics for the provided mailing and list.
	 *
	 * @access private
	 * @param int $list_id
	 * @param int $mailing_id
	 * @return void
	 */
	private function _get_metrics( $list_id, $mailing_id ) {

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/metrics/{list_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'metrics' => array(
				'mailingid' => $mailing_id,
			),
		);

		$this->_get_http_instance()->set_method( 'POST' );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['metrics'] ) ) {
			return false; //error
		}

		return $response['metrics'];

	}

	/**
	 * Gets the metrics for provided AR sequence.
	 *
	 * @access public
	 * @static
	 * @param int    $sequence_id
	 * @param string $start_date in format 'Y-m-d'
	 * @return array
	 */
	static function get_ar_metrics( $sequence_id, $start_date, $end_date ) {
		return static::get_instance()->_get_ar_metrics( $sequence_id, $start_date, $end_date );
	}

	/**
	 * Gets the metrics for the provided AR sequence.
	 *
	 * @access private
	 * @param int    $sequence_id
	 * @param string $start_date in format 'Y-m-d'
	 * @return array
	 */
	private function _get_ar_metrics( $sequence_id, $start_date, $end_date ) {

		$args = array(
			'sequence_id' => $sequence_id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
		);

		$api_url = $this->_get_api_url( 'f.api/autoresponder/{sequence_id}/entries/stats/{start_date}/{end_date}/?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['entries']['stats']['day'] ) ) {
			return false; //error
		}

		return $response['entries']['stats']['day'];

	}

	/**
	 * Gets the triggers for specified list.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @return void
	 */
	static function get_triggers( $list_id ) {
		return static::get_instance()->_get_triggers( $list_id );
	}

	/**
	 * Gets triggers for the specified $list_id.
	 *
	 * @access private
	 * @param int    $list_id
	 * @return array
	 */
	private function _get_triggers( $list_id ) {

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/triggers/{list_id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['triggers']['trigger'] ) ) {
			return false; //error
		}

		return $response['triggers'];

	}

	/**
	 * Adds/Edits trigger.
	 * Invokes the private _edit_trigger() method via get_instance().
	 *
	 * @access public
	 * @static
	 * @param  int   $list_id
	 * @param  int   $trigger_id
	 * @param  array $trigger
	 * @return mixed array on success bool(false) on failure
	 */
	static function edit_trigger( $list_id, $trigger_id, $trigger ) {
		return static::get_instance()->_edit_trigger( $list_id, $trigger_id, $trigger );
	}

	/**
	 * Adds/Edits trigers.
	 *
	 * @access private
	 * @param  int   $list_id
	 * @param  int   $trigger_id
	 * @param  array $trigger
	 * @return mixed array on success bool(false) on failure
	 */
	private function _edit_trigger( $list_id, $trigger_id, $trigger )  {

		$args = array(
			'list_id'    => $list_id,
			'trigger_id' => $trigger_id,
		);

		$api_url = $this->_get_api_url( 'f.api/triggers/{list_id}/{trigger_id}?key={apikey}', $args );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0"></feedblitzapi>';

		$body = array(
			'triggers' => array(
				'trigger' => $trigger,
			),
		);

		if ( $trigger_id ) {
			$this->_get_http_instance()->set_method( 'POST' );
		} else {

			$this->_get_http_instance()->set_method( 'PUT' );

			unset( $body['triggers']['trigger']['id'] );

		}

		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['triggers']['trigger'] ) ) {
			return false; //error
		}

		return $response['triggers'];

	}


}
