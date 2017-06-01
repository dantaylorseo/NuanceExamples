<?php

class Rainmaker_Mail_Subscribers {

	/**
	 * The columns used for the edit table
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $_columns = array();

	/**
	 * Stores the posts that have been processed previously
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $_posts   = array();

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Edit_Table` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Subscribers` instance of this class.
	 *
	 * @return object Singleton The `Rainmaker_Mail_Subscribers` instance.
	 */
	protected static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Handles custom table output for the subscribers `mail` screen..
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct() {

		$this->_columns = array(
			'name'    => __( 'Name'  , '' ),
			'lists'   => __( 'Email Lists'   , '' ),
			'subdate' => __( 'Subscription Date'  , '' ),
		);

	}

	/**
	 * Callback on the `manage_mail_posts_columns` filter.
	 *
	 * @access public
	 * @static
	 * @param  array $post_columns
	 * @return array get_instance()->_posts_columns( $post_columns )
	 */
	static function posts_columns( $post_columns ) {

		return static::get_instance()->_posts_columns( $post_columns );

	}

	/**
	 * Modifies the columns in the subscribers edit lists table..
	 *
	 * @access private
	 * @param  array $post_columns
	 * @return array
	 */
	private function _posts_columns( $post_columns ) {

		$post_columns['title'] = __( 'Email Address', '' );

		$post_columns = array_slice( $post_columns, 0, 2, true ) + $this->_columns + array_slice( $post_columns, 2, count( $post_columns )-2, true );

		unset( $post_columns['date'] );

		return $post_columns;

	}

	/**
	 * Callback on the `manage_subscribers_posts_custom_column` action.
	 * Invokes static::get_instance()->_custom_column( $column_id, $post_id )
	 *
	 * @access public
	 * @static
	 * @param string $column_id
	 * @param int $post_id
	 * @return void
	 */
	static function custom_column( $column_id, $post_id ) {

		static::get_instance()->_custom_column( $column_id, $post_id );

	}

	/**
	 * Checks to see if the current column is handled by this object.
	 * Then invokes the appriate column method.
	 *
	 * @access private
	 * @param  string $column_id
	 * @param  int $post_id
	 * @return void
	 */
	private function _custom_column( $column_id, $post_id ) {

		$data  = get_post_meta( $post_id );

		if ( 'name' === $column_id ) {
			$this->get_name( $data );
		}

		if ( 'lists' === $column_id ) {
			$this->get_lists( $post_id );
		}

		if ( 'subdate' === $column_id ) {
			echo get_the_date( 'M d, Y', $post_id );
		}

	}

	/**
	 * Outputs the name of the subscriber.
	 *
	 * @access public
	 * @param  array $data
	 * @return void
	 */
	private function get_name( $data ) {

		$output  = ! empty( $data['firstname'] ) ? $data['firstname'][0] : '';
		$output .= ! empty( $data['lastname'] ) ? ' '.$data['lastname'][0] : '';

		if ( ! empty( $output ) ) {
			echo $output;
		} else {
			echo '&dash;';
		}

	}

	/**
	 * Outputs the lists the subscriber is a member of.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return void
	 */
	private function get_lists( $post_id ) {

		$output = '';

		if ( $lists = get_post_meta( $post_id, '_rm_subscriber_lists', true ) ) {
			foreach ( $lists as $id=>$list ) {
				$output .= $list['name'].'<br>';
			}
		}

		if ( ! empty( $output ) ) {
			echo $output;
		} else {
			echo '&dash;';
		}

	}

}