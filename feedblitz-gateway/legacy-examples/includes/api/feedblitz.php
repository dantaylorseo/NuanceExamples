<?php


	
	/**
	 * Add lead function
	 * 
	 * @access public
	 * @param  $attributes array key pair values of the lead attributes to be set
	 * @return contact 
	 */
	function add_lead( $attributes = array() ) {
	
		if( $this->api === false ){
			return false;
		}
		
		// Add a contact using the ContactService.add method
		$contact = $this->api->contact( 'addWithDupCheck', $attributes, 'Email' );
		
		// Check whether or not the API returned an error
		if ( ! is_wp_error( $contact ) ) {
		    // The $contact variable contains the return value as specified in the documentation
		    return $contact;
		} else {
		    // There was an error, which uses the WP_Error class
		    echo 'There was an error adding the lead: ' . $contact->get_error_message();
		    exit;
		}
		
	}
	
	/**
	 * Update lead function
	 * 
	 * @access public
	 * @param  lead_id     string the lead
	 * @param  $attributes array  key pair values of the lead attributes to be set
	 * @return contact 
	 */
	function update_lead( $lead_id = false, $attributes = array() ) {
	
		if( $this->api === false ){
			return false;
		}
		
		// Update a contact using the ContactService.update method
		$response = $this->api->contact( 'update', (int) $lead_id, $attributes );
		
		// Check whether or not the API returned an error
		if ( ! is_wp_error( $response ) ) {
		    // The $$response variable contains the return value as specified in the documentation
		    return $response;
		} else {
		    // There was an error, which uses the WP_Error class
		    echo 'There was an error adding the lead: ' . $response->get_error_message();
		    exit;
		}
		
	}
	
	/**
	 * Add Lead to group function
	 * 
	 * @access public
	 * @param  lead_id string the lead
	 * @param  $tag_id string the tag
	 * @return contact 
	 */
	function tag_lead( $lead_id = false, $tag_id = '' ) {
	
		if( $this->api === false ){
			return false;
		}
		
		// Update a contact using the ContactService.update method
		$response = $this->api->contact( 'addToGroup', (int) $lead_id, (int) $tag_id );
		
		// Check whether or not the API returned an error
		if ( ! is_wp_error( $response ) ) {
		    // The $$response variable contains the return value as specified in the documentation
		    return $response;
		} else {
		    // There was an error, which uses the WP_Error class
		    echo 'There was an error adding the tag for the lead: ' . $response->get_error_message();
		    exit;
		}
		
	}
	
	/**
	 * find_lead function.
	 * 
	 * @access public
	 * @param  $email_address
	 * @return contact 
	 */
	function find_lead( $email_address = '' ) {
	
		if( $this->api === false ){
			return false;
		}
		
		// the fields we want returned
		$return_fields = array('Id', 'FirstName', 'LastName', 'Email');
		
		// Find a contact using the ContactService.findByEmail method
		$contact = $this->api->contact( 'findByEmail', $email_address, $return_fields );
		
		// Check whether or not the API returned an error
		if ( ! is_wp_error( $contact ) ) {    
		    return $contact;
		} else {
		    // There was an error, which uses the WP_Error class
		    echo 'There was an error finding the lead: ' . $contact->get_error_message();
		    exit;
		}
		
	}
	
	/**
	 * query_groups function.
	 * 
	 * Get a list of all the contact tags in the db
	 * Use this to show a list of tags for the user
	 * to pick from
	 * 
	 * @return array
	 */
	function query_groups() {
	
		if( $this->api === false ){
			return false;
		}
		
		$return_fields = array('Id', 'GroupName');
		
		// Find a contact using the ContactService.findByEmail method
		$group_list = $this->api->data( 'query', 'ContactGroup', 1000, 0, array('Id' => '%'), $return_fields );
		
		// Check whether or not the API returned an error
		if ( ! is_wp_error( $group_list ) ) {    
		    return $group_list;
		} else {
		    // There was an error, which uses the WP_Error class
		    echo 'There was an error: ' . $group_list->get_error_message();
		    exit;
		}
		
	}
	

