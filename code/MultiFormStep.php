<?php

/**
 * MultiFormStep controls the behaviour of a single from step in the multi-form
 * process. All form steps should be subclasses of this class, as it encapsulates
 * the functionality required for the step to be aware of itself in the form step
 * process.
 * 
 * @package multiform
 */
class MultiFormStep extends DataObject {

	static $db = array(
		'Data' => 'Text' // stores serialized maps with all session information
	);
	
	static $has_one = array(
		'Session' => 'MultiFormSession'
	);
	
	/**
	 * Centerpiece of the flow control for the form.
	 * If set to a string, you pretty much have a linear
	 * form flow - if set to an array, you should
	 * use {@link getNextStep()} to enact flow control
	 * and branching to different form steps,
	 * most likely based on previously set session data
	 * (e.g. a checkbox field or a dropdown).
	 *
	 * @var array|string
	 */
	protected static $next_steps;
	
	/**
	 * Each {@link MultiForm} subclass
	 * needs at least one step which is marked as the "final" one
	 * and triggers the {@link MultiForm->finish()}
	 * method that wraps up the whole submission.
	 *
	 * @var boolean
	 */
	protected static $is_final_step = false;

	/**
	 * Title of this step, can be used by each step that sub-classes this.
	 * It's useful for creating a list of steps in your template.
	 *
	 * @var string
	 */
	protected $title;
	
	/**
	 * Formfields to be rendered with this step
	 * (Form object is created in {@link MultiForm}.
	 * This function needs to be implemented
	 *
	 * @return FieldSet
	 */
	public function getFields() {
		user_error('Please implement getFields on your MultiFormStep subclass', E_USER_ERROR);
	}
	
	/**
	 * 
	 * @return FieldSet
	 */
	public function getExtraActions() {
		return new FieldSet();
	}
	
	/**
	 * Get a validator specific to this form.
	 *
	 * @return Validator
	 */
	public function getValidator() {
		return null;
	}
	
	/**
	 * Accessor method for $this->title
	 */
	public function getTitle() {
		return $this->title;
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
		return Controller::curr()->Link() . '?MultiFormSessionID=' . $this->Session()->ID;
	}

	/**
	 * Unserialize stored session data and return it.
	 * This should be called when the form is constructed,
	 * so the fields can be loaded with the values.
	 */
	public function loadData() {
		return unserialize($this->Data);
	}
	
	/**
	 * Save the data for this step into session, serializing it first.
	 * 
	 * @TODO write a code snippet on how to overload this method!
	 *
	 * @param array $data The processed data from save() on MultiForm
	 */
	public function saveData($data) {
		$this->Data = serialize($data);
		$this->write();
	}
	
	/**
	 * @TODO what does this method do? What is it's responsibility?
	 *
	 */
	public function start() {
		
	}
	
	/**
	 * @TODO what does this method do, in relation to MultiForm->finish() ?
	 * I thought we were finalising the entire form on MultiForm, and not
	 * each step?
	 */
	public function finish() {
		
	}
	
	/**
	 * Returns the first value of $next_step
	 * 
	 * @return String Classname of a {@link MultiFormStep} subclass
	 */
	public function getNextStep() {
		$nextSteps = $this->stat('next_steps');

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
	 * the step ahead of the current step, so if you've gone to the
	 * step ahead, and then gone back a step.
	 * 
	 * @return MultiFormStep|boolean
	 */
	public function getNextStepFromDatabase() {
		$nextSteps = $this->stat('next_steps');
		if(is_string($nextSteps)) {
			$step = DataObject::get($nextSteps, "SessionID = {$this->SessionID}", 'LastEdited DESC');
			if($step) return $step->First();
		} elseif(is_array($nextSteps)) {
			$step = DataObject::get($nextSteps[0], "SessionID = {$this->SessionID}", 'LastEdited DESC');
			if($step) return $step->First();
		} else {
			return false;
		}
	}
	
	/**
	 * Accessor method for self::$next_steps
	 */
	public function getNextSteps() {
		return $this->stat('next_steps');
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
		$steps = DataObject::get('MultiFormStep', "SessionID = {$this->SessionID}", 'LastEdited DESC');
		if($steps) {
			foreach($steps as $step) {
				if($step->getNextStep()) {
					if($step->getNextStep() == $this->class) {
						return $step->class;
					}
				}
			}
		}
	}
	
	// ##################### Utility ####################
	
	/**
	 * @TODO Do we need this? Can't we just check for the return value of getPreviousStep,
	 * and do boolean logic from that?
	 */
	public function hasPreviousStep() {
		die('MultiFormStep->hasPreviousStep(): Not implemented yet');
	}
	
	/**
	 * Determines whether this step is the final step in the multi-step process or not,
	 * based on the variable $is_final_step - to set the final step, create this variable
	 * on your form step class.
	 *
	 * @return boolean
	 */
	public function isFinalStep() {
		return $this->stat('is_final_step');
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
		if($this->class == $this->Session()->CurrentStep()->class) return true;
	}
	
}

?>