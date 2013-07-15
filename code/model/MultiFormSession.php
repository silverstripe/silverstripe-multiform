<?php

/**
 * Serializes one or more {@link MultiFormStep}s into 
 * a database object.
 * 
 * MultiFormSession also stores the current step, so that
 * the {@link MultiForm} and {@link MultiFormStep} classes
 * know what the current step is.
 * 
 * @package multiform
 */
class MultiFormSession extends DataObject {
	
	public static $db = array(
		'Hash' => 'Varchar(40)', 	// cryptographic hash identification to this session
		'IsComplete' => 'Boolean'	// flag to determine if this session is marked completed
	);
	
	public static $has_one = array(
		'Submitter' => 'Member',
		'CurrentStep' => 'MultiFormStep'
	);

	public static $has_many = array(
		'FormSteps' => 'MultiFormStep'
	);
	
	/**
	 * Mark this session as completed.
	 * 
	 * This sets the flag "IsComplete" to true,
	 * and writes the session back.
	 */
	public function markCompleted() {
		$this->IsComplete = 1;
		$this->write();
	}
	
	/**
	 * These actions are performed when write() is called on this object.
	 */
	public function onBeforeWrite() {
		// save submitter if a Member is logged in
		$currentMember = Member::currentUser();
		if(!$this->SubmitterID && $currentMember) $this->SubmitterID = $currentMember->ID;
		
		parent::onBeforeWrite();
	}

	/**
	 * These actions are performed when delete() is called on this object.
	 */
	public function onBeforeDelete() {
		// delete dependent form steps and relation
		$steps = $this->FormSteps();
		if($steps) foreach($steps as $step) {
			if($step && $step->exists()) {
				$steps->remove($step);
				$step->delete();
				$step->destroy();
			}
		}
		
		parent::onBeforeDelete();
	}

}
