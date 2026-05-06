<?php

namespace SilverStripe\MultiForm\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Serializes one or more {@link MultiFormStep}s into a database object.
 *
 * MultiFormSession also stores the current step, so that the {@link MultiForm} and {@link MultiFormStep} classes
 * know what the current step is.
 */
class MultiFormSession extends DataObject
{
    private static array $db = [
        'Hash' => 'Varchar(40)',
        'IsComplete' => 'Boolean'
    ];

    private static array $has_one = [
        'Submitter' => Member::class,
        'CurrentStep' => MultiFormStep::class
    ];

    private static array $has_many = [
        'FormSteps' => MultiFormStep::class
    ];

    private static array $owns = [
        'FormSteps'
    ];

    private static array $cascade_deletes = [
        'FormSteps'
    ];

    private static string $table_name = 'MultiFormSession';

    /**
     * Mark this session as completed.
     *
     * This sets the flag "IsComplete" to true and writes the session back.
     */
    public function markCompleted(): self
    {
        $this->IsComplete = 1;
        $this->write();

        return $this;
    }


    /**
     * These actions are performed when write() is called on this object.
     */
    public function onBeforeWrite(): void
    {
        $currentMember = Security::getCurrentUser();

        if (!$this->SubmitterID && $currentMember) {
            $this->SubmitterID = $currentMember->ID;
        }

        parent::onBeforeWrite();
    }


    public function onAfterWrite(): void
    {
        parent::onAfterWrite();

        // Create encrypted identification to the session instance if it doesn't exist
        if (!$this->Hash) {
            $this->Hash = sha1($this->ID . '-' . microtime());
            $this->write();
        }
    }
}
