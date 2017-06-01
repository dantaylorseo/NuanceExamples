<?php

class Rainmaker_Mail_Build_Content {

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Build_Content` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Build_Content` instance of this class.
	 *
	 * @return object Singleton The `Rainmaker_Mail_Build_Content` instance.
	 */
	static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Callback on the `wp_mail` filter.
	 * Alters the mail content.
	 *
	 * @uses Rainmaker_Mail_Build_Content::get_instance()
	 * @uses Rainmaker_Mail_Build_Content->get_mail_content()
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @return void
	 */
	static function wp_mail( $atts ) {

		$atts['message'] = Rainmaker_Mail_Build_Content::get_instance()->get_mail_content( $atts['message'] );

		return $atts;

	}

	/**
	 * Callback on the `wp_mail_content_type` filter.
	 * Sets the content type to text/html
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	static function set_html_content_type() {

		return 'text/html';

	}

	/**
	 * Creates the formated content for email from the email template.
	 *
	 * @access public
	 * @param string $content
	 * @param array $args (default: array(
	 * 'template' => Rainmaker_Mail_Template_Option::get(                 ),
	 * 'logo'     => rm_lp_get_option( 'email_template_logo'              ),
	 * 'alt'      => rm_lp_get_option( 'email_template_header_text'       ),
	 * 'tag'      => rm_lp_get_option( 'email_template_header_right_text' ),
	 * 'sidebar'  => rm_lp_get_option( 'email_template_sidebar'           ),
	 * ))
	 * @return string
	 */
	public function get_mail_content( $content, $args = array() ) {

		$args = wp_parse_args( $args, array(
				'template'      => Rainmaker_Mail_Template_Option::get(                 ),
				'logo'          => rm_lp_get_option( 'email_template_logo'              ),
				'alt'           => rm_lp_get_option( 'email_template_header_text'       ),
				'tag'           => rm_lp_get_option( 'email_template_header_right_text' ),
				'sidebar'       => rm_lp_get_option( 'email_template_sidebar'           ),
				'do_shortcodes' => true,
			) );

		$template = ( 'plain' == $args['template'] ) ? '' : $args['template'];

		if ( empty( $template ) ) {

			return $content;

		}

		$template_markup = ( 'custom' == $template ) ? rm_lp_get_option( 'custom_email_template' ) : file_get_contents( sprintf( '%sassets/templates/%s.html', RM_MAIL_DIR, $template ), 'r' );

		if ( empty( $template_markup ) || false === strpos( $template_markup, '{{content}}' ) ) {

			return $content;

		}

		add_filter( 'wp_mail_content_type', array( 'Rainmaker_Mail_Build_Content', 'set_html_content_type' ) );

		$this->unset_shortcodes();

		if ( $args['do_shortcodes'] ) {
			$this->add_shortcodes();
		}

		$content = wpautop( $this->_convert_links( $content         ) );
		$sidebar = wpautop( $this->_convert_links( $args['sidebar'] ) );

		$logo = $this->_get_logo( $args['logo'], $args['alt'] );

		$search  = array( '{{logo}}', '{{tag}}'    , '{{content}}', '{{sidebar}}' );
		$replace = array( $logo     ,  $args['tag'], $content     , $sidebar      );

		$content = str_replace( $search, $replace, $template_markup );

		$content = $this->inline_css( $content );

		$content = do_shortcode( $content );

		$this->reset_shortcodes();

		return $content;

	}

	/**
	 * Gets the logo.
	 * Returns a <img> HTML or heading HTML
	 *
	 * @access private
	 * @param  string $logo
	 * @param  string $alt
	 * @return string
	 */
	private function _get_logo( $logo, $alt ) {

		$alt = empty( $alt ) ? get_bloginfo( 'name' ) : $alt;

		if( $logo ) {

			$logo = sprintf( '<img src="%s" alt="%s" />', $logo, $alt );

		} else {

			$logo = sprintf( '<h1>%s</h1>', $alt );

		}

		return $logo;

	}

	/**
	 * Converts any plain URLs or URLs inside <> tags into links
	 * without double converting them.
	 *
	 * @access private
	 * @param  string $content
	 * @return string
	 */
	private function _convert_links( $content ) {

		$content = preg_replace( '@<((?:http)s?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)>@', '<a href="$1">$1</a>', $content );

		return $content;

	}

	/**
	 * Converts declared CSS to use inline styles.
	 *
	 * @access public
	 * @param  string $html
	 * @return string
	 */
	public function inline_css( $html ) {

		try {

			require_once( RM_MAIL_CLASSES . '/css-to-inline-styles/css-to-inline-styles.php' );

			// create instance
			$cssToInlineStyles = get_css_to_inline_styles_object();

			//$cssToInlineStyles->setHTML( $html );
			//$cssToInlineStyles->setUseInlineStylesBlock();
			//$cssToInlineStyles->setExcludeMediaQueries();
			// output
			$html = $cssToInlineStyles->convert( $html );

		} catch ( Exception $e ) {

			echo 'Caught exception: ',  $e->getMessage(), "\n";

		}

		return $html;

	}

	/**
	 * Removes the original shortcodes..
	 *
	 * @access public
	 * @return void
	 */
	function unset_shortcodes() {

		remove_shortcode( 'firstname'     );
		remove_shortcode( 'lastname'      );
		remove_shortcode( 'custom_field'  );
		remove_shortcode( 'unique_id'     );
		remove_shortcode( 'show_to_tag'   );
		remove_shortcode( 'show-to-tag'   );
		remove_shortcode( 'hide_from_tag' );
		remove_shortcode( 'hide-from-tag' );

	}

	/**
	 * Adds shortcodes for the logic used in the mailings
	 *
	 * @access public
	 * @return void
	 */
	function add_shortcodes() {

		add_shortcode( 'firstname'    , array( $this, 'firstname'     ) );
		add_shortcode( 'lastname'     , array( $this, 'lastname'      ) );
		add_shortcode( 'custom_field' , array( $this, 'custom_field'  ) );
		add_shortcode( 'unique_id'    , array( $this, 'unique_id'     ) );
		add_shortcode( 'show_to_tag'  , array( $this, 'show_to_tag'   ) );
		add_shortcode( 'show-to-tag'  , array( $this, 'show_to_tag'   ) );
		add_shortcode( 'hide_from_tag', array( $this, 'hide_from_tag' ) );
		add_shortcode( 'hide-from-tag', array( $this, 'hide_from_tag' ) );

	}

	/**
	 * Removes the shortcodes for the logic used in the mailings.
	 * adds the original shortcodes back.
	 *
	 * @access public
	 * @return void
	 */
	function reset_shortcodes() {

		remove_shortcode( 'firstname'     );
		remove_shortcode( 'lastname'      );
		remove_shortcode( 'custom_field'  );
		remove_shortcode( 'unique_id'     );
		remove_shortcode( 'show_to_tag'   );
		remove_shortcode( 'show-to-tag'   );
		remove_shortcode( 'hide_from_tag' );
		remove_shortcode( 'hide-from-tag' );

		add_shortcode( 'firstname'    , array( 'Rainmaker_Subscriber_Shortcodes', 'firstname'     ) );
		add_shortcode( 'lastname'     , array( 'Rainmaker_Subscriber_Shortcodes', 'lastname'      ) );
		add_shortcode( 'custom_field' , array( 'Rainmaker_Subscriber_Shortcodes', 'custom_field'  ) );
		add_shortcode( 'unique_id'    , array( 'Rainmaker_Subscriber_Shortcodes', 'unique_id'     ) );
		add_shortcode( 'show_to_tag'  , array( 'Rainmaker_Tagging_Shortcodes'   , 'show_to_tag'   ) );
		add_shortcode( 'show-to-tag'  , array( 'Rainmaker_Tagging_Shortcodes'   , 'show_to_tag'   ) );
		add_shortcode( 'hide_from_tag', array( 'Rainmaker_Tagging_Shortcodes'   , 'hide_from_tag' ) );
		add_shortcode( 'hide-from-tag', array( 'Rainmaker_Tagging_Shortcodes'   , 'hide_from_tag' ) );

	}

	/**
	 * Callback for the `firstname` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return string
	 */
	function firstname( $atts, $content ) {

		$atts['default'] = isset( $atts['default'] ) ? $atts['default'] : __( 'Subscriber', '' );
		$atts['tag']     = 'firstname';

		return $this->custom_field( $atts, $content );

	}

	/**
	 * Callback for the `lastname` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return string
	 */
	function lastname( $atts, $content ) {

		$atts['tag'] = 'lastname';

		return $this->custom_field( $atts, $content );

	}

	/**
	 * Callback for the `custom_field` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return string
	 */
	function custom_field( $atts, $content ) {

		if ( empty( $atts['tag'] ) ) {
			return;
		}

		$default = empty( $atts['default'] ) ? '' : sprintf( '<$else$>%s', $atts['default'] );

		return sprintf( '<$if %1$s!=""$><$%1$s$>%2$s<$endif$>', $atts['tag'], $default );

	}

	/**
	 * Callback for the `unique_id` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return string
	 */
	function unique_id( $atts, $content ) {

		$atts['tag'] = '_subscriber_hash';

		return $this->custom_field( $atts, $content );

	}

	/**
	 * Callback for the `show_to_tag` and `show-to-tag` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return void
	 */
	function show_to_tag( $atts, $content ) {

		if ( empty( $atts['tag'] ) ) {
			return $content;
		}

		$expression = sprintf( '%s!=""', $atts['tag'] );

		return sprintf( '<$if %s$>%s<$endif$>', $expression, $content );

	}

	/**
	 * Callback for the `hide_from_tag` `hide-from-tag` shortcode.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return void
	 */
	function hide_from_tag( $atts, $content ) {

		if ( empty( $atts['tag'] ) ) {
			return $content;
		}

		$expression = sprintf( '%s=""', $atts['tag'] );

		return sprintf( '<$if %s$>%s<$endif$>', $expression, $content );

	}

}
