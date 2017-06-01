<?php

class Rainmaker_Mail_AJAX {

	/**
	 * ID of the broadcast being previewed
	 *
	 * @var string
	 * @access private
	 */
	private $_post_id;

	/**
	 * Selected template for the email being tested
	 *
	 * @var string
	 * @access private
	 */
	private $_template;

	/**
	 * Sender Name for the test email
	 *
	 * @var string
	 * @access private
	 */
	private $_fromname;

	/**
	 * Sender Email for the test email
	 *
	 * @var string
	 * @access private
	 */
	private $_fromaddress;

	/**
	 * The lists the broadcast should be sent to
	 *
	 * @var array
	 * @access private
	 */
	private $_lists = array();

	/**
	 * Recipients for the test email
	 *
	 * @var string
	 * @access private
	 */
	private $_recipient;

	/**
	 * Subject of the email being previewed
	 *
	 * @var string
	 * @access private
	 */
	private $_subject;

	/**
	 * Message being previewed
	 *
	 * @var string
	 * @access private
	 */
	private $_message;

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Settings` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Settings` instance of this class.
	 *
	 * @return object Singleton The `Rainmaker_Mail_Settings` instance.
	 */
	protected static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Callback function on `wp_ajax_mail_preview` action.
	 * Instantiates the object and calls the _preview() method.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function preview() {

		Rainmaker_Mail_AJAX::get_instance()->_preview();

		die();

	}

	/**
	 * Callback function on the `rm_lp_pre_get_option_default_email_template` filter.
	 * Sets the template to the broadcast selected template if available.
	 *
	 * @access public
	 * @static
	 * @param  string $opt
	 * @return string
	 */
	static function template_filter( $opt ) {

		$template = Rainmaker_Mail_AJAX::get_instance()->_template;

		if ( $template ){
			return $template;
		}

		return $opt;

	}

	/**
	 * Sets up the object properties for the message being previewed
	 * and does a switch to see if this should send an email preview or display preview.
	 *
	 * @access private
	 * @return void
	 */
	private function _preview() {

		$this->_post_id     = isset( $_POST['post_id']      ) ? $_POST['post_id']                              : '';
		$this->_template    = isset( $_POST['template']     ) ? $_POST['template']                             : '';
		$this->_template    = empty( $this->_template       ) ? Rainmaker_Mail_Template_Option::get()          : $this->_template;
		$this->_lists       = isset( $_POST['lists']        ) ? json_decode( stripslashes( $_POST['lists'] ) ) : array();
		$this->_recipient   = isset( $_POST['recipients']   ) ? $_POST['recipients']                           : '';
		$this->_fromname    = isset( $_POST['sender_name']  ) ? $_POST['sender_name']                          : '';
		$this->_fromaddress = isset( $_POST['sender_email'] ) ? $_POST['sender_email']                         : '';
		$this->_subject     = isset( $_POST['subject']      ) ? stripslashes( $_POST['subject'] )              : '';
		$this->_message     = isset( $_POST['message']      ) ? stripslashes( $_POST['message'] )              : '';

		$type = isset( $_POST['type'] )  ? $_POST['type']  : '';

		switch( $type ) {

		case 'display':
			$this->_display_preview();
			break;

		case 'email':
			$this->_send_preview();
			break;

		}

	}

	/**
	 * Gets the formated preview then displays it using die().
	 *
	 * @access private
	 * @return void
	 */
	private function _display_preview() {

		$preview = Rainmaker_Mail_Build_Content::get_instance()->get_mail_content( $this->_message, array( 'template' => $this->_template, 'do_shortcodes' => false, ) );

		die( $preview );

	}

	/**
	 * Send the preview email.
	 * Uses the rm_lp_pre_get_option_default_email_template filter to set the templae
	 * Dies with succes ( 1 ) or 'error' message.
	 *
	 * @access private
	 * @return void
	 */
	private function _send_preview() {

		add_filter( 'rm_lp_pre_get_option_default_email_template', array( $this, 'template_filter' ) );

		$send_by_service = $this->_send_by_service();

		if ( ! empty( $send_by_service ) ){

			$preview = Rainmaker_Mail_Build_Content::get_instance()->get_mail_content( $this->_message, array( 'template' => $this->_template ) );

			$results = array();

			foreach ( $send_by_service as $list => $recipients ) {

				$recipient = implode( ',', $recipients );

				$results[] = Rainmaker_Opt_In_Gateway_FeedBlitz::sendmail( $list, sprintf( '[test] %s', $this->_subject ), $preview, $recipient, $this->_fromname, $this->_fromaddress );

			}

			foreach ( $results as $result ) {

				if ( ! is_array( $result ) ) {

					$success = true;

				}

			}

		}

		if ( ! empty( $this->_recipient ) ) {

			$headers = ( ! empty( $this->_sender_name ) && ! empty( $this->_sender_email ) ) ? sprintf( 'From: %s <%s>%s', $this->_sender_name, $this->_sender_email, "\r\n" ) : '';

			$success = wp_mail( $this->_recipient, sprintf( '[test] %s', $this->_subject ), $this->_message, $headers );
		}

		if ( ! empty( $success ) ){
			die( $success );
		}

		die('error');
	}

	/**
	 * Checks to see if we can send mail to email address via feedblitz
	 * then builds a list of email addresses that can receive the list.
	 * Otherwise the email address will go back into the $_recipient property
	 * to be sent by wp_mail().
	 *
	 * @access private
	 * @return array
	 */
	private function _send_by_service() {

		if ( exceeds_subscriber_limit() ) {
			return false;
		}

		if ( ! is_array( $this->_recipient ) ) {

			$this->_recipient = explode( ',', $this->_recipient );

		}

		$lists   = array();
		$wp_mail = array();

		foreach ( $this->_recipient as $recipient ) {

			$recipient = trim( $recipient );

			$subscriber = Rainmaker_Opt_In_Gateway_FeedBlitz::get_user_by_email( $recipient );

			if ( $subscriber ) {

				$list = '';

				if ( isset( $subscriber['subscription'][0] ) ) {
					foreach ( $subscriber['subscription'] as $subscription ) {

						if ( 'ok' === $subscription['status'] && 'ok' === $subscription['liststatus'] && in_array( $subscription['id'], $this->_lists ) ) {

							$list = $subscription['id'];
							break 1;

						}

					}
				} elseif ( 'ok' === $subscriber['subscription']['status'] && 'ok' === $subscriber['subscription']['liststatus'] && in_array( $subscriber['subscription']['id'], $this->_lists ) ) {

					$list = $subscriber['subscription']['id'];

				}

				if ( $list ) {

					$lists[$list][] = $recipient;

				} else {

					$wp_mail[] = $recipient;

				}

			} else {

				$wp_mail[] = $recipient;

			}

		}

		$this->_recipient = $wp_mail;

		return $lists;

	}

}
