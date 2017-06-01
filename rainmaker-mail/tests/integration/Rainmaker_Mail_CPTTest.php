<?php
namespace rainmakerMail;

class Rainmaker_Mail_CPTTest extends \Codeception\TestCase\WPTestCase {

	public static function setUpBeforeClass() {

		require_once( SYNTHESIS_SITE_PLUGIN_DIR . 'library/rainmaker-mail/includes/classes/class.Rainmaker_Mail_CPT.php' );

		\Rainmaker_Mail_CPT::register_post_type();

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
	 * Tests that the Rainmaker_Mail_CPT object exists.
	 *
	 * @access public
	 * @return void
	 */
	public function testRainmaker_Mail_CPT() {

		$this->assertTrue( class_exists( 'Rainmaker_Mail_CPT' ) );

	}

	/**
	 * Tests the register post type exists.
	 *
	 * @access public
	 * @return void
	 */
	public function testRegisterPostType() {

		$this->assertTrue( post_type_exists( 'mail'                ), 'Mail post type was not registered.'                      );

	}

	/**
	 * Tests the registered post type supports the title.
	 *
	 * @access public
	 * @return void
	 */
	public function testRegisterPostTypeSupportsTitle() {

		$this->assertTrue( post_type_supports( 'mail', 'title'     ), 'Mail post type does not correctly support the title'     );

	}

	/**
	 * Tests the registered post type supports the editor.
	 *
	 * @access public
	 * @return void
	 */
	public function testRegisterPostTypeSupportsEditor() {

		$this->assertTrue( post_type_supports( 'mail', 'editor'    ), 'Mail post type does not correctly support the editor'    );

	}

	/**
	 * Tests the registered post type supports revisions.
	 *
	 * @access public
	 * @return void
	 */
	public function testRegisterPostTypeSupportsRevisions() {

		$this->assertTrue( post_type_supports( 'mail', 'revisions' ), 'Mail post type does not correctly support the revisions' );

	}

}
