<?php
namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\MultiForm\Models\MultiFormStep;

class MultiFormTestStepTwo extends MultiFormStep implements TestOnly
{
    private static $next_steps = MultiFormTestStepThree::class;

    public function getFields()
    {
        return new FieldList(
            new TextareaField('Comments', 'Tell us a bit about yourself...')
        );
    }
}
