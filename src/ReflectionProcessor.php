<?php

namespace Prokl\CacheProxificator;

use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class ReflectionProcessor
 * @package Prokl\CacheProxificator
 *
 * @since 01.05.2021
 */
class ReflectionProcessor
{
    /**
     * @var MethodResolverInterface[] $resolvers Ресолверы.
     */
    private $resolvers;

    /**
     * ReflectionProcessor constructor.
     *
     * @param MethodResolverInterface[] $resolvers Ресолверы, определяющие (через аннотаицю или еще как)
     * - проксировать метод или нет.
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Собрать все публичные методы класса и отдать данные.
     *
     * @param mixed $object Объект или название класса.
     * @param array $filter Фильтр методов (только эти).
     *
     * @return array
     *
     * @throws ReflectionException Когда рефлексия не задалась.
     */
    public function reflectClassMethods($object, array $filter = []) : array
    {
        $result = [];

        $class = new ReflectionClass($object);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        // Фильтр имеет наивысший приоритет.
        // Если он задан, то ресолверы не исполняются.
        if (count($filter) > 0) {
            foreach ($methods as $method) {
                $name = $method->getName();
                if (!in_array($name, $filter, true) || $name === '__construct') {
                    continue;
                }

                $result[] = $method;
            }

            return $result;
        }

        foreach ($methods as $method) {
            if ($this->throughPipeline($method)) {
                $result[] = $method;
            }
        }


        return $result;
    }

    /**
     * Вызвать метод.
     *
     * @param mixed  $instance Экземпляр класса.
     * @param string $method   Метод.
     * @param array  $params   Параметры в виде сортированного ассоциированного массива.
     *
     * @return mixed
     * @throws ReflectionException Когда рефлексия не задалась.
     */
    public function invoke($instance, string $method, array $params)
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);

        $sortedParams = $this->sortParamsMethod($reflectionMethod, $params);

        return $reflectionMethod->invoke($instance, ...$sortedParams);
    }

    /**
     * Отсортировать аргументы в порядке, годном к вызову метода.
     *
     * @param ReflectionMethod $method Рефлексированный метод.
     * @param array            $params Параметры в виде сортированного ассоциированного массива.
     *  $params['idElement'] = 33.
     *
     * @return array
     * @throws ReflectionException Когда рефлексия не задалась.
     */
    public function sortParamsMethod(ReflectionMethod $method, array $params) : array
    {
        $result = [];
        $paramsMethod = $method->getParameters();

        foreach ($paramsMethod as $param) {
            if (array_key_exists($param->getName(), $params)) {
                $result[] = $params[$param->getName()];
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $result[] = $param->getDefaultValue();
            }
        }

        return $result;
    }

    /**
     * Пропустить метод через ресолверы.
     *
     * @param ReflectionMethod $method Рефлексированный метод.
     *
     * @return boolean
     */
    private function throughPipeline(ReflectionMethod $method) : bool
    {
        if ($method->getName() ===  '__construct') {
            return false;
        }

        if (count($this->resolvers) === 0) {
            return true;
        }

        $results = [];
        foreach ($this->resolvers as $resolver) {
            $results[] = $resolver->supply($method);
        }

        return !in_array(false, $results, true);
    }
}
