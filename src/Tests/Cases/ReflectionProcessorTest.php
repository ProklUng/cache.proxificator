<?php

namespace Prokl\CacheProxificator\Tests\Cases;

use Prokl\CacheProxificator\ReflectionProcessor;
use Prokl\TestingTools\Base\BaseTestCase;

/**
 * Class ReflectionProcessorTest
 * @package Prokl\CacheProxificator\Tests\Cases
 *
 * @coversDefaultClass ReflectionProcessor
 * @since 02.05.2021
 */
class ReflectionProcessorTest extends BaseTestCase
{
    /**
     * @var ReflectionProcessor $obTestObject
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new ReflectionProcessor();
    }

    /**
     * reflectClassMethods(). Без фильтра.
     *
     * @return void
     */
    public function testReflectClassMethodsWithoutFilter() : void
    {
        
    }

    /**
     * reflectClassMethods(). С фильтром методов.
     *
     * @return void
     */
    public function testReflectClassMethodsWithFilter() : void
    {

    }
}