<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class MultiFormObjectDecoratorDataObject extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $table_name = 'MultiFormObjectDecoratorDataObject';
}
