<?php
/**
 * Tests for {@link MultiFormSessionTest}
 * 
 * @package multiform
 * @subpackage tests
 */
class MultiFormSessionTest extends SapphireTest {
	
	/**
	 * Set up the instance of MultiFormSession, writing
	 * a record to the database for this test. We persist
	 * the object in our tests by assigning $this->session
	 */
	function setUp() {
		parent::setUp();
		$this->session = new MultiFormSession();
		$this->session->write();
	}
	
	/**
	 * Test generation of a new session.
	 */
	function testSessionGeneration() {
		$this->assertTrue($this->session->ID != 0);
		$this->assertTrue($this->session->ID > 0);
	}
	
	/**
	 * Test that a MemberID was set on MultiFormSession if
	 * a member is logged in.
	 */
	function testMemberLogging() {
		$session = new MultiFormSession();
		$session->write();
		
		if($memberID = Member::currentUserID()) {
			$this->assertEquals($memberID, $session->SubmitterID);
		}
	}
	
}
