<?php

/**
 * MultiFormTest
 * 
 * @TODO create some behavioural test cases, such as examining what occurs after
 * submitting the "previous", "next" form actions that a user would normally
 * be doing.
 * 
 * For testing purposes, we have some test MultiForm classes:
 * 
 *  - MultiFormTestClass (subclass of MultiForm)
 *  - MultiFormTestStepOne (subclass of MultiFormStep - the first step)
 *  - MultiFormTestStepTwo (subclass of MultiFormStep - the second step)
 *  - MultiFormTestStepThree (subclass of MultiFormStep - the third step)
 * 
 * These test classes should be used for testing the operation of a "real"
 * instance of this multiform step system. Also, as a note here: every instance
 * of MultiFormStep, which is every step in a form, requires a db/build, as it
 * is a subclass of DataObject. This is a bit of a pain, but it's required for
 * the database to store the step data for each step, which is very important!
 * 
 * @TODO make use of .yml file to populate test db data instead?
 * 
 * So, if you're going to create some new tests, and want to use some test classes,
 * make sure to use the ones mentioned above.
 */
class MultiFormTest extends SapphireTest {

	/**
	 * Set up the instance of MultiForm, writing a record
	 * to the database for this test. We persist the object
	 * in our tests by assigning $this->getSession()
	 */
	function setUp() {
		$this->form = new MultiFormTestClass(new Controller(), 'Form');
	}
	
	/**
	 * Tests initialising a new instance of a test class.
	 * 
	 * @TODO Write some decent tests! The current assertions are very basic, and are
	 * nowhere near touching on the more advanced concepts of MultiForm, such
	 * as the form actions (prev/next), session handling, and step handling
	 * through {@link MultiFormStep->getPreviousStep()} and
	 * {@link MultiFormStep->getNextStep()} for example.
	 */
	function testInitialisingForm() {
		$this->assertTrue(is_numeric($this->form->getCurrentStep()->ID) && ($this->form->getCurrentStep()->ID > 0));
		$this->assertTrue(is_numeric($this->form->getSession()->ID) && ($this->form->getSession()->ID > 0));
		$this->assertEquals('MultiFormTestStepOne', $this->form->getStartStep());
	}
	
	/**
	 * Test that the 2nd step is correct to what we expect it to be.
	 */
	function testSecondStep() {
		$this->assertEquals('MultiFormTestStepTwo', $this->form->getCurrentStep()->getNextStep());
	}
	
	/**
	 * Test that the amount of steps we have has been calculated correctly.
	 */
	function testTotalStepCount() {
		$this->assertEquals(3, $this->form->getAllStepsLinear()->Count());
	}
	
	/**
	 * Remove the session data that was created. Note: This should delete all the
	 * dependencies such as MultiFormStep instances that are related directly to
	 * this session. These directives can be found on {@link MultiFormSession->onBeforeWrite()}
	 */
	function tearDown() {
		$this->form->getSession()->delete();
	}
	
}

?>