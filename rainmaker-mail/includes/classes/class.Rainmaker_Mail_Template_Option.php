<?php

class Rainmaker_Mail_Template_Option {

	private $_option;

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Template_Option` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Template_Option` instance of this class.
	 *
	 * @return object Singleton The `Rainmaker_Mail_Template_Option` instance.
	 */
	protected static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Gets the default template option.
	 * Only one public method static::get().
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Gets the default template option.
	 *
	 * @access public
	 * @static
	 * @param  boolean $actual indicates to return the actual value instead of processed.
	 * @return string
	 */
	static function get( $actual = false ) {

		return Rainmaker_Mail_Template_Option::get_instance()->_get( $actual );

	}

	/**
	 * Sets the $_option value if required.
	 * Returns the _process() method.
	 *
	 * @access private
	 * @param  boolean $actual
	 * @return string
	 */
	private function _get( $actual = false ) {

		if ( empty( $this->_option ) ) {
			$this->_set();
		}

		return $this->_process( $actual );

	}

	/**
	 * Sets the $_option property.
	 * If the rm_lp_get_option( 'default_email_template' ) value is empty
	 * it will add the value using the _add() method.
	 *
	 * @access private
	 * @return void
	 */
	private function _set() {

		$this->_option = rm_lp_get_option( 'default_email_template' );

		if ( empty( $this->_option ) ) {
			$this->_add();
		}

	}

	/**
	 * Adds the appropriate default template option.
	 * If the account predates the `new_rainmail_1_8` option the default is `plain`
	 * otherwise it is set to `basic`.
	 *
	 * @access private
	 * @return void
	 */
	private function _add() {

		if ( get_option( 'new_rainmail_1_8' ) ) {
			$this->_option = 'basic';
		} else {
			$this->_option = 'plain';
		}

		$options = (array) get_option( '_rm_lp_settings' );

		$options['default_email_template'] = $this->_option;

		update_option( 'default_email_template', $options );

	}

	/**
	 * Processes the return value.
	 * If the value is `plain` it returns a null value..
	 *
	 * @access private
	 * @param  boolean $actual
	 * @return string
	 */
	private function _process( $actual ) {

		return ( 'plain' === $this->_option && empty( $actual ) ) ? '' : $this->_option;

	}



}
