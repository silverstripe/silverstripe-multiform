<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\MultiForm\Models\MultiForm;

/**
 * @package multiform
 * @subpackage tests
 */
class MultiFormTestForm extends MultiForm implements TestOnly
{
    public static $start_step = MultiFormTestStepOne::class;

    public function getStartStep()
    {
        return self::$start_step;
    }
}
