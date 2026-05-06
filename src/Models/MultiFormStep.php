<?php

namespace SilverStripe\MultiForm\Models;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Validation\Validator;
use SilverStripe\MultiForm\Forms\MultiForm;
use SilverStripe\ORM\DataObject;

/**
 * MultiFormStep controls the behaviour of a single form step in the MultiForm
 * process. All form steps are required to be subclasses of this class, as it
 * encapsulates the functionality required for the step to be aware of itself
 * in the process by knowing what it's next step is, and if applicable, it's previous
 * step.
 *
 */
class MultiFormStep extends DataObject
{
    private static array $db = [
        'Data' => 'Text' // stores serialized maps with all session information
    ];

    private static array $has_one = [
        'Session' => MultiFormSession::class
    ];

    private static string $table_name = 'MultiFormStep';

    /**
     * Centerpiece of the flow control for the form.
     *
     * If set to a string, you have a linear form flow
     * If set to an array, you should use {@link getNextStep()}
     * to enact flow control and branching to different form
     * steps, most likely based on previously set session data
     * (e.g. a checkbox field or a dropdown).
     *
     * @var array|string|null
     */
    private static array|string|null $next_steps = null;

    /**
     * Each {@link MultiForm} subclass needs at least
     * one step which is marked as the "final" one
     * and triggers the {@link MultiForm->finish()}
     * method that wraps up the whole submission.
     *
     * @var bool
     */
    private static bool $is_final_step = false;

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
    private static bool $can_go_back = true;

    /**
     * Title of this step.
     *
     * Used for the step indicator templates.
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Form class that this step is directly related to.
     *
     * @var MultiForm|null
     */
    protected MultiForm|null $form = null;

    /**
     * List of additional CSS classes for this step
     *
     * @var array<string,string>
     */
    protected array $extraClasses = [];

    /**
     * Temporary cache to increase the performance for repeated look ups.
     *
     * @var array<string,array<string,mixed>>
     */
    protected array $step_data_cache = [];

    /**
     * Form fields to be rendered with this step.
     * (Form object is created in {@link MultiForm}.
     *
     * This function needs to be implemented on your
     * subclasses of MultiFormStep.
     *
     * @throws LogicException
     * @return FieldList
     */
    public function getFields(): FieldList
    {
        throw new LogicException('Please implement getFields on your MultiFormStep subclass');
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
    public function getExtraActions()
    {
        return FieldList::create();
    }

    /**
     * Get a validator specific to this form.
     * The form is automatically validated in {@link Form->httpSubmission()}.
     *
     * @return Validator|null
     */
    public function getValidator()
    {
        return null;
    }

    /**
     * Accessor method for $this->title
     *
     * @return string Title of this step
     */
    public function getTitle()
    {
        return $this->title ? $this->title : get_class($this);
    }

    /**
     * Gets a direct link to this step (only works if you're allowed to skip
     * steps, or this step has already been saved to the database for the
     * current {@link MultiFormSession}).
     *
     * @return string Relative URL to this step
     */
    public function Link()
    {
        $form = $this->form;

        return Controller::join_links(
            $form->getDisplayLink(),
            sprintf("?%s=%s&StepID=%s", $form->getGetVar(), $this->getSession()->Hash, $this->ID)
        );
    }

    /**
     * Unserialize stored session data and return it.
     *
     * This is used for loading data previously saved in session back into the form.
     *
     * You need to overload this method onto your own step if you require custom loading. An example would be
     * selective loading specific fields, leaving others that are not required.
     *
     * @return array<string,mixed>
     */
    public function loadData(): array
    {
        return $this->Data ? unserialize($this->Data) : [];
    }

    /**
     * Save the data for this step into session, serializing it first.
     *
     * To selectively save fields, instead of it all, this method would need to be overloaded on your step class.
     *
     * @param array<string,mixed> $data The processed data from save() on {@link MultiForm}
     */
    public function saveData(array $data): void
    {
        $this->Data = serialize($data);
        $this->write();
    }

    /**
     * Save the data on this step into an object, similiar to {@link Form->saveInto()} - by building a stub form
     * from {@link getFields()}. This is necessary to trigger each {@link FormField->saveInto()} method individually,
     * rather than assuming that all data serialized through {@link saveData()} can be saved as a simple value outside
     * of the original FormField context.
     *
     * @param DataObject $obj
     * @return DataObject|null
     */
    public function saveInto($obj): DataObject|null
    {
        $form = Form::create(
            Controller::curr(),
            'Form',
            $this->getFields(),
            FieldList::create()
        );
        $form->loadDataFrom($this->loadData());
        $form->saveInto($obj);
        return $obj;
    }

    /**
     * Custom validation for a step. In most cases, it should be sufficient to have built-in validation through the
     * {@link Validator} class on the {@link getValidator()} method.
     *
     * Use {@link Form->sessionMessage()} to feed back validation messages to the user. Please don't redirect from
     * this method, this is taken care of in {@link MultiForm->next()}.
     *
     * @param array<string,mixed> $data Request data
     * @return boolean Validation success
     */
    public function validateStep(array $data, Form $form): bool
    {
        return true;
    }

    /**
     * Returns the first value of $next_step
     *
     * @return string|false Classname of a {@link MultiFormStep} subclass, or false if none defined
     */
    public function getNextStep()
    {
        $nextSteps = $this->config()->get('next_steps');

        // Check if next_steps have been implemented properly if not the final step
        if (!$this->isFinalStep() && !$nextSteps) {
            throw new LogicException(
                'MultiFormStep->getNextStep(): Please define at least one $next_steps on ' . static::class
            );
        }

        if (is_string($nextSteps)) {
            return $nextSteps;
        }

        if (is_array($nextSteps) && count($nextSteps)) {
            return $nextSteps[0];
        }

        return false;
    }

    /**
     * Returns the next step to the current step in the database.
     *
     * This will only return something if you've previously visited the step ahead of the current step, and then gone
     * back a step.
     *
     * @return MultiFormStep|null
     */
    public function getNextStepFromDatabase()
    {
        if (!$this->SessionID) {
            return null;
        }

        $nextSteps = $this->config()->get('next_steps');

        if (is_string($nextSteps)) {
            $next = DataObject::get($nextSteps)->filter('SessionID', $this->SessionID)->first();
            return $next instanceof MultiFormStep ? $next : null;
        }

        if (is_array($nextSteps) && count($nextSteps)) {
            $class = $nextSteps[0] ?? null;
            if (!$class) {
                return null;
            }
            $next = DataObject::get($class)->filter('SessionID', $this->SessionID)->first();
            return $next instanceof MultiFormStep ? $next : null;
        }

        return null;
    }

    /**
     * Accessor method for self::$next_steps
     *
     * @return string|array<int,string>|null
     */
    public function getNextSteps(): array|string|null
    {
        return $this->config()->get('next_steps');
    }

    /**
     * Returns the previous step, if there is one.
     *
     * To determine if there is a previous step, we check the database to see if there's a previous step for this
     * multi form session ID.
     *
     * @return string|null Classname of a {@link MultiFormStep} subclass
     */
    public function getPreviousStep()
    {
        $steps = MultiFormStep::get()->filter('SessionID', $this->SessionID)->sort('LastEdited', 'DESC');

        foreach ($steps as $step) {
            $step->setForm($this->form);

            if ($step->getNextStep() && $step->getNextStep() === static::class) {
                return get_class($step);
            }
        }

        return null;
    }

    /**
     * Retrieves the previous step class record from the database.
     *
     * This will only return a record if you've previously been on the step.
     *
     * @return MultiFormStep|null
     */
    public function getPreviousStepFromDatabase()
    {
        $prevStepClass = $this->getPreviousStep();
        if (!$prevStepClass) {
            return null;
        }

        $prev = DataObject::get($prevStepClass)->filter('SessionID', $this->SessionID)->last();

        return $prev instanceof MultiFormStep ? $prev : null;
    }

    /**
     * Get the text to the use on the button to the previous step.
     * @return string
     */
    public function getPrevText()
    {
        return _t(__CLASS__ . '.BACK', 'Back');
    }

    /**
     * Get the text to use on the button to the next step.
     * @return string
     */
    public function getNextText()
    {
        return _t(__CLASS__ . '.NEXT', 'Next');
    }

    /**
     * Get the text to use on the button to submit the form.
     * @return string
     */
    public function getSubmitText()
    {
        return _t(__CLASS__ . '.SUBMIT', 'Submit');
    }

    /**
     * Sets the form that this step is directly related to.
     */
    public function setForm(?MultiForm $form): void
    {
        $this->form = $form;
    }

    public function getForm(): ?MultiForm
    {
        return $this->form;
    }

    /**
     * Determines whether the user is able to go back using the "action_back"
     * form action, based on the boolean value of $can_go_back.
     *
     * @return boolean
     */
    public function canGoBack(): bool
    {
        return $this->config()->get('can_go_back');
    }

    /**
     * Determines whether this step is the final step in the multi-step process or not,
     * based on the variable $is_final_step - which must be defined on at least one step.
     */
    public function isFinalStep(): bool
    {
        return $this->config()->get('is_final_step');
    }

    /**
     * Determines whether the currently viewed step is the current step set in the session.
     * This assumes you are checking isCurrentStep() against a data record of a MultiFormStep
     * subclass, otherwise it doesn't work. An example of this is using a singleton instance - it won't
     * work because there's no data.
     *
     * @return boolean
     */
    public function isCurrentStep()
    {
        return (static::class == get_class($this->getSession()->CurrentStep())) ? true : false;
    }

    /**
     * Add a CSS-class to the step. If needed, multiple classes can be added by delimiting a string with spaces.
     *
     * @param string $class A string containing a classname or several class names delimited by a space.
     * @return MultiFormStep
     */
    public function addExtraClass($class)
    {
        // split at white space
        $classes = preg_split('/\s+/', $class);
        foreach ($classes as $class) {
            // add classes one by one
            $this->extraClasses[$class] = $class;
        }
        return $this;
    }

    /**
     * Remove a CSS-class from the step. Multiple classes names can be passed through as a space delimited string.
     *
     * @param string $class
     * @return MultiFormStep
     */
    public function removeExtraClass(string $class): self
    {
        // split at white space
        $classes = preg_split('/\s+/', $class);
        foreach ($classes as $class) {
            // unset one by one
            unset($this->extraClasses[$class]);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getExtraClasses(): string
    {
        return join(' ', array_keys($this->extraClasses));
    }

    /**
     * Returns the submitted value, if any, of any steps.
     *
     * @param string $fromStep (classname)
     * @param string $key
     *
     * @return mixed
     */
    public function getValueFromOtherStep(string $fromStep, string $key): mixed
    {
        // load the steps in the cache, if this one doesn't exist
        if (!array_key_exists('steps_' . $fromStep, $this->step_data_cache)) {
            $steps = MultiFormStep::get()->filter('SessionID', $this->form->getMultiFormSession()->ID);

            foreach ($steps as $step) {
                $this->step_data_cache['steps_' . $step->ClassName] = $step->loadData();
            }
        }

        // check both as PHP isn't recursive
        if (isset($this->step_data_cache['steps_' . $fromStep])) {
            if (isset($this->step_data_cache['steps_' . $fromStep][$key])) {
                return $this->step_data_cache['steps_' . $fromStep][$key];
            }
        }

        return null;
    }

    /**
     * allows to get a value from another step copied over
     */
    public function copyValueFromOtherStep(
        FieldList $fields,
        string $formStep,
        string $fieldName,
        ?string $fieldNameTarget = null
    ): void {
        // if a target field isn't defined use the same fieldname
        if (!$fieldNameTarget) {
            $fieldNameTarget = $fieldName;
        }

        $fields->fieldByName($fieldNameTarget)->setValue($this->getValueFromOtherStep($formStep, $fieldName));
    }

    /**
     * Gets the linked MultiFormSession
     */
    public function getSession(): MultiFormSession
    {
        return $this->Session();
    }
}
