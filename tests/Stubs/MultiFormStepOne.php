<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * @package multiform
 * @subpackage tests
 */
class MultiFormTestStepOne extends MultiFormStep implements TestOnly
{
    public static $next_steps = MultiFormTestStepTwo::class;

    public function getFields()
    {
        return FieldList::create(
            new TextField('FirstName', 'First name'),
            new TextField('Surname', 'Surname'),
            new EmailField('Email', 'Email address')
        );
    }
}
