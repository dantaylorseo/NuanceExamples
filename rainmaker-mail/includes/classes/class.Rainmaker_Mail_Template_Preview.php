<?php

class Rainmaker_Mail_Template_Preview {

	/**
	 * Callback on the `wp_ajax_rm_rainmail_template_preview` hook.
	 * Initializes the template preview output.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function preview() {
		new Rainmaker_Mail_Template_Preview;
	}

	/**
	 * The template to display
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	var $template     = '';

	/**
	 * The sidebar content if available.
	 * Does not display with all templates.
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	var $sidebar      = '';

	/**
	 * The logo if available.
	 * May not output with all templates.
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	var $logo         = '';

	/**
	 * The logo/header alter text.
	 * May not output with all templates.
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	var $header_alt   = '';

	/**
	 * The header right copy.
	 * May not output with all templates.
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	var $header_right = '';

	/**
	 * The HTML of the preview.
	 *
	 * @var string
	 * @access public
	 */
	var $template_preview;

	/**
	 * Builds the template preview output.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->template     = isset( $_POST['template']     ) ? $_POST['template']     : '';
		$this->sidebar      = isset( $_POST['sidebar']      ) ? $_POST['sidebar']      : '';
		$this->logo         = isset( $_POST['logo']         ) ? $_POST['logo']         : '';
		$this->header_alt   = isset( $_POST['header_alt']   ) ? $_POST['header_alt']   : '';
		$this->header_right = isset( $_POST['header_right'] ) ? $_POST['header_right'] : '';

		$this->_set();

		die( $this->template_preview );

	}

	/**
	 * Builds the HTML output for the preview and sets it to the $template_preview property.
	 *
	 * @access private
	 * @return void
	 */
	private function _set() {

		$content = wpautop( do_shortcode( $this->_get_template_content() ) );

		$Rainmaker_Mail_Build_Content = new Rainmaker_Mail_Build_Content;

		$args = array(
			'template' => $this->template,
			'logo'     => $this->logo,
			'alt'      => $this->header_alt,
			'tag'      => $this->header_right,
			'sidebar'  => empty( $this->sidebar ) ? $this->_get_template_sidebar() : $this->sidebar,
		);

		$this->template_preview = $Rainmaker_Mail_Build_Content->get_mail_content( $content, $args );

	}

	/**
	 * Returns the sample content copy for the template preview.
	 *
	 * @access private
	 * @return string
	 */
	private function _get_template_content() {
		return file_get_contents( sprintf( '%sassets/sample_copy/content.html', RM_MAIL_DIR ), 'r' );
	}

	/**
	 * Returns the sample sidebar copy for the template preview.
	 *
	 * @access private
	 * @return string
	 */
	private function _get_template_sidebar() {
		return file_get_contents( sprintf( '%sassets/sample_copy/sidebar.html', RM_MAIL_DIR ), 'r' );
	}

}
