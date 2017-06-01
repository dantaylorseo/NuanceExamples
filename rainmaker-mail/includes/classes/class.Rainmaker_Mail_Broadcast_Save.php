<?php

class Rainmaker_Mail_Broadcast_Save {

	/**
	 * The id of the formula being saved.
	 *
	 * @var int
	 * @access private
	 */
	private $_post_id;

	/**
	 * The post object of the formula being saved.
	 *
	 * @var object
	 * @access private
	 */
	private $_post;

	/**
	 * __construct function.
	 * Sets the post ID and post object class variables.
	 * calls the save methods.
	 *
	 * @access public
	 * @param int $post_id
	 * @param object $post
	 * @return void
	 */
	function __construct( $post_id, $post ) {

		$this->_post_id = $post_id;
		$this->_post    = $post;

		$keys = array(
			'_sender_name'                 => 'no_html',
			'_sender_email'                => 'no_html',
			'_email_recipient_list'        => 'no_html_array',
			'_email_suppression_list'      => 'no_html_array',
			'_email_template'              => 'no_html',
			'_email_recipient_tagged_with' => 'no_html_array',
			'_email_recipient_not_tagged'  => 'no_html_array',
		);

		foreach ( $keys as $key => $sanitize ) {

			$value = isset( $_POST[$key] ) ?  $_POST[$key] : '';

			$value = $this->_sanitize_input( $value, $sanitize );

			$this->_save_data( $key, $value );

		}

	}

	/**
	 * Saves the meta data to the formula meta.
	 *
	 * @access private
	 * @param string $field
	 * @param string $data
	 * @return void
	 */
	private function _save_data( $field, $data ) {

		update_post_meta( $this->_post_id, $field, $data );

	}

	/**
	 * Uses the Genesis Sanitize object to ensure all inputs are sanitized.
	 *
	 * @access private
	 * @param  string $value
	 * @param  string $sanitize
	 * @return string
	 */
	private function _sanitize_input( $value, $sanitize ) {

		switch( $sanitize ) {

		case 'one_zero':
			return Genesis_Settings_Sanitizer::$instance->one_zero( $value );
			break;

		case 'absint':
			return Genesis_Settings_Sanitizer::$instance->absint( $value );
			break;

		case 'url':
			return Genesis_Settings_Sanitizer::$instance->url( $value );
			break;

		case 'email_address':
			return Genesis_Settings_Sanitizer::$instance->email_address( $value );
			break;

		case 'safe_html':
			return Genesis_Settings_Sanitizer::$instance->safe_html( $value );
			break;

		case 'no_html_array':
			return $this->_no_html_array( $value );
			break;

		case 'no_html':
		default:
			return Genesis_Settings_Sanitizer::$instance->no_html( $value );
			break;

		}

	}

	/**
	 * Handles array inputs recursivly
	 * and passes keys and values through $this->_sanitize_input() to ensure all content is sanitized.
	 *
	 * @access private
	 * @param  array $array
	 * @return array
	 */
	private function _no_html_array( $array ) {

		$array     = (array) $array;
		$new_array = array();

		foreach ( $array as $key => $value ) {

			$key = $this->_sanitize_input( $key, 'no_html' );

			$value = is_array( $value ) ? $this->_no_html_array( $value ) : $this->_sanitize_input( $value, 'no_html' );

			$new_array[$key] = $value;

		}

		return $new_array;

	}


}
