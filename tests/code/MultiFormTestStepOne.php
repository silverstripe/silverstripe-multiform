<?php

class MultiFormTestStepOne extends MultiFormStep implements TestOnly {
	
	protected static $next_steps = 'MultiFormTestStepTwo';
	
	function getFields() {
		return new FieldSet(
			new TextField('FirstName', 'First name'),
			new TextField('Surname', 'Surname'),
			new EmailField('Email', 'Email address')
		);
	}
	
}

?>