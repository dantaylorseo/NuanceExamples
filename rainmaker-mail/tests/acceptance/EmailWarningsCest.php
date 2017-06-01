<?php


class EmailWarningsCest
{
    public function _before(AcceptanceTester $I)
    {
        admin_login($I);
        $I->resizeWindow(1200, 1040);
    }

    public function _after(AcceptanceTester $I)
    {
    }

    //Tests

    //*
    public function VerifyOptInEmailErrors(AcceptanceTester $I)
    {
        $I->wantTo( 'verify the copy of the error message for a custom opt-in email regarding a missing required shortcode.' );
        $I->amOnPage( '/admin/admin.php?page=rainmail-manage-lists' );
        $I->wait(2);
        $I->click( 'a.edit-list' );
        $I->wait(4);
        $I->see( 'Edit List', 'h1' );
        $I->click( 'ul.email-list-tab-navigation li:nth-of-type(4) a');
        $I->click( 'Edit Email' );
        $I->wait(4);
        $I->uncheckOption( '#rainmail-optin-default' );
        $I->wait(1);
        $I->click( '#rainmail-optin-save' );
        $I->see( 'Alert! Required shortcode [confirm_text_link="Click here to confirm your subscription."] missing from email content.', 'p' );
    }
    /**/
}
