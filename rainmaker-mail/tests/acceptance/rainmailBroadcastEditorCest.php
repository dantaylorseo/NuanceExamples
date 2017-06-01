<?php


class rainmailBroadcastEditorCest {

	public function _before( AcceptanceTester $I ) {
	}

	public function _after( AcceptanceTester $I ) {
	}

	/**
	 * Test to verify the required content flag works.
	 *
	 * @access public
	 * @param AcceptanceTester $I
	 * @return void
	 */
	public function testRequiredContent( AcceptanceTester $I ) {

		$I->wantTo('verify the required content flag works.');

		$I->amOnPage( '/admin/index.php' );

		admin_login( $I ); //function in _bootstrap.php

		//default width for desktop
		$I->resizeWindow( 1200, 1040 );

		$I->amOnPage( '/admin/post-new.php?post_type=mail' );

		$I->executeJS( 'jQuery(".first_list").click();' );

		$I->executeJS( 'jQuery(".menu-tab a[href=\"#poststuff\"]").click();' );

		$I->fillField( 'Enter subject here', 'subject' );

		$I->click( 'Test & Schedule' );

		$I->click( '#publish' );
		
		$I->moveMouseOver('#postdivrich');
		
		$I->waitForElement( '.tpd-content' );

		$I->see( 'The broadcast content is required.', '.tpd-content' );

	}

}
