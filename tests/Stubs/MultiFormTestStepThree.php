<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\MultiForm\Models\MultiFormStep;

class MultiFormTestStepThree extends MultiFormStep implements TestOnly
{
    private static string $table_name = 'MultiFormTestStepThree';

    private static bool $is_final_step = true;

    public function getFields(): FieldList
    {
        return FieldList::create(
            TextField::create('Test', 'Anything else you\'d like to tell us?')
        );
    }
}
