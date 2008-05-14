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
	
	static $db = array(
		'Data' => 'Text', 			// stores serialized maps with all session information
		'Hash' => 'Varchar(40)', 	// cryptographic hash identification to this session
		'IsComplete' => 'Boolean'	// flag to determine if this session is marked completed
	);
	
	static $has_one = array(
		'Submitter' => 'Member',
		'CurrentStep' => 'MultiFormStep'
	);

	static $has_many = array(
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
		$currentMember = Member::currentMember();
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
			$steps->remove($step);
			$step->delete();
		}
		
		parent::onBeforeDelete();
	}
	
	/**
	 * Get all the temporary objects, and set them as temporary, writing
	 * them back to the database.
	 */
	public function markTemporaryDataObjectsFinished() {
		$temporaryObjects = $this->getTemporaryDataObjects();
		if($temporaryObjects) foreach($temporaryObjects as $obj) {
			$obj->MultiFormIsTemporary = 0;
			$obj->write();
		}
	}
	
	/**
	 * Get all classes that implement the MultiFormObjectDecorator,
	 * find the records for each and merge them together into a 
	 *	DataObjectSet.
	 *
	 * @return DataObjectSet
	 */
	public function getTemporaryDataObjects() {
		$implementors = Object::get_implementors_for_extension('MultiFormObjectDecorator');
		$objs = new DataObjectSet();
		if($implementors) foreach($implementors as $implementorClass) {
			$objs->merge(
				DataObject::get($implementorClass, "MultiFormSessionID = {$this->ID}")
			);
		}
		
		return $objs;
	}
	
	/**
	 * Remove all related data, either serialized
	 * in $Data property, or in related stored
	 * DataObjects.
	 *
	 * @return boolean
	 */
	public function purgeStoredData() {
		die('MultiFormSession->purgeStoredData(): Not implemented yet');
	}
	
}

?>