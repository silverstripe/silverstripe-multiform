<?php

/**
 * MultiFormStep controls the behaviour of a single form step in the MultiForm
 * process. All form steps are required to be subclasses of this class, as it
 * encapsulates the functionality required for the step to be aware of itself
 * in the process by knowing what it's next step is, and if applicable, it's previous
 * step.
 * 
 * @package multiform
 */
class MultiFormStep extends DataObject {

	public static $db = array(
		'Data' => 'Text' // stores serialized maps with all session information
	);
	
	public static $has_one = array(
		'Session' => 'MultiFormSession'
	);
	
	/**
	 * Centerpiece of the flow control for the form.
	 * 
	 * If set to a string, you have a linear form flow
	 * If set to an array, you should use {@link getNextStep()}
	 * to enact flow control and branching to different form
	 * steps, most likely based on previously set session data
	 * (e.g. a checkbox field or a dropdown).
	 *
	 * @var array|string
	 */
	public static $next_steps;
	
	/**
	 * Each {@link MultiForm} subclass needs at least
	 * one step which is marked as the "final" one
	 * and triggers the {@link MultiForm->finish()}
	 * method that wraps up the whole submission.
	 *
	 * @var boolean
	 */
	public static $is_final_step = false;

	/**
	 * This variable determines whether a user can use
	 * the "back" action from this step.
	 * 
	 * @TODO This does not check if the arbitrarily chosen step
	 * using the step indicator is actually a previous step, so
	 * unless you remove the link from the indicator template, or
	 * type in StepID=23 to the address bar you can still go back
	 * using the step indicator.
	 *
	 * @var boolean
	 */
	protected static $can_go_back = true;
	
	/**
	 * Title of this step.
	 * 
	 * Used for the step indicator templates.
	 *
	 * @var string
	 */
	protected $title;
	
	/**
	 * Form class that this step is directly related to.
	 *
	 * @var MultiForm subclass
	 */
	protected $form;
	
	/**
	 * Form fields to be rendered with this step.
	 * (Form object is created in {@link MultiForm}.
	 *
	 * This function needs to be implemented on your
	 * subclasses of MultiFormStep.
	 *
	 * @return FieldList
	 */
	public function getFields() {
		user_error('Please implement getFields on your MultiFormStep subclass', E_USER_ERROR);
	}
	
	/**
	 * Additional form actions to be added to this step.
	 * (Form object is created in {@link MultiForm}.
	 *
	 * Note: This is optional, and is to be implemented
	 * on your subclasses of MultiFormStep.
	 *
	 * @return FieldList
	 */
	public function getExtraActions() {
		return (class_exists('FieldList')) ? new FieldList() : new FieldSet();
	}
	
	/**
	 * Get a validator specific to this form.
	 * The form is automatically validated in {@link Form->httpSubmission()}.
	 * 
	 * @return Validator
	 */
	public function getValidator() {
		return false;
	}
	
	/**
	 * Accessor method for $this->title
	 * 
	 * @return string Title of this step
	 */
	public function getTitle() {
		return $this->title ? $this->title : $this->class;
	}
	
	/**
	 * Gets a direct link to this step (only works
	 * if you're allowed to skip steps, or this step
	 * has already been saved to the database
	 * for the current {@link MultiFormSession}).
	 *
	 * @return string Relative URL to this step
	 */
	public function Link() {
		return Controller::join_links($this->form->getDisplayLink(), "?MultiFormSessionID={$this->Session()->Hash}");
	}

	/**
	 * Unserialize stored session data and return it.
	 * This is used for loading data previously saved
	 * in session back into the form.
	 * 
	 * You need to overload this method onto your own
	 * step if you require custom loading. An example
	 * would be selective loading specific fields, leaving
	 * others that are not required.
	 * 
	 * @return array
	 */
	public function loadData() {
		return ($this->Data && is_string($this->Data)) ? unserialize($this->Data) : array();
	}
	
	/**
	 * Save the data for this step into session, serializing it first.
	 * 
	 * To selectively save fields, instead of it all, this
	 * method would need to be overloaded on your step class.
	 *
	 * @param array $data The processed data from save() on {@link MultiForm}
	 */
	public function saveData($data) {
		$this->Data = serialize($data);
		$this->write();
	}
	
	/**
	 * Save the data on this step into an object,
	 * similiar to {@link Form->saveInto()} - by building
	 * a stub form from {@link getFields()}. This is necessary
	 * to trigger each {@link FormField->saveInto()} method
	 * individually, rather than assuming that all data
	 * serialized through {@link saveData()} can be saved
	 * as a simple value outside of the original FormField context.
	 *
	 * @param DataObject $obj
	 */
	public function saveInto($obj) {
		$form = new Form(
			Controller::curr(),
			'Form',
			$this->getFields(),
			((class_exists('FieldList')) ? new FieldList() : new FieldSet())
		);
		$form->loadDataFrom($this->loadData());
		$form->saveInto($obj);
		return $obj;
	}
	
	/**
	 * Custom validation for a step. In most cases, it should be sufficient
	 * to have built-in validation through the {@link Validator} class
	 * on the {@link getValidator()} method.
	 *
	 * Use {@link Form->sessionMessage()} to feed back validation messages
	 * to the user. Please don't redirect from this method,
	 * this is taken care of in {@link next()}.
	 *
	 * @param array $data Request data
	 * @param Form $form
	 * @return boolean Validation success
	 */
	public function validateStep($data, $form) {
		return true;
	}
	
	/**
	 * Returns the first value of $next_step
	 * 
	 * @return String Classname of a {@link MultiFormStep} subclass
	 */
	public function getNextStep() {
		$nextSteps = static::$next_steps;

		// Check if next_steps have been implemented properly if not the final step
		if(!$this->isFinalStep()) {
			if(!isset($nextSteps)) user_error('MultiFormStep->getNextStep(): Please define at least one $next_steps on ' . $this->class, E_USER_ERROR);
		}
		
		if(is_string($nextSteps)) {
			return $nextSteps;
		} elseif(is_array($nextSteps) && count($nextSteps)) {
			// custom flow control goes here
			return $nextSteps[0];
		} else {
			return false;
		}
	}

	/**
	 * Returns the next step to the current step in the database.
	 * 
	 * This will only return something if you've previously visited
	 * the step ahead of the current step, and then gone back a step.
	 * 
	 * @return MultiFormStep|boolean
	 */
	public function getNextStepFromDatabase() {
		if($this->SessionID && is_numeric($this->SessionID)) {
			$nextSteps = static::$next_steps;

			if(is_string($nextSteps)) {
				return DataObject::get_one($nextSteps, "\"SessionID\" = {$this->SessionID}");
			} elseif(is_array($nextSteps)) {
				return DataObject::get_one($nextSteps[0], "\"SessionID\" = {$this->SessionID}");
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Accessor method for self::$next_steps
	 * 
	 * @return string|array
	 */
	public function getNextSteps() {
		return static::$next_steps;
	}
	
	/**
	 * Returns the previous step, if there is one.
	 * 
	 * To determine if there is a previous step, we check the database to see if there's
	 * a previous step for this multi form session ID.
	 * 
	 * @return String Classname of a {@link MultiFormStep} subclass
	 */
	public function getPreviousStep() {
		$steps = DataObject::get('MultiFormStep', "\"SessionID\" = {$this->SessionID}", '"LastEdited" DESC');
		if($steps) {
			foreach($steps as $step) {
				$step->setForm($this->form);

				if($step->getNextStep()) {
					if($step->getNextStep() == $this->class) {
						return $step->class;
					}
				}
			}
		}
	}
	
	/**
	 * Retrieves the previous step class record from the database.
	 *
	 * This will only return a record if you've previously been on the step.
	 *
	 * @return MultiFormStep subclass
	 */
	public function getPreviousStepFromDatabase() {
		if($prevStepClass = $this->getPreviousStep()) {
			return DataObject::get_one($prevStepClass, "\"SessionID\" = {$this->SessionID}");
		}
	}

	/**
	 * Get the text to the use on the button to the previous step.
	 * @return string
	 */
	public function getPrevText() {
		return _t('MultiForm.BACK', 'Back');
	}

	/**
	 * Get the text to use on the button to the next step.
	 * @return string
	 */
	public function getNextText() {
		return _t('MultiForm.NEXT', 'Next');
	}

	/**
	 * Get the text to use on the button to submit the form.
	 * @return string
	 */
	public function getSubmitText() {
		return _t('MultiForm.SUBMIT', 'Submit');
	}

	/**
	 * Sets the form that this step is directly related to.
	 *
	 * @param MultiForm subclass $form
	 */
	public function setForm($form) {
		$this->form = $form;
	}
	
	/**
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}
	
	// ##################### Utility ####################
	
	/**
	 * Determines whether the user is able to go back using the "action_back"
	 * form action, based on the boolean value of $can_go_back.
	 * 
	 * @return boolean
	 */
	public function canGoBack() {
		return static::$can_go_back;
	}
	
	/**
	 * Determines whether this step is the final step in the multi-step process or not,
	 * based on the variable $is_final_step - which must be defined on at least one step.
	 *
	 * @return boolean
	 */
	public function isFinalStep() {
		return static::$is_final_step;
	}
	
	/**
	 * Determines whether the currently viewed step is the current step set in the session.
	 * This assumes you are checking isCurrentStep() against a data record of a MultiFormStep
	 * subclass, otherwise it doesn't work. An example of this is using a singleton instance - it won't
	 * work because there's no data.
	 * 
	 * @return boolean
	 */
	public function isCurrentStep() {
		return ($this->class == $this->Session()->CurrentStep()->class) ? true : false;
	}
	
}
