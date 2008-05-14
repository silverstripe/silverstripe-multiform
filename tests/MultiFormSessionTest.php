<?php

class MultiFormSessionTest extends SapphireTest {
	
	/**
	 * Test generation of a new session.
	 */
	function testSessionGeneration() {
		$session = new MultiFormSession();
		$session->write();
		
		$this->assertTrue($session->ID != 0);
		$this->assertTrue($session->ID > 0);
		
		$session->delete();
	}
	
	/**
	 * Test that a MemberID was set on MultiFormSession if
	 * a member is logged in.
	 */
	function testMemberLogging() {
		$session = new MultiFormSession();
		$session->write();
		
		if($memberID = Member::currentUserID()) {
			$this->assertTrue($memberID == $session->SubmitterID);
		}
	}
	
}

?>