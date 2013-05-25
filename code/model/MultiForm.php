<?php

/**
 * MultiForm manages the loading of single form steps, and acts as a state
 * machine that connects to a {@link MultiFormSession} object as a persistence
 * layer.
 * 
 * CAUTION: If you're using controller permission control,
 * you have to allow the following methods:
 *
 * <code>
 * private static $allowed_actions = array('next','prev');
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
	 * The current encrypted MultiFormSession identification.
	 * @var string
	 */
	protected $currentSessionHash;
	
	/**
	 * Defines which subclass of {@link MultiFormStep} should be the first
	 * step in the multi-step process.
	 *
	 * @var string Classname of a {@link MultiFormStep} subclass
	 */
	public static $start_step;
	
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
	 * Any of the actions defined in this variable are exempt from
	 * being validated.
	 * 
	 * This is most useful for the "Back" (action_prev) action, as
	 * you typically don't validate the form when the user is going
	 * back a step.
	 * 
	 * @var array
	 */
	public static $actions_exempt_from_validation = array(
		'action_prev'
	);

	/**
	 * @var string
	 */
	protected $displayLink;

	/**
	 * Start the MultiForm instance.
	 *
	 * @param Controller instance $controller Controller this form is created on
	 * @param string $name The form name, typically the same as the method name
	 */
	public function __construct($controller, $name) {
		if(isset($_GET['MultiFormSessionID'])) {
			$this->setCurrentSessionHash($_GET['MultiFormSessionID']);
		}

		// First set the controller and name manually so they are available for
		// field construction.
		$this->controller = $controller;
		$this->name       = $name;

		// Set up the session for this MultiForm instance
		$this->setSession();

		// Get the current step available (Note: either returns an existing
		// step or creates a new one if none available)
		$currentStep = $this->getCurrentStep();

		// Set the step returned above as the current step
		$this->setCurrentStep($currentStep);

		// Set the form of the step to this form instance
		$currentStep->setForm($this);

		// Set up the fields for the current step
		$fields = $currentStep->getFields();

		// Set up the actions for the current step
		$actions = $this->actionsFor($currentStep);

		// Set up validation (if necessary)
		$validator = null;
		$applyValidation = true;

		$actionNames = static::$actions_exempt_from_validation;

		if( $actionNames ) {
			foreach( $actionNames as $exemptAction) {
				if(!empty($_REQUEST[$exemptAction])) {
					$applyValidation = false;
					break;
				} 
			}
		}

		// Apply validation if the current step requires validation (is not exempt)
		if($applyValidation) {
			if($currentStep->getValidator()) {
				$validator = $currentStep->getValidator();
			}
		}
		
		// Give the fields, actions, and validation for the current step back to the parent Form class
		parent::__construct($controller, $name, $fields, $actions, $validator);

		// Set a hidden field in our form with an encrypted hash to identify this session.
		$this->fields->push(new HiddenField('MultiFormSessionID', false, $this->session->Hash));
		
		// If there is saved data for the current step, we load it into the form it here
		//(CAUTION: loadData() MUST unserialize first!)
		if($data = $currentStep->loadData()) {
			$this->loadDataFrom($data);
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
		$startStepClass = static::$start_step;
		
		// Check if there was a start step defined on the subclass of MultiForm
		if(!isset($startStepClass)) user_error('MultiForm::init(): Please define a $startStep on ' . $this->class, E_USER_ERROR);
		
		// Determine whether we use the current step, or create one if it doesn't exist
		$currentStep = null;
		if(isset($_GET['StepID'])) {
			$stepID = (int)$_GET['StepID'];
			$currentStep = DataObject::get_one('MultiFormStep', "\"SessionID\" = {$this->session->ID} AND \"ID\" = {$stepID}");
		} elseif($this->session->CurrentStepID) {
			$currentStep = $this->session->CurrentStep();
		} 
		
		// Always fall back to creating a new step (in case the session or request data is invalid)
		if(!$currentStep || !$currentStep->ID) {
			$currentStep = new $startStepClass();
			$currentStep->SessionID = $this->session->ID;
			$currentStep->write();
			$this->session->CurrentStepID = $currentStep->ID;
			$this->session->write();
			$this->session->flushCache();
		}
		
		if($currentStep) $currentStep->setForm($this);
		
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
		$step->setForm($this);

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
		$this->session = $this->getCurrentSession();
		
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
	 * Set the currently used encrypted hash to identify
	 * the MultiFormSession.
	 * 
	 * @param string $hash Encrypted identification to session
	 */
	function setCurrentSessionHash($hash) {
		$this->currentSessionHash = $hash;
	}
	
	/**
	 * Return the currently used {@link MultiFormSession}
	 * @return MultiFormSession|boolean FALSE
	 */
	function getCurrentSession() {
		if(!$this->currentSessionHash) return false;
		$SQL_hash = Convert::raw2sql($this->currentSessionHash);
		return DataObject::get_one('MultiFormSession', "\"Hash\" = '$SQL_hash' AND \"IsComplete\" = 0");
	}
	
	/**
	 * Get all steps saved in the database for the currently active session,
	 * in the order they were saved, oldest to newest (automatically ordered by ID).
	 * If you want a full chain of steps regardless if they've already been saved
	 * to the database, use {@link getAllStepsLinear()}.
	 * 
	 * @param String $filter SQL WHERE statement
	 * @return DataObjectSet|boolean A set of MultiFormStep subclasses
	 */
	function getSavedSteps($filter = null) {
		$filter .= ($filter) ? ' AND ' : '';
		$filter .= sprintf("\"SessionID\" = '%s'", $this->session->ID);
		return DataObject::get('MultiFormStep', $filter);
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
			sprintf("\"SessionID\" = '%s' AND \"ClassName\" = '%s'",
				$this->session->ID,
				Convert::raw2sql($className)
			)
		);
	}

	/**
	 * Build a FieldList of the FormAction fields for the given step.
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
	 * @return FieldList of FormAction objects
	 */
	function actionsFor($step) {
		// Create default multi step actions (next, prev), and merge with extra actions, if any
		$actions = (class_exists('FieldList')) ? new FieldList() : new FieldSet();
		
		// If the form is at final step, create a submit button to perform final actions
		// The last step doesn't have a next button, so add that action to any step that isn't the final one
		if($step->isFinalStep()) {
			$actions->push(new FormAction('finish', $step->getSubmitText()));
		} else {
			$actions->push(new FormAction('next', $step->getNextText()));
		}
		
		// If there is a previous step defined, add the back button
		if($step->getPreviousStep() && $step->canGoBack()) {
			// If there is a next step, insert the action before the next action
			if($step->getNextStep()) {
				$actions->insertBefore(new FormAction('prev', $step->getPrevText()), 'action_next');
			// Assume that this is the last step, insert the action before the finish action
			} else {
				$actions->insertBefore(new FormAction('prev', $step->getPrevText()), 'action_finish');
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
			$this->controller->redirectBack();
			return false;
		}

		if(!$this->getCurrentStep()->validateStep($data, $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			$this->controller->redirectBack();
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
		// Save the form data for the current step
		$this->save($form->getData());

		// Get the next step class
		$nextStepClass = $this->getCurrentStep()->getNextStep();
		
		if(!$nextStepClass) {
			$this->controller->redirectBack();
			return false;
		}

		// Perform custom step validation (use MultiFormStep->getValidator() for
		// built-in functionality). The data needs to be manually saved on error
		// so the form is re-populated.
		if(!$this->getCurrentStep()->validateStep($data, $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			$this->controller->redirectBack();
			return false;
		}

		// validation succeeded so we reset it to remove errors and messages
		$this->resetValidation();

		// Determine whether we can use a step already in the DB, or have to create a new one
		if(!$nextStep = DataObject::get_one($nextStepClass, "\"SessionID\" = {$this->session->ID}")) {
			$nextStep = new $nextStepClass();
			$nextStep->SessionID = $this->session->ID;
			$nextStep->write();
		}

		// Set the next step found as the current step
		$this->setCurrentStep($nextStep);
		
		// Redirect to the next step
		$this->controller->redirect($nextStep->Link());
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
		// Save the form data for the current step
		$this->save($form->getData());

		// Get the previous step class
		$prevStepClass = $this->getCurrentStep()->getPreviousStep();

		if(!$prevStepClass && !$this->getCurrentStep()->canGoBack()) {
			$this->controller->redirectBack();
			return false;
		}

		// Get the previous step of the class instance returned from $currentStep->getPreviousStep()
		$prevStep = DataObject::get_one($prevStepClass, "\"SessionID\" = {$this->session->ID}");
		
		// Set the current step as the previous step
		$this->setCurrentStep($prevStep);
		
		// Redirect to the previous step
		$this->controller->redirect($prevStep->Link());
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
				if(in_array($field, static::$ignored_fields)) {
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
	 * Returns the link to the page where the form is displayed. The user is
	 * redirected to this link with a session param after each step is
	 * submitted.
	 *
	 * @return string
	 */
	public function getDisplayLink() {
		return $this->displayLink ? $this->displayLink : Controller::curr()->Link();
	}

	/**
	 * Set the link to the page on which the form is displayed.
	 *
	 * The link defaults to the controllers current link. However if the form
	 * is displayed inside an action the display link must be explicitly set.
	 *
	 * @param string $link
	 */
	public function setDisplayLink($link) {
		$this->displayLink = $link;
	}

	/**
	 * Determine the steps to show in a linear fashion, starting from the
	 * first step. We run {@link getAllStepsRecursive} passing the steps found
	 * by reference to get a listing of the steps.
	 *
	 * @return DataObjectSet of MultiFormStep instances
	 */
	public function getAllStepsLinear() {
		$stepsFound = (class_exists('ArrayList')) ? new ArrayList() : new DataObjectSet();
		
		$firstStep = DataObject::get_one(static::$start_step, "\"SessionID\" = {$this->session->ID}");
		$firstStep->LinkingMode = ($firstStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link';
		$firstStep->setForm($this);
		$stepsFound->push($firstStep);

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
	 * @return DataObjectSet of MultiFormStep instances
	 */
	protected function getAllStepsRecursive($step, &$stepsFound) {
		// Find the next step to the current step, the final step has no next step
		if(!$step->isFinalStep()) {
			if($step->getNextStep()) {
				// Is this step in the DB? If it is, we use that
				$nextStep = $step->getNextStepFromDatabase();
				if(!$nextStep) {
					// If it's not in the DB, we use a singleton instance of it instead - this step hasn't been accessed yet
					$nextStep = singleton($step->getNextStep());
				} 
				$nextStep->LinkingMode = ($nextStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link';
				$nextStep->setForm($this);
				// Add the array data, and do a callback
				$stepsFound->push($nextStep);
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
		$steps = DataObject::get('MultiFormStep', "\"SessionID\" = {$this->session->ID} && \"Data\" IS NOT NULL");
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

}
