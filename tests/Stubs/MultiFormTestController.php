<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * @package multiform
 * @subpackage tests
 */
class MultiFormTestController extends Controller implements TestOnly
{
    public function Link()
    {
        return self::class;
    }

    public function Form($request = null)
    {
        $form = new MultiFormTestForm($this, 'Form');
        $form->setHTMLID(self::class);
        return $form;
    }
}
