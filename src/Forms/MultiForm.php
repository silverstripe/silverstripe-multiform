<?php

namespace SilverStripe\MultiForm\Forms;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\MultiForm\Models\MultiFormSession;
use SilverStripe\MultiForm\Models\MultiFormStep;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * MultiForm manages the loading of single form steps, and acts as a state
 * machine that connects to a {@link MultiFormSession} object as a persistence
 * layer.
 *
 * CAUTION: If you're using controller permission control,
 * you have to allow the following methods:
 *
 * <code>
 * private static $allowed_actions = ['next','prev'];
 * </code>
 *
 */
abstract class MultiForm extends Form
{
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * A session object stored in the database, to identify and store
     * data for this MultiForm instance.
     *
     * @var MultiFormSession|null
     */
    protected $session;

    /**
     * The current encrypted MultiFormSession identification.
     *
     * @var string
     */
    protected $currentSessionHash;

    /**
     * Defines which subclass of {@link MultiFormStep} should be the first
     * step in the multi-step process.
     *
     * @var string|null Classname of a {@link MultiFormStep} subclass
     */
    private static string|null $start_step = null;

    /**
     * Set the casting for these fields.
     *
     * @var array
     */
    private static array $casting = [
        'CompletedStepCount' => 'Int',
        'TotalStepCount' => 'Int',
        'CompletedPercent' => 'Float'
    ];

    /**
     * @var string
     */
    private static string $get_var = 'MultiFormSessionID';

    /**
     * These fields are ignored when saving the raw form data into session.
     * This ensures only field data is saved, and nothing else that's useless
     * or potentially dangerous.
     *
     * @var array
     */
    private static $ignored_fields = [
        'url',
        'executeForm',
        'SecurityID'
    ];

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
    private static $actions_exempt_from_validation = [
        'action_prev'
    ];

    /**
     * @var string
     */
    protected $displayLink;

    /**
     * Flag which is being used in getAllStepsRecursive() to allow adding the completed flag on the steps
     *
     * @var boolean
     */
    protected $currentStepHasBeenFound = false;

    /**
     * Start the MultiForm instance.
     *
     * @param Controller $controller Controller instance this form is created on
     * @param string $name The form name, typically the same as the method name
     */
    public function __construct($controller, $name)
    {
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

        // Give the fields, actions, and validation for the current step back to the parent Form class
        parent::__construct($controller, $name, $fields, $actions);

        // Set up validation (if necessary)
        $validator = null;
        $applyValidation = true;

        $actionNames = $this->config()->get('actions_exempt_from_validation');

        if ($actionNames) {
            foreach ($actionNames as $exemptAction) {
                if (!empty($this->getRequest()->requestVar($exemptAction))) {
                    $applyValidation = false;
                    break;
                }
            }
        }

        // Apply validation if the current step requires validation (is not exempt)
        if ($applyValidation) {
            if ($currentStep->getValidator()) {
                $this->setValidator($currentStep->getValidator());
            }
        }

        $getVar = $this->getGetVar();

        // Set a hidden field in our form with an encrypted hash to identify this session.
        $this->fields->push(HiddenField::create($getVar, false, $this->session->Hash));

        // If there is saved data for the current step, we load it into the form it here
        //(CAUTION: loadData() MUST unserialize first!)
        if ($data = $currentStep->loadData()) {
            $this->loadDataFrom($data);
        }

        // Disable security token - we tie a form to a session ID instead
        $this->disableSecurityToken();

        $this->config()->merge('ignored_fields', [$getVar]);
    }

    /**
     * Accessor method to $this->controller.
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * Returns the get_var to the template engine
     */
    public function getGetVar(): string
    {
        $var = (string) $this->config()->get('get_var');

        return $var !== '' ? $var : 'MultiFormSessionID';
    }

    /**
     * Get the current step.
     *
     * If StepID has been set in the URL, we attempt to get that record
     * by the ID. Otherwise, we check if there's a current step ID in
     * our session record. Failing those cases, we assume that the form has
     * just been started, and so we create the first step and return it.
     *
     * @return MultiFormStep
     * @throws LogicException
     */
    public function getCurrentStep(): MultiFormStep
    {
        $startStepClass = $this->config()->get('start_step');

        // Check if there was a start step defined on the subclass of MultiForm
        if (!$startStepClass) {
            // check for a method getStartStep()
            if (method_exists($this, 'getStartStep')) {
                $startStepClass = $this->getStartStep();
            } else {
                throw new LogicException('MultiForm::init(): Please define a $start_step on ' . static::class);
            }
        }

        // Determine whether we use the current step, or create one if it doesn't exist
        $currentStep = null;
        $StepID = $this->controller->getRequest()->getVar('StepID');
        if ($StepID !== null) {
            $currentStep = MultiFormStep::get()->filter([
                'SessionID' => $this->session->ID,
                'ID' => $StepID
            ])->first();
        }

        // if current step doesn't exist and no session current step then get the current step
        if (!$currentStep && $this->session->CurrentStepID) {
            $currentStep = $this->session->CurrentStep();
        }

        // Always fall back to creating a new step (in case the session or request data is invalid)
        if (!$currentStep || !$currentStep->ID) {
            $currentStep = Injector::inst()->create($startStepClass);
            $currentStep->SessionID = $this->session->ID;
            $currentStep->write();

            $this->session->CurrentStepID = $currentStep->ID;
            $this->session->write();
            $this->session->flushCache();
        }

        $currentStep->setForm($this);

        return $currentStep;
    }

    /**
     * Set the step passed in as the current step.
     */
    protected function setCurrentStep(MultiFormStep $step): int
    {
        $this->session->CurrentStepID = $step->ID;
        $step->setForm($this);

        return $this->session->write();
    }

    /**
     * Accessor method to $this->session.
     */
    public function getMultiFormSession(): MultiFormSession|null
    {
        if (!$this->session) {
            $this->setSession();
        }
        return $this->session;
    }

    /**
     * Set up the session.
     *
     * If MultiFormSessionID isn't set, we assume that this is a new multiform that requires a new session record to be
     * created.
     */
    protected function setSession(): void
    {
        $this->session = $this->getCurrentSession();

        // If there was no session found, create a new one instead
        if (!$this->session) {
            $session = MultiFormSession::create();
            $session->write();
            $this->session = $session;
        }
    }

    /**
     * Set the currently used encrypted hash to identify the MultiFormSession.
     */
    public function setCurrentSessionHash(string $hash): void
    {
        $this->currentSessionHash = $hash;
        $this->setSession();
    }

    /**
     * Return the currently used {@link MultiFormSession}
     * @return MultiFormSession|null
     */
    public function getCurrentSession()
    {
        if (!$this->currentSessionHash) {
            $this->currentSessionHash = $this->controller->getRequest()->getVar($this->getGetVar());

            if (!$this->currentSessionHash) {
                return null;
            }
        }

        $session = MultiFormSession::get()->filter([
            "Hash" => $this->currentSessionHash,
            "IsComplete" => 0
        ])->first();

        $this->session = $session instanceof MultiFormSession ? $session : null;

        return $this->session;
    }

    /**
     * Get all steps saved in the database for the currently active session,
     * in the order they were saved, oldest to newest (automatically ordered by ID).
     * If you want a full chain of steps regardless if they've already been saved
     * to the database, use {@link getAllStepsLinear()}.
     *
     * @param string|null $filter SQL WHERE statement
     * @return DataList<MultiFormStep> A set of MultiFormStep subclasses
     */
    public function getSavedSteps(?string $filter = null): DataList
    {
        $filter .= ($filter) ? ' AND ' : '';
        $filter .= sprintf("\"SessionID\" = '%s'", $this->session->ID);
        /** @var DataList<MultiFormStep> $steps */
        $steps = MultiFormStep::get()->where($filter);
        return $steps;
    }

    /**
     * Get a step which was previously saved to the database in the current session.
     * Caution: This might cause unexpected behaviour if you have multiple steps
     * in your chain with the same classname.
     *
     * @param string $className Classname of a {@link MultiFormStep} subclass
     * @return DataObject
     */
    public function getSavedStepByClass($className)
    {
        return MultiFormStep::get()->filter([
            'SessionID' => $this->session->ID,
            'ClassName' => $className
        ])->first();
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
     * @param MultiFormStep $step Subclass of MultiFormStep
     * @return FieldList of FormAction objects
     */
    public function actionsFor($step)
    {
        // Create default multi step actions (next, prev), and merge with extra actions, if any
        $actions = FieldList::create();

        // If the form is at final step, create a submit button to perform final actions
        // The last step doesn't have a next button, so add that action to any step that isn't the final one
        if ($step->isFinalStep()) {
            $actions->push(FormAction::create('finish', $step->getSubmitText()));
        } else {
            $actions->push(FormAction::create('next', $step->getNextText()));
        }

        // If there is a previous step defined, add the back button
        if ($step->getPreviousStep() && $step->canGoBack()) {
            $prev = FormAction::create('prev', $step->getPrevText());

            // If there is a next step, insert the action before the next action
            if ($step->getNextStep()) {
                $actions->insertBefore('action_next', $prev);
            } else {
                $actions->insertBefore('action_finish', $prev);
            }
            //remove browser validation from prev action
            $prev->setAttribute("formnovalidate", "formnovalidate");
        }

        // Merge any extra action fields defined on the step
        $actions->merge($step->getExtraActions());

        return $actions;
    }

    /**
     * This method saves the data on the final step, after submitting.
     * It should always be overloaded with parent::finish($data, $form)
     * so you can create your own functionality which handles saving
     * of all the data collected through each step of the form.
     *
     * @param array<string,mixed> $data The request data returned from the form
     * @param Form $form The form that the action was called on
     */
    public function finish(array $data, Form $form): bool
    {
        // Save the form data for the current step
        $this->save($data);

        if (!$this->getCurrentStep()->isFinalStep()) {
            $this->controller->redirectBack();
            return false;
        }

        if (!$this->getCurrentStep()->validateStep($data, $form)) {
            $this->getRequest()->getSession()->set("FormInfo.{$form->FormName()}.data", $form->getData());
            $this->controller->redirectBack();
            return false;
        }

        return true;
    }

    /**
     * Determine what to do when the next action is called.
     *
     * Saves the current step session data to the database, creates the
     * new step based on getNextStep() of the current step (or fetches
     * an existing one), resets the current step to the next step,
     * then redirects to the newly set step.
     *
     * @param array<string,mixed> $data The request data returned from the form
     * @param Form $form The form that the action was called on
     * @return bool|HTTPResponse
     */
    public function next(array $data, Form $form)
    {
        // Save the form data for the current step
        $this->save($form->getData());

        // Get the next step class
        $nextStepClass = $this->getCurrentStep()->getNextStep();

        if (!$nextStepClass) {
            $this->controller->redirectBack();
            return false;
        }

        // Perform custom step validation (use MultiFormStep->getValidator() for
        // built-in functionality). The data needs to be manually saved on error
        // so the form is re-populated.
        if (!$this->getCurrentStep()->validateStep($data, $form)) {
            $this->getRequest()->getSession()->set("FormInfo.{$form->FormName()}.data", $form->getData());
            $this->controller->redirectBack();
            return false;
        }

        // validation succeeded so we reset it to remove errors and messages
        $this->clearFormState();

        // Determine whether we can use a step already in the DB, or have to create a new one
        $nextStep = DataObject::get_one($nextStepClass, "\"SessionID\" = {$this->session->ID}");
        if (!$nextStep instanceof MultiFormStep) {
            $nextStep = Injector::inst()->create($nextStepClass);
            $nextStep->SessionID = $this->session->ID;
            $nextStep->write();
        }

        // Set the next step found as the current step
        $this->setCurrentStep($nextStep);

        // Redirect to the next step
        return $this->controller->redirect($nextStep->Link());
    }

    /**
     * Determine what to do when the previous action is called.
     *
     * Retrieves the previous step class, finds the record for that
     * class in the DB, and sets the current step to that step found.
     * Finally, it redirects to that step.
     *
     * @param array<string,mixed> $data The request data returned from the form
     * @param Form $form The form that the action was called on
     * @return bool|HTTPResponse
     */
    public function prev(array $data, Form $form)
    {
        // Save the form data for the current step
        $this->save($form->getData());

        // Get the previous step class
        $prevStepClass = $this->getCurrentStep()->getPreviousStep();

        if (!$prevStepClass && !$this->getCurrentStep()->canGoBack()) {
            $this->controller->redirectBack();
            return false;
        }

        // Get the previous step of the class instance returned from $currentStep->getPreviousStep()
        $prevStep = DataObject::get_one($prevStepClass, "\"SessionID\" = {$this->session->ID}");
        if (!$prevStep instanceof MultiFormStep) {
            $this->controller->redirectBack();
            return false;
        }

        // Set the current step as the previous step
        $this->setCurrentStep($prevStep);

        // Redirect to the previous step
        return $this->controller->redirect($prevStep->Link());
    }

    /**
     * Save the raw data given back from the form into session.
     *
     * Take the submitted form data for the current step, removing
     * any key => value pairs that shouldn't be saved, then saves
     * the data into the session.
     *
     * @param array<string,mixed> $data An array of data to save
     */
    protected function save(array $data): void
    {
        $currentStep = $this->getCurrentStep();
        foreach ($data as $field => $value) {
            if (in_array($field, $this->config()->get('ignored_fields'))) {
                unset($data[$field]);
            }
        }
        $currentStep->saveData($data);
    }

    /**
     * Add the MultiFormSessionID variable to the URL on form submission.
     * This is a means to persist the session, by adding it's identification
     * to the URL, which ties it back to this MultiForm instance.
     *
     * @return string
     */
    public function FormAction()
    {
        $action = parent::FormAction();
        $action .= (strpos($action, '?')) ? '&amp;' : '?';
        $action .= "{$this->getGetVar()}={$this->session->Hash}";

        return $action;
    }

    /**
     * Returns the link to the page where the form is displayed. The user is
     * redirected to this link with a session param after each step is
     * submitted.
     *
     * @return string
     */
    public function getDisplayLink()
    {
        return $this->displayLink ? $this->displayLink : Controller::curr()->Link();
    }

    /**
     * Set the link to the page on which the form is displayed.
     *
     * The link defaults to the controllers current link. However if the form
     * is displayed inside an action the display link must be explicitly set.
     */
    public function setDisplayLink(string $link): void
    {
        $this->displayLink = $link;
    }

    /**
     * Determine the steps to show in a linear fashion, starting from the
     * first step. We run {@link getAllStepsRecursive} passing the steps found
     * by reference to get a listing of the steps.
     *
     * @return ArrayList<MultiFormStep>
     */
    public function getAllStepsLinear(): ArrayList
    {
        /** @var ArrayList<MultiFormStep> $stepsFound */
        $stepsFound = ArrayList::create();

        $firstStep = DataObject::get_one($this->config()->get('start_step'), "\"SessionID\" = {$this->session->ID}");
        if (!$firstStep instanceof MultiFormStep) {
            return $stepsFound;
        }

        $firstStep->LinkingMode = ($firstStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link';
        $firstStep->setForm($this);
        $stepsFound->push($firstStep);

        // mark the further steps as non-completed if the first step is the current
        if ($firstStep->ID == $this->getCurrentStep()->ID) {
            $this->currentStepHasBeenFound = true;
        } else {
            $firstStep->addExtraClass('completed');
        }

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
     * @param MultiFormStep $step Subclass of MultiFormStep to find the next step of
     * @param ArrayList<MultiFormStep> $stepsFound The steps found so far, passed by reference
     * @return ArrayList<MultiFormStep>
     */
    protected function getAllStepsRecursive(MultiFormStep $step, ArrayList &$stepsFound): ArrayList
    {
        // Once we've reached the final step, we just return what we've collected
        if ($step->isFinalStep()) {
            return $stepsFound;
        }

        if (!$step->getNextStep()) {
            return $stepsFound;
        }

        // Is this step in the DB? If it is, we use that
        $nextStep = $step->getNextStepFromDatabase();
        if (!$nextStep) {
            // If it's not in the DB, we use a singleton instance of it instead -
            // - this step hasn't been accessed yet
            $nextStep = singleton($step->getNextStep());
        }

        // once the current steps has been found we won't add the completed class anymore.
        if ($nextStep->ID == $this->getCurrentStep()->ID) {
            $this->currentStepHasBeenFound = true;
        }

        $nextStep->LinkingMode = ($nextStep->ID == $this->getCurrentStep()->ID) ? 'current' : 'link';

        // add the completed class
        if (!$this->currentStepHasBeenFound) {
            $nextStep->addExtraClass('completed');
        }

        $nextStep->setForm($this);

        $stepsFound->push($nextStep);
        return $this->getAllStepsRecursive($nextStep, $stepsFound);
    }

    /**
     * Number of steps already completed (excluding currently started step).
     * The way we determine a step is complete is to check if it has the Data
     * field filled out with a serialized value, then we know that the user has
     * clicked next on the given step, to proceed.
     *
     * @return int
     */
    public function getCompletedStepCount(): int
    {
        $steps = MultiFormStep::get()->filter([
            "SessionID" => $this->session->ID,
            "Data:not" => null
        ]);

        return $steps->Count();
    }

    /**
     * Total number of steps in the shortest path (only counting straight path without any branching)
     * The way we determine this is to check if each step has a next_step string variable set. If it's
     * anything else (like an array, for defining multiple branches) then it gets counted as a single step.
     */
    public function getTotalStepCount(): int
    {
        return $this->getAllStepsLinear()->Count();
    }

    /**
     * Percentage of steps completed (excluding currently started step)
     */
    public function getCompletedPercent(): float
    {
        return (float) $this->getCompletedStepCount() * 100 / $this->getTotalStepCount();
    }
}
