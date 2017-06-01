<?php
namespace feedblitzGateway;

class Rainmaker_Opt_In_Gateway_FeedBlitzTest extends \Codeception\TestCase\WPTestCase {

	public static function setUpBeforeClass() {

		require_once( dirname(__FILE__) .'/functions/rainmaker-landing-pages.php' );
		require_once( SYNTHESIS_SITE_PLUGIN_DIR . 'library/rainmaker-utilities/classes/opt-in/class.Rainmaker_Opt_In_Gateway.php'  );
		require_once( SYNTHESIS_SITE_PLUGIN_DIR . 'library/feedblitz-gateway/classes/class.Rainmaker_Opt_In_Gateway_FeedBlitz.php' );

	}

	public function setUp() {
		// before
		parent::setUp();

	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	// tests

	/**
	 * Tests that the RMUI_Plugins object exists.
	 *
	 * @access public
	 * @return void
	 */
	public function testRainmaker_Opt_In_Gateway_FeedBlitz() {

		$this->assertTrue( class_exists( 'Rainmaker_Opt_In_Gateway_FeedBlitz' ) );

	}

	/**
	 * Tests the delete_sub method with a failing response from the mock Rainmaker_HTTP object.
	 *
	 * @access public
	 * @return void
	 */
	public function testdeleteSubFail() {

		$_get_http_instance = $this->getMockBuilder( 'Rainmaker_HTTP' )
		->setMethods( array( 'set_method', 'set_timeout', 'set_headers', 'set_body', 'send_request', 'get_error', 'get_request_template', 'get_response_body' ) )
		->getMock();

		$Rainmaker_Opt_In_Gateway_FeedBlitz = $this->getMockBuilder( '\Rainmaker_Opt_In_Gateway_FeedBlitz' )
		->setMethods( array( '_log_request' ) )
		->disableOriginalConstructor()
		->getMock();

		$Rainmaker_Opt_In_Gateway_FeedBlitz::$_instances[get_class($Rainmaker_Opt_In_Gateway_FeedBlitz)] = $Rainmaker_Opt_In_Gateway_FeedBlitz;
		$Rainmaker_Opt_In_Gateway_FeedBlitz->_http_instance = $_get_http_instance;

		$list_id       = 1;
		$subscriber_id = 2;

		$response = $Rainmaker_Opt_In_Gateway_FeedBlitz::delete_sub( $list_id, $subscriber_id );

		$this->assertTrue( is_array( $response ), 'Did not receive an error array from delete sub when fail response given' );

		$this->assertEquals( 'User could not be deleted.', $response['error'], 'Error message was incorrect or did not receive an error array from delete sub when fail response given' );

	}

	/**
	 * Tests the delete_sub method with a success response from the mock Rainmaker_HTTP object.
	 *
	 * @access public
	 * @return void
	 */
	public function testdeleteSubSucceed() {

		$_get_http_instance = $this->getMockBuilder( 'Rainmaker_HTTP' )
		->setMethods( array( 'set_method', 'set_timeout', 'set_headers', 'set_body', 'send_request', 'get_error', 'get_request_template', 'get_response_body' ) )
		->getMock();

		$_get_http_instance->expects( $this->any() )
		->method( 'get_response_body' )
		->will( $this->returnValue( array( 'rsp' => array( 'success' => 1 ) ) ) );

		$Rainmaker_Opt_In_Gateway_FeedBlitz = $this->getMockBuilder( '\Rainmaker_Opt_In_Gateway_FeedBlitz' )
		->setMethods( array( '_log_request' ) )
		->disableOriginalConstructor()
		->getMock();

		$Rainmaker_Opt_In_Gateway_FeedBlitz::$_instances[get_class($Rainmaker_Opt_In_Gateway_FeedBlitz)] = $Rainmaker_Opt_In_Gateway_FeedBlitz;
		$Rainmaker_Opt_In_Gateway_FeedBlitz->_http_instance = $_get_http_instance;

		$list_id       = 1;
		$subscriber_id = 2;

		$response = $Rainmaker_Opt_In_Gateway_FeedBlitz::delete_sub( $list_id, $subscriber_id );

		$this->assertFalse( is_array( $response ), 'Received an error array from delete sub when success response given' );

		$this->assertEquals( $subscriber_id, $response, 'Error message given when success response provided' );

	}

}
