<?php

class MultiFormTestStepTwo extends MultiFormStep implements TestOnly {
	
	protected static $next_steps = 'MultiFormTestStepThree';
	
	function getFields() {
		return new FieldSet(
			new TextareaField('Comments', 'Tell us a bit about yourself...')
		);
	}
	
}

?>