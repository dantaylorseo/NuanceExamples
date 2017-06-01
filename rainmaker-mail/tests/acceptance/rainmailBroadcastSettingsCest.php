<?php


class rainmailBroadcastSettingsCest {

	public function _before( AcceptanceTester $I ) {
	}

	public function _after( AcceptanceTester $I ) {
	}

	public function testSettingsHeader( AcceptanceTester $I ) {

		//default width for desktop
		$I->resizeWindow( 1200, 1040 );

		$I->amOnPage( '/admin/index.php' );

		admin_login( $I ); //function in _bootstrap.php

		$I->wantTo( 'verify the email template options work.' );

		$I->amOnPage( '/admin/admin.php?page=universal-settings&tab=email' );

		$I->see( 'RainMail Settings', 'h3' );
	}

	/**
	 * Test to verify template options.
	 *
	 * @access public
	 * @param AcceptanceTester $I
	 * @return void
	 */
	public function testTemplateOptions( AcceptanceTester $I ) {

		//default width for desktop
		$I->resizeWindow( 1200, 1040 );

		$I->amOnPage( '/admin/index.php' );

		admin_login( $I ); //function in _bootstrap.php

		$I->wantTo('verify the email template options work.');

		$I->amOnPage( '/admin/admin.php?page=universal-settings&tab=email' );

		//plain template
		$I->executeJS( 'jQuery("label[for=\"email-template-plain\"]").click();' );

		$I->seeOptionIsSelected( 'input[name="_rm_lp_settings[default_email_template]"]', 'plain' );

		//basic template
		$I->executeJS( 'jQuery("label[for=\"email-template-basic\"]").click();' );

		$I->seeOptionIsSelected( 'input[name="_rm_lp_settings[default_email_template]"]', 'basic' );

		//sidebar template
		$I->executeJS( 'jQuery("label[for=\"email-template-sidebar\"]").click();' );

		$I->seeOptionIsSelected( 'input[name="_rm_lp_settings[default_email_template]"]', 'sidebar' );

		//custom template
		$I->executeJS( 'jQuery("label[for=\"email-template-custom\"]").click();' );

		$I->seeOptionIsSelected( 'input[name="_rm_lp_settings[default_email_template]"]', 'custom' );

	}

	/**
	 * Test to verify template options.
	 *
	 * @access public
	 * @param AcceptanceTester $I
	 * @return void
	 *
	 public function testTemplatePreview( AcceptanceTester $I ) {

	 //default width for desktop
	 $I->resizeWindow( 1200, 1040 );

	 $I->amOnPage( '/admin/index.php' );

	 admin_login( $I ); //function in _bootstrap.php

	 $I->wantTo('verify the email template preview works.');

	 $I->amOnPage( '/admin/admin.php?page=universal-settings&tab=email' );

	 //plain template
	 $I->executeJS( 'jQuery("label[for=\"email-template-plain\"]").click();' );

	 $I->executeJS( 'jQuery("label[for=\"email-template-plain\"] .rm-button-template-preview").click();' );

	 $I->waitForElement( '#rainmail_template_preview_window', 10 );

	 $I->seeElement( '#rainmail_template_preview_window' );

	 }/**/

	/**
	 * Test to verify Personalization and Email Branding label.
	 *
	 * @access public
	 * @param AcceptanceTester $I
	 * @return void
	 */
	public function testPersonalizationAndBranding( AcceptanceTester $I ) {

		//default width for desktop
		$I->resizeWindow( 1200, 1040 );

		$I->amOnPage( '/admin/index.php' );

		admin_login( $I ); //function in _bootstrap.php

		$I->wantTo('verify the Personalization and Email Branding label.');

		$I->amOnPage( '/admin/admin.php?page=universal-settings&tab=email' );

		$I->see( 'Personalization and Email Branding', 'label' );

	}/**/

	/**
	 * Test to verify Header Image label.
	 *
	 * @access public
	 * @param AcceptanceTester $I
	 * @return void
	 */
	public function testHeaderImageSettings( AcceptanceTester $I ) {

		//default width for desktop
		$I->resizeWindow( 1200, 1040 );

		$I->amOnPage( '/admin/index.php' );

		admin_login( $I ); //function in _bootstrap.php

		$I->wantTo( 'verify the header image settings.' );

		$I->amOnPage( '/admin/admin.php?page=universal-settings&tab=email' );

		$I->see( 'Header Image'         , 'strong' );
		$I->see( 'Upload New Image'     , 'a'      );
		$I->see( 'Remove Header Image'  , 'a'      );
		$I->see( 'enter your image URL:', 'strong' );

		$I->see( 'Header Image Alt Text', 'label' );
		$I->see( 'Header Right Text'    , 'label' );

		$I->see( 'Default Sidebar Content', 'strong' );

		$I->see( 'Footer Postal Address'  , 'strong' );

	}/**/


}
