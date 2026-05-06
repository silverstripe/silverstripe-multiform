<?php

namespace SilverStripe\MultiForm\Tests\Stubs;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;

class MultiFormTestController extends Controller implements TestOnly
{
    private static string $url_segment = 'MultiFormTestController';

    public function Form(): MultiFormTestForm
    {
        return Injector::inst()->get(MultiFormTestForm::class, false, [$this, 'Form'])
            ->setHTMLID(MultiFormTestForm::class);
    }
}
