<?php

final class Rainmaker_Opt_In_Gateway_Constant_Contact extends Rainmaker_Opt_In_Gateway {

	/**
	 * https://constantcontact.mashery.com/apps/mykeys
	 * Login: rainmakerdigital
	 * Pass: nl6ew4YJ3BlD
	 *
	 * oauth URL:
	 */
	const CLIENT_ID     = 'sgqjpzwf8ens9pnd854d9bgy';
	const CLIENT_SECRET = 'XtwXjU4mb4zR3xADNvDCw6Cs';

	const REDIRECT_URI           = 'https://oauth.newrainmaker.com/authorization/';
	const AUTHORIZATION_ENDPOINT = 'https://oauth2.constantcontact.com/oauth2/oauth/siteowner/authorize';
	const TOKEN_ENDPOINT         = 'https://oauth2.constantcontact.com/oauth2/oauth/token';

	/**
	 * Stores the API Key
	 *
	 * @var	string
	 * @access protected
	 */
	protected $_api_key;

	static $client;
	static $state;
	/**
	 * The constructor should be extended.
	 * When extended it must set the $_api_uri_base property.
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct() {

		$this->_api_uri_base = 'https://api.constantcontact.com/v2/';

	}

	private static function setup_oauth() {

		require_once( 'oAuth2_Client.php' );
		require_once( 'GrantType/IGrantType.php' );
		require_once( 'GrantType/AuthorizationCode.php' );

		self::$state   = esc_url_raw( add_query_arg( array( 'tab' => 'email', 'service' => 'constantcontact' ), menu_page_url( 'universal-settings', false ) ) );
		self::$client  = new cc_oAuth_Client\cc_oAuth_Client( self::CLIENT_ID, self::CLIENT_SECRET );

		$token = rm_lp_get_option( 'constantcontact_token' );

		if ( $token ) {

			self::$client->setAccessToken( $token );
			self::$client->setAccessTokenType( 1 );

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
		self::setup_oauth();

		$apikey = rm_lp_get_option( 'constantcontact_token' );

		//parent::settings();
?>
		  <table class="form-table">
			  <tbody>
				  <tr valign="top">

				  <?php
		if( isset( $_GET['error'] ) ){
			$this->error( $_GET['error'] );
		}
		elseif ( isset( $_GET['code'] ) && isset( $_GET['service'] ) && 'constantcontact' === $_GET['service'] ) {
			$apikey = self::get_credentials();
		}
		if( ! empty( $apikey ) ) {
			$url = self::$client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, self::REDIRECT_URI, array( 'state' => self::$state ) );
?>

					  <th scope="row">Constant Contact is Configured</th>
					  <td><input type="hidden" id="_rm_lp_settings_constantcontact_token" name="_rm_lp_settings[constantcontact_token]" value="<?php echo $apikey; ?>">
						  <a class="button-secondary button-ccontact" href="<?php echo $url; ?>">Re-Authenticate Constant Contact Account</a> <a class="deactivate-ccontact" href="#_rm_lp_settings_constantcontact_token">Remove Constant Contact Authentication</a></td>
					  <script type="text/javascript">
					  (function($) {
						  $( '.deactivate-ccontact' ).click(function(){
							  var ccontactInput  = $(this).attr('href');
							  var deleteButton = $(this);
							  var confirmTipped;

							  confirmTipped = Tipped.create( deleteButton, '<p class="tool-tip-title"><?php _e( 'Are you sure? If so, click "Yes" to continue, then save the settings to confirm your decision.', '' ); ?></p><a href="#yes" class="button confirm-delete confirm-delete-positive deactivate-ccontact"><?php _e( 'Yes', '' ); ?></a> <a href="#no" class="button confirm-delete confirm-delete-negative"><?php _e( 'No', '' ); ?></a>', {
								  skin: 'light',
								  size: 'small',
								  close: true,
								  hideOn: false,
								  hideOnClickOutside: true,
								  hideOthers: true,
								  showOn: false,
								  });

								  setTimeout(function(){
								  confirmTipped.show();
								  }, 100 );

							$('body').on('click', '.confirm-delete', function(){
								confirmTipped.hide();
								if ( $(this).hasClass('deactivate-ccontact') ) {
									$( ccontactInput ).attr('value', '');
									$('.button-ccontact').removeClass('button-secondary').addClass('button-primary').text('<?php _e( 'Set Up Constant Contact Account', '' ); ?>');
									deleteButton.hide();
									}
									return false;
								});

								return false;

							});
						})(jQuery);
					</script>
	  <?php
		} else {
?>

					  <th scope="row">Connect with Constant Contact</th>
					  <td>
					  <?php
			$url = self::$client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, self::REDIRECT_URI, array( 'state' => self::$state ) );

?>
						  <a class="button-primary button-ccontact"  href="<?php echo $url; ?>">Set Up Constant Contact Account</a>

					  </td> <?php
		}
?>
				  </tr>
			  </tbody>
		  </table>
	  <?php
	}
	/**
	 * Returns an array that is used to build the settings.
	 * Format array like:
	 * array(
	 *  'key1' => array(
	 *   'name'        => 'Key1 Name', //required. All other fields are optional. `type` defaults to 'text'
	 *   'description' => 'A description',
	 *   'type'        => 'text',
	 *   'required'    => true,
	 *   'options'     => array(), //required for type radio and select
	 *  ),
	 * );
	 *
	 * @access protected
	 * @return array
	 */
	protected function _setting_fields() {

		$settings = array(
			'constantcontact_token' => array(
				'name'     => __( 'CC Token', '' ),
				'required' => true,
				'type'     => 'hidden',
			),
		);

		return $settings;
	}

	/**
	 * Returns an array used to build the product metabox and conversion wizard options.
	 * Format array like:
	 * array(
	 *  'key1' => array(
	 *   'name'        => 'Key1 Name', //required. All other fields are optional. `type` defaults to 'text'
	 *   'description' => 'A description',
	 *   'type'        => 'text',
	 *   'options'     => array(), //required for type radio and select
	 *  ),
	 * );
	 *
	 * @access protected
	 * @return array
	 */
	protected function _product_fields() {

		$lists = static::get_lists();

		if ( empty( $lists ) ){
			_e( 'No Constant Contact lists found.', '' );
			return array();
		}

		$lists = array_replace( array( '' => __( 'Select List', '' ) ), $lists );

		$options = array(
			sprintf( '_acp_product_%s_list', $this->get_service() ) => array(
				'name'    => __( 'Constant Contact List', '' ),
				'type'    => 'select',
				'options' => $lists,
			),
		);

		return $options;

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

			$this->_api_key = rm_lp_get_option( 'constantcontact_token' );

		}

		return array(
			'apikey' => $this->_api_key,
		);

	}

	/**
	 * Gets an array of available lists from the opt-in service.
	 * Lists should be provided in $lists[list-id'] = 'List Name'; format..
	 *
	 * @access protected
	 * @return array()
	 */
	protected function _get_lists() {

		self::setup_oauth();

		$api_url = $this->_get_api_url( 'lists' );

		$response = self::$client->fetch($api_url, array( 'api_key' => self::CLIENT_ID ));

		if ( $response['code'] != 200 ) {
			return array();
		}

		$lists = array();

		foreach ( $response['result'] as $list ) {
			$lists[$list['id']] = $list['name'];
		}

		return $lists;

	}

	/**
	 * Gets an array of available fields for a list.
	 *
	 * @access protected
	 * @param  string $list_id
	 * @return array  $fields['id/key'] = 'Field Name';
	 */
	protected function _get_fields( $list_id ) {

		return array(
			'email'      => __( 'Email Address', '' ) ,
			'first_name' => __( 'First Name', '' ),
			'last_name'  => __( 'Last Name', '' )
		);

	}

	/**
	 * Gets an array of available fields for a list.
	 *
	 * @access protected
	 * @param  string $list_id
	 * @return array  array( 'count' => (int) 'number of visible fields', 'inputs' => 'html string of input fields for form' )
	 */
	protected function _get_inputs( $list_id ) {

		$input_array = $this->_get_fields( $list_id );

		$inputs = '';
		$count  = 0;

		foreach ( $input_array as $id => $name ) {

			$type = 'email' === $id ? $id : 'text';

			$inputs .= sprintf(
				'<input type="%1$s" value="" placeholder="%2$s" name="%3$s" class="field-%4$s field-%3$s opt-in-field">',
				$type,
				$name,
				$id,
				++$count
			);

		}

		return array( 'count' => $count, 'inputs' => $inputs );

	}

	/**
	 * Add subscriber to list.
	 * When extending this should check to see if the user has opted in previously
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
	protected function _subscribe( $list_id, $email, $first_name, $last_name ) {

		self::setup_oauth();

		$contact = new stdClass();

		$contact->first_name        = $first_name;
		$contact->last_name         = $last_name;
		$contact->email_addresses[] = array( 'email_address' => $email );
		$contact->status            = 'ACTIVE';
		$contact->lists[]           = array( 'id' => $list_id );

		$contact = json_encode( $contact );

		$api_url = $this->_get_api_url( 'contacts' );

		$params = array(
			'api_key'   => self::CLIENT_ID,
			'action_by' => 'ACTION_BY_OWNER',
		);
		$params = http_build_query($params, null, '&');

		$response = self::$client->fetch( $api_url . '?' . $params, $contact, 'POST', array( 'api_key' => self::CLIENT_ID, 'Content-Type' => 'application/json' ), 0 );

		if ( empty( $response['result']['id'] ) ) {
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
		}

		return $response['result']['id'];

	}

	/**
	 * Remove subscriber from list.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @return mixed
	 */
	protected function _unsubscribe( $list_id, $email ) {

		self::setup_oauth();

		$email_check = self::_get_user_by_email( $email );
		$lists       = $email_check['lists'];

		if ( ( $key = array_search( $list_id, $lists ) ) !== false ) {
			unset($lists[$key]);
		}

		if ( ! isset( $email_check['error'] ) ) {

			$args = array(
				'email'   => $email,
				'list_id' => $list_id,
			);

			$contact = new stdClass();

			$contact->email_addresses[] = array( 'email_address' => $email );
			$contact->status            = 'ACTIVE';
			$contact->lists[]           = $lists;

			$api_url = $this->_get_api_url( 'contacts/'.$email );
			$params  = array(
				'api_key'   => self::CLIENT_ID,
				'action_by' => 'ACTION_BY_OWNER',
			);

			$params = http_build_query($params, null, '&');

			$response = self::$client->fetch($api_url.'?'.$params, $contact, 'PUT', array( 'api_key' => self::CLIENT_ID, 'Content-Type' => 'application/json' ), 0 );

			if ( $response['status']  != 'REMOVED' ){
				return array( 'error' => __( 'User could not be unsubscribed.', '' ) );
			}

			return $response['id'];

		} else {

			return array( 'error' => __( 'Email not found.', '' ) );

		}

	}

	/**
	 * Searches for the user by provided email address.
	 *
	 * @access protected
	 * @param  string $email
	 * @return array
	 */
	protected function _get_user_by_email( $email ) {

		self::setup_oauth();

		$api_url = $this->_get_api_url( 'contacts' );

		$response = self::$client->fetch( $api_url, array( 'api_key' => self::CLIENT_ID, 'email' => $email, 'status' => 'ALL', 'limit' =>  50 ) );

		if ( isset( $response['results'] ) ) {
			$result = $response['results'];
			return $result;
		} else {
			return array( 'error' => __( 'Email not found.', '' ) );
		}

	}

	/**
	 * Prepares and/or submits the input data for subscription processing for Landing Page form submission.
	 * When extended this can either process the submission directly or return the email, first name, and last name for processing.
	 * If the submission is directly processed the subscriber_id needs to be returned in the array.
	 * If there is an error this must return an array( 'error' => 'Error Description' ).
	 *
	 * return value like:
	 * array(
	 *  'email'         => 'example@domain.com',
	 *  'first_name'    => 'First',
	 *  'last_name'     => 'Last',
	 *  'subscriber_id' => '1234', //optional. Provide only if subscription processed directly
	 * )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @param  array  $component_details
	 * @return array
	 */
	protected function _landing_pages_subscribe_handler( $list_id, $email, $first_name, $last_name, $component_details ) {

		$data = array(
			'first_name' => empty( $_POST['first_name'] ) ? $first_name : $_POST['first_name'],
			'last_name'  => empty( $_POST['last_name'] )  ? $last_name  : $_POST['last_name'],
			'email'      => empty( $_POST['email'] )      ? $email      : $_POST['email'],
		);

		return $data;

	}

	/**
	 * Prepares and/or submits the input data for subscription processing for Forms submission.
	 * When extended this can either process the submission directly or return the email, first name, and last name for processing.
	 * If the submission is directly processed the subscriber_id needs to be returned in the array.
	 * If there is an error this must return an array( 'error' => 'Error Description' ).
	 *
	 * return value like:
	 * array(
	 *  'email'         => 'example@domain.com',
	 *  'first_name'    => 'First',
	 *  'last_name'     => 'Last',
	 *  'subscriber_id' => '1234', //optional. Provide only if subscription processed directly
	 * )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $email
	 * @param  string $first_name
	 * @param  string $last_name
	 * @param  array  $component_details
	 * @return array
	 */
	protected function _forms_subscribe_handler( $list_id, $email, $first_name, $last_name, $component_details ) {

		$data = array(
			'first_name' => empty( $component_details['first_name'] ) ? $first_name : $component_details['first_name'],
			'last_name'  => empty( $component_details['last_name'] )  ? $last_name  : $component_details['last_name'],
			'email'      => empty( $component_details['email'] )      ? $email      : $component_details['email'],
		);

		return $data;

	}

	/**
	 * Gets and saves the login credentials
	 * @since 0.1.1
	 */
	private function get_credentials(){

		$params = array( 'code' => $_GET['code'], 'redirect_uri' => self::REDIRECT_URI );
		$response = self::$client->getAccessToken( self::TOKEN_ENDPOINT, 'authorization_code', $params );

		$token = $response['result']['access_token'];

		$array = get_option( RAINMAKER_LP_SETTINGS_FIELD );
		$array['constantcontact_token'] = $token;

		update_option( RAINMAKER_LP_SETTINGS_FIELD, $array );

		return $token;

	}

}
