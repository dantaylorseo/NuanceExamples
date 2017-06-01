<?php

class Feedblitz {
	public $api_key;
	public $error = FALSE;

	public function __construct( $api_key = NULL ) {
	
		$this->api_key = $api_key;

		if ( empty( $this->api_key ) ) {
			$this->error = new WP_Error( 'invalid-request', __( 'You must provide an API key for your Feedblitz application.', 'Feedblitzwp' ) );
		}
		
	}
	
	
	
	/**
	 * submit_request function.
	 *
	 * Send the API request and get response
	 * 
	 * @access public
	 * @param string $api_url
	 * @param string $json_payload (default: "")
	 * @param string $request_method (default: "GET")
	 * @return response
	 */
	function submit_request( $api_url, $json_payload = "", $request_method = "GET" ) {
	
		// Make sure no error already exists
		if ( $this->error ) {
			return new WP_Error( 'invalid-request', __( 'You must provide an API key for your Feedblitz application.', 'Feedblitzwp' ) );
		}

	
		// REST arguments
		$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		// submit the request
		$response = wp_remote_get( $api_url, $args );

		// Call the function and return any error that happens
		if ( ! $response ) {
			return new WP_Error( 'invalid-request', "ErrorMessage" );
		}


		// Pass the response directly to the user
		return $response;
	}	
	
}

class Feedblitz_Interface {

	public $api = false;
	public $api_key = '';

	public function __construct(){
		
		if( defined( 'FEEDBLITZ_API_KEY' ) ) {
		
			$api_key   = FEEDBLITZ_API_KEY;
			
		} 
		else {
		
			global $Feedblitz_Optin_Gateway;
		
			$api_key = $Feedblitz_Optin_Gateway->get_credentials();
			
		}
		
		if( empty( $api_key ) ){
			$this->api = false;
		}
		else{
			$this->api_key = $api_key;
			$this->api = new Feedblitz( $api_key );
		}
		
	}



	/* 
		METHODS
		
	*/
	
	function add_subscriber( $list_id, $email_address ) {

		$api_key = $this->$api_key;

		$api_url = "https://www.feedblitz.com/f?SimpleApiSubscribe&key=$api_key&email=$email_address&listid=$list_id";

		$response = $this->api->submit_request( $api_url );
		
		return $reponse['subscriptions']['subscription'];
	
	}
	
	
	function email_subscriber( $api_key, $list_id, $recipient, $subject = "EMail subject", $body = "EMail body" ) {
	
	
		$api_url = "https://www.feedblitz.com/f.api/sendmail/$list_id/?key=$api_key";
	
	
	
	
		$json_payload = "
		                <feedblitzapi version=\"1.0\" >
	                                <sendmail>
	                                                <subject>This &amp; that - an API initiated mailing</subject>
	                                                <message>&lt;h1&gt;Headline&lt;/h1&gt;&lt;p&gt;Something or other&lt;/p&gt;</message>
	                                                <fromname>Philbert</fromname>
	                                                <fromaddress>phil@hollows.com</fromaddress>
	                                                <testmode>0</testmode>
	                                                <recipients>$recipient</recipients>
	                                </sendmail>
	                </feedblitzapi>";
		
		$args = array(
						'method'  => "POST",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		print( "<p><a href=\"$api_url\">API call</a></p>" );
		
		$response = wp_remote_post( $api_url, $args );
	
		return $response;
	
	}
	
	
	function feedblitz_optout_subscriber( $list_id, $email_address ) {
	
		$api_key = $this->api_key;
	
		$api_url = "https://www.feedblitz.com/f/?SimpleApiUnregister&key=$api_key&email=$email_address&listid=$list_id";
	
	
	
	
		$json_payload = "";
		
		$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		
		$response = wp_remote_get( $api_url, $args );
		
		return $response;
	
	}
	
	
	function feedblitz_tag_subscriber( $api_key, $email_address, $tag_name, $tag_value ) {
	
		$api_url = "https://www.feedblitz.com/f/?SimpleApiSetFields&key=$api_key&email=$email_address&$tag_name=$tag_value";
	
	
	
	
		$json_payload = "";
		
		$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		
		$response = wp_remote_get( $api_url, $args );
	
		return $response;
	
	}
	
	
	
	

	
	
	// get the list of subscribers
	// for a given list
	function list_subscribers( $feedblitz_api, $list_id ) {
	
		
		$api_url = "https://www.feedblitz.com/f.api/syndications/$list_id/subscribers?key=$feedblitz_api";
		
		print( "<p><a href=\"$api_url\">API call</a></p>" );
		
	
		
		
		$json_payload = "";
		
		$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		
		$response = wp_remote_get( $api_url, $args );
		
		print_r($response);
		return true;
		
		if ( is_wp_error( $response ) )
		{
		
			return "Error getting API key for $member_email <p/>";
		
		
		}
		else
		{
		
			$xml      = simplexml_load_string( $response['body'] );
			$json     = json_encode( $xml );
			$result	  = json_decode( $json, TRUE );
			
			// get back the results
			$rssfeed 	= $result['rssfeed'];
		    $rssfeedid	= $result['rssfeedid'];
		    $api_key	= $result['apikey'];
			
			// create the array
			$feedblitz_info = array(
			
						"rssfeed" 	=> $rssfeed,
						"rssfeedid" => $rssfeedid,
						"apikey"	=> $api_key
			);
			
			// update the user's values
			update_site_option( "feedblitz", $feedblitz_info ) ;
	
		
		}
	
	
		return $feedblitz_info;
	
	}
	
	
	function get_feedblitz_lists() {
		
		$feedblitz_api = $this->api_key;
		
		$api_url = "https://www.feedblitz.com/f.api/syndications?key=$feedblitz_api&summary=1";
	
			$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		// get the response from the API
		$response = wp_remote_get( $api_url, $args );
	
		// extract the xml from the returned response
		$xml_string = $response['body'];
		
		// convert the xml into JSON->Array
		$xml      = simplexml_load_string( $xml_string );
		$json     = json_encode( $xml );
		$result   = json_decode( $json, TRUE );
		
		return $result['syndications']['syndication'];
	
	}
	
	function get_feedblitz_fields( $feedblitz_api, $list_id ) {
	
		$api_url = "https://www.feedblitz.com/f.api/fields?key=$feedblitz_api";
	
			$args = array(
						'method'  => "GET",
						'timeout' => 1000,
						'user-agent' => "Rainmaker",
						'headers' => array( "Content-Type" => "application/xml" ),
						'body'    => $json_payload,
														);
		
		// get the response from the API
		$response = wp_remote_get( $api_url, $args );
	
		// extract the xml from the returned response
		$xml_string = $response['body'];
		
		// convert the xml into JSON->Array
		$xml      = simplexml_load_string( $xml_string );
		$json     = json_encode( $xml );
		$result   = json_decode( $json, TRUE );
		
		print( "<p><a href=\"$api_url\">API call</a></p>" );
		
		return $result;
	
	}
		
}




global $Feedblitz_interface;

$Feedblitz_interface = new Feedblitz_Interface;
