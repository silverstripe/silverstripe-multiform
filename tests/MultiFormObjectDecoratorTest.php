<?php
class MultiFormObjectDecoratorTest extends SapphireTest
{

    protected static $fixture_file = 'MultiFormObjectDecoratorTest.yml';

    protected $requiredExtensions = array(
        'MultiFormObjectDecorator_DataObject' => array('MultiFormObjectDecorator')
    );

    protected $extraDataObjects = array(
        'MultiFormObjectDecorator_DataObject'
    );

    public function testTemporaryDataFilteredQuery()
    {
        $records = MultiFormObjectDecorator_DataObject::get()
            ->map('Name')
            ->toArray();

        $this->assertContains('Test 1', $records);
        $this->assertContains('Test 2', $records);
        $this->assertNotContains('Test 3', $records);
    }

    public function testTemporaryDataQuery()
    {
        $records = MultiFormObjectDecorator_DataObject::get()
            ->filter(array('MultiFormIsTemporary' => 1))
            ->map('Name')
            ->toArray();

        $this->assertNotContains('Test 1', $records);
        $this->assertNotContains('Test 2', $records);
        $this->assertContains('Test 3', $records);
    }
}

class MultiFormObjectDecorator_DataObject extends DataObject
{

    private static $db = array(
        'Name' => 'Varchar'
    );
}
