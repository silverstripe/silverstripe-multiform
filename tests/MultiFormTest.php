<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;

/**
 * MultiFormTest
 * For testing purposes, we have some test classes:
 *
 *  - MultiFormTestController (simulation of a real Controller class)
 *  - MultiFormTestForm (subclass of MultiForm)
 *  - MultiFormTestStepOne (subclass of MultiFormStep)
 *  - MultiFormTestStepTwo (subclass of MultiFormStep)
 *  - MultiFormTestStepThree (subclass of MultiFormStep)
 *
 * The above classes are used to simulate real-world behaviour
 * of the multiform module - for example, MultiFormTestController
 * is a simulation of a page where MultiFormTest_Form is a simple
 * multi-step contact form it belongs to.
 *
 * @package multiform
 * @subpackage tests
 */
class MultiFormTest extends FunctionalTest
{

    public static $fixture_file = 'MultiFormTest.yml';

    protected $controller;

    /**
     * @var MultiFormTestForm
     */
    protected $form;

    public function setUp()
    {
        parent::setUp();

        $this->controller = new MultiFormTestController();
        $this->form = $this->controller->Form();
    }

    public function testInitialisingForm()
    {
        $this->assertTrue(is_numeric($this->form->getCurrentStep()->ID) && ($this->form->getCurrentStep()->ID > 0));
        $this->assertTrue(is_numeric($this->form->getSession()->ID) && ($this->form->getSession()->ID > 0));
        $this->assertEquals(MultiFormTestStepOne::class, $this->form->getStartStep());
    }

    public function testSessionGeneration()
    {
        $this->assertTrue($this->form->getSession()->ID > 0);
    }

    public function testMemberLogging()
    {
        // Grab any user to fake being logged in as, and ensure that after a session is written it has
        // that user as the submitter.

        $userId = $this->logInWithPermission('ADMIN');

        $session = $this->form->getSession();
        $session->write();

        $this->assertEquals($userId, $session->SubmitterID);
    }

    public function testSecondStep()
    {
        $this->assertEquals(MultiFormTestStepTwo::class, $this->form->getCurrentStep()->getNextStep());
    }

    public function testParentForm()
    {
        $currentStep = $this->form->getCurrentStep();
        $this->assertEquals($currentStep->getForm()->class, $this->form->class);
    }

    public function testTotalStepCount()
    {
        $this->assertEquals(3, $this->form->getAllStepsLinear()->Count());
    }

    public function testCompletedSession()
    {
        $this->form->setCurrentSessionHash($this->form->getSession()->Hash);
        $this->assertInstanceOf('MultiFormSession', $this->form->getCurrentSession());
        $this->form->getSession()->markCompleted();
        $this->assertNull($this->form->getCurrentSession());
    }

    public function testIncorrectSessionIdentifier()
    {
        $this->form->setCurrentSessionHash('sdfsdf3432325325sfsdfdf'); // made up!

        // A new session is generated, even though we made up the identifier
        $this->assertInstanceOf('MultiFormSession', $this->form->getSession());
    }

    public function testCustomGetVar()
    {
        Config::nest();
        Config::modify()->set('MultiForm', 'get_var', 'SuperSessionID');

        $form = $this->controller->Form();
        $this->assertContains('SuperSessionID', $form::$ignored_fields, "GET var wasn't added to ignored fields");
        $this->assertContains(
            'SuperSessionID',
            $form->FormAction(),
            "Form action doesn't contain correct session ID parameter"
        );
        $this->assertContains(
            'SuperSessionID',
            $form->getCurrentStep()->Link(),
            "Form step doesn't contain correct session ID parameter"
        );

        Config::unnest();
    }
}
