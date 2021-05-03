<?php

namespace Prokl\CacheProxificator\Tests\Cases;

use Mockery;
use Prokl\CacheProxificator\Base\BaseProxificator;
use Prokl\CacheProxificator\CacheProxificator;
use Prokl\CacheProxificator\ReflectionProcessor;
use Prokl\CacheProxificator\Tests\Fixtures\FixtureClass;
use Prokl\CacheProxificator\Tests\Fixtures\SampleClass;
use Prokl\TestingTools\Base\BaseTestCase;
use Prokl\TestingTools\Tools\PHPUnitUtils;
use ReflectionException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class CacherPiecesTest
 * @package Prokl\CacheProxificator\Tests\Cases
 *
 * @coversDefaultClass BaseProxificator
 *
 * @since 03.05.2021
 */
class CacherPiecesTest extends BaseTestCase
{
    /**
     * @var CacheProxificator $obTestObject
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new CacheProxificator(
            new FixtureClass,
            $this->getMockCacheInterface(),
            new ReflectionProcessor(),
            ['action'],
            'dev'
        );
    }

    /**
     * @return void
     *
     * @throws ReflectionException
     */
    public function testGenerateKey() : void
    {
        $params = [
          'id' => 1,
          'obj' => new SampleClass()
        ];

        $result = PHPUnitUtils::callMethod(
            $this->obTestObject,
            'implodeRecursive',
            [
                '',
                $params
            ]
        );

        $this->assertStringContainsString('Prokl\CacheProxificator\Tests\Fixtures\SampleClass', $result);
    }

    /**
     * Мок CacheInterface.
     *
     * @return mixed
     */
    private function getMockCacheInterface() {
        $mock = Mockery::mock(CacheInterface::class);
        $mock = $mock->shouldReceive('get')->andReturn('OK');

        return $mock->getMock();
    }

}