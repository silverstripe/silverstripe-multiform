<?php

class MultiFormTestStepThree extends MultiFormStep implements TestOnly {
	
	protected static $is_final_step = true;
	
	function getFields() {
		return new FieldSet(
			new TextField('Test', 'Anything else you\'d like to tell us?')
		);
	}
	
}

?>