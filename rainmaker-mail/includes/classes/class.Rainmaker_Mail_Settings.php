<?php

class Rainmaker_Mail_Settings {

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
	 * Callback on the admin_enqueue_scripts action.
	 * Enqueues scripts and styles for the settings page.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function enqueue_scripts() {

		wp_enqueue_style(  'rainmaker-mail-settings-css', RM_MAIL_ASSETS . 'css/rainmaker-email.admin.settings.css', array(          ), RAINMAKER_BUILD_VERSION, 'all' );
		wp_enqueue_script( 'rainmaker-mail-settings-js' , RM_MAIL_ASSETS . 'js/rainmaker-email.admin.settings.js'  , array( 'jquery' ), RAINMAKER_BUILD_VERSION, false  );

		wp_enqueue_media();

	}

	/**
	 * Callback function on `rm_universal_settings_content`.
	 * Invokes the `Rainmaker_Mail_Settings` singleton to load the private _display() method.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function display() {

		Rainmaker_Mail_Settings::get_instance()->_display();

	}

	/**
	 * Organizes and structures the settings display output.
	 *
	 * @uses $this->_display_template_options()
	 * @uses $this->_display_logo_options()
	 * @uses $this->_display_sidebar_options()
	 *
	 * @access private
	 * @return void
	 */
	private function _display() {

		printf(
			'<h3>%s%s</h3>',
			__( 'RainMail Settings', '' ),
			rainmaker_get_tooltip( sprintf( '<p>%s</p>', __( 'These settings will dictate how your broadcast emails and system emails (including signup and purchase receipts, password resets, forum notifications) appear in your subscribers\' inboxes.', '' ) ) )
		);

		$this->_display_template_options();

		$this->_display_custom_template_options();

		printf(
			'<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label>%s</label></th>
						<td>',
			__( 'Personalization and Email Branding', '' )
		);

		$this->_display_logo_options();

		$this->_display_sidebar_options();

		$this->_display_address_options();

		echo '
						</td>
					</tr>
				</tbody>
			</table>';

	}


	/**
	 * Generates the output for the template selection.
	 *
	 * @uses rm_lp_get_option()
	 *
	 * @access private
	 * @return void
	 */
	private function _display_template_options() {

		printf(
			'<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label>%s</label></th>
						<td>
							<ul class="email-template-options">',
			__( 'Default template', '' )
		);

		$templates = array(
			'plain'   => __( 'Plain Text', '' ),
			'basic'   => __( 'Basic'     , '' ),
			'sidebar' => __( 'Sidebar'   , '' ),
			'custom'  => __( 'Custom'    , '' ),
		);

		$current = Rainmaker_Mail_Template_Option::get( true );

		foreach ( $templates as $id => $name ) {

			printf(
				'<li class="email-template-option"><label for="email-template-%1$s"><input type="radio" id="email-template-%1$s" name="%4$s[default_email_template]" value="%1$s" %5$s/><div class="buttons"><button class="button tool-tip-title rm-button-icon-preview rm-button-template-preview" title="%6$s" data-template="%1$s">%6$s</button>%7$s</div><img src="%2$s" alt="%3$s" />%3$s</label></li>',
				$id,
				sprintf( '%s/images/%s.png',
					RM_MAIL_ASSETS,
					$id
				),
				$name,
				RAINMAKER_LP_SETTINGS_FIELD,
				checked( $id, $current, false ),
				__( 'Preview', '' ),
				$id !== 'custom' ? '' : sprintf( '<button class="button button-primary tool-tip-title rm-button-icon-edit rm-button-template-edit" title="%1$s">%1$s</button>', __( 'Edit HTML', '' ) )
			);

		}

		echo '
							</ul>
						</td>
					</tr>
				</tbody>
			</table>';

?>
		<div id="rainmail_template_preview" class="white-popup mfp-hide">

			<span class="loader spinner-page"></span>

			<div class="preview">
				<iframe id="rainmail_template_preview_window"></iframe>
			</div>

		</div>
		<?php


	}

	/**
	 * Generates the output for the email template header section.
	 *
	 * @uses $this->_get_image_option() to build the image selector.
	 * @uses rm_lp_get_option()
	 *
	 * @access private
	 * @return void
	 */
	private function _display_logo_options() {

		printf(
			'<div class="form-table-sub-option">
				<p><label for="email_template_logo"><strong>%s</strong></label></p>
				%s
				<p class="description">%s</p>
			</div>
			<div class="form-table-sub-option">
				<p><label for="email_template_header_text"><strong>%s</strong></label></p>
				<input type="text" id="email_template_header_text" name="%s[email_template_header_text]" value="%s" class="large-text" />
				<p class="description">%s</p>
			</div>
			<div class="form-table-sub-option">
				<p><label for="email_template_header_right_text"><strong>%s</strong></label></p>
				<input type="text" id="email_template_header_right_text" name="%s[email_template_header_right_text]" value="%s" class="large-text" />
				<p class="description">%s</p>
			</div>',
			__( 'Header Image', '' ),
			$this->_get_image_option( 'email_template_logo' ),
			__( '200 x 50 is the recommended image size.', '' ),
			__( 'Header Image Alt Text', '' ),
			RAINMAKER_LP_SETTINGS_FIELD,
			rm_lp_get_option( 'email_template_header_text' ),
			__( 'This is used as the alternate text for the header image. If no header image is provided, or if someone has automatic loading of email images turned off in their email client, this text will show instead of the header. The default text is the site title.', '' ),
			__( 'Header Right Text', '' ),
			RAINMAKER_LP_SETTINGS_FIELD,
			rm_lp_get_option( 'email_template_header_right_text' ),
			__( 'This text will display on the right side of the header, beside your header image, as a short tagline. This text will be shown first in the email preview for most email clients.', '' )
		);

	}

	/**
	 * Generates the output for the email template sidebar section.
	 *
	 * @uses rm_lp_get_option()
	 * @uses wp_editor()
	 *
	 * @access private
	 * @return void
	 */
	private function _display_sidebar_options() {

		printf(
			'<div class="form-table-sub-option">
				<p><label for="email_template_logo"><strong>%s</strong></label></p>
				<a href="#email_template_sidebar_editor" class="button button-secondary button-icon rm-template-sidebar-editor"><span class="dashicons dashicons-edit"></span> %s</a>
				<p class="description">%s</p>
			</div>',
			__( 'Default Sidebar Content', '' ),
			__( 'Edit Sidebar Content', '' ),
			__( 'This will display to the right of your email\'s body text and below the header. This is an excellent place to provide basic navigation links to your site, as well as any bulletins or advertisements that will be universal on all of your broadcast emails.', '' )
		);

		$content        = rm_lp_get_option( 'email_template_sidebar' );
		$editor_options = array( 'textarea_name' => sprintf( '%s[email_template_sidebar]', RAINMAKER_LP_SETTINGS_FIELD ) );

		printf( '
		<div id="email_template_sidebar_editor" class="editor-popup hidden"">
			<div class="wrap">
				<a class="close-box dashicons dashicons-no" href="#">Close</a>
				<h2>%s</h2>',
			__( 'Edit Sidebar Content', '' )
		);

		wp_editor( $content, 'email_template_sidebar', $editor_options );

		echo '<p><button type="button" class="button button-primary button-close-popup">Done</button></p>';

		echo '
			</div>
		</div>';

	}

	/**
	 * Generates the output for the email template sidebar section.
	 *
	 * @uses rm_lp_get_option()
	 * @uses wp_editor()
	 *
	 * @access private
	 * @return void
	 */
	private function _display_custom_template_options() {

		$id = 'custom_email_template';

		echo '<div id="rainmail_custom_template_editor" class="white-popup mfp-hide mfp-close-btn-in">';

		$content = rm_lp_get_option( $id );

		$content = empty( $content ) ? file_get_contents( sprintf( '%sassets/templates/basic.html', RM_MAIL_DIR ), 'r' ) : $content;

		echo '<h2 class="mfp-title">Custom email template HTML code</h2>';

		printf( '<p class="description">%s</p>', __( 'The custom email template is for advanced users who are comfortable working with HTML, CSS, and custom merge tags. It\'s not hard to learn, but just understand that one wrong character could mess up the entire template. For this reason, we recommend that even experienced coders validate the HTML and CSS used in this field, which you can do at <a href="//validator.w3.org" target="_blank">validator.w3.org</a>. Use the {{logo}}, {{content}}, {{sidebar}}, and {{tag}} (for the tagline) fields to output the appropriate data. [Note: {{content}} is a required field and you will get an error if you do not have that in your template.]', '' ) );

		printf(
			'<textarea class="custom-email-template-field" rows="20" autocomplete="off" cols="40" name="%2$s[%1$s]" id="%1$s">%3$s</textarea>',
			$id,
			RAINMAKER_LP_SETTINGS_FIELD,
			esc_html( trim( str_replace( '    ', '	', $content ) ) )
		);

		echo '<p><button type="button" class="button button-primary button-close-popup">Done</button></p>';

		echo '</div>';

	}

	/**
	 * Generates the output for the postal address section.
	 *
	 * @uses rm_lp_get_option()
	 *
	 * @access private
	 * @return void
	 */
	private function _display_address_options() {
?>
		<div class="form-table-sub-option">
			<p><label for=""><strong><?php _e( 'Footer Details', '' ) ?></strong></label></p>
			<div id="rainmail_current_footer_address"><div class="spinner-page" style="display:block;width:190px"><?php _e( 'Loading current address...', '' ); ?></div></div>
					<p><button class="button button-primary button-icon" id="edit-rainmail-address"><span class="dashicons dashicons-edit"></span> <?php _e( 'Edit Footer Details', '' ) ?></button> <button class="button button-secondary button-icon rainmail_reload_address"><span class="dashicons dashicons-update"></span> <?php _e( 'Refresh Footer Details', '' ) ?></button></p>
		</div>

		<div id="rainmail-edit-address" class="white-popup mfp-hide mfp-close-btn-in">
			<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			<h2 class="mfp-title" id="edit_list_title"><?php _e( 'Edit Footer Details', '' ) ?></h2>
			<p class="description"><?php _e( 'Many anti-spam laws around the world require you to include a valid postal address on your emails, whether you are based in the U.S. or not.', '' ); ?></p>

			<div class="loading">
				<div class="spinner-page" style="display:block;"><?php _e( 'Loading...', '' ) ?></div>
			</div>
			<div class="loaded">

				<div class="rm-form-address">
					<p class="description"><?php _e( '* denotes a required field.', '' ); ?></p>

					<p class="field-company-name">
						<label for="rainmail_address_company"><?php _e( 'Company Name: *', '' ); ?></label>
						<input id="rainmail_address_company" type="text" class="regular-text">
					</p>

					<p class="field-name">
						<label for="rainmail_address_name"><?php _e( 'Name: *', '' ); ?></label>
						<input id="rainmail_address_name" type="text" class="regular-text">
					</p>

					<p class="field-address-1">
						<label for="rainmail_address_street"><?php _e( 'Address Line 1: *', '' ); ?></label>
						<input id="rainmail_address_street" type="text" class="regular-text">
					</p>

					<p class="field-address-2">
						<label for="rainmail_address_street2"><?php _e( 'Address Line 2:', '' ); ?></label>
						<input id="rainmail_address_street2" type="text" class="regular-text">
					</p>

					<p class="field-city">
						<label for="rainmail_address_city"><?php _e( 'City: *', '' ); ?></label>
						<input id="rainmail_address_city" type="text" class="regular-text">
					</p>

					<p class="field-state">
						<label for="rainmail_address_state"><?php _e( 'State/Province/Region: *', '' ); ?></label>
						<input id="rainmail_address_state" type="text" class="regular-text">
					</p>

					<p class="field-zip">
						<label for="rainmail_address_zip"><?php _e( 'ZIP: *', '' ); ?></label>
						<input id="rainmail_address_zip" type="text" class="regular-text">
					</p>

					<p class="field-country">
						<label for="rainmail_address_country"><?php _e( 'Country: *', '' ); ?></label>
						<input id="rainmail_address_country" type="text" class="regular-text">
					</p>

					<p class="field-email">
						<label for="rainmail_address_email"><?php _e( 'Email Address: *', '' ); ?></label>
						<input id="rainmail_address_email" type="text" class="regular-text">
					</p>

					<p class="field-phone">
						<label for="rainmail_address_tel"><?php _e( 'Phone Number: *', '' ); ?></label>
						<input id="rainmail_address_tel" type="text" class="regular-text">
					</p>

					<p class="field-company-name">
						<label for="rainmail_address_tag"><?php _e( 'Tag Line:', '' ); ?></label>
						<input id="rainmail_address_tag" type="text" placeholder="Email subscription powered by the Rainmaker Platform" class="regular-text">
					</p>

				</div>

				<button type="button" class="button-primary" id="saveRMailAddress"><?php _e( 'Save Address', '' ); ?></button>
				<button type="button" class="button" id="cancelsubmit"><?php _e( 'Cancel', '' ); ?></button>
			</div>
		</div>
<?php
	}

	/**
	 * Creates a button uploader which will use the WP Media uploader.
	 *
	 * @uses rm_lp_get_option()
	 *
	 * @access private
	 * @param mixed $id
	 * @return string
	 */
	private function _get_image_option( $id ) {

		$default_image = sprintf( '%simages/header-image.png', RM_MAIL_ASSETS );
		$opt           = rm_lp_get_option( $id );
		$url           = empty( $opt ) ? $default_image : $opt;

		$button = sprintf(
			'<p><img src="%8$s" alt="%6$s" id="%1$s_preview" /></p>
			<p><a href="#%1$s" data-preview="#%1$s_preview" class="button button-primary button-upload-image">%3$s</a>
			<a href="#%1$s" data-preview="#%1$s_preview" class="button button-secondary button-remove-image">%7$s</a></p>
			<p>%10$s</p>
			<input type="text" id="%1$s" name="%2$s[%1$s]" class="regular-text" value="%4$s" placeholder="%9$s" style="width:90%%" />
			<input type="hidden" id="%1$s_default" value="%5$s" />',
			$id,
			RAINMAKER_LP_SETTINGS_FIELD,
			__( 'Upload New Image', '' ),
			$url === $default_image ? '' : $url,
			$default_image,
			__( 'Email Template Header Image', '' ),
			__( 'Remove Header Image', '' ),
			$url,
			__( 'http://example.com/image.png', '' ),
			__( 'or <strong>enter your image URL:</strong>', '' )
		);

		return $button;

	}

}
