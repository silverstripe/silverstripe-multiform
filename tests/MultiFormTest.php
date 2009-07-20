<?php
/**
 * MultiFormTest
 * For testing purposes, we have some test classes:
 * 
 *  - MultiFormTest_Controller (simulation of a real Controller class)
 *  - MultiFormTest_Form (subclass of MultiForm)
 *  - MultiFormTest_StepOne (subclass of MultiFormStep)
 *  - MultiFormTest_StepTwo (subclass of MultiFormStep)
 *  - MultiFormTest_StepThree (subclass of MultiFormStep)
 *
 * The above classes are used to simulate real-world behaviour
 * of the multiform module - for example, MultiFormTest_Controller
 * is a simulation of a page where MultiFormTest_Form is a simple
 * multi-step contact form it belongs to.
 * 
 * @package multiform
 * @subpackage tests
 */
class MultiFormTest extends FunctionalTest {
	
	protected $controller;
	
	function setUp() {
		parent::setUp();
		$this->controller = new MultiFormTest_Controller();
		$this->form = $this->controller->Form();
	}
	
	function testInitialisingForm() {
		$this->assertTrue(is_numeric($this->form->getCurrentStep()->ID) && ($this->form->getCurrentStep()->ID > 0));
		$this->assertTrue(is_numeric($this->form->getSession()->ID) && ($this->form->getSession()->ID > 0));
		$this->assertEquals('MultiFormTest_StepOne', $this->form->getStartStep());
	}
	
	function testSecondStep() {
		$this->assertEquals('MultiFormTest_StepTwo', $this->form->getCurrentStep()->getNextStep());
	}
	
	function testParentForm() {
		$currentStep = $this->form->getCurrentStep();
		$this->assertEquals($currentStep->getForm()->class, $this->form->class);
	}
	
	function testTotalStepCount() {
		$this->assertEquals(3, $this->form->getAllStepsLinear()->Count());
	}
	
	/*function testNextStepAction() {
		$this->get($this->controller->class);
		$response = $this->submitForm('MultiFormTest_Form', 'next', array(
			'FirstName' => 'Joe',
			'Surname' => 'Bloggs',
			'Email' => 'joe@bloggs.com'
		));
		
		$this->assertNotNull($response->getBody());
	}*/
	
}
class MultiFormTest_Controller extends Controller implements TestOnly {

	function Link($action = null) {
		return $this->class . '/' . $action;
	}
	
	public function Form($request = null) {
		$form = new MultiFormTest_Form($this, 'Form');
		$form->setHTMLID('MultiFormTest_Form');
		return $form;
	}

}
class MultiFormTest_Form extends MultiForm implements TestOnly {

	protected static $start_step = 'MultiFormTest_StepOne';
	
	function getStartStep() {
		return $this->stat('start_step');
	}

}
class MultiFormTest_StepOne extends MultiFormStep implements TestOnly {
	
	protected static $next_steps = 'MultiFormTest_StepTwo';
	
	function getFields() {
		return new FieldSet(
			new TextField('FirstName', 'First name'),
			new TextField('Surname', 'Surname'),
			new EmailField('Email', 'Email address')
		);
	}
	
}
class MultiFormTest_StepTwo extends MultiFormStep implements TestOnly {
	
	protected static $next_steps = 'MultiFormTest_StepThree';
	
	function getFields() {
		return new FieldSet(
			new TextareaField('Comments', 'Tell us a bit about yourself...')
		);
	}
	
}
class MultiFormTest_StepThree extends MultiFormStep implements TestOnly {
	
	protected static $is_final_step = true;
	
	function getFields() {
		return new FieldSet(
			new TextField('Test', 'Anything else you\'d like to tell us?')
		);
	}
	
}

