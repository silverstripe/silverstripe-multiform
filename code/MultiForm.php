<?php

/**
 * Manages the loading of single form steps, and acts as a state machine
 * that connects to a {@link MultiFormSession} object as a persistence layer.
 * 
 * CAUTION: If you're using controller permission control,
 * you have to allow the following methods:
 * <code>
 * static $allowed_actions = array('next','prev');
 * </code> 
 * 
 * @todo Deal with Form->securityID
 * 
 * @package multiform
 */
abstract class MultiForm extends Form {
	
	/**
	 * A session object stored in the database, which might link
	 * to further temporary {@link DataObject}s.
	 *
	 * @var MultiFormSession
	 */
	protected $session;
	
	/**
	 * Defines which subclass of {@link MultiFormStep} starts the form -
	 * needs to be defined for the controller to work correctly
	 *
	 * @var string Classname of a {@link MultiFormStep} subclass
	 */
	protected static $start_step; 
	
	/**
	 * Define what type of URL you want to use throughout the step process.
	 * 
	 * The non-secure way is to go by ID, for example: http://mysite.com/my-form/?MultiFormSessionID=50
	 * Alternatively, we store a hash, for example: http://mysite.com/my-form/?MultiFormSessionID=de9f2c7fd25e1b3afad3e850bd17d9b100db4b3
	 *
	 * @var $url_type either "ID", or "Hash"
	 */
	protected static $url_type = 'Hash';
	
	static $casting = array(
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
	static $ignored_fields = array(
		'url',
		'executeForm',
		'MultiFormSessionID',
		'SecurityID'
	);
	
	/**
	 * Perform actions when the multiform is first started.
	 * 
	 * It does NOT work like a normal controller init()! It has to be explicity called when MultiForm
	 * is intanciated on your controller.
	 * 
	 * It sets up the right form session, gets the form step and populates the fields, actions,
	 * and validation (if it's applicable).
	 * 
	 * @TODO We've currently got some start up routines that probably need to be moved to their own method,
	 * like start() - like creating a new MultiFormSession instance.
	 * 
	 * @TODO init() may not be an appropriate name, considering there's already an init() automatically called
	 * for controller classes. Perhaps we rename this?
	 * 
	 * @TODO Security. Currently you're able to just change the ID of MultiFormSessionID in the URL. We need some
	 * sort of identification so you can't just change to another session by changing the ID.
	 * 
	 * @TODO Expiration. We need to make sure that these sessions, making use of {@link MultiFormPurgeTask} and
	 * {@link MultiFormObjectDecorator}
	 */
	public function init() {
		$startStepClass = $this->stat('start_step');
		if(!isset($startStepClass)) user_error('MultiForm::init(): Please define a $startStep', E_USER_ERROR);
		
		// If there's a MultiFormSessionID variable set, find that, otherwise create a new session
		if(isset($_GET['MultiFormSessionID'])) {
			$this->session = DataObject::get_by_id('MultiFormSession', (int)$_GET['MultiFormSessionID']);  
		} else {
			// @TODO fix the fact that you can continually refresh on the first step creating new records
			$this->session = new MultiFormSession();
			$this->session->write();
		}

		// Determine whether we use the current step, or create one if it doesn't exist
		if(isset($_GET['StepID'])) {
			$stepID = (int)$_GET['StepID'];
			$step = DataObject::get_one('MultiFormStep', "SessionID = {$this->session->ID} AND ID = {$stepID}");
			if($step) {
				$currentStep = $step;
				$this->session->CurrentStepID = $currentStep->ID;
				$this->session->write();
			}
		} elseif($this->session->CurrentStepID) {
			$currentStep = $this->session->CurrentStep();
		} else {
			// @TODO fix the fact that you can continually refresh on the first step creating new records
			// @TODO encapsulate this into it's own method - it's the same code as the next() method anyway
			$currentStep = new $startStepClass();
			$currentStep->start();
			$currentStep->SessionID = $this->session->ID;
			$currentStep->write();
			$this->session->CurrentStepID = $currentStep->ID;
			$this->session->write();
		}
		
		// Set up the fields from the current step
		$this->setFields($currentStep->getFields());
		
		// Set up the actions from the current step
		$this->setActions();
		
		// Set a hidden field in the form to define what this form session ID is
		$this->fields->push(new HiddenField('MultiFormSessionID', false, $this->session->ID));
		
		// Set up validator from the form step class
		$this->validator = $currentStep->getValidator();
		
		// If there is form data, we populate it here (CAUTION: loadData() MUST unserialize first!)
		if($currentStep->loadData()) {
			$this->loadDataFrom($currentStep->loadData());
		}
	}

	/**
	 * Set the fields for this form.
	 *
	 * @param FieldSet $fields
	 */
	function setFields($fields) {
		foreach($fields as $field) $field->setForm($this);
		$this->fields = $fields;
	}
	
	/**
	 * Set the actions for this form.
	 * @TODO is it appropriate to call it setActions?
	 * @TODO should we put this on MultiFormStep, so it's easy to override on a per-step basis?
	 */
	function setActions() {
		// Create default multi step actions (next, prev), and merge with extra actions, if any
		$this->actions = new FieldSet();
		
		// If the form is at final step, create a submit button to perform final actions
		// The last step doesn't have a next button, so add that action to any step that isn't the final one
		if($this->session->CurrentStep()->isFinalStep()) {
			$this->actions->push(new FormAction('finish', _t('MultiForm.SUBMIT', 'Submit')));
		} else {
			$this->actions->push(new FormAction('next', _t('MultiForm.NEXT', 'Next')));
		}

		// If there is a previous step defined, add the back button
		if($this->session->CurrentStep()->getPreviousStep()) {
			if($this->actions->fieldByName('action_next')) {
				$this->actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_next');
			} elseif($this->actions->fieldByName('action_finish')) {
				$this->actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_finish');
			} else {
				$this->actions->push(new FormAction('prev', _t('MultiForm.BACK', 'Back')));
			}
		}

		// Merge any extra action fields defined on the step
		$this->actions->merge($this->session->CurrentStep()->getExtraActions());
	}
	
	/**
	 * Return a rendered version of this form, with a specific template.
	 * Looks through the step ancestory templates (MultiFormStep, current step
	 * subclass template) to see if one is available to render the form with. If
	 * any of those don't exist, look for a default Form template to render
	 * with instead.
	 */
	function forTemplate() {
		return $this->renderWith(array(
			$this->session->CurrentStep()->class,
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
		if(!$this->session->CurrentStep()->isFinalStep()) {
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
	 * new step based on getNextStep() of the current step, resets the current
	 * step to the next step, then redirects to the step.
	 * 
	 * @param array $data The request data returned from the form
	 * @param object $form The form that the action was called on
	 */
	public function next($data, $form) {
		if(!$this->session->CurrentStep()->getNextStep()) {
			Director::redirectBack();
			return false;
		}
		
		// Switch the step to the next!
		$nextStepClass = $this->session->CurrentStep()->getNextStep();
		
		// Save the form data for the current step
		$this->save($data);

		// Determine whether we can use a step already in the DB, or create a new one
		if(!$nextStep = DataObject::get_one($nextStepClass, "SessionID = {$this->session->ID}")) {
			$nextStep = new $nextStepClass();
			$nextStep->SessionID = $this->session->ID;
		}

		$nextStep->finish();
		$nextStep->write();
		$this->session->CurrentStepID = $nextStep->ID;
		$this->session->write();
		
		// Redirect to the next step
		Director::redirect($this->session->CurrentStep()->Link());
		return;
	}
	
	/**
	 * Determine what to do when the previous action is called.
	 * 
	 * Saves the current step session data to the database, retrieves the
	 * previous step instance based on the classname returned by getPreviousStep()
	 * on the current step instance, and resets the current step to the previous
	 * step found, then redirects to the step.
	 * 
	 * @TODO handle loading the data back into the previous step, from session.
	 * 
	 * @param array $data The request data returned from the form
	 * @param object $form The form that the action was called on
	 */
	public function prev($data, $form) {
		if(!$this->session->CurrentStep()->getPreviousStep()) {
			Director::redirectBack();
			return false;
		}
		
		// Switch the step to the previous!
		$prevStepClass = $this->session->CurrentStep()->getPreviousStep();

		// Get the previous step of the class instance returned from $currentStep->getPreviousStep()
		$prevStep = DataObject::get_one($prevStepClass, "SessionID = {$this->session->ID}");
		
		// Set the current step as the previous step
		$this->session->CurrentStepID = $prevStep->ID;
		$this->session->write();
		
		// Redirect to the previous step
		Director::redirect($this->session->CurrentStep()->Link());
		return;
	}

	/**
	 * Save the raw data given back from the form into session.
	 * 
	 * Harmful values provided from the internal form system will be unset from
	 * the map as defined in self::$ignored_fields. It also unsets any fields
	 * that look be be form action values, since they aren't required either.
	 * 
	 * @param array $data An array of data to save
	 */
	protected function save($data) {
		$currentStep = $this->session->CurrentStep();
		if(is_array($data)) {
			foreach($data as $field => $value) {
				if(in_array($field, self::$ignored_fields) || self::is_action_field($field)) {
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
	 * We use this to determine what session the multiform is currently using.
	 * 
	 * @return string
	 */
	function FormAction() {
		$action = parent::FormAction();
		$action .= (strpos($action, '?')) ? '&amp;' : '?';
		$action .= "MultiFormSessionID={$this->session->ID}";
		
		return $action;
	}

	/**
	 * Determine the steps to show in a linear fashion, starting from the
	 * first step. We run a recursive function passing the steps found
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
			'Title' => $firstStep->getTitle(),
			'SessionID' => $firstStep->SessionID,
			'LinkingMode' => ($firstStep->ID == $this->session->CurrentStep()->ID) ? 'current' : 'link'
		);
		$stepsFound->push(new ArrayData($templateData));

		$this->getAllStepsRecursive($firstStep, $stepsFound);
		
		return $stepsFound;
	}
	
	/**
	 * Recursively run through steps using the getNextStep() method on each step
	 * to determine what the next step is, gathering each step along the way.
	 * We stop on the last step, and return the results.
	 * 
	 * @TODO make use of $step->getNextStepFromDatabase() instead of doing a direct
	 * DataObject::get() which is doing the same thing.
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
				if($nextSteps = DataObject::get($step->getNextStep(), "SessionID = {$this->session->ID}", "LastEdited DESC")) {
					$nextStep = $nextSteps->First();
					$templateData = array(
						'ID' => $nextStep->ID,
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->getTitle(),
						'SessionID' => $nextStep->SessionID,
						'LinkingMode' => ($nextStep->ID == $this->session->CurrentStep()->ID) ? 'current' : 'link'
					);
					$stepsFound->push(new ArrayData($templateData));
				} else {
					// If it's not in the DB, we use a singleton instance of it instead - this step hasn't been accessed yet
					$nextStep = singleton($step->getNextStep());
					$templateData = array(
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->getTitle()
					);
					$stepsFound->push(new ArrayData($templateData));
				}
				// Call back so we can recursively step through
				$this->getAllStepsRecursive($nextStep, $stepsFound);
			}
		// Once we've reached the final step, we just return what we've collected
		} else {
			return $stepsFound;
		}
	}
	
	/**
	 * Returns the current step in the form process.
	 * 
	 * @return Instance of a MultiFormStep subclass
	 */
	public function getCurrentStep() {
		return $this->session->CurrentStep();
	}
	
	/**
	 * Number of steps already completed (excluding currently started step).
	 * The way we determine a step is complete is to check if it has the Data
	 * field filled out with a serialized value, then we know that the user has
	 * clicked next on the given step, to proceed.
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
	 * @param string $fieldName The name of the field to check is an action
	 * @param string $prefix The prefix of the string to check for, default is "action_"
	 * @return boolean
	 */
	public static function is_action_field($fieldName, $prefix = 'action_') {
		if(substr((string)$fieldName, 0, strlen($prefix)) == $prefix) return true;
	}
	
}

?>