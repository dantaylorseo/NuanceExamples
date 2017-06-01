<?php

class Rainmaker_Mail_Mailer {

	/**
	 * Post ID for the published broadcast.
	 *
	 * @var int
	 * @access public
	 */
	var $post_id;

	/**
	 * Post object for the published broadcast.
	 *
	 * @var object
	 * @access public
	 */
	var $post;

	/**
	 * Who the current broadcast is from.
	 *
	 * @var string
	 * @access public
	 */
	var $fromname;

	/**
	 * Email address the current broadcast is from.
	 *
	 * @var string
	 * @access public
	 */
	var $fromaddress;

	/**
	 * Lists that should receive the current broadcast.
	 *
	 * @var array
	 * @access public
	 */
	var $lists;

	/**
	 * Lists that should not receive the current broadcast.
	 *
	 * @var array
	 * @access public
	 */
	var $supressed_lists;

	/**
	 * Custom fields that should receive the current broadcast.
	 *
	 * @var array
	 * @access public
	 */
	var $tags;

	/**
	 * Custom fields that should not receive the current broadcast.
	 *
	 * @var array
	 * @access public
	 */
	var $not_tagged;

	/**
	 * The segment criteria field for controlling who receives the email broadcast.
	 *
	 * @var mixed
	 * @access public
	 */
	var $seg_criteria = '';

	/**
	 * Message to be sent in the current broadcast.
	 *
	 * @var string
	 * @access public
	 */
	var $message;

	/**
	 * Subject of the current broadcast.
	 *
	 * @var string
	 * @access public
	 */
	var $subject;

	/**
	 * Template for the current broadcast.
	 *
	 * @var string
	 * @access public
	 */
	var $template;

	/**
	 * Stored responses from email broadcasts.
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access public
	 */
	var $mail_ids = array();

	/**
	 * Callback function on the `publish_mail` action.
	 * Instanstiates the Rainmaker_Mail_Mailer class.
	 *
	 * @access public
	 * @static
	 * @param int    $post_id
	 * @param object $post
	 * @return void
	 */
	static function publish( $post_id, $post ) {

		if ( exceeds_subscriber_limit() ) {
			return;
		}

		new Rainmaker_Mail_Mailer( $post_id, $post );

	}

	/**
	 * __construct function.
	 * Sets the object properties and calls the set_formated_message() and send_emails() methods.
	 *
	 * @access public
	 * @param int    $post_id
	 * @param object $post
	 * @return void
	 */
	function __construct( $post_id, $post ) {

		if ( get_post_meta( $post_id, '_broadcast_message_ids', true ) ) {
			return;
		}

		Rainmaker_Mail_Build_Content::get_instance()->unset_shortcodes();

		$message = $post->post_content;
		$message = do_shortcode( wpautop( $message ) );

		Rainmaker_Mail_Build_Content::get_instance()->add_shortcodes();

		$this->post_id         = $post_id;
		$this->post            = $post;
		$this->message         = $message;
		$this->subject         = do_shortcode( $post->post_title );
		$this->fromname        = get_post_meta( $post_id, '_sender_name'                , true );
		$this->fromaddress     = get_post_meta( $post_id, '_sender_email'               , true );
		$this->lists           = get_post_meta( $post_id, '_email_recipient_list'       , true );
		$this->supressed_lists = get_post_meta( $post_id, '_email_suppression_list'     , true );
		$this->tags            = get_post_meta( $post_id, '_email_recipient_tagged_with', true );
		$this->not_tagged      = get_post_meta( $post_id, '_email_recipient_not_tagged' , true );
		$this->template        = $this->_get_template();

		$this->fromname        = empty( $this->fromname    ) ? accesspress_get_option( 'email_receipt_name'    ) : $this->fromname;
		$this->fromaddress     = empty( $this->fromaddress ) ? accesspress_get_option( 'email_receipt_address' ) : $this->fromaddress;

		if ( empty( $this->lists ) ) {
			return;
		}

		$this->set_formated_message();
		$this->set_seg_criteria();
		$this->send_emails();
		$this->save_response();

		Rainmaker_Mail_Build_Content::get_instance()->reset_shortcodes();

	}

	/**
	 * Gets the template option or uses the default template.
	 *
	 * @access private
	 * @return string
	 */
	private function _get_template() {

		$template = get_post_meta( $this->post_id, '_email_template', true );

		if ( empty( $template ) ) {
			$template = Rainmaker_Mail_Template_Option::get();
		}

		return $template;

	}

	/**
	 * Uses the Rainmaker_Mail_Build_Content get_mail_content() method to get the formated content in the template.
	 *
	 * @access public
	 * @return void
	 */
	function set_formated_message() {

		$unformated_message = $this->message;

		$this->message = Rainmaker_Mail_Build_Content::get_instance()->get_mail_content( $this->message, array( 'template' => $this->template ) );

		$this->message = do_shortcode( $this->message );

		if ( do_shortcode( $unformated_message ) !== $this->message ) {
			$this->message = array(
				'html' => $this->message,
				'text' => do_shortcode( strip_tags( $unformated_message ) ),
			);
		}

	}

	/**
	 * Processes the $tags and $not_tagged properties
	 * then sets the $seg_criteria property if there are tags.
	 *
	 * @access public
	 * @return void
	 */
	function set_seg_criteria() {

		if ( empty( $this->tags ) && empty( $this->not_tagged ) ) {
			return;
		}

		if ( ! empty( $this->tags ) ) {

			$tags = array_keys( $this->tags );

			foreach ( $tags as $tag ) {

				if ( empty( $tag ) || '0' == $tag ) {
					continue;
				}

				$and = empty( $this->seg_criteria ) ? '' : ' AND ';

				$this->seg_criteria .= sprintf( '%s`%s` > 0', $and, $tag );

			}

		}

		if ( ! empty( $this->not_tagged ) ) {

			$tags = array_keys( $this->not_tagged );

			foreach ( $tags as $tag ) {

				if ( empty( $tag ) || '0' == $tag ) {
					continue;
				}

				$and = empty( $this->seg_criteria ) ? '' : ' AND ';

				$this->seg_criteria .= sprintf( '%s`%s` == 0', $and, $tag );

			}

		}

	}

	/**
	 * Uses the Rainmaker_Opt_In_Gateway_FeedBlitz::newsflash() method
	 * to send the message to each of the supplied lists.
	 *
	 * @access public
	 * @return void
	 */
	function send_emails() {

		$this->supressed_lists = implode( '+,', array_keys( $this->supressed_lists ) );

		if ( $this->supressed_lists ) {
			$this->supressed_lists = sprintf( '%s+', $this->supressed_lists );
		}

		foreach ( $this->lists as $list => $value ) {

			$this->supressed_lists = preg_replace( '/(^0,?)/', '', $this->supressed_lists );

			$this->mail_ids[$list] = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::newsflash( $list, $this->subject, $this->message, $this->fromname, $this->fromaddress, $this->supressed_lists, $this->seg_criteria );

			$this->supressed_lists .= empty( $this->supressed_lists ) ? sprintf( '%s+', $list ) : sprintf( ',%s+', $list );

		}

	}

	/**
	 * Saves the response from the broadcast to the.
	 *
	 * @access public
	 * @return void
	 */
	function save_response() {

		$mail_ids = array();

		$errors   = array();

		foreach ( $this->mail_ids as $list => $response ) {

			if ( is_array( $response ) ) {
				$errors[$list] = $response['error'];
			} else {
				$mail_ids[$list] = $response;
			}

		}

		if ( ! empty( $mail_ids ) ) {

			update_post_meta( $this->post_id, '_broadcast_message_ids', $mail_ids );

			$time = current_time('mysql');

			wp_update_post(
				array (
					'ID'            => $this->post_id, // ID of the post to update
					'post_date'     => $time,
					'post_date_gmt' => get_gmt_from_date( $time )
				)
			);

		}

	}

}
