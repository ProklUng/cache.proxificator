<?php

namespace Prokl\CacheProxificator\Tests\Cases;

use Mockery;
use Prokl\CacheProxificator\Base\BaseProxificator;
use Prokl\CacheProxificator\CacheProxificator;
use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use Prokl\CacheProxificator\ReflectionProcessor;
use Prokl\CacheProxificator\Tests\Fixtures\FixtureClass;
use Prokl\TestingTools\Base\BaseTestCase;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class ReflectionProcessorTest
 * @package Prokl\CacheProxificator\Tests\Cases
 *
 * @coversDefaultClass BaseProxificator
 *
 * @since 02.05.2021
 */
class BaseProxificatorTest extends BaseTestCase
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
     * Проксификация.
     *
     * @return void
     */
    public function testProxify() : void
    {
        $result = $this->obTestObject->action();

        $this->assertSame('OK', $result, 'Проксификация не сработала.');
    }

    /**
     * Проксификация. Не кэшируемый метод.
     *
     * @return void
     */
    public function testProxifyNotCachingMethod() : void
    {
        $result = $this->obTestObject->actionTwo();

        $this->assertSame('Not caching', $result, 'Проксификация не сработала.');
    }

    /**
     * Проксификация. Несколько методов.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testProxifySomeMethods() : void
    {
        $this->obTestObject = new CacheProxificator(
            new FixtureClass,
            $this->getMockCacheInterface(),
            new ReflectionProcessor(),
            ['action', 'actionTwo'],
            'dev'
        );

        $result = $this->obTestObject->action();

        $this->assertSame('OK', $result, 'Проксификация не сработала.');

        $result = $this->obTestObject->actionTwo();

        $this->assertSame('OK', $result, 'Проксификация не сработала.');
    }

    /**
     * Проксификация. Несуществующий метод.
     *
     * @return void
     */
    public function testProxifyFakeMethod() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Метод fakeMethod не существует.');

        $this->obTestObject->fakeMethod();
    }

    /**
     * proxificate(). Существующий метод.
     *
     * @return void
     */
    public function testProxificateExistMethod() : void
    {
        $result = $this->obTestObject->action();

        $this->assertSame('OK', $result, 'Проксификация не сработала.');
    }

    /**
     * proxificate(). Несуществующий метод.
     *
     * @return void
     */
    public function testProxificateNotExistMethod() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Метод fakeMethod не существует.');

        $this->obTestObject->fakeMethod();
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

    /**
     * @return object
     */
    private function getTestClass()
    {
        return new class {
            public function __construct()
            {

            }

            public function action()
            {
                return 'Working';
            }

            public function actionTwo()
            {

            }

            private function hiddenMethod()
            {

            }

            protected function protectedMethod()
            {

            }
        };
    }
}