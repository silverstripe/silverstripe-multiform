<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Dev\SapphireTest;

class MultiFormObjectDecoratorTest extends SapphireTest
{
    protected static $fixture_file = 'MultiFormObjectDecoratorTest.yml';

    protected $requiredExtensions = [
        'MultiFormObjectDecoratorDataObject' => ['MultiFormObjectDecorator']
    ];

    protected $extraDataObjects = [
        'MultiFormObjectDecoratorDataObject'
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
