<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\MultiForm\Models\MultiFormStep;

class MultiFormTestStepTwo extends MultiFormStep implements TestOnly
{
    private static string $table_name = 'MultiFormTestStepTwo';

    private static string $next_steps = MultiFormTestStepThree::class;

    public function getFields(): FieldList
    {
        return FieldList::create(
            TextareaField::create('Comments', 'Tell us a bit about yourself...')
        );
    }
}
