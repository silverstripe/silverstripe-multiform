<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * @package multiform
 * @subpackage tests
 */
class MultiFormTestStepThree extends MultiFormStep implements TestOnly
{
    public static $is_final_step = true;

    public function getFields()
    {
        return FieldList::create(
            new TextField('Test', 'Anything else you\'d like to tell us?')
        );
    }
}
