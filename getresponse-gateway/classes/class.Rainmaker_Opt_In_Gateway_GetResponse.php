<?php

final class Rainmaker_Opt_In_Gateway_GetResponse extends Rainmaker_Opt_In_Gateway {

	/**
	 * Stores the API Key
	 *
	 * @var    string
	 * @access protected
	 */
	protected $_api_key;
	private $errorsOn = true;
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

		$this->_api_uri_base = 'http://api2.getresponse.com';
		$this->_api_key = rm_lp_get_option( 'getresponse_api' );

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
			'getresponse_api' => array(
				'name'     => __( 'API Key', '' ),
				'required' => true,
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
			_e( 'No GetResponse lists found.', '' );
			return array();
		}

		$lists = array_replace( array( '' => __( 'Select List', '' ) ), $lists );

		$options = array(
			sprintf( '_acp_product_%s_list', $this->get_service() ) => array(
				'name'    => __( 'GetResponse List', '' ),
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

			$this->_api_key = rm_lp_get_option( 'getresponse_api' );

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

		$lists    = array();

		$params   = null;
		$params   = array( 'name' => array( 'CONTAINS' => '%' ));

		$request  = $this->prepRequest('get_campaigns', $params);
		$response = $this->execute($request);

		$response = (array) $response;

		foreach ( $response as $key=>$value ) {
			$lists[$key] = $response[$key]->name;
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
			'email'          => __( 'Email Address', '' ) ,
			'first_name'     => __( 'First Name', '' ),
			'last_name'      => __( 'Last Name', '' ),
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

		$name = implode( ' ', array( $first_name, $last_name ) );

		$params   = null;
		$params   = array(
			'campaign'    => $list_id,
			'name'        => $name,
			'email'       => $email,
			'cycle_day'  => 0
		);

		$request  = $this->prepRequest('add_contact', $params);
		$response = $this->execute($request);

		if ( $response->queued == 1 ) {
			$id = 1;
		} else {
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
		}

		return $id;
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

		$params = null;
		$params['campaigns'] = array( $list_id );
		$params['email'] = array( 'CONTAINS' => '%'.$email.'%' );
		$request  = $this->prepRequest( 'get_contacts', $params );
		$response = $this->execute( $request );

		$response = (array) $response;

		$id = key( $response );

		$request  = $this->prepRequest( 'delete_contact', array( 'contact' => $id ) );
		$response = $this->execute( $request );

		if ( $response->deleted == 1 ) {
			return $id;
		} else {
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
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
	 * Return array as a JSON encoded string
	 * @param string $method API method to call
	 * @param array  $params Array of parameters
	 * @return string JSON encoded string
	 * @access private
	 */
	private function prepRequest( $method, $params = null, $id = null )
	{
		$array = array( $this->_api_key );

		if ( !is_null( $params ) ) {
			$array[1] = $params;
		}

		$request = json_encode( array( 'method' => $method, 'params' => $array, 'id' => $id ) );

		return $request;
	}

	/**
	 * Executes an API call
	 * @param string $request JSON encoded array
	 * @return object
	 * @access private
	 */
	private function execute( $request )
	{
		$handle = curl_init( $this->_api_uri_base );
		curl_setopt( $handle, CURLOPT_POST, 1 );
		curl_setopt( $handle, CURLOPT_POSTFIELDS, $request );
		curl_setopt($handle, CURLOPT_HEADER, 'Content-type: application/json' );
		curl_setopt(  $handle, CURLOPT_RETURNTRANSFER, true );
		$response = json_decode( curl_exec( $handle ) );

		if ( curl_error( $handle ) ) {
			trigger_error( curl_error( $handle ), E_USER_ERROR );
		}

		$httpCode = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

		if ( !( ( $httpCode == '200' ) || ( $httpCode == '204' ) ) ) {
			trigger_error('API call failed. Server returned status code '.$httpCode, E_USER_ERROR);
		}

		curl_close( $handle );

		if ( !$response->error ) {
			return $response->result;
		} elseif ( $this->errorsOn ){
			return $response->error;
		}
	}

}
