<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\MultiForm\Forms\MultiForm;

class MultiFormTestForm extends MultiForm implements TestOnly
{
    private static string $start_step = MultiFormTestStepOne::class;

    public function getStartStep(): string
    {
        return MultiFormTestStepOne::class;
    }
}
