<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\MultiForm\Forms\MultiForm;

class MultiFormTestForm extends MultiForm implements TestOnly
{
    private static $start_step = MultiFormTestStepOne::class;

    public function getStartStep()
    {
        return $this->config()->get('start_step');
    }
}
