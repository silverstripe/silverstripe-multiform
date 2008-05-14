<?php

class MultiFormTestStepOne extends MultiFormStep {
	
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