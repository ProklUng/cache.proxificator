<?php

namespace Prokl\CacheProxificator\Contracts;

use ReflectionMethod;

/**
 * Interface MethodResolverInterface
 * @package Prokl\CacheProxificator\Contracts
 *
 * @since 01.05.2021
 */
interface MethodResolverInterface
{
    /**
     * Метод подлежит проксированию или нет.
     *
     * @param ReflectionMethod $reflectionMethod Рефлексированный метод.
     *
     * @return boolean
     */
    public function supply(ReflectionMethod $reflectionMethod) : bool;
}