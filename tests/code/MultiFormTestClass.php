<?php

class MultiFormTestClass extends MultiForm {
	
	protected static $start_step = 'MultiFormTestStepOne';
	
	/**
	 * Accessor method to $start_step
	 * @return string
	 */
	function getStartStep() {
		return $this->stat('start_step');
	}
	
}

?>