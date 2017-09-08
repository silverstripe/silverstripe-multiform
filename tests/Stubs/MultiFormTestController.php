<?php

namespace SilverStripe\MultiForm\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;

/**
 * @package multiform
 * @subpackage tests
 */
class MultiFormTestController extends Controller implements TestOnly
{
    private static $url_segment = 'MultiFormTestController';

    public function Form()
    {
        return Injector::inst()->get(MultiFormTestForm::class, false, [$this, 'Form'])
            ->setHTMLID(MultiFormTestForm::class);
    }
}
