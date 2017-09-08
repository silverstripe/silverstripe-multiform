<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\MultiForm\Models\MultiForm;
use SilverStripe\MultiForm\Models\MultiFormSession;

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

    /**
     * @var MultiFormTestController
     */
    protected $controller;

    /**
     * @var MultiFormTestForm
     */
    protected $form;

    protected function setUp()
    {
        parent::setUp();

        $this->controller = new MultiFormTestController();
        $this->controller->setRequest(new HTTPRequest('GET', '/'));
        $this->controller->getRequest()->setSession(new Session([]));
        $this->controller->pushCurrent();
        $form = $this->form = $this->controller->Form();
        Injector::inst()->registerService($form, MultiForm::class);
        $this->form =  $form;
    }

    public function testInitialisingForm()
    {
        $this->assertTrue(is_numeric($this->form->getCurrentStep()->ID) && ($this->form->getCurrentStep()->ID > 0));
        $this->assertTrue(
            is_numeric($this->form->getMultiFormSession()->ID)
            && ($this->form->getMultiFormSession()->ID > 0)
        );
        $this->assertEquals(MultiFormTestStepOne::class, $this->form->getStartStep());
    }

    public function testSessionGeneration()
    {
        $this->assertTrue($this->form->getMultiFormSession()->ID > 0);
    }

    public function testMemberLogging()
    {
        // Grab any user to fake being logged in as, and ensure that after a session is written it has
        // that user as the submitter.

        $userId = $this->logInWithPermission('ADMIN');

        $session = $this->form->getMultiFormSession();
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
        $this->form->setCurrentSessionHash($this->form->getMultiFormSession()->Hash);
        $this->assertInstanceOf(MultiFormSession::class, $this->form->getCurrentSession());
        $this->form->getMultiFormSession()->markCompleted();
        $this->assertNull($this->form->getCurrentSession());
    }

    public function testIncorrectSessionIdentifier()
    {
        $this->form->setCurrentSessionHash('sdfsdf3432325325sfsdfdf'); // made up!

        // A new session is generated, even though we made up the identifier
        $this->assertInstanceOf(MultiFormSession::class, $this->form->getMultiFormSession());
    }

    public function testCustomGetVar()
    {
        Config::modify()->set(MultiForm::class, 'get_var', 'SuperSessionID');

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
    }
}
