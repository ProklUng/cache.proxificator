<?php

namespace Prokl\CacheProxificator\Tests\Cases;

use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use Prokl\CacheProxificator\ReflectionProcessor;
use Prokl\TestingTools\Base\BaseTestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * Class ReflectionProcessorTest
 * @package Prokl\CacheProxificator\Tests\Cases
 *
 * @coversDefaultClass ReflectionProcessor
 *
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
     * reflectClassMethods(). Без фильтра. Без ресолверов.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsWithoutFilter() : void
    {
        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass);

        /** @var ReflectionMethod $method */
        foreach ($result as $method) {
            $name = $method->name;

            $this->assertNotSame(
                '__construct',
                $name,
                'Конструктор проскочил, а не должен.'
            );

            $this->assertNotSame(
                'hiddenMethod',
                $name,
                'Приватный метод проскочил, а не должен.'
            );

            $this->assertNotSame(
                'protectedMethod',
                $name,
                'Защищенный метод проскочил, а не должен.'
            );
        }

        $this->assertCount(2, $result, 'Количество ожидаемых методов неверное.');

        foreach (['action', 'actionTwo'] as $method) {
            $this->assertHasMethod($result, $method);
        }
    }

    /**
     * reflectClassMethods(). С фильтром методов. Без ресолверов.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsWithFilter() : void
    {
        $filter = ['actionTwo'];

        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass, $filter);

        $this->assertCount(1, $result, 'Количество ожидаемых методов неверное.');

        foreach (['actionTwo'] as $method) {
            $this->assertHasMethod($result, $method);
        }
    }

    /**
     * reflectClassMethods(). С фильтром методов, несуществующий метод. Без ресолверов.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsWithFilterUnexistMethod() : void
    {
        $filter = ['fakeMethod'];

        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass, $filter);

        $this->assertCount(0, $result, 'Количество ожидаемых методов неверное.');
    }

    /**
     * reflectClassMethods(). С фильтром методов, ресолвером, проверка приоритета фильтра.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsWithFilterPriority() : void
    {
        $filter = ['action'];
        $this->obTestObject = new ReflectionProcessor([
            $this->getFalseResolver()
        ]);

        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass, $filter);

        $this->assertCount(1, $result, 'Количество ожидаемых методов неверное.');

        $name = $result[0]->getName();

        $this->assertSame('action', $name, 'Результат покорежен.');
    }

    /**
     * reflectClassMethods(). Без фильтра, с ресолвером.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsResolverWork() : void
    {
        $this->obTestObject = new ReflectionProcessor([
            $this->getFalseResolver()
        ]);

        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass);

        $this->assertCount(0, $result, 'Количество ожидаемых методов неверное.');
    }

    /**
     * reflectClassMethods(). Без фильтра, с ресолвером. Вторая итерация.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testReflectClassMethodsResolverWorkSecond() : void
    {
        $this->obTestObject = new ReflectionProcessor([
            $this->getSampleResolver()
        ]);

        $testClass = $this->getTestClass();

        $result = $this->obTestObject->reflectClassMethods($testClass);
        $name = $result[0]->getName();

        $this->assertCount(1, $result, 'Количество ожидаемых методов неверное.');
        $this->assertSame('action', $name, 'Результат неверный.');
    }

    /**
     * sortParamsMethod().
     *
     * @param array $data
     *
     * @return void
     * @throws ReflectionException
     *
     * @dataProvider dataProviderParams
     */
    public function testSortParamsMethod(array $data) : void
    {
        $class = new class {
            public function action(string $video, int $count, bool $default = true)
            {
                return true;
            }
        };

        $reflection = new ReflectionMethod($class, 'action');

        $result = $this->obTestObject->sortParamsMethod($reflection, $data);

        $this->assertSame('video', $result[0], 'Порядок аргументов неверный');
        $this->assertSame(2, $result[1], 'Порядок аргументов неверный');
        $this->assertTrue($result[2], 'Порядок аргументов неверный');
    }

    /**
     * invoke().
     *
     * @param array $data
     *
     * @return void
     *
     * @dataProvider dataProviderParams
     * @throws ReflectionException
     */
    public function testInvoke(array $data) : void
    {
        $class = new class {
            public function action(string $video, int $count, bool $default = true)
            {
                return 'OK';
            }
        };

        $result = $this->obTestObject->invoke($class, 'action', $data);

        $this->assertSame('OK', $result);
    }

    /**
     * @return array
     */
    public function dataProviderParams() : array
    {
        return [
            [
                ['count' => 2, 'default' => true, 'video' => 'video'],
            ],
            [
                ['default' => true, 'count' => 2, 'video' => 'video'],
            ],
            [
                ['default' => true, 'video' => 'video', 'count' => 2],
                ['video' => 'video', 'count' => 2],

            ],
        ];
    }

    /**
     * @param ReflectionMethod[] $methods Рефлексия методов.
     * @param string             $method  Метод.
     *
     * @return void
     */
    private function assertHasMethod(array $methods, string $method) : void
    {
        $result = false;
        foreach ($methods as $methodRef) {
            if ($methodRef->getName() === $method) {
                $result = true;
            }
        }

        $this->assertTrue($result, 'Метод ' . $method . ' отсутствует.');
    }

    /**
     * Ресолвер, возвращающий false на любой метод.
     *
     * @return MethodResolverInterface
     */
    private function getFalseResolver()
    {
        return new class implements MethodResolverInterface {
            public function supply(ReflectionMethod $reflectionMethod) : bool
            {
                return false;
            }
        };
    }

    /**
     * Ресолвер.
     *
     * @return MethodResolverInterface
     */
    private function getSampleResolver()
    {
        return new class implements MethodResolverInterface {
            public function supply(ReflectionMethod $reflectionMethod) : bool
            {
                if ($reflectionMethod->getName() === 'action') {
                    return true;
                }

                return false;
            }
        };
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