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
	 * By default, we store a hash, for example: http://mysite.com/my-form/?MultiFormSessionID=de9f2c7fd25e1b3afad3e850bd17d9b100db4b3
	 * Alternatively, if you set this variable to "ID", then you get ?MultiFormSessionID=20
	 * 
	 * The ID is not as secure as the hash, but it all depends on your set up.
	 * If you're going to add security, such as check the SubmitterID on init
	 * of the MultiForm and use "ID" for this parameter, then security should be fine.
	 * 
	 * In any other case, where there's no Member tied to a MultiFormSession, using
	 * the Hash is the recommended approach.
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
	 * is intanciated on your controller. @TODO perhaps find a better name, that doesn't quite conflict.
	 * 
	 * This method sets up the session, figures out the current step, sets the current step, and 
	 * 
	 */
	public function init() {
		// Set up the session
		$this->setSession();
		
		// Get the current step, and set it
		$currentStep = $this->getCurrentStep();
		$this->setCurrentStep($currentStep);
		
		// Set up the fields from the current step
		$this->setFields($currentStep->getFields());
		
		// Set up the actions from the current step
		$this->setActions();
		
		// Set a hidden field in the form to define what this form session ID is
		$this->fields->push(new HiddenField('MultiFormSessionID', false, ($this->stat('url_type') == 'ID') ? $this->session->ID : $this->session->Hash));
		
		// Set up validator from the form step class
		$this->validator = $currentStep->getValidator();
		
		// If there is form data, we populate it here (CAUTION: loadData() MUST unserialize first!)
		if($currentStep->loadData()) {
			$this->loadDataFrom($currentStep->loadData());
		}
	}

	/**
	 * Get the current step.
	 * @return MultiFormStep subclass
	 */
	public function getCurrentStep() {
		$startStepClass = $this->stat('start_step');
		
		// Check if there was a start step defined on the subclass of MultiForm
		if(!isset($startStepClass)) user_error('MultiForm::init(): Please define a $startStep on ' . $this->class, E_USER_ERROR);
		
		// Determine whether we use the current step, or create one if it doesn't exist
		if(isset($_GET['StepID'])) {
			$stepID = (int)$_GET['StepID'];
			$step = DataObject::get_one('MultiFormStep', "SessionID = {$this->session->ID} AND ID = {$stepID}");
			if($step) {
				$currentStep = $step;
			}
		// @TODO if you set a wrong ID, then it ends up at this point with a non-object error.
		} elseif($this->session->CurrentStepID) {
			$currentStep = $this->session->CurrentStep();
		} else {
			// @TODO fix the fact that you can continually refresh on the first step creating new records
			// @TODO encapsulate this into it's own method - it's the same code as the next() method anyway
			$currentStep = new $startStepClass();
			$currentStep->SessionID = $this->session->ID;
			$currentStep->write();
		}
		return $currentStep;
	}

	/**
	 * Set the step passed in as the current step.
	 * @param MultiFormStep $step A subclass of MultiFormStep
	 */
	protected function setCurrentStep($step) {
		$this->session->CurrentStepID = $step->ID;
		$this->session->write();
	}
	
	/**
	 * Set up the session. There's a bit of logic to determine this, specifically
	 * checking if there's a GET variable for a current session set.
	 */
	protected function setSession() {
		$urlType = $this->stat('url_type');
		
		// If there's a MultiFormSessionID variable set, find that, otherwise create a new session
		if(isset($_GET['MultiFormSessionID'])) {
			switch($urlType) {
				case 'Hash':
					$this->session = $this->getSessionRecordByHash($_GET['MultiFormSessionID']);
					break;
				case 'ID':
					$this->session = $this->getSessionRecordByID($_GET['MultiFormSessionID']);
					break;
					
				default:
					user_error('MultiForm::init(): Please define a correct value for $url_type on ' . $this->class, E_USER_ERROR);
					break;
			}
		} else {
			// @TODO fix the fact that you can continually refresh on the first step creating new records
			$this->session = new MultiFormSession();
			$this->session->write();
			
			// We have to have an ID, before we can hash the ID of the session. @TODO a better way here?
			if($urlType == 'Hash') $this->session->Hash = sha1($this->session->ID . '-' . microtime());
			$this->session->write(); // I guess we could hash something else than the ID, this is a bit ugly...
		}
	}
	
	/**
	 * Return an instance of MultiFormSession from the database by a single
	 * record with the hash passed into this method.
	 *
	 * @param string $hash The Hash field of the record to retrieve
	 * @return MultiFormSession
	 */
	function getSessionRecordByHash($hash) {
		$SQL_hash = Convert::raw2sql($hash);
		return DataObject::get_one('MultiFormSession', "Hash = '$SQL_hash'");
	}
	
	/**
	 * Return an instance of MultiFormSession from the database by it's ID.
	 *
	 * @param int|string $id The ID of the record to retrieve
	 * @return MultiFormSession
	 */
	function getSessionRecordByID($id) {
		return DataObject::get_by_id('MultiFormSession', $id);
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
		if($this->getCurrentStep()->isFinalStep()) {
			$this->actions->push(new FormAction('finish', _t('MultiForm.SUBMIT', 'Submit')));
		} else {
			$this->actions->push(new FormAction('next', _t('MultiForm.NEXT', 'Next')));
		}

		// If there is a previous step defined, add the back button
		if($this->getCurrentStep()->getPreviousStep()) {
			if($this->actions->fieldByName('action_next')) {
				$this->actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_next');
			} elseif($this->actions->fieldByName('action_finish')) {
				$this->actions->insertBefore(new FormAction('prev', _t('MultiForm.BACK', 'Back')), 'action_finish');
			} else {
				$this->actions->push(new FormAction('prev', _t('MultiForm.BACK', 'Back')));
			}
		}

		// Merge any extra action fields defined on the step
		$this->actions->merge($this->getCurrentStep()->getExtraActions());
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
		if(!$this->getCurrentStep->isFinalStep()) {
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
		if(!$this->getCurrentStep()->getNextStep()) {
			Director::redirectBack();
			return false;
		}
		
		// Switch the step to the next!
		$nextStepClass = $this->getCurrentStep()->getNextStep();
		
		// Save the form data for the current step
		$this->save($data);

		// Determine whether we can use a step already in the DB, or create a new one
		if(!$nextStep = DataObject::get_one($nextStepClass, "SessionID = {$this->session->ID}")) {
			$nextStep = new $nextStepClass();
			$nextStep->SessionID = $this->session->ID;
			$nextStep->write();
		}

		// Set the next step to be the current step
		$this->setCurrentStep($nextStep);
		
		// Redirect to the next step
		Director::redirect($this->getCurrentStep()->Link());
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
		if(!$this->getCurrentStep()->getPreviousStep()) {
			Director::redirectBack();
			return false;
		}
		
		// Switch the step to the previous!
		$prevStepClass = $this->getCurrentStep()->getPreviousStep();

		// Get the previous step of the class instance returned from $currentStep->getPreviousStep()
		$prevStep = DataObject::get_one($prevStepClass, "SessionID = {$this->session->ID}");
		
		// Set the current step as the previous step
		$this->setCurrentStep($prevStep);
		
		// Redirect to the previous step
		Director::redirect($this->getCurrentStep()->Link());
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
		$currentStep = $this->getCurrentStep();
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
		$urlMethod = $this->stat('url_type');
		$action = parent::FormAction();
		$action .= (strpos($action, '?')) ? '&amp;' : '?';
		$action .= "MultiFormSessionID={$this->session->$urlMethod}";
		
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
			'SessionID' => ($this->stat('url_type') == 'ID') ? $this->session->ID : $this->session->Hash,
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
				if($nextStep = $step->getNextStepFromDatabase()) {
					$record = array(
						'ID' => $nextStep->ID,
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->getTitle(),
						'SessionID' => ($this->stat('url_type') == 'ID') ? $this->session->ID : $this->session->Hash,
						'LinkingMode' => ($nextStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link'
					);
				} else {
					// If it's not in the DB, we use a singleton instance of it instead - this step hasn't been accessed yet
					$nextStep = singleton($step->getNextStep());
					$record = array(
						'ClassName' => $nextStep->class,
						'Title' => $nextStep->getTitle()
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