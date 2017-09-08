<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

class MultiFormTestStepOne extends MultiFormStep implements TestOnly
{
    private static $next_steps = MultiFormTestStepTwo::class;

    public function getFields()
    {
        return FieldList::create(
            new TextField('FirstName', 'First name'),
            new TextField('Surname', 'Surname'),
            new EmailField('Email', 'Email address')
        );
    }
}
