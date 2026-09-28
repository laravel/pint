<?php

namespace Tests;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, VerifiesDoubles;
}
