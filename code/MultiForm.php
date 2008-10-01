<?php

/**
 * MultiForm manages the loading of single form steps, and acts as a state
 * machine that connects to a {@link MultiFormSession} object as a persistence
 * layer.
 * 
 * CAUTION: If you're using controller permission control,
 * you have to allow the following methods:
 * <code>
 * static $allowed_actions = array('next','prev');
 * </code> 
 * 
 * @package multiform
 */
abstract class MultiForm extends Form {
	
	/**
	 * A session object stored in the database, to identify and store
	 * data for this MultiForm instance.
	 *
	 * @var MultiFormSession
	 */
	protected $session;
	
	/**
	 * Defines which subclass of {@link MultiFormStep} should be the first
	 * step in the multi-step process.
	 *
	 * @var string Classname of a {@link MultiFormStep} subclass
	 */
	protected static $start_step;
	
	/**
	 * Set the casting for these fields.
	 *
	 * @var array
	 */
	public static $casting = array(
		'CompletedStepCount' => 'Int',
		'TotalStepCount' => 'Int',
		'CompletedPercent' => 'Float'
	);
	
	/**
	 * These fields are ignored when saving the raw form data into session.
	 * This ensures only field data is saved, and nothing else that's useless
	 * or potentially dangerous.
	 *
	 * @var array
	 */
	public static $ignored_fields = array(
		'url',
		'executeForm',
		'MultiFormSessionID',
		'SecurityID'
	);
	
	/**
	 * Start the MultiForm instance.
	 *
	 * @param Controller instance $controller Controller this form is created on
	 * @param string $name The form name, typically the same as the method name
	 */
	public function __construct($controller, $name) {
		
		// Set up the session for this MultiForm instance
		$this->setSession();

		// Get the current step available (Note: either returns an existing
		// step or creates a new one if none available)
		$currentStep = $this->getCurrentStep();

		// Set the step returned above as the current step
		$this->setCurrentStep($currentStep);

		// Set the form of the step to this form instance
		$currentStep->form = $this;

		// Set up the fields for the current step
		$fields = $currentStep->getFields();

		// Set up the actions for the current step
		$actions = $this->actionsFor($currentStep);

		// Set up validation (if necessary)
		// @todo find a better way instead of hardcoding a check for action_prev in order to prevent validation when hitting the back button
		$validator = null;
		if(empty($_REQUEST['action_prev'])) {
			if($this->getCurrentStep()->getValidator()) {
				$validator = $this->getCurrentStep()->getValidator();
			}
		}
		
		// Give the fields, actions, and validation for the current step back to the parent Form class
		parent::__construct($controller, $name, $fields, $actions, $validator);

		// Set a hidden field in our form with an encrypted hash to identify this session.
		$this->fields->push(new HiddenField('MultiFormSessionID', false, $this->session->Hash));
		
		// If there is saved data for the current step, we load it into the form it here
		//(CAUTION: loadData() MUST unserialize first!)
		if($currentStep->loadData()) {
			$this->loadDataFrom($currentStep->loadData());
		}
		
		// Disable security token - we tie a form to a session ID instead
		$this->disableSecurityToken();
	}

	/**
	 * Accessor method to $this->controller.
	 *
	 * @return Controller this MultiForm was instanciated on.
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 * Get the current step.
	 * 
	 * If StepID has been set in the URL, we attempt to get that record
	 * by the ID. Otherwise, we check if there's a current step ID in
	 * our session record. Failing those cases, we assume that the form has
	 * just been started, and so we create the first step and return it.
	 * 
	 * @return MultiFormStep subclass
	 */
	public function getCurrentStep() {
		$startStepClass = $this->stat('start_step');
		
		// Check if there was a start step defined on the subclass of MultiForm
		if(!isset($startStepClass)) user_error('MultiForm::init(): Please define a $startStep on ' . $this->class, E_USER_ERROR);
		
		// Determine whether we use the current step, or create one if it doesn't exist
		if(isset($_GET['StepID'])) {
			$stepID = (int)$_GET['StepID'];
			$currentStep = DataObject::get_one('MultiFormStep', "SessionID = {$this->session->ID} AND ID = {$stepID}");
		} elseif($this->session->CurrentStepID) {
			$currentStep = $this->session->CurrentStep();
		} else {
			$currentStep = new $startStepClass();
			$currentStep->SessionID = $this->session->ID;
			$currentStep->write();
		}
		
		return $currentStep;
	}

	/**
	 * Set the step passed in as the current step.
	 * 
	 * @param MultiFormStep $step A subclass of MultiFormStep
	 * @return boolean The return value of write()
	 */
	protected function setCurrentStep($step) {
		$this->session->CurrentStepID = $step->ID;
		return $this->session->write();
	}
	
	/**
	 * Accessor method to $this->session.
	 * 
	 * @return MultiFormSession
	 */
	function getSession() {
		return $this->session;
	}
	
	/**
	 * Set up the session.
	 * 
	 * If MultiFormSessionID isn't set, we assume that this is a new
	 * multiform that requires a new session record to be created.
	 * 
	 * @TODO Fix the fact you can continually refresh and create new records
	 * if MultiFormSessionID isn't set.
	 * 
	 * @TODO Not sure if we should bake the session stuff directly into MultiForm.
	 * Perhaps it would be best dealt with on a separate class?
	 */
	protected function setSession() {
		// If there's a MultiFormSessionID variable set, find that, otherwise create a new session
		if(isset($_GET['MultiFormSessionID'])) {
			$this->session = $this->getSessionRecord($_GET['MultiFormSessionID']);
		}

		// If there was no session found, create a new one instead
		if(!$this->session) {
			$this->session = new MultiFormSession();
			$this->session->write();
		}
			
		// Create encrypted identification to the session instance if it doesn't exist
		if(!$this->session->Hash) {
			$this->session->Hash = sha1($this->session->ID . '-' . microtime());
			$this->session->write();
		}
	}
	
	/**
	 * Return an instance of MultiFormSession.
	 *
	 * @param string $hash The unique, encrypted hash to identify the session
	 * @return MultiFormSession
	 */
	function getSessionRecord($hash) {
		$SQL_hash = Convert::raw2sql($hash);
		return DataObject::get_one('MultiFormSession', "Hash = '$SQL_hash' AND IsComplete = 0");
	}
	
	/**
	 * Get all steps saved in the database for the currently active session,
	 * in the order they were saved, oldest to newest (automatically ordered by ID).
	 * If you want a full chain of steps regardless if they've already been saved
	 * to the database, use {@link getAllStepsLinear()}.
	 * 
	 * @return DataObjectSet|boolean A set of MultiFormStep subclasses
	 */
	function getSavedSteps() {
		return DataObject::get(
			'MultiFormStep', 
			sprintf("SessionID = '%s'",
				$this->session->ID
			)
		);
	}
	
	/**
	 * Get a step which was previously saved to the database in the current session.
	 * Caution: This might cause unexpected behaviour if you have multiple steps
	 * in your chain with the same classname.
	 * 
	 * @param string $className Classname of a {@link MultiFormStep} subclass
	 * @return MultiFormStep
	 */
	function getSavedStepByClass($className) {
		return DataObject::get_one(
			'MultiFormStep', 
			sprintf("SessionID = '%s' AND ClassName = '%s'",
				$this->session->ID,
				Convert::raw2sql($className)
			)
		);
	}

	/**
	 * Build a FieldSet of the FormAction fields for the given step.
	 * 
	 * If the current step is the final step, we push in a submit button, which
	 * calls the action {@link finish()} to finalise the submission. Otherwise,
	 * we push in a next button which calls the action {@link next()} to determine
	 * where to go next in our step process, and save any form data collected.
	 * 
	 * If there's a previous step (a step that has the current step as it's next
	 * step class), then we allow a previous button, which calls the previous action
	 * to determine which step to go back to.
	 * 
	 * If there are any extra actions defined in MultiFormStep->getExtraActions()
	 * then that set of actions is appended to the end of the actions FieldSet we
	 * have created in this method.
	 * 
	 * @param $currentStep Subclass of MultiFormStep
	 * @return FieldSet of FormAction objects
	 */
	function actionsFor($step) {
		// Create default multi step actions (next, prev), and merge with extra actions, if any
		$actions = new FieldSet();
		
		// If the form is at final step, create a submit button to perform final actions
		// The last step doesn't have a next button, so add that action to any step that isn't the final one
		if($step->isFinalStep()) {
			$actions->push(new FormAction('finish', _t('MultiForm.SUBMIT', 'Submit')));
		} else {
			$actions->push(new FormAction('next', _t('MultiForm.NEXT', 'Next')));
		}
		
		// If there is a previous step defined, add the back button
		if($step->getPreviousStep() && $step->canGoBack()) {
			// If there is a next step, insert the action before the next action
			if($step->getNextStep()) {
				$actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_next');
			// Assume that this is the last step, insert the action before the finish action
			} else {
				$actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_finish');
			}
		}

		// Merge any extra action fields defined on the step
		$actions->merge($step->getExtraActions());
		
		return $actions;
	}
	
	/**
	 * Return a rendered version of this form, with a specific template.
	 * Looks through the step ancestory templates (MultiFormStep, current step
	 * subclass template) to see if one is available to render the form with. If
	 * any of those don't exist, look for a default Form template to render
	 * with instead.
	 *
	 * @return SSViewer object to render the template with
	 */
	function forTemplate() {
		return $this->renderWith(array(
			$this->getCurrentStep()->class,
			'MultiFormStep',
			$this->class,
			'MultiForm',
			'Form'
		));
	}
	
	/**
	 * This method saves the data on the final step, after submitting.
	 * It should always be overloaded with parent::finish($data, $form)
	 * so you can create your own functionality which handles saving
	 * of all the data collected through each step of the form.
	 * 
	 * @param array $data The request data returned from the form
	 * @param object $form The form that the action was called on
	 */
	public function finish($data, $form) {
		if(!$this->getCurrentStep()->isFinalStep()) {
			Director::redirectBack();
			return false;
		}
		
		// Save the form data for the current step
		$this->save($data);
	}
	
	/**
	 * Determine what to do when the next action is called.
	 * 
	 * Saves the current step session data to the database, creates the
	 * new step based on getNextStep() of the current step (or fetches
	 * an existing one), resets the current step to the next step,
	 * then redirects to the newly set step.
	 * 
	 * @param array $data The request data returned from the form
	 * @param object $form The form that the action was called on
	 */
	public function next($data, $form) {
		// Get the next step class
		$nextStepClass = $this->getCurrentStep()->getNextStep();
		
		if(!$nextStepClass) {
			Director::redirectBack();
			return false;
		}

		// custom validation (use MultiFormStep->getValidator() for built-in functionality)
		if(!$this->getCurrentStep()->validateStep($data, $form)) {
			Director::redirectBack();
			return false;
		}
		
		// Save the form data for the current step
		$this->save($form->getData());

		// Determine whether we can use a step already in the DB, or have to create a new one
		if(!$nextStep = DataObject::get_one($nextStepClass, "SessionID = {$this->session->ID}")) {
			$nextStep = new $nextStepClass();
			$nextStep->SessionID = $this->session->ID;
			$nextStep->write();
		}

		// Set the next step found as the current step
		$this->setCurrentStep($nextStep);
		
		// Redirect to the next step
		Director::redirect($nextStep->Link());
		return;
	}
	
	/**
	 * Determine what to do when the previous action is called.
	 * 
	 * Retrieves the previous step class, finds the record for that
	 * class in the DB, and sets the current step to that step found.
	 * Finally, it redirects to that step.
	 * 
	 * @param array $data The request data returned from the form
	 * @param object $form The form that the action was called on
	 */
	public function prev($data, $form) {
		if(!$this->getCurrentStep()->getPreviousStep() && !$this->getCurrentStep()->canGoBack()) {
			Director::redirectBack();
			return false;
		}
		
		// Switch the step to the previous!
		$prevStepClass = $this->getCurrentStep()->getPreviousStep();

		// Save the form data for the current step
		$this->save($data);
		
		// Get the previous step of the class instance returned from $currentStep->getPreviousStep()
		$prevStep = DataObject::get_one($prevStepClass, "SessionID = {$this->session->ID}");
		
		// Set the current step as the previous step
		$this->setCurrentStep($prevStep);
		
		// Redirect to the previous step
		Director::redirect($prevStep->Link());
		return;
	}

	/**
	 * Save the raw data given back from the form into session.
	 * 
	 * Take the submitted form data for the current step, removing
	 * any key => value pairs that shouldn't be saved, then saves
	 * the data into the session.
	 * 
	 * @param array $data An array of data to save
	 */
	protected function save($data) {
		$currentStep = $this->getCurrentStep();
		if(is_array($data)) {
			foreach($data as $field => $value) {
				if(in_array($field, $this->stat('ignored_fields'))) {
					unset($data[$field]);
				}
			}
			$currentStep->saveData($data);
		}
		return;
	}
	
	// ############ Misc ############
	
	/**
	 * Add the MultiFormSessionID variable to the URL on form submission.
	 * This is a means to persist the session, by adding it's identification
	 * to the URL, which ties it back to this MultiForm instance.
	 * 
	 * @return string
	 */
	function FormAction() {
		$action = parent::FormAction();
		$action .= (strpos($action, '?')) ? '&amp;' : '?';
		$action .= "MultiFormSessionID={$this->session->Hash}";
		
		return $action;
	}

	/**
	 * Determine the steps to show in a linear fashion, starting from the
	 * first step. We run {@link getAllStepsRecursive} passing the steps found
	 * by reference to get a listing of the steps.
	 *
	 * @return DataObjectSet
	 */
	public function getAllStepsLinear() {
		$stepsFound = new DataObjectSet();
		
		$firstStep = DataObject::get_one($this->stat('start_step'), "SessionID = {$this->session->ID}");
		$templateData = array(
			'ID' => $firstStep->ID,
			'ClassName' => $firstStep->class,
			'Title' => $firstStep->title ? $firstStep->title : $firstStep->class,
			'SessionID' => $this->session->Hash,
			'LinkingMode' => ($firstStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link'
		);
		$stepsFound->push(new ArrayData($templateData));

		$this->getAllStepsRecursive($firstStep, $stepsFound);
		
		return $stepsFound;
	}
	
	/**
	 * Recursively run through steps using the getNextStep() method on each step
	 * to determine what the next step is, gathering each step along the way.
	 * We stop on the last step, and return the results.
	 * If a step in the chain was already saved to the database in the current
	 * session, its used - otherwise a singleton of this step is used.
	 * Caution: Doesn't consider branching for steps which aren't in the database yet.
	 * 
	 * @param $step Subclass of MultiFormStep to find the next step of
	 * @param $stepsFound $stepsFound DataObjectSet reference, the steps found to call back on
	 * @return DataObjectSet
	 */
	protected function getAllStepsRecursive($step, &$stepsFound) {
		// Find the next step to the current step, the final step has no next step
		if(!$step->isFinalStep()) {
			if($step->getNextStep()) {
				// Is this step in the DB? If it is, we use that
				if($nextStep = $step->getNextStepFromDatabase()) {
					$record = array(
						'ID' => $nextStep->ID,
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->title ? $nextStep->title : $nextStep->class,
						'SessionID' => $this->session->Hash,
						'LinkingMode' => ($nextStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link'
					);
				} else {
					// If it's not in the DB, we use a singleton instance of it instead - this step hasn't been accessed yet
					$nextStep = singleton($step->getNextStep());
					$record = array(
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->title ? $nextStep->title : $nextStep->class
					);
				}
				// Add the array data, and do a callback
				$stepsFound->push(new ArrayData($record));
				$this->getAllStepsRecursive($nextStep, $stepsFound);
			}
		// Once we've reached the final step, we just return what we've collected
		} else {
			return $stepsFound;
		}
	}
	
	/**
	 * Number of steps already completed (excluding currently started step).
	 * The way we determine a step is complete is to check if it has the Data
	 * field filled out with a serialized value, then we know that the user has
	 * clicked next on the given step, to proceed.
	 * 
	 * @TODO Not sure if it's entirely appropriate to check if Data is set as a
	 * way to determine a step is "completed".
	 * 
	 * @return int
	 */
	public function getCompletedStepCount() {
		$steps = DataObject::get('MultiFormStep', "SessionID = {$this->session->ID} && Data IS NOT NULL");
		return $steps ? $steps->Count() : 0;
	}
	
	/**
	 * Total number of steps in the shortest path (only counting straight path without any branching)
	 * The way we determine this is to check if each step has a next_step string variable set. If it's
	 * anything else (like an array, for defining multiple branches) then it gets counted as a single step.
	 *
	 * @return int
	 */
	public function getTotalStepCount() {
		return $this->getAllStepsLinear() ? $this->getAllStepsLinear()->Count() : 0;
	}
	
	/**
	 * Percentage of steps completed (excluding currently started step)
	 *
	 * @return float
	 */
	public function getCompletedPercent() {
		return (float)$this->CompletedStepCount * 100 / $this->TotalStepCount;
	}

	/**
	 * Determines whether the field is an action. This checks the string name of the
	 * field, and not the actual field object of one. The actual checking is done
	 * by doing a string check to see if "action_" is prefixed to the name of the
	 * field. For example, in the form system: FormAction('next', 'Next') field
	 * gives an ID of "action_next"
	 * 
	 * The assumption here is the ID we're checking against has the prefix that we're
	 * looking for, otherwise this won't work.
	 * 
	 * @param string $fieldName The name of the field to check is an action
	 * @param string $prefix The prefix of the string to check for, default is "action_"
	 * @return boolean
	 */
	public static function is_action_field($fieldName, $prefix = 'action_') {
		if(substr((string)$fieldName, 0, strlen($prefix)) == $prefix) return true;
	}
	
}

?>