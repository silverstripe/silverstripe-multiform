<?php

/**
 * Serializes one or more {@link MultiFormStep}s into 
 * a database object.
 * 
 * @package multiform
 *
 */
class MultiFormSession extends DataObject {
	
	static $db = array(
		'Data' => 'Text', // stores serialized maps with all session information
		'Hash' => 'Varchar(40)' // cryptographic hash identification to this session
	);
	
	static $has_one = array(
		'Submitter' => 'Member',
		'CurrentStep' => 'MultiFormStep'
	);

	static $has_many = array(
		'FormSteps' => 'MultiFormStep'
	);
	
	public function onBeforeWrite() {
		// save submitter if a Member is logged in
		$currentMember = Member::currentMember();
		if(!$this->SubmitterID && $currentMember) $this->SubmitterID = $currentMember->ID;
		
		parent::onBeforeWrite();
	}
	
	public function onBeforeDelete() {
		// delete dependent form steps
		$steps = $this->FormSteps();
		if($steps) foreach($steps as $step) {
			$step->delete();
		}
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function markTemporaryDataObjectsFinished() {
		$temporaryObjects = $this->getTemporaryDataObjects();
		if($temporaryObjects) foreach($temporaryObjects as $obj) {
			$obj->MultiFormIsTemporary = 0;
			$obj->write();
		}
	}
	
	/**
	 * Enter description here...
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