<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\MultiForm\Extensions\MultiFormObjectDecorator;
use SilverStripe\MultiForm\Tests\Stubs\MultiFormObjectDecoratorDataObject;

class MultiFormObjectDecoratorTest extends SapphireTest
{
    protected static $fixture_file = 'MultiFormObjectDecoratorTest.yml';

    protected static $required_extensions = [
        MultiFormObjectDecoratorDataObject::class => [MultiFormObjectDecorator::class]
    ];

    protected static $extra_dataobjects = [
        MultiFormObjectDecoratorDataObject::class
    ];

    public function testTemporaryDataFilteredQuery()
    {
        $records = MultiFormObjectDecoratorDataObject::get()
            ->map('Name')
            ->toArray();

        $this->assertContains('Test 1', $records);
        $this->assertContains('Test 2', $records);
        $this->assertNotContains('Test 3', $records);
    }

    public function testTemporaryDataQuery()
    {
        $records = MultiFormObjectDecoratorDataObject::get()
            ->filter(['MultiFormIsTemporary' => 1])
            ->map('Name')
            ->toArray();
        $this->assertNotContains('Test 1', $records);
        $this->assertNotContains('Test 2', $records);
        $this->assertContains('Test 3', $records);
    }
}
