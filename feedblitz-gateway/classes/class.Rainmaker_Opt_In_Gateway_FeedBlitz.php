<?php

class Rainmaker_Opt_In_Gateway_FeedBlitz extends Rainmaker_Opt_In_Gateway {

	/**
	 * Stores the API Key
	 *
	 * @var    string
	 * @access protected
	 */
	protected $_api_key;

	/**
	 * Stores the current subscriber count so the _get_subscriber_count() only checks the API once.
	 *
	 * @var int
	 * @access protected
	 */
	protected $_subscriber_count;

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
			'feedblitz_api' => array(
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

		if( empty( $lists ) ){
			_e( 'No RainMail lists found.', '' );
			return array();
		}

		$lists = array_replace( array( '' => __( 'Select List', '' ) ), $lists );

		$options = array(
			sprintf( '_acp_product_%s_list', $this->get_service() ) => array(
				'name'    => __( 'RainMail List', '' ),
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

		if( empty( $this->_api_key ) ) {

			$this->_api_key = rm_lp_get_option( 'feedblitz_api' );

		}

		return array(
			'apikey' => $this->_api_key,
		);

	}

	protected function _get_autoresponders() {

		$lists = array();

		$api_url = $this->_get_api_url( 'f.api/autoresponders?key={apikey}&summary=1' );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $reponse['error'] ) ) {

			if ( isset( $response['autoresponders'] ) && ! empty( $response['autoresponders']['autoresponder'] ) ) {

				//only one list will return a single item instead of an array of lists.
				if ( isset( $response['autoresponders']['autoresponder']['id'] ) ) {

					$name = sprintf(
						'%s',
						$response['autoresponders']['autoresponder']['name']
					);

					$lists[$response['autoresponders']['autoresponder']['id']] = $name;

				} else {

					foreach ( $response['autoresponders']['autoresponder'] as $list ) {

						if ( $list['status'] == 'ok' || $list['status'] == 'paused' ) {

							$name = sprintf(
								'%s',
								$list['name']
							);

							$lists[$list['id']] = $name;

						}

					}

				}

			}

		}
		return $lists;
	}

	protected function _get_list( $id ) {

		$list = array();

		/*

		$args = array(
			'list_id' => $id,
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}?key={apikey}', $args );
		//echo $api_url;
		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 120 );

		$response = $this->_get_response( $api_url, 'xml' );
		//die(print_r($response));
		if ( empty( $reponse['error'] ) ) {
			if ( isset( $response['syndications'] ) && ! empty( $response['syndications']['syndication'] ) ) {
				//only one list will return a single item instead of an array of lists.
				if ( isset( $response['syndications']['syndication']['id'] ) ) {
					$list = array(
						'name'                   => $response['syndications']['syndication']['name'],
						'description'            => $response['syndications']['syndication']['description'],
						'autoresponderid'        => $response['syndications']['syndication']['autoresponderid'],
						'confirmredirecturl'     => $response['syndications']['syndication']['confirmredirecturl'],
						'unsubscriberedirecturl' => $response['syndications']['syndication']['unsubscriberedirecturl'],
						'api_url'                => $api_url
					);

				}

			} else {
				$list['api'] = $api_url;
			}


		}
		*/
		$lists = array();

		$api_url = $this->_get_api_url( 'f.api/syndications?key={apikey}' );

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 120 );
		$response = $this->_get_response( $api_url, 'xml' );
		if ( empty( $reponse['error'] ) ) {

			if ( isset( $response['syndications'] ) && ! empty( $response['syndications']['syndication'] ) ) {

				if ( isset( $response['syndications']['syndication']['id'] ) ) {

					if( $response['syndications']['syndication']['id'] == $id && $list['status'] == 'ok' ) {
						$list = array(
							'name'                   => $response['syndications']['syndication']['name'],
							'description'            => $response['syndications']['syndication']['description'],
							'autoresponderid'        => $response['syndications']['syndication']['autoresponderid'],
							'confirmredirecturl'     => $response['syndications']['syndication']['confirmredirecturl'],
							'unsubscriberedirecturl' => $response['syndications']['syndication']['unsubscriberedirecturl'],
							'api_url'                => $api_url
						);
					}

				} else {

					foreach ( $response['syndications']['syndication'] as $syndication ) {

						if ( $syndication['id'] == $id && $syndication['status'] == 'ok' ) {
							$list = array(
								'name'                   => $syndication['name'],
								'description'            => $syndication['description'],
								'autoresponderid'        => $syndication['autoresponderid'],
								'confirmredirecturl'     => $syndication['confirmredirecturl'],
								'unsubscriberedirecturl' => $syndication['unsubscriberedirecturl'],
								'api_url'                => $api_url
							);
						}

					}

				}

			} else {
				$list['api'] = $api_url;
			}

		}

		//return( $response );
		return json_encode( $list );

	}

	/**
	 * Gets an array of available lists from the opt-in service.
	 * Lists should be provided in $lists[list-id'] = 'List Name'; format..
	 *
	 * @access protected
	 * @param $detail                      bool   Return a detailed array? Default = false
	 * @param $autoresponders[optional]    bool   Show autoresponders in the returned array? Default = true
	 * @return array()
	 */
	protected function _get_lists( $detail = false, $autoresponders = true ) {

		$lists = array();

		$api_url = $this->_get_api_url( 'f.api/syndications?key={apikey}&summary=1' );

		$this->_get_http_instance()->set_timeout( 120 );
		$this->_get_http_instance()->set_method( 'GET' );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $reponse['error'] ) ) {
			if ( isset( $response['syndications'] ) && ! empty( $response['syndications']['syndication'] ) ) {

				//only one list will return a single item instead of an array of lists.
				if ( isset( $response['syndications']['syndication']['id'] ) ) {
					if ( $detail ) {
						$lists[$response['syndications']['syndication']['id']] = array(
							'id'                     => $response['syndications']['syndication']['id'],
							'name'                   => $response['syndications']['syndication']['name'],
							'description'            => $response['syndications']['syndication']['description'],
							'subscribers'            => $response['syndications']['syndication']['subscribersummary']['subscribers'],
							'autoresponderid'        => $response['syndications']['syndication']['autoresponderid'],
							'landingpage'            => $response['syndications']['syndication']['landingpage'],
							'turbo'                  => $response['syndications']['syndication']['turbo'],
							'link'                   => $response['syndications']['syndication']['link'],
							'unsubscriberedirecturl' => $response['syndications']['syndication']['unsubscriberedirecturl'],
						);
					} else {
						$lists[$response['syndications']['syndication']['id']] = $response['syndications']['syndication']['name'];
					}

				} else {

					foreach ( $response['syndications']['syndication'] as $list ) {

						if ( $list['status'] == 'ok' && $list['isauto'] == 0 ) {
							if ( $detail ) {
								$lists[$list['id']] = array(
									'id'                     => $list['id'],
									'name'                   => $list['name'],
									'description'            => ( is_array( $list['description'] )            ? '' : $list['description'] ),
									'turbo'                  => ( is_array( $list['turbo'] )                  ? '' : $list['turbo'] ),
									'link'                   => ( is_array( $list['link'] )                   ? '' : $list['link'] ),
									'subscribers'            => $list['subscribersummary']['subscribers'],
									'autoresponderid'        => ( is_array( $list['autoresponderid'] )        ? '' : $list['autoresponderid'] ),
									'landingpage'            => ( is_array( $list['landingpage'] )            ? '' : $list['landingpage'] ),
									'unsubscriberedirecturl' => ( is_array( $list['unsubscriberedirecturl'] ) ? '' : $list['unsubscriberedirecturl'] ),
								);
							} else {
								$lists[$list['id']] = $list['name'];
							}
						}

					}

				}

			}

		}

		if ( ! $detail ) {
			natcasesort( $lists );
		}

		if( $autoresponders ) {

			$ar_lists = array();

			$api_url = $this->_get_api_url( 'f.api/autoresponders?key={apikey}&summary=1' );

			$response = $this->_get_response( $api_url, 'xml' );

			if ( empty( $reponse['error'] ) ) {

				if ( isset( $response['autoresponders'] ) && ! empty( $response['autoresponders']['autoresponder'] ) ) {

					//only one list will return a single item instead of an array of lists.
					if ( isset( $response['autoresponders']['autoresponder']['id'] ) ) {

						$name = sprintf(
							'%s',
							$response['autoresponders']['autoresponder']['name']
						);
						if ( $detail ) {
							$lists[$response['autoresponders']['autoresponder']['id']] = array(
								'id'                     => $response['autoresponders']['autoresponder']['id'],
								'name'                   => $name,
								'description'            => $response['autoresponders']['autoresponder']['description'],
								'subscribers'            => '',
								'autoresponderid'        => '',
								'confirmredirecturl'     => '',
								'unsubscriberedirecturl' => '',
							);
						} else {
							$ar_lists[$response['autoresponders']['autoresponder']['id']] = $name;
						}

					} else {

						foreach ( $response['autoresponders']['autoresponder'] as $list ) {

							if ( $list['status'] == 'ok' || $list['status'] == 'paused' ) {

								$name = sprintf(
									'%s',
									$list['name']
								);
								if ( $detail ) {
									$lists[$list['id']] = array(
										'id'                     => $list['id'],
										'name'                   => $name,
										'description'            => $list['description'],
										'subscribers'            => '',
										'autoresponderid'        => '',
										'confirmredirecturl'     => '',
										'unsubscriberedirecturl' => '',
									);
								} else {
									$ar_lists[$list['id']] = $name;
								}

							}

						}

					}

				}

			}

			if ( ! $detail ) {
				natcasesort( $ar_lists );
			}

			if ( ! $detail ) {

				$merge_lists = array(
					__( 'Email Lists'   , '' ) => $lists,
					__( 'Autoresponders', '' ) => $ar_lists,
				);

				$lists = $merge_lists;

			}

		}


		return $lists;

	}

	/**
	 * Gets an array of available lists from the opt-in service.
	 * Lists should be provided in $lists[list-id'] = 'List Name'; format..
	 *
	 * @access protected
	 * @param $detail                      bool   Return a detailed array? Default = false
	 * @param $autoresponders[optional]    bool   Show autoresponders in the returned array? Default = true
	 * @return array()
	 */
	protected function _rm_manage_lists_get_lists( $id, $detail, $autoresponders ) {

		$lists = $this->_get_lists( $detail, $autoresponders );

		if ( ! empty( $id ) && ! empty( $lists[$id] ) ) {
			return $lists[$id];
		}

		return $lists;

	}


	/**
	 * Gets an array of available custom fields from the opt-in service.
	 * Lists should be provided in $fields[field-id'] = 'Field Name'; format..
	 *
	 * @access public
	 * @return array()
	 */
	static function get_remote_fields() {
		return static::get_instance()->_get_remote_fields();
	}

	/**
	 * Gets an array of available custom fields from the opt-in service.
	 * Lists should be provided in $fields[field-id'] = 'Field Name'; format..
	 *
	 * @access protected
	 * @return array()
	 */
	protected function _get_remote_fields() {

		$fields = array();

		$api_url = $this->_get_api_url( 'f.api/fields?key={apikey}&summary=1' );

		$this->_get_http_instance()->set_method( 'GET' );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $reponse['error'] ) ) {

			if ( isset( $response['fields'] ) && ! empty( $response['fields']['field'] ) ) {

				//only one list will return a single item instead of an array of lists.
				if ( isset( $response['fields']['field']['id'] ) ) {

					if ( isset( $response['fields']['field']['status'] ) && 'ok' !== $response['fields']['field']['status'] ) {
						return $fields; //no fields set
					}

					$fields[$response['fields']['field']['id']] = $response['fields']['field']['name'];

				} else {

					foreach ( $response['fields']['field'] as $field ) {

						if ( isset( $field['status'] ) && 'ok' !== $field['status'] ) {
							continue;
						}

						$fields[$field['id']] = $field['name'];

					}

				}

			}

		}

		return $fields;

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
			'email' => __( 'Email Address', '' ),
			'fname' => __( 'First Name'   , '' ),
			'lname' => __( 'Last Name'    , '' ),
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

		foreach( $input_array as $id => $name ) {

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

		$args = array(
			'email'   => $email,
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f?SimpleApiSubscribe&key={apikey}&email={email}&listid={list_id}', $args );

		$response = $this->_get_response( $api_url, 'xml' );

		if( empty( $response['subscriberid'] ) ){
			return array( 'error' => __( 'It seems there was an error subscribing at this time. Please try again.', '' ) );
		}

		return $response['subscriberid'];

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

		$args = array(
			'email'   => $email,
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f/?SimpleApiUnregister&key={apikey}&email={email}&listid={list_id}', $args );

		$response = $this->_get_response( $api_url, 'xml' );

		if( empty( $response['subscriberid'] ) ){
			return array( 'error' => __( 'User could not be unsubscribed.', '' ) );
		}

		/**
		 * Runs after verifying a successful unsubscribe for rm_email service.
		 *
		 * @param  int    $subscriberid the remote ID from Feedblitz
		 * @param  int    $list_id      list the user was subscribed to
		 * @param  string $email        user email address
		 */
		do_action( 'rm_email_unsubscribed', $response['subscriberid'], $list_id, $email );

		return $response['subscriberid'];

	}

	/**
	 * Stub for the finish method that is no longer available.
	 * Returns an error array if called.
	 *
	 * @access public
	 * @static
	 * @param int $list_id
	 * @param int $subscriber_id
	 * @return array error if called
	 */
	static function finish( $list_id, $subscriber_id ) {

		return array( 'error' => __( 'Finished is not an available subscriber status.', '' ) );

	}

	/**
	 * Static function to handle setting a subscriber to deleted for a list
	 *
	 * @uses static::get_instance()->_delete_sub( $list_id, $subscriber_id )
	 *
	 * @access public
	 * @static
	 * @param mixed $list_id
	 * @param mixed $subscriber_id
	 * @return void
	 */
	static function delete_sub( $list_id, $subscriber_id ) {
		return static::get_instance()->_delete_sub( $list_id, $subscriber_id );
	}
	/**
	 * Remove subscriber from list.
	 *
	 * Must return a string with the subscriber_id or array if an error.
	 * Error array format array( 'error' => 'error message' )
	 *
	 * @access protected
	 * @param  string $list_id
	 * @param  string $subscriber_id
	 * @return mixed
	 */
	protected function _delete_sub( $list_id, $subscriber_id ) {

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$args = array(
			'list_id'       => $list_id,
			'subscriber_id' => $subscriber_id,
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}/subscribers/{subscriber_id}/?key={apikey}', $args );

		$body = array(
			'subscribers' => array(
				'subscriber' => array(
					'id'      => $subscriber_id,
					'status'  => 'deleted'
				),
			),
		);

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['rsp']['success'] ) ) {
			return array( 'error' => __( 'User could not be deleted.', '' ) );
		}

		return $subscriber_id;

	}

	/**
	 * Static function used to get a user by email address.
	 * Calls the private _get_user_by_email method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @param string $email
	 * @return void
	 */
	static function get_user_by_email( $email ) {

		return static::get_instance()->_get_user_by_email( $email );

	}

	/**
	 * Searches for the user by provided email address.
	 *
	 * @access protected
	 * @param  string $email
	 * @return array
	 */
	protected function _get_user_by_email( $email ) {

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$body = array(
			'searchpattern' => $email,
		);

		$api_url = $this->_get_api_url( 'f.api/subscribers/search?key={apikey}' );

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['subscribers'] ) || empty( $response['subscribers']['subscriber'] ) || empty( $response['subscribers']['subscriber']['subscription'] ) ) {

			return false;

		}

		return $response['subscribers']['subscriber'];


	}

	/**
	 * Static function used to get a user by subscriber ID.
	 * Calls the private _get_user_by_email method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @param string $email
	 * @return void
	 */
	static function get_user_by_id( $id ) {

		return static::get_instance()->_get_user_by_id( $id );

	}

	/**
	 * Searches for the user by provided email address.
	 *
	 * @access protected
	 * @param  string $email
	 * @return array
	 */
	protected function _get_user_by_id( $id ) {

		$args = array(
			'id' => $id,
		);

		$api_url = $this->_get_api_url( 'f.api/subscribers/{id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml' );

		if ( empty( $response['subscribers'] ) || empty( $response['subscribers']['subscriber'] ) || empty( $response['subscribers']['subscriber']['subscription'] ) ) {

			return false;

		}

		return $response['subscribers']['subscriber'];


	}

	/**
	 * Static function used to get subscribers subscribers for a specific list.
	 * Calls the private _get_all_subscribers method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	static function get_subscribers( $list_id, $start, $step, $since = '' ) {
		return static::get_instance()->_get_subscribers( $list_id, $start, $step, $since  );
	}

	/**
	 * Retrieves subscribers.
	 *
	 * @access protected
	 * @return array
	 */
	protected function _get_subscribers( $list_id, $start, $step, $since = '' ) {

		$args = array(
			'list_id'    => $list_id,
			'limitcount' => $step,
			'limitstart' => $start,
		);

		$api_url = $this->_get_api_url( 'f.api/syndications/{list_id}/subscribers?key={apikey}&limitcount={limitcount}&limitstart={limitstart}&summary=1', $args );

		if ( $since ) {
			$api_url = add_query_arg( 'since', $since, $api_url );
		}

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['subscribers'] ) ) {

			return false;

		}

		return $response['subscribers'];


	}

	/**
	 * Static function used to get all subscribers.
	 * Calls the private _get_all_subscribers method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	static function get_all_subscribers() {

		return static::get_instance()->_get_all_subscribers();

	}

	/**
	 * Retrieves all subscribers.
	 *
	 * @access protected
	 * @return array
	 */
	protected function _get_all_subscribers() { ///subscribers/search

		$api_url = $this->_get_api_url( 'f.api/subscribers/search?key={apikey}' );

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$body = array(
			'searchstatus' => 'ok'
			//<limitstart>250</limitstart>
			//<limitcount>500</limitcount>
		);

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['subscribers'] ) ) {

			return false;

		}

		return $response['subscribers'];


	}

	/**
	 * Static function used to get a user by email address.
	 * Calls the private _get_user_by_email method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @param string $subscriber_id
	 * @return void
	 */
	static function get_custom_fields( $subscriber_id ) {

		return static::get_instance()->_get_custom_fields( $subscriber_id );

	}

	/**
	 * Searches for the user by provided email address.
	 *
	 * @access protected
	 * @param  string $subscriber_id
	 * @return array
	 */
	protected function _get_custom_fields( $subscriber_id ) {

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$args = array(
			'subscriber_id' => $subscriber_id,
		);

		$api_url = $this->_get_api_url( 'f.api/fieldvalues/subscriber/{subscriber_id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'GET' );
		$this->_get_http_instance()->set_timeout( 30 );

		$response = $this->_get_response( $api_url, 'xml');

		if (
			empty( $response['subscribers'] ) ||
			empty( $response['subscribers']['subscriber'] ) ||
			empty( $response['subscribers']['subscriber']['publishervalues'] ) ||
			0 == intval( $response['subscribers']['subscriber']['publishervalues']['count'] )
		) {

			return false;

		}

		return $response['subscribers']['subscriber']['publishervalues']['fieldvalue'];

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
			'first_name' => empty( $_POST['fname'] ) ? $first_name : $_POST['fname'],
			'last_name'  => empty( $_POST['lname'] ) ? $last_name  : $_POST['lname'],
			'email'      => empty( $_POST['email'] ) ? $email      : $_POST['email'],
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
			'first_name' => empty( $component_details['fname'] ) ? $first_name : $component_details['fname'],
			'last_name'  => empty( $component_details['lname'] ) ? $last_name  : $component_details['lname'],
			'email'      => empty( $component_details['email'] ) ? $email      : $component_details['email'],
		);

		return $data;

	}



	/**
	 * Static function used to send a message to the specified list.
	 * Calls the private _newsflash method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @param string $list_id
	 * @param string $subject
	 * @param string $message
	 * @param string $fromname
	 * @param string $fromaddress
	 * @return void
	 */
	static function newsflash( $list_id, $subject, $message, $fromname, $fromaddress, $suppressed_lists = '', $seg_criteria = '' ) {

		return static::get_instance()->_newsflash( $list_id, $subject, $message, $fromname, $fromaddress, $suppressed_lists, $seg_criteria );

	}

	/**
	 * Used to send a message to the specified list.
	 *
	 * @access private
	 * @param string $list_id
	 * @param string $subject
	 * @param string $message
	 * @param string $fromname
	 * @param string $fromaddress
	 * @return void
	 */
	private function _newsflash( $list_id, $subject, $message, $fromname, $fromaddress, $suppressed_lists = '', $seg_criteria = '' ) {

		$text = '';

		if ( is_array( $message ) ) {
			$text    = empty( $message['text'] ) ? $text : $message['text'];
			$message = empty( $message['html'] ) ? $text : $message['html'];
		}

		$fromaddress = empty( $fromaddress ) ? sprintf( 'support@%s', preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] ) ) : $fromaddress;

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$body = array(
			'newsflash' => array(
				'fromname'     => $fromname,
				'fromaddress'  => $fromaddress,
				'subject'      => $subject,
				'message'      => $message,
				'textpart'     => $text,
				'usetemplate'  => 0,
				'seg_criteria' => $seg_criteria,
			)
		);

		if ( $suppressed_lists ) {
			$body['newsflash']['suppressionlistid'] = $suppressed_lists;
		}

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/newsflash/{list_id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'PUT' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if ( empty( $response['newsflash'] ) || empty( $response['newsflash']['mailingid'] ) ) {

			return array( 'error' => __( 'There was a problem sending the newsflash.', '' ) );

		}

		return $response['newsflash']['mailingid'];

	}

	/**
	 * Static function used to send a message to a specific email in the specified list.
	 * Calls the private _sendmail method via the get_instance() method.
	 *
	 * @access public
	 * @static
	 * @param string $list_id
	 * @param string $subject
	 * @param string $message
	 * @param string $recipients
	 * @param string $fromname
	 * @param string $fromaddress
	 * @return void
	 */
	static function sendmail( $list_id, $subject, $message, $recipients, $fromname, $fromaddress ) {

		return static::get_instance()->_sendmail( $list_id, $subject, $message, $recipients, $fromname, $fromaddress );

	}

	/**
	 * Used to send a message to the specified list.
	 *
	 * @access private
	 * @param string $list_id
	 * @param string $subject
	 * @param string $message
	 * @param string $recipients
	 * @param string $fromname
	 * @param string $fromaddress
	 * @param string $suppressed_lists
	 * @return void
	 */
	private function _sendmail( $list_id, $subject, $message, $recipients, $fromname, $fromaddress ) {

		$wrapper = '<?xml version="1.0" encoding="utf-8" ?><feedblitzapi version="1.0" xmlns:xlink="http://www.w3.org/1999/xlink"></feedblitzapi>';

		$body = array(
			'sendmail' => array(
				'fromname'    => $fromname,
				'fromaddress' => $fromaddress,
				'subject'     => $subject,
				'message'     => $message,
				'recipients'  => $recipients,
				'usetemplate' => 0,
			)
		);

		$args = array(
			'list_id' => $list_id,
		);

		$api_url = $this->_get_api_url( 'f.api/sendmail/{list_id}?key={apikey}', $args );

		$this->_get_http_instance()->set_method( 'POST' );
		$this->_get_http_instance()->set_timeout( 60 );

		$response = $this->_get_response( $api_url, 'xml', $body, $wrapper );

		if( empty( $response['sendmail'] ) || empty( $response['sendmail']['mailingid'] ) ) {

			return array( 'error' => __( 'There was a problem sending the email.', '' ) );

		}

		return $reponse['sendmail']['mailingid'];

	}

	/**
	 * Static function used to get the subscriber count.
	 *
	 * @access public
	 * @static
	 * @return int
	 */
	static function get_subscriber_count() {

		return static::get_instance()->_get_subscriber_count();

	}

	/**
	 * Used to get subscriber count.
	 *
	 * @access private
	 * @return int
	 */
	private function _get_subscriber_count() {

		if ( empty( $this->_subscriber_count ) ) {

			if ( $count = get_transient( 'fb_subscriber_count' ) ) {
				$this->_subscriber_count = $count;
			} else {

				$args = array();

				$api_url = $this->_get_api_url( 'f/?SimpleApiSubscriberCount&key={apikey}', $args );

				$this->_get_http_instance()->set_method( 'GET' );
				$this->_get_http_instance()->set_timeout( 60 );

				$response = $this->_get_response( $api_url, 'xml' );

				if ( isset( $response['uniquesubscribers'] ) ) {

					$this->_subscriber_count = (int) $response['uniquesubscribers'];

					set_transient( 'fb_subscriber_count', $this->_subscriber_count, $this->_cache_limit() );


				} else {
					return array( 'error', __( 'There was an error getting the subscriber count', '' ) );
				}

			}

		}

		return $this->_subscriber_count;

	}

	/**
	 *
	 * Returns JSON encoded array of lists detail.
	 * Uses get_lists_detail()
	 *
	 * @return JSON
	 */
	public function _load_email_lists() {

		$lists = $this->get_lists_detail( true, false );

		return json_encode(
			array(
				'status'    => 'ok',
				'admin_url' => get_admin_url( null, 'admin.php?page=rainmail-edit-list' ),
				'lists'     => $lists
			)
		);
	}

	/**
	 * Gets an array of available lists from the opt-in service.
	 * Lists should be provided in $lists[list-id'] = 'List Name'; format.
	 * Output is cached if cache limit is set.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_lists_detail( $detail = true, $autoresponders = false ) {

		$lists = static::get_instance()->_get_lists( $detail, $autoresponders );
		$sort  = array();

		foreach ( $lists as $key => $row ) {
			$sort[$key] = strtolower($row['name']);
		}

		array_multisort( $sort, SORT_ASC, $lists );

		return $lists;

	}

}
