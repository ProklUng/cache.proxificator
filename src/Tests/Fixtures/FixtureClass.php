<?php

namespace Prokl\CacheProxificator\Tests\Fixtures;

/**
 * Class FixtureClass
 * @package Prokl\CacheProxificator\Tests\Fixtures
 */
class FixtureClass
{
    public function __construct()
    {

    }

    public function action()
    {
        return 'Working';
    }

    public function actionTwo()
    {
        return 'Not caching';
    }

    private function hiddenMethod()
    {

    }

    protected function protectedMethod()
    {

    }
}