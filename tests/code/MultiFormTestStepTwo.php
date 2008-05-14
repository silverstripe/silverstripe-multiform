<?php

class MultiFormTestStepTwo extends MultiFormStep {
	
	protected static $next_steps = 'MultiFormTestStepThree';
	
	function getFields() {
		return new FieldSet(
			new TextareaField('Comments', 'Tell us a bit about yourself...')
		);
	}
	
}

?>