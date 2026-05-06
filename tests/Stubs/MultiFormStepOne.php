<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

class MultiFormTestStepOne extends MultiFormStep implements TestOnly
{
    private static string $table_name = 'MultiFormTestStepOne';

    private static string $next_steps = MultiFormTestStepTwo::class;

    public function getFields(): FieldList
    {
        return FieldList::create(
            TextField::create('FirstName', 'First name'),
            TextField::create('Surname', 'Surname'),
            EmailField::create('Email', 'Email address')
        );
    }
}
